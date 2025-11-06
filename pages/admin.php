<?php
/**
 * Админ-панель
 * Управление пользователями, репетиторами и бронированиями
 */

// Проверка прав администратора
if (!isAdmin()) {
    setFlash('error', 'Доступ запрещен');
    redirect('/');
}

// Обработка верификации репетитора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_tutor'])) {
    $tutorId = (int)$_POST['tutor_id'];
    $verified = (int)$_POST['verified'];
    
    $stmt = $conn->prepare("UPDATE tutors SET verified = ? WHERE id = ?");
    if ($stmt->execute([$verified, $tutorId])) {
        setFlash('success', $verified ? 'Репетитор верифицирован!' : 'Верификация отменена');
    } else {
        setFlash('error', 'Ошибка при обновлении статуса');
    }
    redirect('/admin');
}

// Получаем статистику
$stats = [];

// Общее количество пользователей
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$stats['users_total'] = $stmt->fetchColumn();

// Количество репетиторов
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'tutor'");
$stats['tutors_count'] = $stmt->fetchColumn();

// Количество студентов
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$stats['students_count'] = $stmt->fetchColumn();

// Общее количество бронирований
$stmt = $conn->query("SELECT COUNT(*) FROM bookings");
$stats['bookings_total'] = $stmt->fetchColumn();

// Бронирования в ожидании
$stmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$stats['bookings_pending'] = $stmt->fetchColumn();

// Оплаченные бронирования
$stmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE payment_status = 'paid'");
$stats['bookings_paid'] = $stmt->fetchColumn();

// Общая сумма оплат
$stmt = $conn->query("SELECT SUM(price) FROM bookings WHERE payment_status = 'paid'");
$stats['total_revenue'] = $stmt->fetchColumn() ?? 0;

// Получаем последние бронирования
$stmt = $conn->query("
    SELECT b.*, 
           u1.name as student_name, 
           u2.name as tutor_name
    FROM bookings b
    JOIN users u1 ON b.student_id = u1.id
    JOIN tutors t ON b.tutor_id = t.id
    JOIN users u2 ON t.user_id = u2.id
    ORDER BY b.created_at DESC
    LIMIT 10
");
$recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем всех пользователей
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем всех репетиторов с их данными
$stmt = $conn->query("
    SELECT t.*, u.name, u.email, u.phone, u.created_at
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.verified ASC, u.created_at DESC
");
$tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Подключаем шаблон
$title = 'Админ-панель';
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Панель администратора</h1>
    </div>
    
    <!-- Статистика -->
    <h2 style="margin-bottom: 1rem; color: #2d3748;">📊 Статистика</h2>
    
    <div class="grid">
        <!-- Пользователи -->
        <div style="
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        ">
            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">
                Всего пользователей
            </div>
            <div style="font-size: 2.5rem; font-weight: bold;">
                <?= $stats['users_total'] ?>
            </div>
            <div style="font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.8;">
                Репетиторов: <?= $stats['tutors_count'] ?> | Студентов: <?= $stats['students_count'] ?>
            </div>
        </div>
        
        <!-- Бронирования -->
        <div style="
            padding: 1.5rem;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border-radius: 10px;
        ">
            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">
                Всего бронирований
            </div>
            <div style="font-size: 2.5rem; font-weight: bold;">
                <?= $stats['bookings_total'] ?>
            </div>
            <div style="font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.8;">
                Ожидают: <?= $stats['bookings_pending'] ?> | Оплачено: <?= $stats['bookings_paid'] ?>
            </div>
        </div>
        
        <!-- Выручка -->
        <div style="
            padding: 1.5rem;
            background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
            color: white;
            border-radius: 10px;
        ">
            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">
                Общая выручка
            </div>
            <div style="font-size: 2rem; font-weight: bold;">
                <?= formatPrice($stats['total_revenue']) ?>
            </div>
            <div style="font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.8;">
                Из оплаченных бронирований
            </div>
        </div>
    </div>
</div>

<!-- Последние бронирования -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Последние бронирования</h2>
    </div>
    
    <?php if (empty($recentBookings)): ?>
        <p style="text-align: center; padding: 2rem; color: #718096;">
            Бронирований пока нет
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Студент</th>
                    <th>Репетитор</th>
                    <th>Дата занятия</th>
                    <th>Стоимость</th>
                    <th>Статус</th>
                    <th>Оплата</th>
                    <th>Создано</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBookings as $booking): ?>
                    <tr>
                        <td><?= $booking['id'] ?></td>
                        <td><?= e($booking['student_name']) ?></td>
                        <td><?= e($booking['tutor_name']) ?></td>
                        <td><?= formatDate($booking['lesson_date']) ?></td>
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
                            <?php endif; ?>
                        </td>
                        <td><?= formatDate($booking['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Управление репетиторами -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Управление репетиторами</h2>
    </div>
    
    <?php if (empty($tutors)): ?>
        <p style="text-align: center; padding: 2rem; color: #718096;">
            Репетиторов пока нет
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Предметы</th>
                    <th>Опыт</th>
                    <th>Цена/час</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tutors as $tutor): ?>
                    <tr style="<?= $tutor['verified'] ? '' : 'background-color: #fff9e6;' ?>">
                        <td><?= $tutor['id'] ?></td>
                        <td><?= e($tutor['name']) ?></td>
                        <td><?= e($tutor['email']) ?></td>
                        <td><?= e($tutor['phone'] ?? '-') ?></td>
                        <td><?= e($tutor['subjects']) ?></td>
                        <td><?= $tutor['experience'] ?> лет</td>
                        <td><?= formatPrice($tutor['price_per_hour']) ?></td>
                        <td>
                            <?php if ($tutor['verified']): ?>
                                <span class="badge badge-success">✓ Верифицирован</span>
                            <?php else: ?>
                                <span class="badge badge-warning">⏳ На проверке</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="tutor_id" value="<?= $tutor['id'] ?>">
                                <input type="hidden" name="verified" value="<?= $tutor['verified'] ? 0 : 1 ?>">
                                <button 
                                    type="submit" 
                                    name="verify_tutor" 
                                    class="btn <?= $tutor['verified'] ? 'btn-danger' : 'btn-success' ?>"
                                    style="padding: 5px 15px; font-size: 0.85rem;"
                                >
                                    <?= $tutor['verified'] ? 'Отменить' : 'Верифицировать' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Пользователи -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Все пользователи</h2>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['phone'] ?? '-') ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge badge-danger">Админ</span>
                        <?php elseif ($user['role'] === 'tutor'): ?>
                            <span class="badge badge-info">Репетитор</span>
                        <?php else: ?>
                            <span class="badge badge-success">Студент</span>
                        <?php endif; ?>
                    </td>
                    <td><?= formatDate($user['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>
