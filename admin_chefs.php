<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $bio = trim($_POST['bio']);
        
        $photoName = 'default_chef.jpg';
        if (!empty($_FILES['photo']['name'])) {
            $photoName = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], 'img/' . $photoName);
        }
        
        $pdo->prepare("INSERT INTO chefs (name, bio, photo_url) VALUES (?, ?, ?)")
            ->execute([$name, $bio, $photoName]);
        
        $message = "Шеф-повар добавлен!";
    }
    
    if ($_POST['action'] === 'edit') {
        $chefId = (int)$_POST['chef_id'];
        $name = trim($_POST['name']);
        $bio = trim($_POST['bio']);
        
        $stmt = $pdo->prepare("SELECT photo_url FROM chefs WHERE id_chefs = ?");
        $stmt->execute([$chefId]);
        $current = $stmt->fetch();
        $photoName = $current['photo_url'];
        
        if (!empty($_FILES['photo']['name'])) {
            $photoName = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], 'img/' . $photoName);
        }
        
        $pdo->prepare("UPDATE chefs SET name = ?, bio = ?, photo_url = ? WHERE id_chefs = ?")
            ->execute([$name, $bio, $photoName, $chefId]);
        
        $message = "Шеф-повар обновлён!";
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM chefs WHERE id_chefs = ?")->execute([$id]);
        $message = "Шеф-повар удален!";
    }
}

$editChefId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editChef = null;

if ($editChefId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM chefs WHERE id_chefs = ?");
    $stmt->execute([$editChefId]);
    $editChef = $stmt->fetch();
}

$chefs = $pdo->query("SELECT * FROM chefs ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Управление шеф-поварами</title>
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
            <li class="account_ul_li"><a href="admin_recipes.php"><h2>Рецепты</h2></a></li>
            <li class="account_ul_li"><a href="admin_chefs.php"><h2>Шеф-повара</h2></a></li>
            <li class="account_ul_li"><a href="admin_article.php"><h2>Статьи</h2></a></li>
            <li class="account_ul_li"><a href="admin_complaints.php"><h2>Жалобы</h2></a></li>
            <li class="account_ul_li"><a href="admin_views.php"><h2>Представления</h2></a></li>
        </ul>
    </div>

    <h2>Управление шеф-поварами</h2>
    
    <?php if ($message): ?>
        <p style="text-align: center; color: #4CAF50; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="admin_create">
        <button class="btn_support" type="button" onclick="document.getElementById('createForm').classList.toggle('active')">Создать</button>
    </div>

    <!-- ФОРМА СОЗДАНИЯ (изначально скрыта) -->
    <form class="form_all create_form" method="POST" enctype="multipart/form-data" id="createForm">
        <input type="hidden" name="action" value="create">
        
        <div class="form_group">
            <label>Имя *</label>
            <input type="text" name="name" required placeholder="   Имя/Название">
        </div>

        <div class="form_group">
            <label>Фото</label>
            <input type="file" name="photo" accept="image/*" style="height: auto;">
        </div>

        <div class="form_group">
            <label>Биография/Описание *</label>
            <textarea name="bio" required placeholder="    Добавить текст/Описание"></textarea>
        </div>

        <button class="send" type="submit">Создать</button>
    </form>

    <!-- ФОРМА РЕДАКТИРОВАНИЯ -->
    <?php if ($editChef): ?>
    <form class="form_all create_form active" method="POST" enctype="multipart/form-data" style="margin-top: 2vw;">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="chef_id" value="<?= $editChef['id_chefs'] ?>">
        
        <h3 style="color: #411D03; margin-bottom: 1vw;">Редактирование: <?= htmlspecialchars($editChef['name']) ?></h3>
        
        <div class="form_group">
            <label>Имя *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($editChef['name']) ?>">
        </div>

        <div class="form_group">
            <label>Фото (оставьте пустым, чтобы не менять)</label>
            <p style="font-size: 0.9vw; color: #411D03;">Текущее: <?= htmlspecialchars($editChef['photo_url']) ?></p>
            <input type="file" name="photo" accept="image/*" style="height: auto;">
        </div>

        <div class="form_group">
            <label>Биография/Описание *</label>
            <textarea name="bio" required><?= htmlspecialchars($editChef['bio']) ?></textarea>
        </div>

        <button class="send" type="submit">Сохранить изменения</button>
        <a href="admin_chefs.php" class="btn_small" style="background-color: #D39772; color: white; margin-left: 1vw;">Отмена</a>
    </form>
    <?php endif; ?>

    <!-- ТАБЛИЦА ШЕФ-ПОВАРОВ -->
    <div class="admin_table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Биография</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chefs as $chef): ?>
                <tr>
                    <td><?= $chef['id_chefs'] ?></td>
                    <td><?= htmlspecialchars($chef['name']) ?></td>
                    <td><?= htmlspecialchars(mb_substr($chef['bio'], 0, 50)) ?>...</td>
                    <td>
                        <a href="admin_chefs.php?edit=<?= $chef['id_chefs'] ?>" class="btn_small btn_edit">Редактировать</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $chef['id_chefs'] ?>">
                            <button type="submit" class="btn_small btn_delete" onclick="return confirm('Удалить шеф-повара?')">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
