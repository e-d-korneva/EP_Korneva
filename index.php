<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Гость';

// ========== ЛИЧНЫЕ КОЛЛЕКЦИИ ПОЛЬЗОВАТЕЛЯ (для выпадающего меню) ==========
$userCollections = $userId > 0 ? getUserCollections($pdo, $userId) : [];

// ========== РЕЦЕПТЫ ДНЯ ==========
$dayNumber = (int)date('z');
$categoriesOfDay = [
    'Завтрак' => 'Завтрак',
    'Обед' => 'Обед',
    'Ужин' => 'Ужин',
    'Салат' => 'Салат',
    'Десерт' => 'Десерт',
    'Напиток' => 'Напиток'
];

$recipesOfDay = [];
foreach ($categoriesOfDay as $catName => $catEng) {
    $stmt = $pdo->prepare("
        SELECT r.id_recipes AS id, r.title, r.image_url 
        FROM recipes r
        JOIN recipe_categories rc ON r.id_recipes = rc.recipe_id
        JOIN categories c ON rc.category_id = c.id_categories
        WHERE c.name = ?
        ORDER BY r.id_recipes
    ");
    $stmt->execute([$catEng]);
    $list = $stmt->fetchAll();
    
    if (!empty($list)) {
        $index = $dayNumber % count($list);
        $recipesOfDay[$catName] = $list[$index];
    } else {
        $recipesOfDay[$catName] = null;
    }
}

// ========== СЕЗОННЫЕ БЛЮДА ==========
$currentMonth = (int)date('n');
if ($currentMonth >= 3 && $currentMonth <= 5) {
    $currentSeason = 'Весна';
    $seasonCategories = ['Весна'];
} elseif ($currentMonth >= 6 && $currentMonth <= 8) {
    $currentSeason = 'Лето';
    $seasonCategories = ['Лето'];
} elseif ($currentMonth >= 9 && $currentMonth <= 11) {
    $currentSeason = 'Осень';
    $seasonCategories = ['Осень'];
} else {
    $currentSeason = 'Зима';
    $seasonCategories = ['Зима'];
}

$seasonalRecipes = [];
if (!empty($seasonCategories)) {
    $placeholders = implode(',', array_fill(0, count($seasonCategories), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT r.id_recipes AS id, r.title, r.image_url, c.name as category
        FROM recipes r
        JOIN recipe_categories rc ON r.id_recipes = rc.recipe_id
        JOIN categories c ON rc.category_id = c.id_categories
        WHERE c.name IN ($placeholders)
        LIMIT 6
    ");
    $stmt->execute($seasonCategories);
    $seasonalRecipes = $stmt->fetchAll();
}

// ========== КАТАЛОГ (последние рецепты) ==========
$catalogRecipes = $pdo->query("SELECT id_recipes AS id, title, image_url, country, cooking_time_minutes, calories FROM recipes ORDER BY id_recipes DESC LIMIT 9")->fetchAll();

// ========== ПУБЛИЧНЫЕ КОЛЛЕКЦИИ (для отображения в блоке "Коллекции") ==========
$publicCollections = $pdo->query("
    SELECT c.id_collections AS id, c.name, c.image_url, u.username 
    FROM collections c 
    LEFT JOIN users u ON c.user_id = u.id_users 
    ORDER BY c.created_at DESC LIMIT 4
")->fetchAll();

// ========== ШЕФ-ПОВАРА ==========
$chefsList = $pdo->query("SELECT id_chefs AS id, name, photo_url FROM chefs ORDER BY id_chefs LIMIT 4")->fetchAll();

// ========== БЛОГ ==========
$articlesList = $pdo->query("SELECT id_articles AS id, title, short_description, image_url FROM articles ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Главная</title>
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

    <!-- РЕЦЕПТЫ ДНЯ -->
    <h2>Рецепты дня</h2>
    <div class="days">
        <?php foreach ($recipesOfDay as $catName => $recipe): ?>
            <div class="days_div">
                <?php if ($recipe): ?>
                    <a href="recipe.php?id=<?= $recipe['id'] ?>" style="text-decoration: none;">
                        <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
                        <p style="font-weight: bold;"><?= htmlspecialchars($catName) ?></p>
                        <p style="font-size: x-small; color: #B1AE69;"><?= htmlspecialchars(mb_substr($recipe['title'], 0, 20)) ?>...</p>
                    </a>
                <?php else: ?>
                    <img src="img/default_recipe.jpg" style="width: 15vw; max-width: 200px; opacity: 0.5;">
                    <p><?= htmlspecialchars($catName) ?></p>
                    <p style="font-size: 0.9vw; color: #D39772;">Скоро</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

   <!-- СЕЗОННЫЕ БЛЮДА (СЛАЙДЕР) -->
<h2>Сезонные блюда — <?= htmlspecialchars($currentSeason) ?></h2>
<?php if (!empty($seasonalRecipes)): ?>
<div class="season">
    <img src="img/left.png" class="slider-arrow" onclick="changeSlide(-1)">
    <div class="season_img">
        <?php foreach ($seasonalRecipes as $index => $recipe): ?>
            <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                <a href="recipe.php?id=<?= $recipe['id'] ?>" class="season-link">
                    <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>" 
                         alt="<?= htmlspecialchars($recipe['title']) ?>"
                         style="width: 100%; height: 100%; object-fit: cover;">

                    <div class="season_overlay">
                        <h3 style="color: #FFF5E2; text-align: end; margin-bottom: 0.5vw;">
                            <?= htmlspecialchars($recipe['title']) ?>
                        </h3>
                        <p style="color: #D6B38D;"><?= htmlspecialchars($recipe['category']) ?></p>
                        <button class="btn_support" style="margin-top: 1vw;">К рецепту</button>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <img src="img/right.png" class="slider-arrow" onclick="changeSlide(1)">
</div>
<?php else: ?>
    <p style="text-align: center; color: red;">Сезонные рецепты не найдены. Проверьте БД.</p>
<?php endif; ?>

    <!-- КАТАЛОГ + ASIDE (НЕЗАВИСИМАЯ ОБЁРТКА) -->
    <h2>Каталог</h2>
    <div class="catalog_wrapper">
        
        <!-- Карточки рецептов (отдельный блок с сеткой) -->
        <div class="catalog">
            <?php foreach ($catalogRecipes as $recipe): ?>
                <div class="card">
                    <div class="card_img">
                        <a href="recipe.php?id=<?= $recipe['id'] ?>">
                            <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>">
                        </a>
                    </div>
                    <div class="card_center">
                        <p><a href="recipe.php?id=<?= $recipe['id'] ?>"><?= htmlspecialchars($recipe['title']) ?></a></p>
                        <div class="card_btn">
                            <?php if ($userId > 0): ?>
                                <!-- КНОПКА КОЛЛЕКЦИИ С ВЫПАДАЮЩИМ СПИСКОМ -->
                                <div style="position: relative; display: inline-block;">
                                    <button class="btn_card" onclick="toggleCollMenu(<?= $recipe['id'] ?>)">
                                        <img src="img/<?= getCollIcon($pdo, $userId, $recipe['id']) ?>" width="24" alt="Коллекция">
                                    </button>
                                    
                                    <div class="coll-dropdown" id="coll-menu-<?= $recipe['id'] ?>">
                                        <p>Выберите коллекцию:</p>
                                        
                                        <?php if (empty($userCollections)): ?>
                                            <p style="font-weight: normal; font-size: 0.8vw;">У вас нет коллекций</p>
                                        <?php else: ?>
                                            <?php foreach ($userCollections as $coll): ?>
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

                                <!-- КНОПКА ИЗБРАННОГО -->
                                <button class="btn_card" onclick="toggleFavorite('recipe', <?= $recipe['id'] ?>, this)" style="border:none; background:none; cursor:pointer;">
                                    <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24" alt="Избранное">
                                </button>
                            <?php else: ?>
                                <button class="btn_card" onclick="alert('Войдите в аккаунт')">
                                    <img src="img/collection.png" width="24" alt="Коллекция">
                                </button>
                                <button class="btn_card" onclick="alert('Войдите в аккаунт')">
                                    <img src="img/favorite.png" width="24" alt="Избранное">
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card_tags">
                        <?php if (!empty($recipe['country'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['country']) ?></p></div><?php endif; ?>
                        <?php if (!empty($recipe['cooking_time_minutes'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['cooking_time_minutes']) ?> мин</p></div><?php endif; ?>
                        <?php if (!empty($recipe['calories'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['calories']) ?> ккал</p></div><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ASIDE (независимая боковая панель) -->
        <aside>
            <h3>Категории</h3>
            <div class="aside_categories">
                <div class="aside_categories_div">
                    <img src="img/chicken.png">
                    <p>Мясо</p>
                </div>
                <div class="aside_categories_div">
                    <img src="img/burger.png">
                    <p>Фастфуд</p>
                </div>
                <div class="aside_categories_div">
                    <img src="img/soup.png">
                    <p>Супы</p>
                </div>
                <div class="aside_categories_div">
                    <img src="img/salat.png">
                    <p>Салаты</p>
                </div>
                <div class="aside_categories_div">
                    <img src="img/fish.png">
                    <p>Рыба</p>
                </div>
                <div class="aside_categories_div">
                    <img src="img/cake.png">
                    <p>Десерты</p>
                </div>
            </div>
            <br>
            <br>
            <h3>Кухни мира</h3>
            
            <div class="aside_world">
                <img src="img/germany.png">
                <img src="img/south_korea.png">
                <img src="img/usa.png">
                <img src="img/japan.png">
                <img src="img/russia.png">
                <img src="img/italy.png">
                <img src="img/france.png">
                <img src="img/china.png">
                <img src="img/spain.png">
            </div>
            <br>
            <button class="btn_find"><a href="find.php">Перейти к фильтрам</a></button>
            <br>
            <br>
        </aside>
    </div>

    <!-- КОЛЛЕКЦИИ -->
    <h2>Коллекции</h2>
    <div class="collection">
        <?php foreach ($publicCollections as $coll): ?>
            <div class="collection_div">
                <div class="collection_up">
                    <div class="collection_up_left">
                        <p class="collection_up_left_name"><a href="collection.php?id=<?= $coll['id'] ?>"><?= htmlspecialchars($coll['name']) ?></a></p>
                        <p class="collection_up_left_autor">Автор: <?= htmlspecialchars($coll['username'] ?? 'Неизвестно') ?></p>
                    </div>
                    <?php if ($userId > 0): ?>
                    <button class="btn_card" onclick="toggleFavorite('recipe', <?= $recipe['id'] ?>, this)" style="border:none; background:none; cursor:pointer;">
                        <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24" alt="Избранное">
                    </button>
                    <?php endif; ?>
                </div>
                <div class="collection_img">
                    <a href="collection.php?id=<?= $coll['id'] ?>">
                        <img src="img/<?= htmlspecialchars($coll['image_url'] ?: 'default_collection.jpg') ?>" style="max-width: 100%;">
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ШЕФ-ПОВАРА -->
    <h2>Шеф-повара</h2>
    <div class="chefs">
        <?php foreach ($chefsList as $chef): ?>
            <div class="chefs_div">
                <div class="chefs_img">
                    <a href="chef.php?id=<?= $chef['id'] ?>">
                        <img src="img/<?= htmlspecialchars($chef['photo_url'] ?: 'default_chef.jpg') ?>" style="max-width: 100%;">
                    </a>
                </div>
                <div class="chefs_down">
                    <p><a href="chef.php?id=<?= $chef['id'] ?>" style="color: #411D03; text-decoration: none;"><?= htmlspecialchars($chef['name']) ?></a></p>
                    <?php if ($userId > 0): ?>
                    <button class="btn_card" onclick="toggleFavorite('recipe', <?= $recipe['id'] ?>, this)" style="border:none; background:none; cursor:pointer;">
                        <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24" alt="Избранное">
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- БЛОГ -->
    <h2>Блог</h2>
    <?php foreach ($articlesList as $article): ?>
        <div class="blog">
            <div class="blog_img">
                <img src="img/<?= htmlspecialchars($article['image_url'] ?: 'default_article.jpg') ?>" style="width: 26vw;">
            </div>
            <div class="blog_text">
                <div class="blog_text_up">
                    <?php if ($userId > 0): ?>
                    <button class="btn_card" onclick="toggleFavorite('recipe', <?= $recipe['id'] ?>, this)" style="border:none; background:none; cursor:pointer;">
                        <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24" alt="Избранное">
                    </button>
                    <?php endif; ?>
                    <p><a href="article.php?id=<?= $article['id'] ?>" style="color: #411D03; text-decoration: none; font-weight: bold;"><?= htmlspecialchars($article['title']) ?></a></p>
                </div>
                <div class="blog_text_down">
                    <p><?= htmlspecialchars($article['short_description']) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</main>

<?php include 'footer.php'; ?>

<script src="script.js"></script>

</body>
</html>
