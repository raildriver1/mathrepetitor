<?php
/**
 * Личный кабинет пользователя
 * Показывает профиль и историю бронирований
 */

// Обработка отметки уведомления как прочитанного
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, getCurrentUserId()]);
    redirect('/cabinet');
}

// Обработка удаления уведомления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notificationId = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, getCurrentUserId()]);
    redirect('/cabinet');
}

// Проверка авторизации
if (!isLoggedIn()) {
    setFlash('error', 'Необходимо авторизоваться');
    redirect('/login');
}

// Получаем данные пользователя
$user = getCurrentUser();

// Получаем уведомления пользователя
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([getCurrentUserId()]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем количество непрочитанных уведомлений
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([getCurrentUserId()]);
$unreadCount = $stmt->fetchColumn();

// Получаем бронирования в зависимости от роли
if ($user['role'] === 'tutor') {
    // Для репетитора - получаем его профиль
    $stmt = $conn->prepare("SELECT * FROM tutors WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $tutorProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем записи к этому репетитору
    $bookings = getTutorBookings($tutorProfile['id']);
} else {
    // Для студента - получаем его записи
    $bookings = getUserBookings($user['id']);
}

// Подключаем шаблон
$title = 'Личный кабинет';
include 'templates/header.php';
?>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
    <!-- Боковая панель с информацией о пользователе -->
    <div>
        <div class="card">
            <div style="text-align: center; margin-bottom: 1rem;">
                <div style="
                    width: 100px;
                    height: 100px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    margin: 0 auto 1rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 2.5rem;
                    font-weight: bold;
                ">
                    <?= substr($user['name'], 0, 1) ?>
                </div>
                
                <h3 style="margin-bottom: 0.5rem;"><?= e($user['name']) ?></h3>
                
                <?php if ($user['role'] === 'admin'): ?>
                    <span class="badge badge-danger">Администратор</span>
                <?php elseif ($user['role'] === 'tutor'): ?>
                    <span class="badge badge-info">Репетитор</span>
                <?php else: ?>
                    <span class="badge badge-success">Ученик</span>
                <?php endif; ?>
            </div>
            
            <div style="font-size: 0.9rem; color: #718096;">
                <div style="margin-bottom: 0.5rem;">
                    <strong>Email:</strong><br>
                    <?= e($user['email']) ?>
                </div>
                
                <?php if ($user['phone']): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Телефон:</strong><br>
                        <?= e($user['phone']) ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-bottom: 0.5rem;">
                    <strong>Регистрация:</strong><br>
                    <?= formatDate($user['created_at']) ?>
                </div>
            </div>
        </div>
        
        <!-- Дополнительное меню -->
        <div class="card">
            <h4 style="margin-bottom: 1rem;">Навигация</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <a href="/tutors" class="btn btn-secondary" style="width: 100%;">
                    Репетиторы
                </a>
                
                <?php if (isAdmin()): ?>
                    <a href="/admin" class="btn btn-danger" style="width: 100%;">
                        Админ-панель
                    </a>
                <?php endif; ?>
                
                <a href="/logout" class="btn btn-secondary" style="width: 100%;">
                    Выход
                </a>
            </div>
        </div>
    </div>
    
    <!-- Основной контент -->
    <div>
        <!-- Профиль репетитора (если пользователь - репетитор) -->
        <?php if ($user['role'] === 'tutor' && $tutorProfile): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Мой профиль репетитора</h2>
                </div>
                
                <?php if ($tutorProfile['verified'] == 0): ?>
                    <!-- Предупреждение о неверифицированном статусе -->
                    <div class="flash flash-warning">
                        <strong>⏳ Ваша анкета на проверке</strong>
                        <p style="margin-top: 0.5rem;">
                            Администратор скоро проверит ваш профиль. После одобрения вы сможете принимать заявки от учеников.
                        </p>
                        <p style="margin-top: 0.5rem;">
                            Для связи с администратором: <a href="https://t.me/egorkin_21" target="_blank" style="color: #0088cc;">@egorkin_21</a> в Telegram
                        </p>
                    </div>
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <div style="margin-bottom: 1rem;">
                            <strong>Предметы:</strong>
                            <p style="color: #718096; margin-top: 0.25rem;">
                                <?= e($tutorProfile['subjects']) ?>
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <strong>Описание:</strong>
                            <p style="color: #718096; margin-top: 0.25rem;">
                                <?= e($tutorProfile['description']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <div style="margin-bottom: 1rem;">
                            <strong>Опыт работы:</strong>
                            <p style="color: #718096; margin-top: 0.25rem;">
                                <?= $tutorProfile['experience'] ?> лет
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <strong>Стоимость:</strong>
                            <p style="color: #48bb78; margin-top: 0.25rem; font-size: 1.2rem; font-weight: bold;">
                                <?= formatPrice($tutorProfile['price_per_hour']) ?>/час
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <strong>Рейтинг:</strong>
                            <div style="margin-top: 0.25rem;">
                                <span style="color: #f6ad55; font-size: 1.2rem;">
                                    <?php for ($i = 0; $i < floor($tutorProfile['rating']); $i++): ?>⭐<?php endfor; ?>
                                </span>
                                <span style="color: #718096;">
                                    <?= number_format($tutorProfile['rating'], 1) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <strong>Статус верификации:</strong>
                            <p style="margin-top: 0.25rem;">
                                <?php if ($tutorProfile['verified']): ?>
                                    <span class="badge badge-success">✓ Верифицирован</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">⏳ На проверке</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Центр уведомлений -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    🔔 Уведомления 
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge badge-danger" style="font-size: 0.8rem;"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <?php if (empty($notifications)): ?>
                <p style="text-align: center; padding: 2rem; color: #718096;">
                    У вас пока нет уведомлений
                </p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($notifications as $notification): ?>
                        <div style="
                            padding: 1rem;
                            border-left: 4px solid <?= $notification['type'] === 'success' ? '#48bb78' : ($notification['type'] === 'warning' ? '#ed8936' : '#4299e1') ?>;
                            background: <?= $notification['is_read'] ? '#f7fafc' : '#fff' ?>;
                            border-radius: 5px;
                            <?= !$notification['is_read'] ? 'box-shadow: 0 2px 4px rgba(0,0,0,0.1);' : '' ?>
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <strong style="color: #2d3748; font-size: 1rem;">
                                    <?= e($notification['title']) ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <span style="
                                            display: inline-block;
                                            width: 8px;
                                            height: 8px;
                                            background: #e53e3e;
                                            border-radius: 50%;
                                            margin-left: 0.5rem;
                                        "></span>
                                    <?php endif; ?>
                                </strong>
                                <span style="color: #a0aec0; font-size: 0.85rem;">
                                    <?= formatDate($notification['created_at']) ?>
                                </span>
                            </div>
                            
                            <p style="color: #4a5568; margin-bottom: 0.75rem;">
                                <?= nl2br(e($notification['message'])) ?>
                            </p>
                            
                            <?php if ($notification['link']): ?>
                                <a href="<?= e($notification['link']) ?>" class="btn btn-primary" style="padding: 5px 15px; font-size: 0.85rem; margin-right: 0.5rem;">
                                    Перейти
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <button type="submit" name="mark_read" class="btn btn-secondary" style="padding: 5px 15px; font-size: 0.85rem;">
                                        Отметить прочитанным
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <button type="submit" name="delete_notification" class="btn btn-danger" style="padding: 5px 15px; font-size: 0.85rem;">
                                    Удалить
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Записи на занятия -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <?= $user['role'] === 'tutor' ? 'Записи учеников' : 'Мои записи' ?>
                </h2>
            </div>
            
            <?php if (empty($bookings)): ?>
                <p style="text-align: center; padding: 2rem; color: #718096;">
                    <?= $user['role'] === 'tutor' ? 'Пока нет записей от учеников' : 'У вас пока нет записей' ?>
                </p>
                
                <?php if ($user['role'] !== 'tutor'): ?>
                    <div style="text-align: center;">
                        <a href="/tutors" class="btn btn-primary">
                            Выбрать репетитора
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($user['role'] === 'tutor'): ?>
                                <th>Ученик</th>
                                <th>Телефон</th>
                            <?php else: ?>
                                <th>Репетитор</th>
                            <?php endif; ?>
                            <th>Дата и время</th>
                            <th>Длительность</th>
                            <th>Стоимость</th>
                            <th>Статус</th>
                            <th>Оплата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <?php if ($user['role'] === 'tutor'): ?>
                                    <td><?= e($booking['student_name']) ?></td>
                                    <td><?= e($booking['student_phone'] ?? '-') ?></td>
                                <?php else: ?>
                                    <td><?= e($booking['tutor_name']) ?></td>
                                <?php endif; ?>
                                
                                <td><?= formatDate($booking['lesson_date']) ?></td>
                                <td><?= $booking['duration'] ?> мин</td>
                                <td><?= formatPrice($booking['price']) ?></td>
                                
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'pending' => '<span class="badge badge-warning">Ожидает</span>',
                                        'confirmed' => '<span class="badge badge-success">Подтверждено</span>',
                                        'cancelled' => '<span class="badge badge-danger">Отменено</span>',
                                        'completed' => '<span class="badge badge-info">Завершено</span>'
                                    ];
                                    echo $statusLabels[$booking['status']] ?? $booking['status'];
                                    ?>
                                </td>
                                
                                <td>
                                    <?php if ($booking['payment_status'] === 'paid'): ?>
                                        <span class="badge badge-success">Оплачено</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Не оплачено</span>
                                        <?php if ($user['role'] !== 'tutor' && $booking['status'] === 'pending'): ?>
                                            <br>
                                            <a 
                                                href="/payment?booking_id=<?= $booking['id'] ?>" 
                                                class="btn btn-success" 
                                                style="margin-top: 0.5rem; padding: 5px 10px; font-size: 0.85rem;"
                                            >
                                                Оплатить
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
