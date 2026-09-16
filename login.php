<?php
session_start();
require_once 'config.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['login']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        // Запрашиваем также поле role
        $stmt = $pdo->prepare("SELECT id_users AS id, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; // ← СОХРАНЯЕМ РОЛЬ
            header('Location: index.php');
            exit;
        } else {
            $message = "Неверный логин или пароль!";
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
    <title>Вход</title>
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
        <h2>Войти в аккаунт</h2>
        <a href="register.php">Зарегистрироваться</a>
        
        <?php if ($message): ?>
            <p style="color: red; font-weight: bold; margin: 1vw;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        
        <form class="form_all" method="POST">
            <label for="login"></label>
            <input type="text" name="login" placeholder="   Логин" required maxlength="50">
            
            <label for="password"></label>
            <input type="password" name="password" placeholder="    Пароль" required maxlength="50">
            
            <button class="send" type="submit">Войти</button>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
