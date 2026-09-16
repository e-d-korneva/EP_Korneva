<?php
session_start();
require_once 'config.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shortMessage = trim($_POST['short_message']);
    $fullMessage = trim($_POST['full_message']);
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!empty($shortMessage)) {
        $pdo->prepare("INSERT INTO complaints (user_id, short_message, full_message) VALUES (?, ?, ?)")
            ->execute([$userId, $shortMessage, $fullMessage]);
        $message = "Ваше обращение отправлено! Мы рассмотрим его в ближайшее время.";
    } else {
        $message = "Пожалуйста, заполните краткое сообщение.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Поддержка</title>
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
    <h2>Поддержка</h2>
    
    <?php if ($message): ?>
        <p style="text-align: center; color: <?= strpos($message, 'отправлено') !== false ? 'green' : 'red' ?>; font-weight: bold; margin: 1vw;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form class="form_all" method="POST" style="max-width: 50vw; margin: 2vw auto;">
        <label for="short_message"></label>
        <input type="text" name="short_message" placeholder="   Краткое сообщение*" required maxlength="255">
        
        <label for="full_message"></label>
        <textarea name="full_message" placeholder="    Полное обращение" style="height: 15vw;"></textarea>
        
        <button class="send" type="submit">Отправить</button>
    </form>
</main>

<?php include 'footer.php'; ?>

</body>
</html>