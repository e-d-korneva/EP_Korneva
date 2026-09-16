<?php
session_start();
require_once 'config.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['login']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    if (!empty($username) && !empty($email) && !empty($password)) {
        if ($password !== $password_confirm) {
            $message = "Пароли не совпадают!";
        } else {
            $stmt = $pdo->prepare("SELECT id_users FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $message = "Пользователь с таким логином или email уже существует!";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Явно указываем роль 'user'
                $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')")
                    ->execute([$username, $email, $hash]);
                $message = "Регистрация успешна! Теперь <a href='login.php' style='color:#411D03;'>войдите</a>.";
            }
        }
    } else {
        $message = "Заполните все поля!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Регистрация</title>
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
    <div class="avtoriz">
        <h2>Регистрация</h2>
        <a href="login.php">Авторизоваться</a>
        
        <?php if ($message): ?>
            <p style="color: <?= strpos($message, 'успешна') !== false ? 'green' : 'red' ?>; font-weight: bold; margin: 1vw;"><?= $message ?></p>
        <?php endif; ?>
        
        <form class="form_all" method="POST">
            <label for="login"></label>
            <input type="text" name="login" placeholder="   Логин" required maxlength="50">
            
            <label for="email"></label>
            <input type="email" name="email" placeholder="  Email" required maxlength="50">
            
            <label for="password"></label>
            <input type="password" name="password" placeholder="    Пароль" required maxlength="50">
            
            <label for="password_confirm"></label>
            <input type="password" name="password_confirm" placeholder="    Повторите пароль" required maxlength="50">
            
            <button class="send" type="submit">Зарегистрироваться</button>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
