<?php
// Подключение к базе данных SQLite
$dbFile = __DIR__ . '/bughunter.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_at TEXT,
        owner_id INTEGER,
        FOREIGN KEY (owner_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bugs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        priority TEXT NOT NULL DEFAULT 'Medium',
        status TEXT NOT NULL DEFAULT 'Новая',
        created_by INTEGER,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Добавление новых полей в старую базу без удаления существующих данных.
    $addColumn = function ($table, $column, $definition) use ($pdo) {
        $columns = $pdo->query("PRAGMA table_info(" . $table . ")")->fetchAll();
        foreach ($columns as $item) {
            if ($item['name'] === $column) {
                return;
            }
        }
        $pdo->exec("ALTER TABLE " . $table . " ADD COLUMN " . $column . " " . $definition);
    };

    $addColumn('users', 'role', "TEXT NOT NULL DEFAULT 'user'");
    $addColumn('projects', 'owner_id', 'INTEGER');
    $addColumn('bugs', 'description', 'TEXT');
    $addColumn('bugs', 'priority', "TEXT NOT NULL DEFAULT 'Medium'");
    $addColumn('bugs', 'status', "TEXT NOT NULL DEFAULT 'Новая'");
    $addColumn('bugs', 'created_by', 'INTEGER');
    $addColumn('bugs', 'created_at', "TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $addColumn('bugs', 'updated_at', 'TEXT');

    // Для старых записей заполняем автора владельцем проекта.
    $pdo->exec("UPDATE bugs SET created_by = (
        SELECT owner_id FROM projects WHERE projects.id = bugs.project_id
    ) WHERE created_by IS NULL");
} catch (PDOException $e) {
    error_log('Ошибка базы данных: ' . $e->getMessage());
    http_response_code(500);
    exit('Не удалось подключиться к базе данных.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin($pdo) {
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user && $user['role'] === 'admin';
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Ошибка безопасности: неверный CSRF-токен.');
    }
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function canManageBug($pdo, $bug) {
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    if (isAdmin($pdo) || (int)$bug['created_by'] === (int)$_SESSION['user_id']) {
        return true;
    }
    $stmt = $pdo->prepare('SELECT owner_id FROM projects WHERE id = :id');
    $stmt->execute(['id' => $bug['project_id']]);
    $project = $stmt->fetch();
    return $project && (int)$project['owner_id'] === (int)$_SESSION['user_id'];
}
?>