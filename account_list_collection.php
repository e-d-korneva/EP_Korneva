<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$collections = getUserCollections($pdo, $userId);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Мои коллекции</title>
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
            <li class="account_ul_li"><a href="account.php"><h2>Аккаунт</h2></a></li>
            <li class="account_ul_li"><a href="account_favorite_recipe.php"><h2>Избранное</h2></a></li>
            <li class="account_ul_li"><a href="account_list_collection.php"><h2>Коллекции</h2></a></li>
        </ul>
    </div>

    <!-- Кнопка создания коллекции -->
    <div class="create_collection">
        <button class="btn_create_collection" type="button" onclick="document.getElementById('createForm').style.display = document.getElementById('createForm').style.display === 'none' ? 'flex' : 'none'">
            Создать коллекцию
        </button>
        
        <form class="form_all" method="POST" action="actions.php" id="createForm" enctype="multipart/form-data" style="display: none; flex-direction: column; align-items: center; gap: 1vw; margin-top: 1vw;">
            <input type="hidden" name="action" value="create_collection">
            <input type="hidden" name="recipe_id" value="0">
            <input type="text" name="collection_name" placeholder="   Название" maxlength="50" required style="width: 20vw;">
            <label style="color: #411D03; font-weight: bold;">Обложка коллекции *</label>
            <input type="file" name="collection_image" accept="image/*" required style="width: 20vw; height: auto; padding: 0.5vw;">
            <p style="font-size: 0.8vw; color: #B1AE69; text-align: center;">
                Минимум 400x400 px, максимум 2000x2000 px, до 2MB
            </p>
            <button class="btn_create_collection" type="submit">Создать</button>
        </form>
    </div>
    
    <div class="collection">
        <?php if (empty($collections)): ?>
            <p style="text-align: center; width: 100%; grid-column: 1 / -1;">У вас пока нет коллекций.</p>
        <?php else: ?>
            <?php foreach ($collections as $coll): ?>
                <div class="collection_div">
                    <div class="collection_up">
                        <div class="collection_up_left">
                            <p><a href="account_collection.php?id=<?= $coll['id'] ?>" style="color: #411D03; text-decoration: none; font-weight: bold;"><?= htmlspecialchars($coll['name']) ?></a></p>
                            <p style="font-size: 0.8vw;">Автор: Вы</p>
                        </div>
                    </div>
                    <div class="collection_img">
                        <a href="account_collection.php?id=<?= $coll['id'] ?>">
                            <img src="img/<?= getCollectionImage($coll) ?>" style="max-width: 100%; height: 15vw; object-fit: cover; border-radius: 0.3vw;">
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
