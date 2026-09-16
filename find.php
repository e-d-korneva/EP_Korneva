<?php
set_time_limit(300); // Увеличиваем до 300 секунд
ini_set('max_execution_time', 300);
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;

// Получаем коллекции пользователя для выпадающего списка
$collections = $userId > 0 ? getUserCollections($pdo, $userId) : [];

// Справочники для фильтров (с алиасами AS id)
$categories = $pdo->query("SELECT id_categories AS id, name FROM categories ORDER BY name")->fetchAll();
$diets = $pdo->query("SELECT id_diets AS id, name FROM diets ORDER BY name")->fetchAll();
$ingredients = $pdo->query("SELECT id_ingredients AS id, name, unit FROM ingredients ORDER BY name")->fetchAll();
$chefs = $pdo->query("SELECT id_chefs AS id, name FROM chefs ORDER BY name")->fetchAll();
$countries = $pdo->query("SELECT DISTINCT country FROM recipes WHERE country IS NOT NULL AND country != '' ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

// API справочники
$apiCategories = [];
$apiCountries = [];
$catResponse = @file_get_contents("https://www.themealdb.com/api/json/v1/1/categories.php");
if ($catResponse) {
    $catData = json_decode($catResponse, true);
    $apiCategories = $catData['categories'] ?? [];
}
$areaResponse = @file_get_contents("https://www.themealdb.com/api/json/v1/1/list.php?a=list");
if ($areaResponse) {
    $areaData = json_decode($areaResponse, true);
    $apiCountries = $areaData['meals'] ?? [];
}

// Проверяем, используются ли международные фильтры
$isApiSearch = !empty($_GET['api_category']) || !empty($_GET['api_country']);

$searchQuery = $_GET['search_text'] ?? '';

$recipes = []; // По умолчанию локальные рецепты пустые

// 1. ПОИСК ПО ЛОКАЛЬНОЙ БД (выполняется ТОЛЬКО если международные фильтры НЕ выбраны)
if (!$isApiSearch) {
    $sql = "SELECT r.id_recipes AS id, r.title, r.image_url, r.country, r.cooking_time_minutes, r.calories FROM recipes r";
    $conditions = [];
    $params = [];

    if (!empty($searchQuery)) {
        $conditions[] = "r.title LIKE ?";
        $params[] = "%" . trim($searchQuery) . "%";
    }
    if (!empty($_GET['categories']) && is_array($_GET['categories'])) {
        $ph = implode(',', array_fill(0, count($_GET['categories']), '?'));
        $conditions[] = "r.id_recipes IN (SELECT recipe_id FROM recipe_categories WHERE category_id IN ($ph))";
        $params = array_merge($params, $_GET['categories']);
    }
    if (!empty($_GET['countries']) && is_array($_GET['countries'])) {
        $ph = implode(',', array_fill(0, count($_GET['countries']), '?'));
        $conditions[] = "r.country IN ($ph)";
        $params = array_merge($params, $_GET['countries']);
    }
    if (!empty($_GET['calories_min']) && is_numeric($_GET['calories_min'])) {
        $conditions[] = "r.calories >= ?";
        $params[] = (int)$_GET['calories_min'];
    }
    if (!empty($_GET['calories_max']) && is_numeric($_GET['calories_max'])) {
        $conditions[] = "r.calories <= ?";
        $params[] = (int)$_GET['calories_max'];
    }
    if (!empty($_GET['time_min']) && is_numeric($_GET['time_min'])) {
        $conditions[] = "r.cooking_time_minutes >= ?";
        $params[] = (int)$_GET['time_min'];
    }
    if (!empty($_GET['time_max']) && is_numeric($_GET['time_max'])) {
        $conditions[] = "r.cooking_time_minutes <= ?";
        $params[] = (int)$_GET['time_max'];
    }
    if (!empty($_GET['diets']) && is_array($_GET['diets'])) {
        $ph = implode(',', array_fill(0, count($_GET['diets']), '?'));
        $conditions[] = "r.id_recipes IN (SELECT recipe_id FROM recipe_diets WHERE diet_id IN ($ph))";
        $params = array_merge($params, $_GET['diets']);
    }
    if (!empty($_GET['ingredients']) && is_array($_GET['ingredients'])) {
        $ph = implode(',', array_fill(0, count($_GET['ingredients']), '?'));
        $conditions[] = "r.id_recipes IN (SELECT recipe_id FROM recipe_ingredients WHERE ingredient_id IN ($ph))";
        $params = array_merge($params, $_GET['ingredients']);
    }
    if (!empty($_GET['chefs']) && is_array($_GET['chefs'])) {
        $ph = implode(',', array_fill(0, count($_GET['chefs']), '?'));
        $conditions[] = "r.chef_id IN ($ph)";
        $params = array_merge($params, $_GET['chefs']);
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipes = $stmt->fetchAll();
}

// 2. ПОИСК ПО API (TheMealDB)
$apiCategory = $_GET['api_category'] ?? '';
$apiCountry = $_GET['api_country'] ?? '';
$apiMeals = [];

if ($isApiSearch) {
    // Если выбраны И категория, И страна — делаем пересечение
    if (!empty($apiCategory) && !empty($apiCountry)) {
        $urlCat = "https://www.themealdb.com/api/json/v1/1/filter.php?c=" . urlencode($apiCategory);
        $resCat = @file_get_contents($urlCat);
        $mealsByCat = $resCat ? (json_decode($resCat, true)['meals'] ?? []) : [];

        $urlArea = "https://www.themealdb.com/api/json/v1/1/filter.php?a=" . urlencode($apiCountry);
        $resArea = @file_get_contents($urlArea);
        $mealsByArea = $resArea ? (json_decode($resArea, true)['meals'] ?? []) : [];

        $areaIds = array_column($mealsByArea, 'idMeal');
        foreach ($mealsByCat as $meal) {
            if (in_array($meal['idMeal'], $areaIds)) {
                $apiMeals[] = $meal;
            }
        }
    } 
    // Если выбрана только категория
    elseif (!empty($apiCategory)) {
        $apiUrl = "https://www.themealdb.com/api/json/v1/1/filter.php?c=" . urlencode($apiCategory);
        $response = @file_get_contents($apiUrl);
        if ($response) $apiMeals = json_decode($response, true)['meals'] ?? [];
    } 
    // Если выбрана только страна
    elseif (!empty($apiCountry)) {
        $apiUrl = "https://www.themealdb.com/api/json/v1/1/filter.php?a=" . urlencode($apiCountry);
        $response = @file_get_contents($apiUrl);
        if ($response) $apiMeals = json_decode($response, true)['meals'] ?? [];
    }
} 
// Если международные фильтры НЕ выбраны, но есть текстовый поиск, ищем и в API тоже
elseif (!empty($searchQuery)) {
    $apiUrl = "https://www.themealdb.com/api/json/v1/1/search.php?s=" . urlencode($searchQuery);
    $response = @file_get_contents($apiUrl);
    if ($response) {
        $apiMeals = json_decode($response, true)['meals'] ?? [];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Поиск</title>
</head>
<body>

<header>
    <a href="index.php"><img src="img/logo.png" width="65vw"></a>
    <div class="header_center">
        <ul class="header_ul">
            <li class="header_ul_li"><a href="index.php">Главная</a></li>
            <li class="header_ul_li"><a href="list_collection.php">Коллекции</a></li>
            <li class="header_ul_li"><a href="chefs.php">Шеф-повара</a></li>
            <li class="header_ul_li"><a href="blog.php">Блог</a></li>
        </ul>
    </div>
    <div class="header_right">
        <a href="find.php"><img src="img/find.png" width="30vw"></a>
        <a href="account.php"><img src="img/account.png" width="38vw"></a>
    </div>
</header>

<main>
    <div class="find_layout">
        <div class="find_main">
            <?php if (!$isApiSearch): ?>
                <div class="find_title_row">
                    <h2>Каталог</h2>
                    <button class="filter-toggle-btn" onclick="toggleFilter()">Фильтры</button>
                </div>
                <div class="catalog">
                    <?php if (empty($recipes)): ?>
                        <p style="text-align: center; width: 100%; grid-column: 1 / -1;">Рецепты не найдены. Попробуйте изменить фильтры.</p>
                    <?php endif; ?>
                    
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="card">
                            <div class="card_img">
                                <a href="recipe.php?id=<?= $recipe['id'] ?>">
                                    <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>" style="max-width: 100%;">
                                </a>
                            </div>
                            <div class="card_center">
                                <p><a href="recipe.php?id=<?= $recipe['id'] ?>" style="color: #411D03; text-decoration: none;"><?= htmlspecialchars($recipe['title']) ?></a></p>
                                <div class="card_btn">
                                    <?php if ($userId > 0): ?>
                                        <div style="position: relative; display: inline-block;">
                                            <button class="btn_card" onclick="toggleCollMenu(<?= $recipe['id'] ?>)">
                                                <img src="img/<?= getCollIcon($pdo, $userId, $recipe['id']) ?>" width="24" alt="Коллекция">
                                            </button>
                                            <div class="coll-dropdown" id="coll-menu-<?= $recipe['id'] ?>">
                                                <p>Выберите коллекцию:</p>
                                                <?php if (empty($collections)): ?>
                                                    <p style="font-weight: normal; font-size: 0.8vw;">У вас нет коллекций</p>
                                                <?php else: ?>
                                                    <?php foreach ($collections as $coll): ?>
                                                        <button onclick="addToCollectionAjax(<?= $coll['id'] ?>, <?= $recipe['id'] ?>, this)">
                                                            <?= htmlspecialchars($coll['name']) ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <button onclick="toggleCreateForm(<?= $recipe['id'] ?>)" style="color: #B1AE69; font-weight: bold; border-top: 1px solid #D39772; margin-top: 0.3vw; padding-top: 0.3vw;">
                                                    + Создать коллекцию
                                                </button>
                                                <form class="form_all" method="POST" action="actions.php" class="coll-create-form" id="coll-create-<?= $recipe['id'] ?>">
                                                    <input type="hidden" name="action" value="create_collection">
                                                    <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                                                    <input type="text" name="collection_name" placeholder="Название" required>
                                                    <button type="submit">Создать и добавить</button>
                                                </form>
                                            </div>
                                        </div>
                                        <button class="btn_card" onclick="toggleFavorite('recipe', <?= $recipe['id'] ?>, this)" style="border:none; background:none; cursor:pointer;">
                                            <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24" alt="Избранное">
                                        </button>
                                    <?php else: ?>
                                        <button class="btn_card" onclick="alert('Войдите в аккаунт')"><img src="img/collection.png" width="24" alt="Коллекция"></button>
                                        <button class="btn_card" onclick="alert('Войдите в аккаунт')"><img src="img/favorite.png" width="24" alt="Избранное"></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card_tags">
                                <?php if (!empty($recipe['country'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['country']) ?></p></div><?php endif; ?>
                                <?php if (!empty($recipe['cooking_time_minutes'])): ?><div class="card_tag"><p><?= $recipe['cooking_time_minutes'] ?> мин</p></div><?php endif; ?>
                                <?php if (!empty($recipe['calories'])): ?><div class="card_tag"><p><?= $recipe['calories'] ?> ккал</p></div><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?> 
            <!-- КОНЕЦ БЛОКА ЛОКАЛЬНОГО КАТАЛОГА -->

            <!-- API РЕЗУЛЬТАТЫ (показываются, если есть совпадения) -->
            <?php if (!empty($apiMeals)): ?>
                <h2>Международные рецепты (TheMealDB)</h2>
                <div class="catalog">
                    <?php foreach ($apiMeals as $meal): ?>
                        <div class="card">
                            <div class="card_img">
                                <img src="<?= htmlspecialchars($meal['strMealThumb'] ?? '') ?>" style="max-width: 100%; height: 15vw; object-fit: cover;">
                            </div>
                            <div class="card_center">
                                <p><?= htmlspecialchars($meal['strMeal'] ?? 'Без названия') ?></p>
                            </div>
                            <div class="card_tags">
                                <?php if (!empty($meal['strCategory'])): ?><div class="card_tag"><p><?= htmlspecialchars($meal['strCategory']) ?></p></div><?php endif; ?>
                                <?php if (!empty($meal['strArea'])): ?><div class="card_tag"><p><?= htmlspecialchars($meal['strArea']) ?></p></div><?php endif; ?>
                            </div>
                            <details style="margin-top: 0.5vw; padding: 0.5vw; background-color: #FFF5E2;">
                                <summary style="cursor: pointer; color: #411D03; font-weight: bold;">Показать ингредиенты</summary>
                                <div style="margin-top: 0.5vw; font-size: 0.85vw;">
                                    <ul style="padding-left: 1.5vw;">
                                        <?php for ($i = 1; $i <= 20; $i++): 
                                            $ingKey = "strIngredient" . $i;
                                            $measureKey = "strMeasure" . $i;
                                            if (!empty($meal[$ingKey] ?? null) && trim($meal[$ingKey] ?? '') !== ''): ?>
                                            <li><?= htmlspecialchars(trim($meal[$ingKey])) ?> — <?= htmlspecialchars(trim($meal[$measureKey] ?? '')) ?></li>
                                        <?php endif; endfor; ?>
                                    </ul>
                                    <?php if (!empty($meal['strInstructions'] ?? null)): ?>
                                        <p style="font-weight: bold; margin-top: 0.5vw;">Инструкция:</p>
                                        <p><?= nl2br(htmlspecialchars(mb_substr($meal['strInstructions'], 0, 500))) ?>...</p>
                                    <?php else: ?>
                                        <p style="font-style: italic; margin-top: 0.5vw; color: #666;">Инструкция недоступна для этого рецепта.</p>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($isApiSearch): ?>
                <!-- Сообщение, если международный поиск не дал результатов -->
                <p style="text-align: center; width: 100%; grid-column: 1 / -1; margin-top: 2vw;">Международные рецепты не найдены. Попробуйте изменить фильтры.</p>
            <?php endif; ?>

        </div>

        <!-- ФИЛЬТРЫ -->
        <aside class="find_aside" id="filterSidebar">
            <div class="filter-close-wrapper">
                <button class="filter-close-btn" onclick="toggleFilter()">Закрыть</button>
            </div>
            <div class="find_filters">
                <h3>Фильтры</h3>
                <form class="form_all" method="GET" action="find.php">
                    <label for="search_text"><h4>По названию</h4></label>
                    <input type="text" name="search_text" maxlength="50" value="<?= htmlspecialchars($searchQuery) ?>">
                    
                    <h4>По категории</h4>
                    <select name="categories[]" size="1" multiple>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $_GET['categories'] ?? []) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h4>По стране</h4>
                    <select name="countries[]" size="1" multiple>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country) ?>" <?= in_array($country, $_GET['countries'] ?? []) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($country) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Фильтр по калориям (ДИАПАЗОН) -->
                    <div class="range-filter">
                        <h4>По калориям: от <span id="cal_min_val"><?= htmlspecialchars($_GET['calories_min'] ?? 50) ?></span> до <span id="cal_max_val"><?= htmlspecialchars($_GET['calories_max'] ?? 5000) ?></span></h4>
                        <div class="range-inputs">
                            <input type="range" name="calories_min" min="50" max="5000" step="10" 
                                   value="<?= htmlspecialchars($_GET['calories_min'] ?? 50) ?>" 
                                   oninput="updateRange('cal', 'min', this.value, 5000)">
                            <input type="range" name="calories_max" min="50" max="5000" step="10" 
                                   value="<?= htmlspecialchars($_GET['calories_max'] ?? 5000) ?>" 
                                   oninput="updateRange('cal', 'max', this.value, 5000)">
                        </div>
                    </div>

                    <!-- Фильтр по времени (ДИАПАЗОН) -->
                    <div class="range-filter">
                        <h4>По времени: от <span id="time_min_val"><?= htmlspecialchars($_GET['time_min'] ?? 5) ?></span> до <span id="time_max_val"><?= htmlspecialchars($_GET['time_max'] ?? 180) ?></span> мин</h4>
                        <div class="range-inputs">
                            <input type="range" name="time_min" min="5" max="180" step="5" 
                                   value="<?= htmlspecialchars($_GET['time_min'] ?? 5) ?>" 
                                   oninput="updateRange('time', 'min', this.value, 180)">
                            <input type="range" name="time_max" min="5" max="180" step="5" 
                                   value="<?= htmlspecialchars($_GET['time_max'] ?? 180) ?>" 
                                   oninput="updateRange('time', 'max', this.value, 180)">
                        </div>
                    </div>

                    <h4>По диете</h4>
                    <select name="diets[]" size="1" multiple>
                        <?php foreach ($diets as $diet): ?>
                            <option value="<?= $diet['id'] ?>" <?= in_array($diet['id'], $_GET['diets'] ?? []) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($diet['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h4>По ингредиентам</h4>
                    <select name="ingredients[]" size="1" multiple>
                        <?php foreach ($ingredients as $ing): ?>
                            <option value="<?= $ing['id'] ?>" <?= in_array($ing['id'], $_GET['ingredients'] ?? []) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ing['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h4>По автору</h4>
                    <select name="chefs[]" size="1" multiple>
                        <?php foreach ($chefs as $chef): ?>
                            <option value="<?= $chef['id'] ?>" <?= in_array($chef['id'], $_GET['chefs'] ?? []) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($chef['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h4>Категория (международная)</h4>
                    <select name="api_category" size="1">
                        <option value="">Все</option>
                        <?php foreach ($apiCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['strCategory']) ?>" <?= $apiCategory === $cat['strCategory'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['strCategory']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h4>Страна (международная)</h4>
                    <select name="api_country" size="1">
                        <option value="">Все</option>
                        <?php foreach ($apiCountries as $country): ?>
                            <option value="<?= htmlspecialchars($country['strArea']) ?>" <?= $apiCountry === $country['strArea'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($country['strArea']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button class="btn_create_collection" type="submit">Найти</button>
                    <a href="find.php" class="btn_create_collection">Сбросить</a>
                </form>
            </div>
        </aside>
    </div>
</main>

<?php include 'footer.php'; ?>

<script src="script.js"></script>

<script>
function toggleFilter() {
    const sidebar = document.getElementById('filterSidebar');
    let overlay = document.getElementById('filterOverlay');
    
    if (sidebar.classList.contains('active')) {
        // Закрываем
        sidebar.classList.remove('active');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
    } else {
        // Открываем
        sidebar.classList.add('active');
        
        // Создаем затемнение фона, если его нет
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'filterOverlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:998;';
            overlay.onclick = toggleFilter;
            document.body.appendChild(overlay);
        } else {
            overlay.style.display = 'block';
        }
        document.body.style.overflow = 'hidden';
    }
}
</script>

</body>
</html>
