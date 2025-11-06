<?php
/**
 * Страница авторизации пользователя
 */

// Если уже авторизован - редирект в кабинет
if (isLoggedIn()) {
    redirect('/cabinet');
}

// Переменные для хранения ошибок
$errors = [];
$email = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация
    if (empty($email)) {
        $errors[] = 'Введите email';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    }
    
    // Если нет ошибок - проверяем данные
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Проверяем пароль
        if ($user && password_verify($password, $user['password'])) {
            // Авторизуем пользователя
            $_SESSION['user_id'] = $user['id'];
            
            setFlash('success', 'Добро пожаловать, ' . $user['name'] . '!');
            redirect('/cabinet');
        } else {
            $errors[] = 'Неверный email или пароль';
        }
    }
}

// Подключаем шаблон
$title = 'Вход';
include 'templates/header.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <div class="card-header">
        <h1 class="card-title">Вход в систему</h1>
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
    
    <form method="POST" action="/login">
        <!-- Email -->
        <div class="form-group">
            <label for="email">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?= e($email) ?>" 
                required
                placeholder="Введите ваш email"
            >
        </div>
        
        <!-- Пароль -->
        <div class="form-group">
            <label for="password">Пароль</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control" 
                required
                placeholder="Введите пароль"
            >
        </div>
        
        <!-- Кнопка отправки -->
        <button type="submit" class="btn btn-primary" style="width: 100%;">
            Войти
        </button>
    </form>
    
    <p style="text-align: center; margin-top: 1rem;">
        Нет аккаунта? <a href="/register">Зарегистрироваться</a>
    </p>
    
    <div style="margin-top: 2rem; padding: 1rem; background: #f7fafc; border-radius: 5px;">
        <p style="font-weight: bold; margin-bottom: 0.5rem;">Тестовые аккаунты:</p>
        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
            <strong>Админ:</strong> admin@tutor.ru / admin123
        </p>
        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
            <strong>Репетитор:</strong> ivanov@tutor.ru / tutor123
        </p>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
