<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем избранные рецепты
$stmt = $pdo->prepare("
    SELECT r.id_recipes AS id, r.title, r.image_url, r.country, r.cooking_time_minutes, r.calories
    FROM favorite_recipes fr
    JOIN recipes r ON fr.recipe_id = r.id_recipes
    WHERE fr.user_id = ?
    ORDER BY fr.added_at DESC
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Избранные рецепты</title>
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
    <div class="account">
            <ul class="account_ul">
                <li class="account_ul_li"><a href="account.php"><h2>Аккаунт</h2></a></li>
                <li class="account_ul_li"><a href="account_favorite_recipe.php"><h2>Избранное</h2></a></li>
                <li class="account_ul_li"><a href="account_list_collection.php"><h2>Коллекции</h2></a></li>
            </ul>
        </div>

    <div class="account">
        <ul class="account_ul">
            <li class="account_ul_li"><a href="account_favorite_recipe.php"><h3>Рецепты</h3></a></li>
            <li class="account_ul_li"><a href="account_favorite_collection.php"><h3>Коллекции</h3></a></li>
            <li class="account_ul_li"><a href="account_favorite_chefs.php"><h3>Шеф-повара</h3></a></li>
            <li class="account_ul_li"><a href="account_favorite_blog.php"><h3>Блог</h3></a></li>
        </ul>
    </div>

    <div class="catalog">
        <?php if (empty($favorites)): ?>
            <p style="text-align: center; width: 100%; grid-column: 1 / -1;">У вас пока нет избранных рецептов.</p>
        <?php else: ?>
            <?php foreach ($favorites as $recipe): ?>
                <div class="card">
                    <div class="card_img">
                        <a href="recipe.php?id=<?= $recipe['id'] ?>">
                            <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>" style="max-width: 100%;">
                        </a>
                    </div>
                    <div class="card_center">
                        <p><a href="recipe.php?id=<?= $recipe['id'] ?>" style="color: #411D03; text-decoration: none; font-weight: bold;"><?= htmlspecialchars($recipe['title']) ?></a></p>
                        <div class="card_btn">
                            <!-- КНОПКА УДАЛИТЬ ИЗ ИЗБРАННОГО -->
                            <form class="form_btn" method="POST" action="actions.php" style="display: inline;">
                                <input type="hidden" name="action" value="favorite">
                                <input type="hidden" name="type" value="recipe">
                                <input type="hidden" name="id" value="<?= $recipe['id'] ?>">
                                <button type="submit" class="btn_card">
                                    <img src="img/favorite_del.png" width="24" alt="Удалить из избранного">
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card_tags">
                        <?php if (!empty($recipe['country'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['country']) ?></p></div><?php endif; ?>
                        <?php if (!empty($recipe['cooking_time_minutes'])): ?><div class="card_tag"><p><?= $recipe['cooking_time_minutes'] ?> мин</p></div><?php endif; ?>
                        <?php if (!empty($recipe['calories'])): ?><div class="card_tag"><p><?= $recipe['calories'] ?> ккал</p></div><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
