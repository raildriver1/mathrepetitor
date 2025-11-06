<?php
/**
 * Страница бронирования занятия с репетитором
 */

// Проверка авторизации
if (!isLoggedIn()) {
    setFlash('error', 'Необходимо авторизоваться');
    redirect('/login');
}

// Проверяем что пользователь не репетитор
$currentUser = getCurrentUser();
if ($currentUser['role'] === 'tutor') {
    setFlash('error', 'Репетиторы не могут записываться к другим репетиторам');
    redirect('/cabinet');
}

// Получаем ID репетитора
$tutorId = $_GET['tutor_id'] ?? null;

if (!$tutorId) {
    setFlash('error', 'Репетитор не указан');
    redirect('/tutors');
}

// Получаем информацию о репетиторе
$tutor = getTutorById($tutorId);

if (!$tutor) {
    setFlash('error', 'Репетитор не найден');
    redirect('/tutors');
}

// Переменные для формы
$errors = [];
$lessonDate = '';
$lessonTime = '';
$duration = 60;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lessonDate = $_POST['lesson_date'] ?? '';
    $lessonTime = $_POST['lesson_time'] ?? '';
    $duration = (int)($_POST['duration'] ?? 60);
    
    // Валидация
    if (empty($lessonDate)) {
        $errors[] = 'Выберите дату занятия';
    }
    
    if (empty($lessonTime)) {
        $errors[] = 'Выберите время занятия';
    }
    
    if ($duration < 30 || $duration > 180) {
        $errors[] = 'Длительность должна быть от 30 до 180 минут';
    }
    
    // Проверяем что дата не в прошлом
    $lessonDateTime = $lessonDate . ' ' . $lessonTime;
    if (strtotime($lessonDateTime) < time()) {
        $errors[] = 'Дата и время не могут быть в прошлом';
    }
    
    // Если нет ошибок - создаем бронирование
    if (empty($errors)) {
        // Рассчитываем стоимость
        $price = ($tutor['price_per_hour'] / 60) * $duration;
        
        $stmt = $conn->prepare("
            INSERT INTO bookings (student_id, tutor_id, lesson_date, duration, price, status, payment_status) 
            VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid')
        ");
        
        if ($stmt->execute([getCurrentUserId(), $tutorId, $lessonDateTime, $duration, $price])) {
            $bookingId = $conn->lastInsertId();
            
            // Получаем ID пользователя репетитора для отправки уведомления
            $stmtTutor = $conn->prepare("SELECT user_id FROM tutors WHERE id = ?");
            $stmtTutor->execute([$tutorId]);
            $tutorUserId = $stmtTutor->fetchColumn();
            
            // Создаем уведомление для репетитора
            if ($tutorUserId) {
                $notifTitle = "🔔 Новая заявка на занятие!";
                $notifMessage = "Ученик {$currentUser['name']} записался к вам на занятие.\n";
                $notifMessage .= "Дата: " . formatDate($lessonDateTime) . "\n";
                $notifMessage .= "Длительность: {$duration} мин\n";
                $notifMessage .= "Стоимость: " . formatPrice($price);
                
                $stmtNotif = $conn->prepare("
                    INSERT INTO notifications (user_id, title, message, type, link) 
                    VALUES (?, ?, ?, 'success', '/cabinet')
                ");
                $stmtNotif->execute([$tutorUserId, $notifTitle, $notifMessage]);
            }
            
            setFlash('success', 'Запись создана успешно! Теперь необходимо оплатить занятие.');
            redirect('/payment?booking_id=' . $bookingId);
        } else {
            $errors[] = 'Ошибка при создании записи';
        }
    }
}

// Подключаем шаблон
$title = 'Бронирование';
include 'templates/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem;">
    <!-- Форма бронирования -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Запись на занятие</h2>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="flash flash-error">
                <ul style="list-style: none;">
                    <?php foreach ($errors as $error): ?>
                        <li>• <?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Дата занятия -->
            <div class="form-group">
                <label for="lesson_date">Дата занятия *</label>
                <input 
                    type="date" 
                    id="lesson_date" 
                    name="lesson_date" 
                    class="form-control" 
                    value="<?= e($lessonDate) ?>"
                    min="<?= date('Y-m-d') ?>"
                    required
                >
            </div>
            
            <!-- Время занятия -->
            <div class="form-group">
                <label for="lesson_time">Время занятия *</label>
                <input 
                    type="time" 
                    id="lesson_time" 
                    name="lesson_time" 
                    class="form-control" 
                    value="<?= e($lessonTime) ?>"
                    required
                >
            </div>
            
            <!-- Длительность -->
            <div class="form-group">
                <label for="duration">Длительность (минут) *</label>
                <select id="duration" name="duration" class="form-control" required>
                    <option value="30" <?= $duration == 30 ? 'selected' : '' ?>>30 минут</option>
                    <option value="60" <?= $duration == 60 ? 'selected' : '' ?>>60 минут (1 час)</option>
                    <option value="90" <?= $duration == 90 ? 'selected' : '' ?>>90 минут (1.5 часа)</option>
                    <option value="120" <?= $duration == 120 ? 'selected' : '' ?>>120 минут (2 часа)</option>
                </select>
            </div>
            
            <!-- Расчет стоимости -->
            <div style="
                padding: 1rem;
                background: #f7fafc;
                border-radius: 5px;
                margin-bottom: 1.5rem;
            ">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Стоимость за час:</span>
                    <strong><?= formatPrice($tutor['price_per_hour']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 1px solid #e2e8f0;">
                    <span>Итого к оплате:</span>
                    <strong id="total-price" style="color: #48bb78; font-size: 1.2rem;">
                        <?= formatPrice(($tutor['price_per_hour'] / 60) * $duration) ?>
                    </strong>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success" style="width: 100%;">
                Забронировать и перейти к оплате
            </button>
        </form>
    </div>
    
    <!-- Информация о репетиторе -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Информация о репетиторе</h3>
            </div>
            
            <div style="text-align: center; margin-bottom: 1rem;">
                <div style="
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    margin: 0 auto 1rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 2rem;
                    font-weight: bold;
                ">
                    <?= substr($tutor['name'], 0, 1) ?>
                </div>
                
                <h4 style="margin-bottom: 0.5rem;"><?= e($tutor['name']) ?></h4>
                
                <div style="margin-bottom: 1rem;">
                    <span style="color: #f6ad55; font-size: 1.2rem;">
                        <?php for ($i = 0; $i < floor($tutor['rating']); $i++): ?>⭐<?php endfor; ?>
                    </span>
                    <span style="color: #718096;">
                        <?= number_format($tutor['rating'], 1) ?>
                    </span>
                </div>
            </div>
            
            <div style="font-size: 0.9rem;">
                <div style="margin-bottom: 1rem;">
                    <strong>Предметы:</strong>
                    <p style="color: #718096; margin-top: 0.25rem;">
                        <?= e($tutor['subjects']) ?>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>Опыт:</strong>
                    <p style="color: #718096; margin-top: 0.25rem;">
                        <?= $tutor['experience'] ?> лет
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>О репетиторе:</strong>
                    <p style="color: #718096; margin-top: 0.25rem; line-height: 1.5;">
                        <?= e($tutor['description']) ?>
                    </p>
                </div>
                
                <?php if ($tutor['phone']): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong>Телефон:</strong>
                        <p style="color: #718096; margin-top: 0.25rem;">
                            <?= e($tutor['phone']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Автоматический расчет стоимости при изменении длительности
document.getElementById('duration').addEventListener('change', function() {
    const duration = parseInt(this.value);
    const pricePerHour = <?= $tutor['price_per_hour'] ?>;
    const totalPrice = Math.round((pricePerHour / 60) * duration);
    
    document.getElementById('total-price').textContent = 
        totalPrice.toLocaleString('ru-RU') + ' ₽';
});
</script>

<?php include 'templates/footer.php'; ?>
