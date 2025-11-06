<?php
/**
 * Главная страница сайта
 * Показывает приветствие и топ репетиторов
 */

// Получаем топ-3 репетиторов
$stmt = $conn->query("
    SELECT t.*, u.name 
    FROM tutors t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.rating DESC 
    LIMIT 3
");
$topTutors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Подключаем шаблон
$title = 'Главная';
include 'templates/header.php';
?>

<!-- Баннер -->
<div style="
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 2rem;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 3rem;
">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">
        Репетиторы по математике
    </h1>
    <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9;">
        Найдите своего идеального репетитора и достигните успеха в математике!
    </p>
    <?php if (!isLoggedIn()): ?>
        <a href="/register" class="btn btn-success" style="font-size: 1.1rem; padding: 12px 30px;">
            Начать обучение
        </a>
    <?php else: ?>
        <a href="/tutors" class="btn btn-success" style="font-size: 1.1rem; padding: 12px 30px;">
            Выбрать репетитора
        </a>
    <?php endif; ?>
</div>

<!-- Преимущества -->
<div class="card">
    <h2 style="text-align: center; margin-bottom: 2rem; color: #2d3748;">
        Почему выбирают нас?
    </h2>
    
    <div class="grid">
        <!-- Преимущество 1 -->
        <div style="text-align: center; padding: 1rem;">
            <div style="
                width: 80px;
                height: 80px;
                background: #667eea;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
            ">
                🎓
            </div>
            <h3 style="margin-bottom: 0.5rem;">Опытные преподаватели</h3>
            <p style="color: #718096;">
                Только проверенные репетиторы с многолетним опытом
            </p>
        </div>
        
        <!-- Преимущество 2 -->
        <div style="text-align: center; padding: 1rem;">
            <div style="
                width: 80px;
                height: 80px;
                background: #48bb78;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
            ">
                💰
            </div>
            <h3 style="margin-bottom: 0.5rem;">Доступные цены</h3>
            <p style="color: #718096;">
                Широкий диапазон цен - от 1000 до 3000 рублей за час
            </p>
        </div>
        
        <!-- Преимущество 3 -->
        <div style="text-align: center; padding: 1rem;">
            <div style="
                width: 80px;
                height: 80px;
                background: #f56565;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
            ">
                ⭐
            </div>
            <h3 style="margin-bottom: 0.5rem;">Гарантия качества</h3>
            <p style="color: #718096;">
                Система рейтингов и отзывов от реальных учеников
            </p>
        </div>
    </div>
</div>

<!-- Топ репетиторов -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Лучшие репетиторы</h2>
    </div>
    
    <div class="grid">
        <?php foreach ($topTutors as $tutor): ?>
            <div class="card" style="margin-bottom: 0;">
                <h3 style="margin-bottom: 0.5rem; color: #2d3748;">
                    <?= e($tutor['name']) ?>
                </h3>
                
                <p style="color: #718096; margin-bottom: 1rem;">
                    <?= e($tutor['subjects']) ?>
                </p>
                
                <div style="margin-bottom: 1rem;">
                    <span style="color: #f6ad55; font-size: 1.2rem;">
                        <?php for ($i = 0; $i < floor($tutor['rating']); $i++): ?>⭐<?php endfor; ?>
                    </span>
                    <span style="color: #718096;">
                        <?= number_format($tutor['rating'], 1) ?>
                    </span>
                </div>
                
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding-top: 1rem;
                    border-top: 1px solid #e2e8f0;
                ">
                    <div>
                        <div style="font-size: 0.9rem; color: #718096;">Опыт</div>
                        <div style="font-weight: bold;"><?= $tutor['experience'] ?> лет</div>
                    </div>
                    
                    <div>
                        <div style="font-size: 0.9rem; color: #718096;">Цена</div>
                        <div style="font-weight: bold; color: #48bb78;">
                            <?= formatPrice($tutor['price_per_hour']) ?>/час
                        </div>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <a 
                        href="/booking?tutor_id=<?= $tutor['id'] ?>" 
                        class="btn btn-primary" 
                        style="width: 100%; margin-top: 1rem;"
                    >
                        Записаться
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="/tutors" class="btn btn-primary">
            Посмотреть всех репетиторов
        </a>
    </div>
</div>

<!-- Как это работает -->
<div class="card">
    <h2 style="text-align: center; margin-bottom: 2rem; color: #2d3748;">
        Как это работает?
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
        <!-- Шаг 1 -->
        <div style="text-align: center;">
            <div style="
                width: 60px;
                height: 60px;
                background: #667eea;
                color: white;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: bold;
            ">
                1
            </div>
            <h4 style="margin-bottom: 0.5rem;">Регистрация</h4>
            <p style="color: #718096; font-size: 0.9rem;">
                Создайте аккаунт за 1 минуту
            </p>
        </div>
        
        <!-- Шаг 2 -->
        <div style="text-align: center;">
            <div style="
                width: 60px;
                height: 60px;
                background: #667eea;
                color: white;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: bold;
            ">
                2
            </div>
            <h4 style="margin-bottom: 0.5rem;">Выбор репетитора</h4>
            <p style="color: #718096; font-size: 0.9rem;">
                Найдите подходящего специалиста
            </p>
        </div>
        
        <!-- Шаг 3 -->
        <div style="text-align: center;">
            <div style="
                width: 60px;
                height: 60px;
                background: #667eea;
                color: white;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: bold;
            ">
                3
            </div>
            <h4 style="margin-bottom: 0.5rem;">Оплата</h4>
            <p style="color: #718096; font-size: 0.9rem;">
                Оплатите занятие онлайн
            </p>
        </div>
        
        <!-- Шаг 4 -->
        <div style="text-align: center;">
            <div style="
                width: 60px;
                height: 60px;
                background: #667eea;
                color: white;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: bold;
            ">
                4
            </div>
            <h4 style="margin-bottom: 0.5rem;">Обучение</h4>
            <p style="color: #718096; font-size: 0.9rem;">
                Достигайте новых высот!
            </p>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
