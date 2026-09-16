<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Некорректный запрос.");

$stmtColl = $pdo->prepare("SELECT * FROM collections WHERE id_collections = ?");
$stmtColl->execute([$id]);
$collection = $stmtColl->fetch();
if (!$collection) die("Коллекция не найдена.");

$stmtRecipes = $pdo->prepare("
    SELECT r.id_recipes AS id, r.title, r.image_url, r.country, r.cooking_time_minutes, r.calories
    FROM collection_recipes cr
    JOIN recipes r ON cr.recipe_id = r.id_recipes
    WHERE cr.collection_id = ?
");
$stmtRecipes->execute([$id]);
$recipes = $stmtRecipes->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title><?= htmlspecialchars($collection['name']) ?></title>
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
        <a href="account_list_collection.php"><img src="img/account.png" width="38vw"></a>
    </div>
</header>

<main>
    <h3><?= htmlspecialchars($collection['name']) ?></h3>

    <div class="catalog">
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
                    <button class="btn_card" onclick="toggleFavorite('recipe', <?= $recipe['id'] ?>, this)">
                        <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24">
                    </button>
                    <?php else: ?>
                    <button class="btn_card" onclick="alert('Войдите в аккаунт')">
                        <img src="img/favorite.png" width="24">
                    </button>
                    <?php endif; ?>
                    </div>
                </div>
                <div class="card_tags">
                    <?php if (!empty($recipe['country'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['country']) ?></p></div><?php endif; ?>
                    <?php if (!empty($recipe['cooking_time_minutes'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['cooking_time_minutes']) ?> мин</p></div><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>