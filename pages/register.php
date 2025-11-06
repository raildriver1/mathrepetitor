<?php
/**
 * Страница регистрации нового пользователя
 */

// Если уже авторизован - редирект в кабинет
if (isLoggedIn()) {
    redirect('/cabinet');
}

// Переменные для хранения ошибок и значений полей
$errors = [];
$name = '';
$email = '';
$phone = '';
$role = 'student'; // По умолчанию ученик

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'student'; // Получаем выбранную роль
    
    // Валидация данных
    if (empty($name)) {
        $errors[] = 'Введите ваше имя';
    }
    
    if (empty($email)) {
        $errors[] = 'Введите email';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Некорректный email';
    }
    
    if (!empty($phone) && !validatePhone($phone)) {
        $errors[] = 'Некорректный телефон';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    // Проверяем не занят ли email
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким email уже существует';
        }
    }
    
    // Если нет ошибок - создаем пользователя
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, name, phone, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$email, $passwordHash, $name, $phone, $role])) {
            $userId = $conn->lastInsertId();
            
            // Если регистрируется репетитор - создаем запись в таблице tutors
            if ($role === 'tutor') {
                $stmt = $conn->prepare("
                    INSERT INTO tutors (user_id, description, experience, price_per_hour, subjects, verified) 
                    VALUES (?, 'Новый репетитор. Заполните информацию о себе в личном кабинете.', 0, 0, 'Математика', 0)
                ");
                $stmt->execute([$userId]);
            }
            
            // Авторизуем пользователя
            $_SESSION['user_id'] = $userId;
            
            if ($role === 'tutor') {
                setFlash('info', 'Регистрация прошла успешно! Ваша анкета будет проверена администратором. После одобрения вы сможете принимать заявки.');
            } else {
                setFlash('success', 'Регистрация прошла успешно! Добро пожаловать!');
            }
            
            redirect('/cabinet');
        } else {
            $errors[] = 'Ошибка при регистрации. Попробуйте позже.';
        }
    }
}

// Подключаем шаблон
$title = 'Регистрация';
include 'templates/header.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <div class="card-header">
        <h1 class="card-title">Регистрация</h1>
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
    
    <form method="POST" action="/register">
        <!-- Имя -->
        <div class="form-group">
            <label for="name">Имя *</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="form-control" 
                value="<?= e($name) ?>" 
                required
                placeholder="Введите ваше имя"
            >
        </div>
        
        <!-- Выбор роли -->
        <div class="form-group">
            <label for="role">Я хочу зарегистрироваться как *</label>
            <select id="role" name="role" class="form-control" required>
                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Ученик (ищу репетитора)</option>
                <option value="tutor" <?= $role === 'tutor' ? 'selected' : '' ?>>Репетитор (хочу преподавать)</option>
            </select>
            <small style="color: #666; display: block; margin-top: 0.5rem;">
                💡 Репетиторам необходимо пройти проверку администратором
            </small>
        </div>
        
        <!-- Email -->
        <div class="form-group">
            <label for="email">Email *</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?= e($email) ?>" 
                required
                placeholder="example@mail.ru"
            >
        </div>
        
        <!-- Телефон -->
        <div class="form-group">
            <label for="phone">Телефон</label>
            <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control" 
                value="<?= e($phone) ?>"
                placeholder="+7 (999) 123-45-67"
            >
        </div>
        
        <!-- Пароль -->
        <div class="form-group">
            <label for="password">Пароль *</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control" 
                required
                placeholder="Минимум 6 символов"
            >
        </div>
        
        <!-- Подтверждение пароля -->
        <div class="form-group">
            <label for="password_confirm">Подтверждение пароля *</label>
            <input 
                type="password" 
                id="password_confirm" 
                name="password_confirm" 
                class="form-control" 
                required
                placeholder="Повторите пароль"
            >
        </div>
        
        <!-- Кнопка отправки -->
        <button type="submit" class="btn btn-success" style="width: 100%;">
            Зарегистрироваться
        </button>
    </form>
    
    <p style="text-align: center; margin-top: 1rem;">
        Уже есть аккаунт? <a href="/login">Войти</a>
    </p>
</div>

<?php include 'templates/footer.php'; ?>
