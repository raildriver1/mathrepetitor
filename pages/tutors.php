<?php
/**
 * Страница со списком всех репетиторов
 * Показывает информацию о каждом репетиторе
 */

// Получаем всех репетиторов
$tutors = getAllTutors();

// Подключаем шаблон
$title = 'Репетиторы';
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Наши репетиторы</h1>
        <p style="color: #718096; margin-top: 0.5rem;">
            Выберите репетитора, который подходит вам по опыту, цене и специализации
        </p>
    </div>
    
    <?php if (empty($tutors)): ?>
        <p style="text-align: center; padding: 2rem; color: #718096;">
            Репетиторы не найдены
        </p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($tutors as $tutor): ?>
                <div class="card" style="margin-bottom: 0;">
                    <!-- Имя и рейтинг -->
                    <div style="margin-bottom: 1rem;">
                        <h3 style="margin-bottom: 0.5rem; color: #2d3748;">
                            <?= e($tutor['name']) ?>
                        </h3>
                        
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: #f6ad55; font-size: 1.2rem;">
                                <?php for ($i = 0; $i < floor($tutor['rating']); $i++): ?>⭐<?php endfor; ?>
                            </span>
                            <span style="color: #718096; font-size: 0.9rem;">
                                <?= number_format($tutor['rating'], 1) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Предметы -->
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: #4a5568;">Предметы:</strong>
                        <p style="color: #718096; margin-top: 0.25rem;">
                            <?= e($tutor['subjects']) ?>
                        </p>
                    </div>
                    
                    <!-- Описание -->
                    <div style="margin-bottom: 1rem;">
                        <p style="color: #4a5568; font-size: 0.95rem; line-height: 1.5;">
                            <?= e($tutor['description']) ?>
                        </p>
                    </div>
                    
                    <!-- Контакты -->
                    <div style="margin-bottom: 1rem; font-size: 0.9rem; color: #718096;">
                        <?php if ($tutor['phone']): ?>
                            <div>📞 <?= e($tutor['phone']) ?></div>
                        <?php endif; ?>
                        <div>✉️ <?= e($tutor['email']) ?></div>
                    </div>
                    
                    <!-- Опыт и цена -->
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 1rem;
                        background: #f7fafc;
                        border-radius: 5px;
                        margin-bottom: 1rem;
                    ">
                        <div>
                            <div style="font-size: 0.85rem; color: #718096;">Опыт работы</div>
                            <div style="font-weight: bold; color: #2d3748;">
                                <?= $tutor['experience'] ?> лет
                            </div>
                        </div>
                        
                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; color: #718096;">Стоимость</div>
                            <div style="font-weight: bold; color: #48bb78; font-size: 1.1rem;">
                                <?= formatPrice($tutor['price_per_hour']) ?>/час
                            </div>
                        </div>
                    </div>
                    
                    <!-- Кнопка записи -->
                    <?php if (isLoggedIn()): ?>
                        <?php if (isTutor() && getCurrentUser()['id'] == $tutor['user_id']): ?>
                            <span class="badge badge-info" style="width: 100%; text-align: center; padding: 0.75rem;">
                                Это ваш профиль
                            </span>
                        <?php else: ?>
                            <a 
                                href="/booking?tutor_id=<?= $tutor['id'] ?>" 
                                class="btn btn-primary" 
                                style="width: 100%;"
                            >
                                Записаться на занятие
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a 
                            href="/register" 
                            class="btn btn-success" 
                            style="width: 100%;"
                        >
                            Зарегистрируйтесь для записи
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
