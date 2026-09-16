<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$collectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($collectionId <= 0) {
    header('Location: account_list_collection.php');
    exit;
}

// Проверяем, что коллекция принадлежит пользователю
$stmt = $pdo->prepare("SELECT name, image_url FROM collections WHERE id_collections = ? AND user_id = ?");
$stmt->execute([$collectionId, $userId]);
$collection = $stmt->fetch();

if (!$collection) {
    die("Коллекция не найдена или у вас нет к ней доступа.");
}

// Получаем рецепты из этой коллекции
$stmt = $pdo->prepare("
    SELECT r.id_recipes AS id, r.title, r.image_url, r.country, r.cooking_time_minutes, r.calories
    FROM recipes r
    JOIN collection_recipes cr ON r.id_recipes = cr.recipe_id
    WHERE cr.collection_id = ?
");
$stmt->execute([$collectionId]);
$recipes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title><?= htmlspecialchars($collection['name']) ?></title>
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

    <h3><?= htmlspecialchars($collection['name']) ?></h3>

    <!-- Форма редактирования (скрыта по умолчанию) -->
    <div class="create_collection">
        <button class="btn_create_collection" type="button" onclick="document.getElementById('editForm').style.display = document.getElementById('editForm').style.display === 'none' ? 'flex' : 'none'">
            Редактировать коллекцию
        </button>
        
        <form class="form_all" method="POST" action="actions.php" id="editForm" enctype="multipart/form-data" style="display: none; flex-direction: column; align-items: center; gap: 1vw; margin-top: 1vw;">
            <input type="hidden" name="action" value="edit_collection">
            <input type="hidden" name="collection_id" value="<?= $collectionId ?>">
            <input type="text" name="collection_name" value="<?= htmlspecialchars($collection['name']) ?>" maxlength="50" required style="width: 20vw;">
            
            <label style="color: #411D03; font-weight: bold;">Текущая обложка:</label>
            <img src="img/<?= getCollectionImage($collection) ?>" style="max-width: 10vw; max-height: 8vw; object-fit: cover; border-radius: 0.3vw; margin-bottom: 0.5vw;">
            
            <label style="color: #411D03; font-weight: bold;">Новая обложка (оставьте пустым, чтобы не менять):</label>
            <input type="file" name="collection_image" accept="image/*" style="width: 20vw; height: auto; padding: 0.5vw;">
            
            <button class="btn_create_collection" type="submit">Сохранить</button>
        </form>
    </div>

    <div class="catalog">
        <?php if (empty($recipes)): ?>
            <p style="text-align: center; width: 100%; grid-column: 1 / -1;">В этой коллекции пока нет рецептов.</p>
        <?php else: ?>
            <?php foreach ($recipes as $recipe): ?>
                <div class="card">
                    <div class="card_img">
                        <a href="recipe.php?id=<?= $recipe['id'] ?>">
                            <img src="img/<?= htmlspecialchars($recipe['image_url'] ?: 'default_recipe.jpg') ?>" style="max-width: 100%;">
                        </a>
                    </div>
                    <div class="card_center">
                        <p><a href="recipe.php?id=<?= $recipe['id'] ?>" style="color: #411D03; text-decoration: none;"><?= htmlspecialchars($recipe['title']) ?></a></p>
                        <div class="card_btn">
                            <!-- Кнопка удаления из коллекции -->
                            <form class="form_btn" method="POST" action="actions.php" style="display: inline;">
                                <input type="hidden" name="action" value="remove_from_collection">
                                <input type="hidden" name="collection_id" value="<?= $collectionId ?>">
                                <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                                <button type="submit" class="btn_card" title="Удалить из коллекции">
                                    <img src="img/collection_del.png" width="24">
                                </button>
                            </form>
                            
                            <!-- Кнопка избранного -->
                            <form class="form_btn" method="POST" action="actions.php" style="display: inline;">
                                <input type="hidden" name="action" value="favorite">
                                <input type="hidden" name="type" value="recipe">
                                <input type="hidden" name="id" value="<?= $recipe['id'] ?>">
                                <button type="submit" class="btn_card">
                                    <img src="img/<?= getFavIcon($pdo, $userId, 'recipe', $recipe['id']) ?>" width="24">
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card_tags">
                        <?php if (!empty($recipe['country'])): ?><div class="card_tag"><p><?= htmlspecialchars($recipe['country']) ?></p></div><?php endif; ?>
                        <?php if (!empty($recipe['cooking_time_minutes'])): ?><div class="card_tag"><p><?= $recipe['cooking_time_minutes'] ?> мин</p></div><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
