<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// Обработка отметки/снятия отметки "Выполнено"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Отметить как выполненное
    if ($_POST['action'] === 'resolve') {
        $id = (int)$_POST['complaint_id'];
        $pdo->prepare("UPDATE complaints SET is_resolved = 1 WHERE id_complaints = ?")->execute([$id]);
        $message = "Жалоба отмечена как выполненная!";
    }
    
    // Снять отметку "Выполнено"
    if ($_POST['action'] === 'unresolve') {
        $id = (int)$_POST['complaint_id'];
        $pdo->prepare("UPDATE complaints SET is_resolved = 0 WHERE id_complaints = ?")->execute([$id]);
        $message = "Отметка о выполнении снята!";
    }
    
    // Удалить жалобу
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['complaint_id'];
        $pdo->prepare("DELETE FROM complaints WHERE id_complaints = ?")->execute([$id]);
        $message = "Жалоба удалена!";
    }
}

// Получаем все жалобы с данными пользователей
$complaints = $pdo->query("
    SELECT c.id_complaints AS id, c.short_message, c.full_message, c.is_resolved, c.created_at, u.username, u.email 
    FROM complaints c 
    LEFT JOIN users u ON c.user_id = u.id_users 
    ORDER BY c.is_resolved ASC, c.created_at DESC
")->fetchAll();

// Статистика
$totalComplaints = count($complaints);
$resolvedComplaints = count(array_filter($complaints, fn($c) => $c['is_resolved'] == 1));
$unresolvedComplaints = $totalComplaints - $resolvedComplaints;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Управление жалобами</title>
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

    <h2>Управление жалобами</h2>
    
    <?php if ($message): ?>
        <p style="text-align: center; color: #B1AE69; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- СТАТИСТИКА -->
    <div class="admin_stats">
        <div class="stat_card">
            <h3>Всего жалоб</h3>
            <p><?= $totalComplaints ?></p>
        </div>
        <div class="stat_card">
            <h3>Выполнено</h3>
            <p style="color: #B1AE69;"><?= $resolvedComplaints ?></p>
        </div>
        <div class="stat_card">
            <h3>Ожидает обработки</h3>
            <p style="color: #D39772;"><?= $unresolvedComplaints ?></p>
        </div>
    </div>

    <!-- ТАБЛИЦА ЖАЛОБ -->
    <div class="admin_table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Обращение</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2vw;">Жалоб пока нет</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($complaints as $complaint): ?>
                    <tr class="<?= $complaint['is_resolved'] ? 'resolved' : '' ?>">
                        <td><?= $complaint['id'] ?></td>
                        <td>
                            <div class="user_info">
                                <?php if ($complaint['username']): ?>
                                    <strong><?= htmlspecialchars($complaint['username']) ?></strong><br>
                                    <small><?= htmlspecialchars($complaint['email']) ?></small>
                                <?php else: ?>
                                    <em>Гость</em>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="complaint_details">
                                <div class="complaint_short">
                                    <?= htmlspecialchars($complaint['short_message']) ?>
                                </div>
                                <?php if (!empty($complaint['full_message'])): ?>
                                    <div class="complaint_full">
                                        <?= nl2br(htmlspecialchars($complaint['full_message'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($complaint['created_at'])) ?></td>
                        <td>
                            <?php if ($complaint['is_resolved']): ?>
                                <span class="status_badge status_resolved">Выполнено</span>
                            <?php else: ?>
                                <span class="status_badge status_unresolved">Ожидает</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$complaint['is_resolved']): ?>
                                <form class="form_all" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="resolve">
                                    <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                                    <button type="submit" class="btn_small btn_resolve" title="Отметить как выполненное">Выполнено</button>
                                </form>
                            <?php else: ?>
                                <form class="form_all" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="unresolve">
                                    <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                                    <button type="submit" class="btn_small btn_unresolve" title="Снять отметку">Вернуть</button>
                                </form>
                            <?php endif; ?>
                            
                            <form class="form_all" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                                <button type="submit" class="btn_small btn_delete" onclick="return confirm('Удалить жалобу?')">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
