<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$collections = $userId > 0 ? getUserCollections($pdo, $userId) : [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Некорректный запрос.");

$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id_recipes = ?");
$stmt->execute([$id]);
$recipe = $stmt->fetch();
if (!$recipe) die("Рецепт не найден.");

$recipeId = $recipe['id_recipes'];

$stmtIng = $pdo->prepare("
    SELECT i.name, i.unit, ri.quantity 
    FROM recipe_ingredients ri 
    JOIN ingredients i ON ri.ingredient_id = i.id_ingredients 
    WHERE ri.recipe_id = ?
");
$stmtIng->execute([$recipeId]);
$ingredients = $stmtIng->fetchAll();

$stmtTags = $pdo->prepare("
    SELECT c.name 
    FROM recipe_categories rc 
    JOIN categories c ON rc.category_id = c.id_categories 
    WHERE rc.recipe_id = ?
");
$stmtTags->execute([$recipeId]);
$tags = $stmtTags->fetchAll();
$tags[] = ['name' => $recipe['cooking_time_minutes'] . ' мин'];
$tags[] = ['name' => $recipe['calories'] . ' ккал'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title><?= htmlspecialchars($recipe['title']) ?></title>
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
    <div class="recipe_name">
        <?php if ($userId > 0): ?>
            <!-- КНОПКА КОЛЛЕКЦИИ С ВЫПАДАЮЩИМ СПИСКОМ -->
            <div style="position: relative; display: inline-block;">
                <button class="recipe_btn" onclick="toggleCollMenu(<?= $recipeId ?>)">
                    <!-- Добавлен ID для быстрой смены картинки -->
                    <img src="img/<?= getCollIcon($pdo, $userId, $recipeId) ?>" width="24" id="coll-icon-<?= $recipeId ?>">
                </button>
                
                <div class="coll-dropdown" id="coll-menu-<?= $recipeId ?>">
                    <p>Выберите коллекцию:</p>
                    <?php if (empty($collections)): ?>
                        <p style="font-weight: normal; font-size: 0.8vw;">У вас нет коллекций</p>
                    <?php else: ?>
                        <?php foreach ($collections as $coll): ?>
                            <!-- ЗАМЕНЕНО: форма убрана, теперь это простая кнопка с onclick -->
                            <button onclick="addToColl(<?= $coll['id'] ?>, <?= $recipeId ?>)" style="width: 100%; text-align: left; background: none; border: none; color: #411D03; cursor: pointer; padding: 5px;">
                                <?= htmlspecialchars($coll['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <button onclick="toggleCreateForm(<?= $recipeId ?>)" style="color: #B1AE69; font-weight: bold; border-top: 1px solid #D39772; margin-top: 0.3vw; padding-top: 0.3vw; width: 100%; text-align: left; background: none; border: none; cursor: pointer;">
                        + Создать коллекцию
                    </button>
                    
                    <!-- Форма создания коллекции остается обычной (она перезагрузит страницу, это нормально для создания) -->
                    <form class="form_all" method="POST" action="actions.php" id="coll-create-<?= $recipeId ?>" style="display: none; margin-top: 5px;">
                        <input type="hidden" name="action" value="create_collection">
                        <input type="hidden" name="recipe_id" value="<?= $recipeId ?>">
                        <input type="text" name="collection_name" placeholder="Название" required style="width: 100%; margin-bottom: 5px;">
                        <button type="submit" style="width: 100%;">Создать и добавить</button>
                    </form>
                </div>
            </div>

            <!-- КНОПКА ИЗБРАННОГО -->
            <!-- ЗАМЕНЕНО: форма убрана, теперь это простая кнопка с onclick -->
            <button class="recipe_btn" onclick="toggleFav(<?= $recipeId ?>, this)" style="border: none; background: none; cursor: pointer;">
                <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipeId) ?>" width="24">
            </button>
        <?php endif; ?>
        
        <h2><?= htmlspecialchars($recipe['title']) ?></h2>
    </div>

    <div class="recipe_info">
        <div class="article_img">
            <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>" style="width: auto; height: 15vw;">
        </div>
        <div class="recipe_tags">
            <?php foreach ($tags as $tag): ?>
                <div class="recipe_tag"><p><?= htmlspecialchars($tag['name']) ?></p></div>
            <?php endforeach; ?>
        </div>

        <div class="recipe_tags">
            <table>
                <tr>
                    <th>Порций:</th>
                    <th id="portion-count">1</th>
                    <th><button class="recipe_btn" onclick="changePortions(-1)"><img src="img/minus.png" width="24"></button></th>
                    <th><button class="recipe_btn" onclick="changePortions(1)"><img src="img/plus.png" width="24"></button></th>
                </tr>
                <tr><td colspan="4"><div class="table_ht"></div></td></tr>
                <?php foreach ($ingredients as $ing): ?>
                <tr>
                    <td><div class="recipe_tag"><p><?= htmlspecialchars($ing['name']) ?></p></div></td>
                    <td class="ingredient-qty" data-base-qty="<?= $ing['quantity'] ?>">
                        <?= $ing['quantity'] ?> <?= htmlspecialchars($ing['unit']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="recipe_text">
        <h3>Приготовление</h3>
        <p><?= nl2br(htmlspecialchars($recipe['instructions'])) ?></p>
    </div>
</main>

<?php include 'footer.php'; ?>

<script src="script.js"></script>
<!-- Примитивные функции специально для recipe.php, чтобы не ломать общую логику -->
<script>
function toggleFav(id, btn) {
    let img = btn.querySelector('img');
    let isFav = img.src.includes('favorite_del.png');
    img.src = isFav ? 'img/favorite.png' : 'img/favorite_del.png';

    let data = new FormData();
    data.append('action', 'favorite');
    data.append('type', 'recipe');
    data.append('id', id);
    fetch('actions.php', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: data });
}

function addToColl(collId, recipeId) {
    let data = new FormData();
    data.append('action', 'add_to_collection');
    data.append('collection_id', collId);
    data.append('recipe_id', recipeId);
    
    fetch('actions.php', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: data }).then(() => {
        document.getElementById('coll-icon-' + recipeId).src = 'img/collection_del.png';
        document.getElementById('coll-menu-' + recipeId).style.display = 'none';
        alert('Рецепт добавлен в коллекцию!');
    });
}
</script>

</body>
</html>