<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // СОЗДАНИЕ РЕЦЕПТА
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        $country = trim($_POST['country']);
        $instructions = trim($_POST['instructions']);
        $cooking_time = (int)$_POST['cooking_time'];
        $calories = (int)$_POST['calories'];
        $chef_id = !empty($_POST['chef_id']) ? (int)$_POST['chef_id'] : null;

        $imageName = 'default_recipe.jpg';
        if (!empty($_FILES['image']['name'])) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], 'img/' . $imageName);
        }

        $pdo->prepare("INSERT INTO recipes (title, country, instructions, cooking_time_minutes, calories, image_url, chef_id) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$title, $country, $instructions, $cooking_time, $calories, $imageName, $chef_id]);

        $recipe_id = $pdo->lastInsertId();

        if (!empty($_POST['categories'])) {
            foreach ($_POST['categories'] as $cat_id) {
                $pdo->prepare("INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)")->execute([$recipe_id, $cat_id]);
            }
        }

        if (!empty($_POST['ingredients'])) {
            foreach ($_POST['ingredients'] as $ing_id) {
                $quantity = $_POST['quantity_' . $ing_id] ?? 0;
                $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)")->execute([$recipe_id, $ing_id, $quantity]);
            }
        }

        if (!empty($_POST['diets'])) {
            foreach ($_POST['diets'] as $diet_id) {
                $pdo->prepare("INSERT INTO recipe_diets (recipe_id, diet_id) VALUES (?, ?)")->execute([$recipe_id, $diet_id]);
            }
        }

        $message = "Рецепт успешно создан!";
    }

    // РЕДАКТИРОВАНИЕ РЕЦЕПТА
    if ($_POST['action'] === 'edit') {
        $recipe_id = (int)$_POST['recipe_id'];
        $title = trim($_POST['title']);
        $country = trim($_POST['country']);
        $instructions = trim($_POST['instructions']);
        $cooking_time = (int)$_POST['cooking_time'];
        $calories = (int)$_POST['calories'];
        $chef_id = !empty($_POST['chef_id']) ? (int)$_POST['chef_id'] : null;

        // Получаем текущую картинку
        $stmt = $pdo->prepare("SELECT image_url FROM recipes WHERE id_recipes = ?");
        $stmt->execute([$recipe_id]);
        $current = $stmt->fetch();
        $imageName = $current['image_url'];

        // Если загружена новая картинка
        if (!empty($_FILES['image']['name'])) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], 'img/' . $imageName);
        }

        // Обновляем основную запись
        $pdo->prepare("UPDATE recipes SET title = ?, country = ?, instructions = ?, cooking_time_minutes = ?, calories = ?, image_url = ?, chef_id = ? WHERE id_recipes = ?")
            ->execute([$title, $country, $instructions, $cooking_time, $calories, $imageName, $chef_id, $recipe_id]);

        // Удаляем старые связи
        $pdo->prepare("DELETE FROM recipe_categories WHERE recipe_id = ?")->execute([$recipe_id]);
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipe_id]);
        $pdo->prepare("DELETE FROM recipe_diets WHERE recipe_id = ?")->execute([$recipe_id]);

        // Добавляем новые связи
        if (!empty($_POST['categories'])) {
            foreach ($_POST['categories'] as $cat_id) {
                $pdo->prepare("INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)")->execute([$recipe_id, $cat_id]);
            }
        }

        if (!empty($_POST['ingredients'])) {
            foreach ($_POST['ingredients'] as $ing_id) {
                $quantity = $_POST['quantity_' . $ing_id] ?? 0;
                $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)")->execute([$recipe_id, $ing_id, $quantity]);
            }
        }

        if (!empty($_POST['diets'])) {
            foreach ($_POST['diets'] as $diet_id) {
                $pdo->prepare("INSERT INTO recipe_diets (recipe_id, diet_id) VALUES (?, ?)")->execute([$recipe_id, $diet_id]);
            }
        }

        $message = "Рецепт успешно обновлён!";
    }

    // УДАЛЕНИЕ РЕЦЕПТА
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM recipes WHERE id_recipes = ?")->execute([$id]);
        $message = "Рецепт удален!";
    }
}

// Получаем ID рецепта для редактирования (если передан)
$editRecipeId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRecipe = null;
$editCategories = [];
$editIngredients = [];
$editDiets = [];

if ($editRecipeId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id_recipes = ?");
    $stmt->execute([$editRecipeId]);
    $editRecipe = $stmt->fetch();

    if ($editRecipe) {
        // Получаем текущие категории
        $stmt = $pdo->prepare("SELECT category_id FROM recipe_categories WHERE recipe_id = ?");
        $stmt->execute([$editRecipeId]);
        $editCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Получаем текущие ингредиенты с количеством
        $stmt = $pdo->prepare("SELECT ingredient_id, quantity FROM recipe_ingredients WHERE recipe_id = ?");
        $stmt->execute([$editRecipeId]);
        $editIngredients = $stmt->fetchAll();

        // Получаем текущие диеты
        $stmt = $pdo->prepare("SELECT diet_id FROM recipe_diets WHERE recipe_id = ?");
        $stmt->execute([$editRecipeId]);
        $editDiets = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Справочники (с новыми именами PK)
$chefs = $pdo->query("SELECT id_chefs AS id, name FROM chefs ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT id_categories AS id, name FROM categories ORDER BY name")->fetchAll();
$ingredients = $pdo->query("SELECT id_ingredients AS id, name, unit FROM ingredients ORDER BY name")->fetchAll();
$diets = $pdo->query("SELECT id_diets AS id, name FROM diets ORDER BY name")->fetchAll();

// Список рецептов
$recipes = $pdo->query("SELECT r.id_recipes AS id, r.title, r.country, r.cooking_time_minutes, r.calories, c.name as chef_name FROM recipes r LEFT JOIN chefs c ON r.chef_id = c.id_chefs ORDER BY r.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Управление рецептами</title>
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

    <h2>Управление рецептами</h2>

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
            <label>Название рецепта *</label>
            <input type="text" name="title" required placeholder="   Добавить название">
        </div>

        <div class="form_group">
            <label>Страна</label>
            <input type="text" name="country" placeholder="   Страна">
        </div>

        <div class="form_group">
            <label>Шеф-повар</label>
            <select name="chef_id">
                <option value="">Выберите шеф-повара</option>
                <?php foreach ($chefs as $chef): ?>
                    <option value="<?= $chef['id'] ?>"><?= htmlspecialchars($chef['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form_group">
            <label>Категории (удерживайте Ctrl для выбора нескольких)</label>
            <select name="categories[]" multiple>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form_group">
            <label>Диеты</label>
            <select name="diets[]" multiple>
                <?php foreach ($diets as $diet): ?>
                    <option value="<?= $diet['id'] ?>"><?= htmlspecialchars($diet['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form_group">
            <label>Ингредиенты</label>
            <div class="ingredients-list">
                <?php foreach ($ingredients as $ing): ?>
                    <div class="ingredient-item">
                        <input type="checkbox"
                               name="ingredients[]"
                               value="<?= $ing['id'] ?>"
                               id="ing_<?= $ing['id'] ?>">
                        <label for="ing_<?= $ing['id'] ?>" class="ingredient-name">
                            <?= htmlspecialchars($ing['name']) ?>
                        </label>
                        <input type="number"
                               step="0.01"
                               name="quantity_<?= $ing['id'] ?>"
                               placeholder="<?= htmlspecialchars($ing['unit']) ?>"
                               class="ingredient-qty">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form_group">
            <label>Картинка</label>
            <input type="file" name="image" accept="image/*" style="height: auto;">
        </div>

        <div class="form_group">
            <label>Инструкция приготовления *</label>
            <textarea name="instructions" required placeholder="    Добавить инструкцию"></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1vw;">
            <div class="form_group">
                <label>Время приготовления (мин)</label>
                <input type="number" name="cooking_time" placeholder="30">
            </div>
            <div class="form_group">
                <label>Калории (ккал)</label>
                <input type="number" name="calories" placeholder="250">
            </div>
        </div>

        <button class="send" type="submit">Создать</button>
    </form>

    <!-- ФОРМА РЕДАКТИРОВАНИЯ -->
    <?php if ($editRecipe): ?>
        <form class="form_all create_form active" method="POST" enctype="multipart/form-data" style="margin-top: 2vw;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="recipe_id" value="<?= $editRecipe['id_recipes'] ?>">

            <h3 style="color: #411D03; margin-bottom: 1vw;">Редактирование рецепта: <?= htmlspecialchars($editRecipe['title']) ?></h3>

            <div class="form_group">
                <label>Название рецепта *</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($editRecipe['title']) ?>">
            </div>

            <div class="form_group">
                <label>Страна</label>
                <input type="text" name="country" value="<?= htmlspecialchars($editRecipe['country']) ?>">
            </div>

            <div class="form_group">
                <label>Шеф-повар</label>
                <select name="chef_id">
                    <option value="">Выберите шеф-повара</option>
                    <?php foreach ($chefs as $chef): ?>
                        <option value="<?= $chef['id'] ?>" <?= $editRecipe['chef_id'] == $chef['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($chef['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form_group">
                <label>Категории (удерживайте Ctrl для выбора нескольких)</label>
                <select name="categories[]" multiple>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $editCategories) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form_group">
                <label>Диеты</label>
                <select name="diets[]" multiple>
                    <?php foreach ($diets as $diet): ?>
                        <option value="<?= $diet['id'] ?>" <?= in_array($diet['id'], $editDiets) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($diet['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form_group">
                <label>Ингредиенты</label>
                <div class="edit-ingredients-list">
                    <?php
                    $editIngMap = [];
                    foreach ($editIngredients as $ei) {
                        $editIngMap[$ei['ingredient_id']] = $ei['quantity'];
                    }
                    foreach ($ingredients as $ing):
                        $isChecked = isset($editIngMap[$ing['id']]);
                        $qty = $editIngMap[$ing['id']] ?? '';
                    ?>
                        <div class="edit-ingredient-item">
                            <input type="checkbox"
                                   name="ingredients[]"
                                   value="<?= $ing['id'] ?>"
                                   id="ing_edit_<?= $ing['id'] ?>"
                                   <?= $isChecked ? 'checked' : '' ?>>
                            <label for="ing_edit_<?= $ing['id'] ?>" class="edit-ingredient-name">
                                <?= htmlspecialchars($ing['name']) ?>
                            </label>
                            <input type="number"
                                   step="0.01"
                                   name="quantity_<?= $ing['id'] ?>"
                                   value="<?= $qty ?>"
                                   placeholder="<?= htmlspecialchars($ing['unit']) ?>"
                                   class="edit-ingredient-qty">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form_group">
                <label>Картинка (оставьте пустым, чтобы не менять)</label>
                <p style="font-size: 0.9vw; color: #411D03;">Текущая: <?= htmlspecialchars($editRecipe['image_url']) ?></p>
                <input type="file" name="image" accept="image/*" style="height: auto;">
            </div>

            <div class="form_group">
                <label>Инструкция приготовления *</label>
                <textarea name="instructions" required><?= htmlspecialchars($editRecipe['instructions']) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1vw;">
                <div class="form_group">
                    <label>Время приготовления (мин)</label>
                    <input type="number" name="cooking_time" value="<?= $editRecipe['cooking_time_minutes'] ?>">
                </div>
                <div class="form_group">
                    <label>Калории (ккал)</label>
                    <input type="number" name="calories" value="<?= $editRecipe['calories'] ?>">
                </div>
            </div>

            <button class="send" type="submit">Сохранить изменения</button>
            <a href="admin_recipes.php" class="btn_small" style="background-color: #D39772; color: white; margin-left: 1vw;">Отмена</a>
        </form>
    <?php endif; ?>

    <!-- ТАБЛИЦА РЕЦЕПТОВ -->
    <div class="admin_table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Страна</th>
                    <th>Шеф</th>
                    <th>Время</th>
                    <th>Ккал</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td><?= $recipe['id'] ?></td>
                        <td><?= htmlspecialchars($recipe['title']) ?></td>
                        <td><?= htmlspecialchars($recipe['country']) ?></td>
                        <td><?= htmlspecialchars($recipe['chef_name'] ?? '—') ?></td>
                        <td><?= $recipe['cooking_time_minutes'] ?> мин</td>
                        <td><?= $recipe['calories'] ?></td>
                        <td>
                            <a href="admin_recipes.php?edit=<?= $recipe['id'] ?>" class="btn_small btn_edit">Редактировать</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $recipe['id'] ?>">
                                <button type="submit" class="btn_small btn_delete" onclick="return confirm('Удалить рецепт?')">Удалить</button>
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
