<?php
/**
 * API endpoint для проверки новых уведомлений
 * Возвращает непрочитанные уведомления созданные после определенного времени
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Получаем время последней проверки
$lastCheck = isset($_GET['last_check']) ? (int)$_GET['last_check'] : 0;
$lastCheckDate = date('Y-m-d H:i:s', $lastCheck / 1000); // JavaScript timestamp в миллисекундах

// Получаем новые непрочитанные уведомления
$stmt = $conn->prepare("
    SELECT id, title, message, type, link, created_at
    FROM notifications 
    WHERE user_id = ? 
    AND is_read = 0 
    AND created_at > ?
    ORDER BY created_at DESC
");
$stmt->execute([getCurrentUserId(), $lastCheckDate]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Возвращаем результат
echo json_encode([
    'success' => true,
    'new_notifications' => $notifications,
    'count' => count($notifications)
]);
