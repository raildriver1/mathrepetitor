<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'МатРепетитор' ?> - Репетиторы по математике</title>
    <style>
        /* Общие стили */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f7f9;
        }
        
        /* Хедер */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .logo span {
            color: #ffd700;
        }
        
        .nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }
        
        .nav a:hover {
            opacity: 0.8;
        }
        
        /* Кнопки */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #48bb78;
            color: white;
        }
        
        .btn-success:hover {
            background: #38a169;
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        /* Основной контент */
        .main-content {
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }
        
        /* Flash сообщения */
        .flash {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .flash-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .flash-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .flash-info {
            background: #bee3f8;
            color: #2c5282;
            border: 1px solid #90cdf4;
        }
        
        /* Карточки */
        .card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            color: #2d3748;
        }
        
        /* Формы */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Сетка */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        /* Таблицы */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }
        
        .table tr:hover {
            background: #f7fafc;
        }
        
        /* Бейджи */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-warning {
            background: #feebc8;
            color: #7c2d12;
        }
        
        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .badge-info {
            background: #bee3f8;
            color: #2c5282;
        }
    </style>
    
    <!-- JavaScript для Push-уведомлений -->
    <script>
        // Функция для запроса разрешения на уведомления
        function requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
        
        // Функция для показа browser notification
        function showBrowserNotification(title, body, link) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: body,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: 'tutor-notification',
                    requireInteraction: false
                });
                
                notification.onclick = function() {
                    window.focus();
                    if (link) {
                        window.location.href = link;
                    }
                    notification.close();
                };
                
                // Автоматически закрыть через 10 секунд
                setTimeout(() => notification.close(), 10000);
            }
        }
        
        // Проверка новых уведомлений каждые 30 секунд (только для авторизованных)
        <?php if (isLoggedIn()): ?>
        let lastNotificationCheck = Date.now();
        
        function checkNewNotifications() {
            fetch('/api/check_notifications.php?last_check=' + lastNotificationCheck)
                .then(response => response.json())
                .then(data => {
                    if (data.new_notifications && data.new_notifications.length > 0) {
                        data.new_notifications.forEach(notification => {
                            showBrowserNotification(
                                notification.title,
                                notification.message,
                                notification.link
                            );
                        });
                        lastNotificationCheck = Date.now();
                    }
                })
                .catch(error => console.log('Ошибка проверки уведомлений:', error));
        }
        
        // Запускаем проверку каждые 30 секунд
        setInterval(checkNewNotifications, 30000);
        
        // Запрашиваем разрешение при загрузке страницы
        window.addEventListener('load', function() {
            setTimeout(requestNotificationPermission, 2000);
        });
        <?php endif; ?>
    </script>
</head>
<body>
    <!-- Шапка сайта -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Логотип -->
                <a href="/" class="logo">
                    Мат<span>Репетитор</span>
                </a>
                
                <!-- Навигация -->
                <nav class="nav">
                    <a href="/">Главная</a>
                    <a href="/tutors">Репетиторы</a>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- Меню для авторизованных -->
                        <?php
                        // Получаем количество непрочитанных уведомлений
                        $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                        $stmtUnread->execute([getCurrentUserId()]);
                        $unreadNotifications = $stmtUnread->fetchColumn();
                        ?>
                        
                        <a href="/cabinet" style="position: relative;">
                            🔔 Уведомления
                            <?php if ($unreadNotifications > 0): ?>
                                <span style="
                                    position: absolute;
                                    top: -5px;
                                    right: -10px;
                                    background: #e53e3e;
                                    color: white;
                                    border-radius: 10px;
                                    padding: 2px 6px;
                                    font-size: 0.75rem;
                                    font-weight: bold;
                                "><?= $unreadNotifications ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="/cabinet">Личный кабинет</a>
                        
                        <?php if (isAdmin()): ?>
                            <a href="/admin">Админ-панель</a>
                        <?php endif; ?>
                        
                        <a href="/logout" class="btn btn-secondary">Выход</a>
                    <?php else: ?>
                        <!-- Меню для гостей -->
                        <a href="/login" class="btn btn-primary">Вход</a>
                        <a href="/register" class="btn btn-success">Регистрация</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- Основной контент -->
    <div class="main-content">
        <div class="container">
            <?php 
            // Показываем flash-сообщение если есть
            $flash = getFlash();
            if ($flash): 
            ?>
                <div class="flash flash-<?= $flash['type'] ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
