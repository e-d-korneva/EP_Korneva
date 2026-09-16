<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем избранные коллекции
$stmt = $pdo->prepare("
    SELECT c.id_collections AS id, c.name, c.image_url, c.user_id, u.username
    FROM collections c
    JOIN favorite_collections fc ON c.id_collections = fc.collection_id
    LEFT JOIN users u ON c.user_id = u.id_users
    WHERE fc.user_id = ?
    ORDER BY fc.added_at DESC
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
    <title>Избранные коллекции</title>
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

    <div class="collection">
        <?php if (empty($favorites)): ?>
            <p style="text-align: center; width: 100%; grid-column: 1 / -1;">У вас пока нет избранных коллекций.</p>
        <?php else: ?>
            <?php foreach ($favorites as $coll): ?>
                <div class="collection_div">
                    <div class="collection_up">
                        <div class="collection_up_left">
                            <p><a href="collection.php?id=<?= $coll['id'] ?>" style="color: #411D03; text-decoration: none; font-weight: bold;"><?= htmlspecialchars($coll['name']) ?></a></p>
                            <p style="font-size: 0.8vw;">Автор: <?= htmlspecialchars($coll['username'] ?? 'Неизвестно') ?></p>
                        </div>
                        <!-- Кнопка удаления из избранного -->
                        <form class="form_btn" method="POST" action="actions.php" style="display: inline;">
                            <input type="hidden" name="action" value="favorite">
                            <input type="hidden" name="type" value="collection">
                            <input type="hidden" name="id" value="<?= $coll['id'] ?>">
                            <button type="submit" class="collection_btn_favorite">
                                <img src="img/favorite_del.png" width="24" alt="Удалить из избранного">
                            </button>
                        </form>
                    </div>
                    <div class="collection_img">
                        <a href="collection.php?id=<?= $coll['id'] ?>">
                            <img src="img/<?= htmlspecialchars($coll['image_url'] ?: 'default_collection.jpg') ?>" style="max-width: 100%;">
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
