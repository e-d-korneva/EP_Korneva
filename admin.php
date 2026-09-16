<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Админ-панель</title>
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
    <h2>Панель управления</h2>
    
    <div class="admin_stats">
        <div class="stat_card">
            <h3>Рецепты</h3>
            <p><?= $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn() ?></p>
        </div>
        <div class="stat_card">
            <h3>Шеф-повара</h3>
            <p><?= $pdo->query("SELECT COUNT(*) FROM chefs")->fetchColumn() ?></p>
        </div>
        <div class="stat_card">
            <h3>Статьи</h3>
            <p><?= $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn() ?></p>
        </div>
        <div class="stat_card">
            <h3>Жалобы</h3>
            <p><?= $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn() ?></p>
        </div>
    </div>

    <div class="admin_nav">
        <a href="admin_recipes.php">Управление рецептами</a>
        <a href="admin_chefs.php">Управление шеф-поварами</a>
        <a href="admin_article.php">Управление статьями</a>
        <a href="admin_complaints.php">Жалобы</a>
        <a href="admin_views.php">Представления</a>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
