<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
$userId = $_SESSION['user_id'] ?? 0;
$articles = $pdo->query("SELECT id_articles AS id, title, short_description, image_url FROM articles ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Блог</title>
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
    <h2>Блог</h2>
    <?php foreach ($articles as $article): ?>
        <div class="blog">
            <div class="blog_img">
                <img src="img/<?= htmlspecialchars($article['image_url'] ?: 'default_article.jpg') ?>" style="width: 30vw;">
            </div>
            <div class="blog_text">
                <div class="blog_text_up">
                    <?php if ($userId > 0): ?>
                        <button class="chefs_btn_favorite" onclick="toggleFavorite('article', <?= $article['id'] ?>, this)">
                            <img src="img/<?= getFavIcon($pdo, $userId, 'article', $article['id']) ?>" width="24">
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