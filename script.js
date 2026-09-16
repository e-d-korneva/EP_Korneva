// ========== ФУНКЦИИ ДЛЯ СЛАЙДЕРА (index.php) ==========
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.slider-dot');
const totalSlides = slides.length;
let autoSlideInterval;

function changeSlide(direction) {
    if (totalSlides === 0) return;
    slides[currentSlide].classList.remove('active');
    if (dots[currentSlide]) dots[currentSlide].style.backgroundColor = '#D39772';
    currentSlide += direction;
    if (currentSlide >= totalSlides) currentSlide = 0;
    else if (currentSlide < 0) currentSlide = totalSlides - 1;
    slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) dots[currentSlide].style.backgroundColor = '#B1AE69';
    resetAutoSlide();
}

function goToSlide(index) {
    if (totalSlides === 0) return;
    slides[currentSlide].classList.remove('active');
    if (dots[currentSlide]) dots[currentSlide].style.backgroundColor = '#D39772';
    currentSlide = index;
    slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) dots[currentSlide].style.backgroundColor = '#B1AE69';
    resetAutoSlide();
}

function autoSlide() {
    changeSlide(1);
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    autoSlideInterval = setInterval(autoSlide, 4000);
}

if (totalSlides > 1) {
    autoSlideInterval = setInterval(autoSlide, 4000);
}

const seasonContainer = document.querySelector('.season');
if (seasonContainer) {
    seasonContainer.addEventListener('mouseenter', () => {
        clearInterval(autoSlideInterval);
    });
    seasonContainer.addEventListener('mouseleave', () => {
        if (totalSlides > 1) {
            autoSlideInterval = setInterval(autoSlide, 4000);
        }
    });
}

// ========== ФУНКЦИИ ДЛЯ ПЕРЕСЧЁТА ПОРЦИЙ (recipe.php) ==========
let currentPortions = 1;

function changePortions(change) {
    currentPortions += change;
    if (currentPortions < 1) currentPortions = 1;
    document.getElementById('portion-count').innerText = currentPortions;
    document.querySelectorAll('.ingredient-qty').forEach(el => {
        const baseQty = parseFloat(el.getAttribute('data-base-qty'));
        const unit = el.innerText.split(' ').pop();
        let newQty = baseQty * currentPortions;
        newQty = Number.isInteger(newQty) ? newQty : newQty.toFixed(1);
        el.innerText = newQty + ' ' + unit;
    });
}

// ========== ФУНКЦИИ ДЛЯ ВЫПАДАЮЩЕГО СПИСКА КОЛЛЕКЦИЙ ==========
// (ОБЪЕДИНЁННАЯ ВЕРСИЯ — без дубликатов)

function toggleCollMenu(recipeId) {
    const menu = document.getElementById('coll-menu-' + recipeId);
    // Закрываем все другие открытые меню
    document.querySelectorAll('.coll-dropdown').forEach(el => {
        if (el !== menu) el.style.display = 'none';
    });
    // Переключаем текущее меню
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

function toggleCreateForm(recipeId) {
    const form = document.getElementById('coll-create-' + recipeId);
    form.style.display = (form.style.display === 'block') ? 'none' : 'block';
}

// ОДИН обработчик закрытия меню при клике вне его
document.addEventListener('click', function(e) {
    // Проверяем, что клик был НЕ по кнопке меню, НЕ по самому меню
    if (!e.target.closest('.card_btn') && 
        !e.target.closest('.recipe_btn') && 
        !e.target.closest('.coll-dropdown')) {
        document.querySelectorAll('.coll-dropdown').forEach(el => {
            el.style.display = 'none';
        });
    }
});

// ========== ФУНКЦИЯ ДЛЯ ОБНОВЛЕНИЯ ДИАПАЗОНА (find.php) ==========
function updateRange(prefix, type, value, absoluteMax) {
    const minSpan = document.getElementById(prefix + '_min_val');
    const maxSpan = document.getElementById(prefix + '_max_val');
    
    // ИСПРАВЛЕНИЕ: префикс для ID span-ов ('cal') отличается от name инпутов ('calories')
    const inputPrefix = (prefix === 'cal') ? 'calories' : 'time';
    
    const minInput = document.querySelector(`input[name="${inputPrefix}_min"]`);
    const maxInput = document.querySelector(`input[name="${inputPrefix}_max"]`);
    
    // Защита от null (если на странице нет этих элементов)
    if (!minInput || !maxInput || !minSpan || !maxSpan) return;
    
    const minVal = parseInt(minInput.value) || 0;
    const maxVal = parseInt(maxInput.value) || 0;
    
    // Если минимальное значение больше максимального — меняем их местами
    if (type === 'min' && minVal > maxVal) {
        minInput.value = maxVal;
        minSpan.innerText = maxVal;
    } else if (type === 'max' && maxVal < minVal) {
        maxInput.value = minVal;
        maxSpan.innerText = minVal;
    } else {
        // Обновляем соответствующий span
        if (type === 'min') {
            minSpan.innerText = value;
        } else {
            maxSpan.innerText = value;
        }
    }
}

// ========== AJAX ДЛЯ ИЗБРАННОГО И КОЛЛЕКЦИЙ ==========

function toggleFavorite(type, id, button) {
    const formData = new FormData();
    formData.append('action', 'favorite');
    formData.append('type', type);
    formData.append('id', id);

    fetch('actions.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const img = button.querySelector('img');
            const src = img.getAttribute('src');
            if (src.includes('favorite_del.png')) {
                img.src = 'img/favorite.png';
            } else {
                img.src = 'img/favorite_del.png';
            }
        }
    })
    .catch(err => console.error('Ошибка:', err));
}

function addToCollectionAjax(collectionId, recipeId, button) {
    const formData = new FormData();
    formData.append('action', 'add_to_collection');
    formData.append('collection_id', collectionId);
    formData.append('recipe_id', recipeId);

    fetch('actions.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const menu = button.closest('.coll-dropdown');
            if (menu) menu.style.display = 'none';

            document.querySelectorAll(`.coll-btn[data-recipe-id="${recipeId}"] img, button[onclick*="toggleCollMenu(${recipeId})"] img`).forEach(img => {
                img.src = 'img/collection_del.png';
            });
            alert('Добавлено в коллекцию!');
        }
    })
    .catch(err => console.error('Ошибка:', err));
}