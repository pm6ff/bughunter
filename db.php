<?php
// Настройки подключения
$dbFile = __DIR__ . '/bughunter.sqlite';

try {
    // Создаем PDO-соединение с SQLite
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1. Таблица пользователей
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Таблица проектов
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_at TEXT,
        owner_id INTEGER,
        FOREIGN KEY (owner_id) REFERENCES users(id)
    )");

    // 3. Таблица багов (для проверки запрета удаления)
    $pdo->exec("CREATE TABLE IF NOT EXISTS bugs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER,
        title TEXT,
        FOREIGN KEY (project_id) REFERENCES projects(id)
    )");

    // Миграции (если таблицы уже были созданы ранее без этих полей)
    try { $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE projects ADD COLUMN owner_id INTEGER REFERENCES users(id)"); } catch (PDOException $e) {}

} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Запускаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---

// Проверка авторизации
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Проверка на администратора
function isAdmin($pdo) {
    if (!isset($_SESSION['user_id'])) return false;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user && $user['role'] === 'admin';
}

// Генерация CSRF-токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF-токена
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("Ошибка безопасности: неверный CSRF-токен.");
    }
}
?>