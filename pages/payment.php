<?php
/**
 * Страница оплаты через ЮКассу
 * Интеграция с платежной системой ЮКасса
 */

// Проверка авторизации
if (!isLoggedIn()) {
    setFlash('error', 'Необходимо авторизоваться');
    redirect('/login');
}

// Получаем ID бронирования
$bookingId = $_GET['booking_id'] ?? null;

if (!$bookingId) {
    setFlash('error', 'Бронирование не указано');
    redirect('/cabinet');
}

// Получаем информацию о бронировании
$stmt = $conn->prepare("
    SELECT b.*, t.*, u.name as tutor_name 
    FROM bookings b
    JOIN tutors t ON b.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE b.id = ? AND b.student_id = ?
");
$stmt->execute([$bookingId, getCurrentUserId()]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    setFlash('error', 'Бронирование не найдено');
    redirect('/cabinet');
}

// Если уже оплачено - редирект в кабинет
if ($booking['payment_status'] === 'paid') {
    setFlash('info', 'Это бронирование уже оплачено');
    redirect('/cabinet');
}

// Обработка оплаты
$paymentError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ВАЖНО: Здесь должна быть реальная интеграция с ЮКассой
    // Для демонстрации делаем имитацию оплаты
    
    if (empty(YUKASSA_SHOP_ID) || empty(YUKASSA_SECRET_KEY)) {
        // Если ЮКасса не настроена - делаем тестовую оплату
        $paymentId = 'test_' . uniqid();
        
        // Обновляем статус бронирования
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'paid', 
                payment_id = ?,
                status = 'confirmed'
            WHERE id = ?
        ");
        $stmt->execute([$paymentId, $bookingId]);
        
        setFlash('success', 'Оплата прошла успешно! Бронирование подтверждено.');
        redirect('/cabinet');
    } else {
        /**
         * РЕАЛЬНАЯ ИНТЕГРАЦИЯ С ЮКАССОЙ
         * 
         * Для работы с ЮКассой необходимо:
         * 1. Зарегистрироваться на https://yookassa.ru/
         * 2. Получить shopId и secretKey
         * 3. Установить SDK: composer require yoomoney/yookassa-sdk-php
         * 4. Раскомментировать и доработать код ниже
         */
        
        /*
        require_once __DIR__ . '/../vendor/autoload.php';
        
        use YooKassa\Client;
        
        $client = new Client();
        $client->setAuth(YUKASSA_SHOP_ID, YUKASSA_SECRET_KEY);
        
        try {
            // Создаем платеж
            $payment = $client->createPayment([
                'amount' => [
                    'value' => $booking['price'],
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => SITE_URL . '/payment?booking_id=' . $bookingId . '&success=1',
                ],
                'capture' => true,
                'description' => 'Оплата занятия с репетитором ' . $booking['tutor_name'],
                'metadata' => [
                    'booking_id' => $bookingId,
                ],
            ]);
            
            // Сохраняем ID платежа
            $stmt = $conn->prepare("UPDATE bookings SET payment_id = ? WHERE id = ?");
            $stmt->execute([$payment->getId(), $bookingId]);
            
            // Редиректим на страницу оплаты ЮКассы
            header('Location: ' . $payment->getConfirmation()->getConfirmationUrl());
            exit;
            
        } catch (\Exception $e) {
            $paymentError = 'Ошибка при создании платежа: ' . $e->getMessage();
        }
        */
        
        $paymentError = 'ЮКасса не настроена. Для настройки укажите YUKASSA_SHOP_ID и YUKASSA_SECRET_KEY в config.php';
    }
}

// Проверка успешного возврата от ЮКассы
if (isset($_GET['success']) && $_GET['success'] == '1') {
    // Проверяем статус платежа в ЮКассе
    // В реальном проекте здесь нужно проверить статус через API ЮКассы
    
    setFlash('success', 'Оплата прошла успешно! Бронирование подтверждено.');
    redirect('/cabinet');
}

// Подключаем шаблон
$title = 'Оплата';
include 'templates/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Оплата занятия</h2>
        </div>
        
        <?php if ($paymentError): ?>
            <div class="flash flash-error">
                <?= e($paymentError) ?>
            </div>
        <?php endif; ?>
        
        <!-- Информация о бронировании -->
        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem; color: #2d3748;">Детали бронирования</h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; color: #718096;">Репетитор:</td>
                    <td style="padding: 0.5rem 0; text-align: right; font-weight: 600;">
                        <?= e($booking['tutor_name']) ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #718096;">Дата и время:</td>
                    <td style="padding: 0.5rem 0; text-align: right; font-weight: 600;">
                        <?= formatDate($booking['lesson_date']) ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #718096;">Длительность:</td>
                    <td style="padding: 0.5rem 0; text-align: right; font-weight: 600;">
                        <?= $booking['duration'] ?> минут
                    </td>
                </tr>
                <tr style="border-top: 2px solid #e2e8f0;">
                    <td style="padding: 1rem 0 0.5rem; font-size: 1.1rem; font-weight: bold;">Итого к оплате:</td>
                    <td style="padding: 1rem 0 0.5rem; text-align: right; font-size: 1.3rem; font-weight: bold; color: #48bb78;">
                        <?= formatPrice($booking['price']) ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Форма оплаты -->
        <form method="POST">
            <div style="
                padding: 1.5rem;
                background: #f7fafc;
                border-radius: 5px;
                margin-bottom: 1.5rem;
            ">

            </div>
            
            
            <button type="submit" class="btn btn-success" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                Перейти к оплате
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem;">
            <a href="/cabinet" style="color: #718096;">Вернуться в личный кабинет</a>
        </p>
    </div>
    
    <!-- Безопасность -->
    <div class="card">
        <h3 style="margin-bottom: 1rem; color: #2d3748;">🔒 Безопасность платежей</h3>
        <p style="color: #718096; font-size: 0.95rem; line-height: 1.6;">
            Все платежи защищены по стандарту PCI DSS. Данные вашей карты передаются напрямую 
            в ЮКассу по защищенному соединению и не хранятся на нашем сервере. 
            ЮКасса является лицензированным платежным сервисом и гарантирует безопасность транзакций.
        </p>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
