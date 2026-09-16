<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';
$formOpen = false;
$stmt = $pdo->prepare("SELECT id_users AS id, username, email, role, created_at FROM users WHERE id_users = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        $newUsername = trim($_POST['username']);
        $newEmail = trim($_POST['email']);
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $newPasswordConfirm = $_POST['new_password_confirm'];
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id_users = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch();
        if (!password_verify($currentPassword, $currentUser['password_hash'])) {
            $message = "Неверный текущий пароль!";
            $messageType = 'error';
            $formOpen = true;
        } else {
            if ($newUsername !== $user['username']) {
                $stmt = $pdo->prepare("SELECT id_users FROM users WHERE username = ? AND id_users != ?");
                $stmt->execute([$newUsername, $userId]);
                if ($stmt->fetch()) {
                    $message = "Имя пользователя уже занято!";
                    $messageType = 'error';
                    $formOpen = true;
                }
            }
            if (empty($message) && $newEmail !== $user['email']) {
                $stmt = $pdo->prepare("SELECT id_users FROM users WHERE email = ? AND id_users != ?");
                $stmt->execute([$newEmail, $userId]);
                if ($stmt->fetch()) {
                    $message = "Email уже используется!";
                    $messageType = 'error';
                    $formOpen = true;
                }
            }
            if (empty($message)) {
                if (!empty($newPassword)) {
                    if ($newPassword !== $newPasswordConfirm) {
                        $message = "Новые пароли не совпадают!";
                        $messageType = 'error';
                        $formOpen = true;
                    } elseif (strlen($newPassword) < 6) {
                        $message = "Пароль должен быть не менее 6 символов!";
                        $messageType = 'error';
                        $formOpen = true;
                    } else {
                        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id_users = ?");
                        $stmt->execute([$newUsername, $newEmail, $newPasswordHash, $userId]);
                        $_SESSION['username'] = $newUsername;
                        $message = "Профиль успешно обновлён!";
                        $messageType = 'success';
                        $user['username'] = $newUsername;
                        $user['email'] = $newEmail;
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id_users = ?");
                    $stmt->execute([$newUsername, $newEmail, $userId]);
                    $_SESSION['username'] = $newUsername;
                    $message = "Профиль успешно обновлён!";
                    $messageType = 'success';
                    $user['username'] = $newUsername;
                    $user['email'] = $newEmail;
                }
            }
        }
    }
}
$roleText = '';
switch ($user['role']) {
    case 'admin': $roleText = 'Администратор'; break;
    case 'user': $roleText = 'Пользователь'; break;
    default: $roleText = 'Гость';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Мой аккаунт</title>
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
    <div class="account_page">
        <div class="account">
            <ul class="account_ul">
                <li class="account_ul_li"><a href="account.php"><h2>Аккаунт</h2></a></li>
                <li class="account_ul_li"><a href="account_favorite_recipe.php"><h2>Избранное</h2></a></li>
                <li class="account_ul_li"><a href="account_list_collection.php"><h2>Коллекции</h2></a></li>
            </ul>
        </div>
        <h2>Мой аккаунт</h2>
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <div class="account_info">
            <div class="account_info_up">
                <h3>Информация о профиле</h3>
                <div class="edit_btn_wrapper">
                    <button class="btn_create_collection" type="button" onclick="document.getElementById('editForm').classList.toggle('active')">
                        Редактировать профиль
                    </button>
                </div>
            </div>
            <div class="info_row">
                <span class="info_label">Имя пользователя:</span>
                <span class="info_value"><?= htmlspecialchars($user['username']) ?></span>
            </div>
            <div class="info_row">
                <span class="info_label">Email:</span>
                <span class="info_value"><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="info_row">
                <span class="info_label">Роль:</span>
                <span class="info_value"><?= htmlspecialchars($roleText) ?></span>
            </div>
            <div class="info_row">
                <span class="info_label">Дата регистрации:</span>
                <span class="info_value"><?= date('d.m.Y', strtotime($user['created_at'])) ?></span>
            </div>
        </div>
        <div class="edit_form <?= $formOpen ? 'active' : '' ?>" id="editForm">
            <h3>Редактировать профиль</h3>
            <form class="form_all" method="POST">
                <input type="hidden" name="action" value="update">
                <div class="form_group">
                    <label for="username">Имя пользователя *</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required maxlength="50">
                </div>
                <div class="form_group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required maxlength="100">
                </div>
                <div class="form_group">
                    <label for="current_password">Текущий пароль *</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Введите текущий пароль" required>
                </div>
                <div class="form_group">
                    <label for="new_password">Новый пароль (оставьте пустым, чтобы не менять)</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Новый пароль" minlength="6">
                </div>
                <div class="form_group">
                    <label for="new_password_confirm">Подтверждение нового пароля</label>
                    <input type="password" id="new_password_confirm" name="new_password_confirm" placeholder="Повторите новый пароль" minlength="6">
                </div>
                <button type="submit" class="send btn-save-profile">Сохранить изменения</button>
            </form>
        </div>
        <div class="logout_wrap"><a href="logout.php" class="logout">Выйти из аккаунта</a></div>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
