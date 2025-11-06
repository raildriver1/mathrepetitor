<?php
/**
 * Главный файл приложения - точка входа
 * Здесь происходит инициализация и роутинг запросов
 */

// Запуск сессии для работы с авторизацией
session_start();

// Подключение конфигурации
require_once 'config/config.php';

// Подключение базы данных
require_once 'config/database.php';

// Подключение вспомогательных функций
require_once 'includes/functions.php';

// Получение текущего маршрута из URL
$request = $_SERVER['REQUEST_URI'];
$request = strtok($request, '?'); // Убираем GET параметры

// Роутинг - определяем какую страницу показать
switch ($request) {
    case '/':
    case '/index.php':
        // Главная страница
        require 'pages/home.php';
        break;
    
    case '/register':
        // Страница регистрации
        require 'pages/register.php';
        break;
    
    case '/login':
        // Страница авторизации
        require 'pages/login.php';
        break;
    
    case '/logout':
        // Выход из системы
        require 'modules/auth/logout.php';
        break;
    
    case '/cabinet':
        // Личный кабинет
        require 'pages/cabinet.php';
        break;
    
    case '/tutors':
        // Список репетиторов
        require 'pages/tutors.php';
        break;
    
    case '/booking':
        // Бронирование занятия
        require 'pages/booking.php';
        break;
    
    case '/payment':
        // Оплата через ЮКассу
        require 'pages/payment.php';
        break;
    
    case '/admin':
        // Админ-панель
        require 'pages/admin.php';
        break;
    
    default:
        // Страница 404 - не найдено
        http_response_code(404);
        require 'pages/404.php';
        break;
}
