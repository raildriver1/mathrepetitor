<?php
/**
 * Модуль для работы с базой данных
 * Использует SQLite для простоты развертывания
 */

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Приватный конструктор для реализации Singleton паттерна
     */
    private function __construct() {
        try {
            // Создаем директорию для БД если её нет
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            
            // Подключаемся к SQLite
            $this->connection = new PDO('sqlite:' . DB_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Инициализируем таблицы если их нет
            $this->initTables();
            
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }
    
    /**
     * Получение единственного экземпляра класса (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Получение объекта подключения к БД
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Инициализация таблиц в базе данных
     */
    private function initTables() {
        // Таблица пользователей
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                phone TEXT,
                role TEXT DEFAULT 'student', -- student, tutor, admin
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Таблица репетиторов (дополнительная информация)
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS tutors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                description TEXT,
                experience INTEGER DEFAULT 0, -- опыт в годах
                price_per_hour INTEGER DEFAULT 0, -- цена за час
                subjects TEXT, -- предметы через запятую
                rating REAL DEFAULT 5.0,
                photo TEXT,
                verified INTEGER DEFAULT 0, -- верификация админом (0 - не верифицирован, 1 - верифицирован)
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Таблица бронирований
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                lesson_date DATETIME NOT NULL,
                duration INTEGER DEFAULT 60, -- длительность в минутах
                status TEXT DEFAULT 'pending', -- pending, confirmed, cancelled, completed
                price INTEGER NOT NULL,
                payment_status TEXT DEFAULT 'unpaid', -- unpaid, paid
                payment_id TEXT, -- ID платежа в ЮКассе
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id),
                FOREIGN KEY (tutor_id) REFERENCES tutors(id)
            )
        ");
        
        // Таблица отзывов
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tutor_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                comment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id),
                FOREIGN KEY (student_id) REFERENCES users(id)
            )
        ");
        
        // Таблица уведомлений
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                type TEXT DEFAULT 'info', -- info, success, warning, error
                is_read INTEGER DEFAULT 0,
                link TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Создаем админа по умолчанию если его нет
        $this->createDefaultAdmin();
        
        // Создаем тестовых репетиторов если их нет
        $this->createDefaultTutors();
    }
    
    /**
     * Создание администратора по умолчанию
     */
    private function createDefaultAdmin() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare("
                INSERT INTO users (email, password, name, role) 
                VALUES ('admin@tutor.ru', ?, 'Администратор', 'admin')
            ");
            $stmt->execute([$password]);
        }
    }
    
    /**
     * Создание тестовых репетиторов
     */
    private function createDefaultTutors() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM tutors");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            // Репетитор 1
            $password = password_hash('tutor123', PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare("
                INSERT INTO users (email, password, name, phone, role) 
                VALUES ('ivanov@tutor.ru', ?, 'Иванов Иван Иванович', '+7 (999) 123-45-67', 'tutor')
            ");
            $stmt->execute([$password]);
            $userId = $this->connection->lastInsertId();
            
            $stmt = $this->connection->prepare("
                INSERT INTO tutors (user_id, description, experience, price_per_hour, subjects, rating, verified) 
                VALUES (?, 'Кандидат физико-математических наук. 15 лет опыта преподавания. Готовлю к ЕГЭ и ОГЭ.', 15, 1500, 'Алгебра, Геометрия, Математический анализ', 4.9, 1)
            ");
            $stmt->execute([$userId]);
            
            // Репетитор 2
            $password = password_hash('tutor123', PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare("
                INSERT INTO users (email, password, name, phone, role) 
                VALUES ('petrova@tutor.ru', ?, 'Петрова Мария Сергеевна', '+7 (999) 234-56-78', 'tutor')
            ");
            $stmt->execute([$password]);
            $userId = $this->connection->lastInsertId();
            
            $stmt = $this->connection->prepare("
                INSERT INTO tutors (user_id, description, experience, price_per_hour, subjects, rating, verified) 
                VALUES (?, 'Магистр математики МГУ. Специализируюсь на подготовке к олимпиадам и вузовским экзаменам.', 8, 2000, 'Высшая математика, Теория вероятностей, Статистика', 5.0, 1)
            ");
            $stmt->execute([$userId]);
            
            // Репетитор 3
            $password = password_hash('tutor123', PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare("
                INSERT INTO users (email, password, name, phone, role) 
                VALUES ('sidorov@tutor.ru', ?, 'Сидоров Петр Александрович', '+7 (999) 345-67-89', 'tutor')
            ");
            $stmt->execute([$password]);
            $userId = $this->connection->lastInsertId();
            
            $stmt = $this->connection->prepare("
                INSERT INTO tutors (user_id, description, experience, price_per_hour, subjects, rating, verified) 
                VALUES (?, 'Учитель высшей категории. Работаю с учениками 5-11 классов. Понятно объясняю сложные темы.', 12, 1200, 'Алгебра, Геометрия, Подготовка к ЕГЭ', 4.8, 1)
            ");
            $stmt->execute([$userId]);
        }
    }
}

// Получаем экземпляр базы данных
$db = Database::getInstance();
$conn = $db->getConnection();
