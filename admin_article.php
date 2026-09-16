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
        $title = trim($_POST['title']);
        $short_desc = trim($_POST['short_description']);
        $content = trim($_POST['content']);
        
        $imageName = 'default_article.jpg';
        if (!empty($_FILES['image']['name'])) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], 'img/' . $imageName);
        }
        
        $pdo->prepare("INSERT INTO articles (title, short_description, content, image_url) VALUES (?, ?, ?, ?)")
            ->execute([$title, $short_desc, $content, $imageName]);
        
        $message = "Статья создана!";
    }
    
    if ($_POST['action'] === 'edit') {
        $articleId = (int)$_POST['article_id'];
        $title = trim($_POST['title']);
        $short_desc = trim($_POST['short_description']);
        $content = trim($_POST['content']);
        
        $stmt = $pdo->prepare("SELECT image_url FROM articles WHERE id_articles = ?");
        $stmt->execute([$articleId]);
        $current = $stmt->fetch();
        $imageName = $current['image_url'];
        
        if (!empty($_FILES['image']['name'])) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], 'img/' . $imageName);
        }
        
        $pdo->prepare("UPDATE articles SET title = ?, short_description = ?, content = ?, image_url = ? WHERE id_articles = ?")
            ->execute([$title, $short_desc, $content, $imageName, $articleId]);
        
        $message = "Статья обновлена!";
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM articles WHERE id_articles = ?")->execute([$id]);
        $message = "Статья удалена!";
    }
}

$editArticleId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editArticle = null;

if ($editArticleId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id_articles = ?");
    $stmt->execute([$editArticleId]);
    $editArticle = $stmt->fetch();
}

$articles = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Управление статьями</title>
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

    <h2>Управление статьями</h2>
    
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
            <label>Название *</label>
            <input type="text" name="title" required placeholder="   Имя/Название">
        </div>

        <div class="form_group">
            <label>Картинка</label>
            <input type="file" name="image" accept="image/*" style="height: auto;">
        </div>

        <div class="form_group">
            <label>Краткое описание</label>
            <input type="text" name="short_description" placeholder="   Краткое описание">
        </div>

        <div class="form_group">
            <label>Полный текст *</label>
            <textarea name="content" required placeholder="    Добавить текст/Описание"></textarea>
        </div>

        <button class="send" type="submit">Создать</button>
    </form>

    <!-- ФОРМА РЕДАКТИРОВАНИЯ -->
    <?php if ($editArticle): ?>
    <form class="form_all create_form active" method="POST" enctype="multipart/form-data" style="margin-top: 2vw;">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="article_id" value="<?= $editArticle['id_articles'] ?>">
        
        <h3 style="color: #411D03; margin-bottom: 1vw;">Редактирование статьи: <?= htmlspecialchars($editArticle['title']) ?></h3>
        
        <div class="form_group">
            <label>Название *</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($editArticle['title']) ?>">
        </div>

        <div class="form_group">
            <label>Картинка (оставьте пустым, чтобы не менять)</label>
            <p style="font-size: 0.9vw; color: #411D03;">Текущая: <?= htmlspecialchars($editArticle['image_url']) ?></p>
            <input type="file" name="image" accept="image/*" style="height: auto;">
        </div>

        <div class="form_group">
            <label>Краткое описание</label>
            <input type="text" name="short_description" value="<?= htmlspecialchars($editArticle['short_description']) ?>">
        </div>

        <div class="form_group">
            <label>Полный текст *</label>
            <textarea name="content" required><?= htmlspecialchars($editArticle['content']) ?></textarea>
        </div>

        <button class="send" type="submit">Сохранить изменения</button>
        <a href="admin_article.php" class="btn_small" style="background-color: #D39772; color: white; margin-left: 1vw;">Отмена</a>
    </form>
    <?php endif; ?>

    <!-- ТАБЛИЦА СТАТЕЙ -->
    <div class="admin_table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                <tr>
                    <td><?= $article['id_articles'] ?></td>
                    <td><?= htmlspecialchars($article['title']) ?></td>
                    <td><?= htmlspecialchars(mb_substr($article['short_description'] ?? $article['content'], 0, 50)) ?>...</td>
                    <td><?= date('d.m.Y', strtotime($article['created_at'])) ?></td>
                    <td>
                        <a href="admin_article.php?edit=<?= $article['id_articles'] ?>" class="btn_small btn_edit">Редактировать</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $article['id_articles'] ?>">
                            <button type="submit" class="btn_small btn_delete" onclick="return confirm('Удалить статью?')">Удалить</button>
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
