<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Некорректный запрос.");

$stmt = $pdo->prepare("SELECT * FROM chefs WHERE id_chefs = ?");
$stmt->execute([$id]);
$chef = $stmt->fetch();
if (!$chef) die("Шеф-повар не найден.");

$stmtRecipes = $pdo->prepare("SELECT id_recipes AS id, title, image_url, country, cooking_time_minutes, calories FROM recipes WHERE chef_id = ?");
$stmtRecipes->execute([$id]);
$recipes = $stmtRecipes->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title><?= htmlspecialchars($chef['name']) ?></title>
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
    <div class="chef">
        <div class="chef_img">
            <img src="img/<?= htmlspecialchars($chef['photo_url'] ?: 'default_chef.jpg') ?>" style="max-width: 100%;">
        </div>
        <div class="chef_text">
            <div class="chef_text_up">
                <?php if ($userId > 0): ?>
                <button class="chef_btn_favorite" onclick="toggleFavorite('chef', <?= $chef['id_chefs'] ?>, this)">
                    <img src="img/<?= getFavIcon($pdo, $userId, 'chef', $chef['id_chefs']) ?>" width="24">
                </button>
                <?php endif; ?>
                <p style="font-size: 1.5vw; font-weight: bold;"><?= htmlspecialchars($chef['name']) ?></p>
            </div>
            <div class="chef_text_down">
                <p><?= nl2br(htmlspecialchars($chef['bio'])) ?></p>
            </div>
        </div>
    </div>

    <br><br>
    <h3>Рецепты от шефа</h3>

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
                        <!-- Кнопка избранного РЕЦЕПТА (исправлено) -->
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