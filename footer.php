<?php
// Определяем, является ли пользователь админом
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<footer>
    <div class="footer_left">
        <ul class="footer_ul">
            <li class="header_ul_li"><a href="index.php">Главная</a></li>
            <li class="header_ul_li"><a href="list_collection.php">Коллекции</a></li>
            <li class="header_ul_li"><a href="chefs.php">Шеф-повара</a></li>
            <li class="header_ul_li"><a href="blog.php">Блог</a></li>
        </ul>
    </div>
    <div class="footer_center">
        <a href="mailto:recipes@yummy.ru">recipes@yummy.ru</a>
        <button class="btn_support"><a href="support.php">Поддержка</a></button>
        
        <?php if ($isAdmin): ?>
        <button class="btn_support"><a href="admin.php">Админ панель</a></button>
        <?php endif; ?>
    </div>
    <div class="footer_right">
        <ul class="footer_ul_right">
            <li class="footer_li_right"><a href="https://t.me/yummy"><img src="img/tg.png" width="35px"></a></li>
            <li class="footer_li_right"><a href="https://wa.me/message/yummy"><img src="img/wa.png" width="35px"></a></li>
            <li class="footer_li_right"><a href="https://vk.com/yummy"><img src="img/vk.png" width="35px"></a></li>
        </ul>
    </div>
</footer>