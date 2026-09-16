<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$chefs = $pdo->query("SELECT id_chefs AS id, name, photo_url FROM chefs ORDER BY id_chefs")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Шеф-повара</title>
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
    <h2>Шеф-повара</h2>
    <div class="chefs">
        <?php foreach ($chefs as $chef): ?>
            <div class="chefs_div">
                <div class="chefs_img">
                    <a href="chef.php?id=<?= $chef['id'] ?>">
                        <img src="img/<?= htmlspecialchars($chef['photo_url'] ?: 'default_chef.jpg') ?>" style="max-width: 100%;">
                    </a>
                </div>
                <div class="chefs_down">
                    <p><a href="chef.php?id=<?= $chef['id'] ?>" style="color: #411D03; text-decoration: none;"><?= htmlspecialchars($chef['name']) ?></a></p>
                    
                    <div class="card_btn">
                    <?php if ($userId > 0): ?>
                    <!-- Исправлено: type=chef, id=$chef['id'] -->
                    <button class="chefs_btn_favorite" onclick="toggleFavorite('chef', <?= $chef['id'] ?>, this)">
                        <img src="img/<?= getFavIcon($pdo, $userId, 'chef', $chef['id']) ?>" width="24">
                    </button>
                    <?php else: ?>
                    <button class="chefs_btn_favorite" onclick="alert('Войдите в аккаунт')">
                        <img src="img/favorite.png" width="24">
                    </button>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>