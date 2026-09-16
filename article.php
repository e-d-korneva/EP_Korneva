<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$userId = $_SESSION['user_id'] ?? 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Некорректный запрос.");

$stmt = $pdo->prepare("SELECT * FROM articles WHERE id_articles = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();
if (!$article) die("Статья не найдена.");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title><?= htmlspecialchars($article['title']) ?></title>
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
        <button class="recipe_btn" onclick="toggleFavorite('article', <?= $article['id_articles'] ?>, this)">
            <img src="img/<?= getFavIcon($pdo, $userId, 'article', $article['id_articles']) ?>" width="24">
        </button>
        <?php endif; ?>
        <h2><?= htmlspecialchars($article['title']) ?></h2>
    </div>

    <div class="article_img">
        <img src="img/<?= htmlspecialchars($article['image_url'] ?: 'default_article.jpg') ?>" style="width: 30%;">
    </div>

    <div class="article_text">
        <p><?= nl2br(htmlspecialchars($article['content'])) ?></p>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>