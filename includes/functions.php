<?php
/**
 * Вспомогательные функции для всего проекта
 */

/**
 * Проверка авторизации пользователя
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Получение ID текущего пользователя
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Получение данных текущего пользователя
 */
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Проверка прав администратора
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Проверка прав репетитора
 */
function isTutor() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'tutor';
}

/**
 * Редирект на другую страницу
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Безопасный вывод HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Форматирование цены
 */
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ₽';
}

/**
 * Форматирование даты
 */
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

/**
 * Установка flash-сообщения
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, info
        'message' => $message
    ];
}

/**
 * Получение и удаление flash-сообщения
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Подключение шаблона с передачей данных
 */
function view($template, $data = []) {
    extract($data);
    require __DIR__ . '/../templates/' . $template . '.php';
}

/**
 * Получение всех репетиторов
 * Показывает только верифицированных репетиторов
 */
function getAllTutors() {
    global $conn;
    
    $stmt = $conn->query("
        SELECT t.*, u.name, u.email, u.phone 
        FROM tutors t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.verified = 1
        ORDER BY t.rating DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получение репетитора по ID
 */
function getTutorById($id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT t.*, u.name, u.email, u.phone 
        FROM tutors t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Получение бронирований пользователя
 */
function getUserBookings($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT b.*, u.name as tutor_name 
        FROM bookings b
        JOIN tutors t ON b.tutor_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE b.student_id = ?
        ORDER BY b.lesson_date DESC
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получение бронирований репетитора
 */
function getTutorBookings($tutorId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT b.*, u.name as student_name, u.phone as student_phone
        FROM bookings b
        JOIN users u ON b.student_id = u.id
        WHERE b.tutor_id = ?
        ORDER BY b.lesson_date DESC
    ");
    $stmt->execute([$tutorId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Валидация email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Валидация телефона
 */
function validatePhone($phone) {
    return preg_match('/^\+?[0-9\s\-\(\)]{10,}$/', $phone);
}
