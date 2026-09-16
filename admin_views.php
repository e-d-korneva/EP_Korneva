<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$views = $pdo->query("
    SELECT TABLE_NAME as view_name, 
           UPDATE_TIME as last_updated
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_TYPE = 'VIEW'
    ORDER BY TABLE_NAME
")->fetchAll();

$viewsData = [];
foreach ($views as $view) {
    $viewName = $view['view_name'];
    try {
        $stmt = $pdo->query("SELECT * FROM $viewName");
        $data = $stmt->fetchAll();
        $columns = $stmt->columnCount();
        $viewsData[$viewName] = [
            'data' => $data,
            'columns' => $columns,
            'count' => count($data)
        ];
    } catch (PDOException $e) {
        $viewsData[$viewName] = [
            'data' => [],
            'columns' => 0,
            'count' => 0,
            'error' => $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Представления БД</title>
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
            <li class="account_ul_li"><a href="admin_recipes.php"><h2 style="padding: 2vw 0 0 0;">Рецепты</h2></a></li>
            <li class="account_ul_li"><a href="admin_chefs.php"><h2 style="padding: 2vw 0 0 0;">Шеф-повара</h2></a></li>
            <li class="account_ul_li"><a href="admin_article.php"><h2 style="padding: 2vw 0 0 0;">Статьи</h2></a></li>
            <li class="account_ul_li"><a href="admin_complaints.php"><h2 style="padding: 2vw 0 0 0;">Жалобы</h2></a></li>
            <li class="account_ul_li"><a href="admin_views.php"><h2 style="padding: 2vw 0 0 0;">Представления</h2></a></li>
        </ul>
    </div>

    <?php if (empty($views)): ?>
    <p style="text-align: center; color: #D39772; font-weight: bold;">Представления не найдены</p>
    <?php else: ?>

    <div class="admin_table">
        <h2>Список представлений:</h2>
    
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Название представления</th>
                    <th>Последнее обновление</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>

            <?php 
            $index = 1;
            foreach ($views as $view): 
                $viewName = $view['view_name'];
                $lastUpdated = $view['last_updated'] ?? 'Н/Д';
            ?>

            <tr>
                <td><?= $index++ ?></td>
                <td style="font-weight: bold; color: #411D03;"><?= htmlspecialchars($viewName) ?></td>
                <td><?= htmlspecialchars($lastUpdated) ?></td>
                <td><button class="btn_small btn_edit" onclick="showView('<?= htmlspecialchars($viewName) ?>')">Показать данные</button></td>
            </tr>

            <?php endforeach; ?>
            
            </tbody>
        </table>
    </div>

    <?php foreach ($viewsData as $viewName => $viewInfo): ?>

    <div id="view-<?= htmlspecialchars($viewName) ?>" class="view-data-section" style="display: none; margin-top: 2vw;">
    <div class="admin_table">
        <h3 style="color: #411D03; margin-bottom: 1vw;">
            Представление: <?= htmlspecialchars($viewName) ?>
            <button class="btn_small" style="background-color: #D39772; color: white; margin-left: 1vw;" 
                    onclick="hideView('<?= htmlspecialchars($viewName) ?>')">
                Скрыть
            </button>
        </h3>
        <?php if (isset($viewInfo['error'])): ?>
        <p style="color: red; font-weight: bold;">Ошибка: <?= htmlspecialchars($viewInfo['error']) ?></p>
        <?php elseif (empty($viewInfo['data'])): ?>
        <p style="text-align: center; color: #B1AE69;">Нет данных для отображения</p>
        <?php else: ?>
        <p style="margin-bottom: 1vw; color: #411D03;">Показано записей: <strong><?= $viewInfo['count'] ?></strong></p>

    <table>
        <thead>
            <tr>
                <?php 
                if (!empty($viewInfo['data'])):
                    foreach (array_keys($viewInfo['data'][0]) as $column): 
                ?>
                <th><?= htmlspecialchars($column) ?></th>
                <?php 
                    endforeach;
                endif;
                ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($viewInfo['data'] as $row): ?>
            <tr>
            <?php foreach ($row as $cell): ?>
                <td><?= htmlspecialchars($cell ?? '') ?></td>
            <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
    </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

<script>
function showView(viewName) {
    document.querySelectorAll('.view-data-section').forEach(el => {
        el.style.display = 'none';
    });
    const viewElement = document.getElementById('view-' + viewName);
    if (viewElement) {
        viewElement.style.display = 'block';
        viewElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
function hideView(viewName) {
    const viewElement = document.getElementById('view-' + viewName);
    if (viewElement) {
        viewElement.style.display = 'none';
    }
}
</script>
</body>
</html>