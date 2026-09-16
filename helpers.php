<?php
function getUserFavorites($pdo, $userId, $type) {
    // В таблицах favorite_* колонки остались без префикса id_
    $map = [
        'recipe'     => ['table' => 'favorite_recipes',     'col' => 'recipe_id'],
        'collection' => ['table' => 'favorite_collections', 'col' => 'collection_id'],
        'article'    => ['table' => 'favorite_articles',    'col' => 'article_id'],
        'chef'       => ['table' => 'favorite_chefs',       'col' => 'chef_id']
    ];
    if (!isset($map[$type])) return [];
    
    $stmt = $pdo->prepare("SELECT {$map[$type]['col']} FROM {$map[$type]['table']} WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getUserCollections($pdo, $userId) {
    // Первичный ключ в collections теперь id_collections
    // Используем алиас AS id, чтобы не ломать остальной код ($coll['id'])
    $stmt = $pdo->prepare("SELECT id_collections AS id, name, image_url FROM collections WHERE user_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function isRecipeInUserCollection($pdo, $userId, $recipeId) {
    // В collections первичный ключ id_collections
    // В collection_recipes колонки остались collection_id и recipe_id
    $stmt = $pdo->prepare("
        SELECT cr.collection_id 
        FROM collection_recipes cr 
        JOIN collections c ON cr.collection_id = c.id_collections 
        WHERE c.user_id = ? AND cr.recipe_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$userId, $recipeId]);
    return $stmt->fetch();
}

function getFavIcon($pdo, $userId, $type, $id) {
    if ($userId <= 0) return 'favorite.png';
    return in_array($id, getUserFavorites($pdo, $userId, $type)) ? 'favorite_del.png' : 'favorite.png';
}

function getCollIcon($pdo, $userId, $recipeId) {
    if ($userId <= 0) return 'collection.png';
    return isRecipeInUserCollection($pdo, $userId, $recipeId) ? 'collection_del.png' : 'collection.png';
}

// Получить картинку коллекции (с заглушкой)
function getCollectionImage($collection) {
    if (!empty($collection['image_url'])) {
        return htmlspecialchars($collection['image_url']);
    }
    return 'default_collection.jpg';
}
?>