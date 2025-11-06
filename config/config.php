<?php
/**
 * Файл конфигурации проекта
 * Содержит основные настройки приложения
 */

// Настройки сайта
define('SITE_NAME', 'МатРепетитор');
define('SITE_URL', 'http://localhost:8000');

// Настройки базы данных (SQLite)
define('DB_PATH', __DIR__ . '/../database/tutors.db');

// Настройки безопасности
define('PASSWORD_SALT', 'tutor_site_salt_2024'); // Соль для хеширования паролей

// Настройки ЮКассы (заполнить позже)
define('YUKASSA_SHOP_ID', ''); // ID магазина в ЮКассе
define('YUKASSA_SECRET_KEY', ''); // Секретный ключ ЮКассы

// Часовой пояс
date_default_timezone_set('Europe/Moscow');

// Режим разработки (показывать ошибки)
ini_set('display_errors', 1);
error_reporting(E_ALL);
