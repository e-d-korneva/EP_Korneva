<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT c.id_chefs AS id, c.name, c.bio, c.photo_url
    FROM chefs c
    JOIN favorite_chefs fc ON c.id_chefs = fc.chef_id
    WHERE fc.user_id = ?
    ORDER BY fc.added_at DESC
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Избранные шеф-повара</title>
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
    <div class="account">
        <ul class="account_ul">
            <li class="account_ul_li"><a href="account_favorite_recipe.php"><h3>Рецепты</h3></a></li>
            <li class="account_ul_li"><a href="account_favorite_collection.php"><h3>Коллекции</h3></a></li>
            <li class="account_ul_li"><a href="account_favorite_chefs.php"><h3>Шеф-повара</h3></a></li>
            <li class="account_ul_li"><a href="account_favorite_blog.php"><h3>Блог</h3></a></li>
        </ul>
    </div>
    <div class="chefs">
        <?php if (empty($favorites)): ?>
            <p class="text-center-full">У вас пока нет избранных шеф-поваров.</p>
        <?php else: ?>
            <?php foreach ($favorites as $chef): ?>
                <div class="chefs_div">
                    <div class="chefs_img">
                        <a href="chef.php?id=<?= $chef['id'] ?>">
                            <img src="img/<?= htmlspecialchars($chef['photo_url'] ?: 'default_chef.jpg') ?>" class="img-responsive">
                        </a>
                    </div>
                    <div class="chefs_down">
                        <p><a href="chef.php?id=<?= $chef['id'] ?>" class="link-primary"><?= htmlspecialchars($chef['name']) ?></a></p>
                        <form class="form_btn form-inline" method="POST" action="actions.php">
                            <input type="hidden" name="action" value="favorite">
                            <input type="hidden" name="type" value="chef">
                            <input type="hidden" name="id" value="<?= $chef['id'] ?>">
                            <button type="submit" class="chefs_btn_favorite">
                                <img src="img/favorite_del.png" width="24" alt="Удалить из избранного">
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
