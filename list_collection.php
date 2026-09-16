<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$collList = $pdo->query("SELECT c.id_collections AS id, c.name, c.image_url, u.username FROM collections c LEFT JOIN users u ON c.user_id = u.id_users ORDER BY c.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Коллекции</title>
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
    <h2>Коллекции</h2>
    <div class="collection">
        <?php foreach ($collList as $coll): ?>
            <div class="collection_div">
                <div class="collection_up">
                    <div class="collection_up_left">
                        <p class="collection_up_left_name"><a href="collection.php?id=<?= $coll['id'] ?>"><?= htmlspecialchars($coll['name']) ?></a></p>
                        <p class="collection_up_left_autor">Автор: <?= htmlspecialchars($coll['username'] ?? 'Неизвестно') ?></p>
                    </div>
                    <?php if ($userId > 0): ?>
                        <button class="collection_btn_favorite" onclick="toggleFavorite('collection', <?= $coll['id'] ?>, this)">
                            <img src="img/<?= getFavIcon($pdo, $userId, 'collection', $coll['id']) ?>" width="24">
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
</main>

<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>