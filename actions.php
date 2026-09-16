<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ИЗБРАННОЕ
    if ($action === 'favorite') {
        $type = $_POST['type'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $map = [
            'recipe' => ['table' => 'favorite_recipes', 'col' => 'recipe_id'],
            'collection' => ['table' => 'favorite_collections', 'col' => 'collection_id'],
            'article' => ['table' => 'favorite_articles', 'col' => 'article_id'],
            'chef' => ['table' => 'favorite_chefs', 'col' => 'chef_id']
        ];
        if (isset($map[$type]) && $id > 0) {
            $t = $map[$type]['table'];
            $c = $map[$type]['col'];
            $stmt = $pdo->prepare("SELECT 1 FROM $t WHERE user_id = ? AND $c = ?");
            $stmt->execute([$userId, $id]);
            if ($stmt->fetch()) {
                $pdo->prepare("DELETE FROM $t WHERE user_id = ? AND $c = ?")->execute([$userId, $id]);
            } else {
                $pdo->prepare("INSERT INTO $t (user_id, $c) VALUES (?, ?)")->execute([$userId, $id]);
            }
        }
    }

    // ДОБАВИТЬ В КОЛЛЕКЦИЮ
    if ($action === 'add_to_collection') {
        $collId = (int)($_POST['collection_id'] ?? 0);
        $recId = (int)($_POST['recipe_id'] ?? 0);
        if ($collId > 0 && $recId > 0) {
            $pdo->prepare("INSERT IGNORE INTO collection_recipes (collection_id, recipe_id) VALUES (?, ?)")
                ->execute([$collId, $recId]);
        }
    }

    // УДАЛИТЬ ИЗ КОЛЛЕКЦИИ
    if ($action === 'remove_from_collection') {
        $collId = (int)($_POST['collection_id'] ?? 0);
        $recId = (int)($_POST['recipe_id'] ?? 0);
        $pdo->prepare("DELETE FROM collection_recipes WHERE collection_id = ? AND recipe_id = ?")
            ->execute([$collId, $recId]);
    }

    // СОЗДАТЬ КОЛЛЕКЦИЮ (с обязательной картинкой)
    if ($action === 'create_collection') {
        $name = trim($_POST['collection_name'] ?? '');
        $recId = (int)($_POST['recipe_id'] ?? 0);
        
        if (!empty($name)) {
            // Обработка загрузки картинки (ОБЯЗАТЕЛЬНАЯ)
            $imageName = 'default_collection.jpg'; // заглушка на случай ошибки
            
            if (isset($_FILES['collection_image']) && $_FILES['collection_image']['error'] === UPLOAD_ERR_OK) {
                // Проверка типа файла
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['collection_image']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    // Генерируем уникальное имя файла
                    $ext = pathinfo($_FILES['collection_image']['name'], PATHINFO_EXTENSION);
                    $imageName = 'coll_' . $userId . '_' . time() . '.' . $ext;
                    
                    // Перемещаем файл в папку img/
                    if (move_uploaded_file($_FILES['collection_image']['tmp_name'], 'img/' . $imageName)) {
                        // Файл успешно загружен
                    } else {
                        $imageName = 'default_collection.jpg';
                    }
                }
            }
            
            $pdo->prepare("INSERT INTO collections (user_id, name, image_url) VALUES (?, ?, ?)")
                ->execute([$userId, $name, $imageName]);
            $newCollId = $pdo->lastInsertId();
            
            if ($recId > 0) {
                $pdo->prepare("INSERT IGNORE INTO collection_recipes (collection_id, recipe_id) VALUES (?, ?)")
                    ->execute([$newCollId, $recId]);
            }
        }
    }
    
    // РЕДАКТИРОВАТЬ КОЛЛЕКЦИЮ (название + картинка)
    if ($action === 'edit_collection') {
        $collId = (int)($_POST['collection_id'] ?? 0);
        $name = trim($_POST['collection_name'] ?? '');
        
        if ($collId > 0 && !empty($name)) {
            // Проверяем, что коллекция принадлежит пользователю
            $stmt = $pdo->prepare("SELECT image_url FROM collections WHERE id_collections = ? AND user_id = ?");
            $stmt->execute([$collId, $userId]);
            $current = $stmt->fetch();
            
            if ($current) {
                $imageName = $current['image_url'];
                
                // Если загружена новая картинка
                if (isset($_FILES['collection_image']) && $_FILES['collection_image']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $fileType = $_FILES['collection_image']['type'];
                    
                    if (in_array($fileType, $allowedTypes)) {
                        $ext = pathinfo($_FILES['collection_image']['name'], PATHINFO_EXTENSION);
                        $imageName = 'coll_' . $userId . '_' . time() . '.' . $ext;
                        
                        if (move_uploaded_file($_FILES['collection_image']['tmp_name'], 'img/' . $imageName)) {
                            // Файл успешно загружен, старую картинку можно удалить (опционально)
                            // if ($current['image_url'] !== 'default_collection.jpg' && file_exists('img/' . $current['image_url'])) {
                            //     unlink('img/' . $current['image_url']);
                            // }
                        }
                    }
                }
                
                $pdo->prepare("UPDATE collections SET name = ?, image_url = ? WHERE id_collections = ? AND user_id = ?")
                    ->execute([$name, $imageName, $collId, $userId]);
            }
        }
    }
}

// Проверяем, что запрос AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'action' => $action]);
    exit;
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
?>