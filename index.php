<?php
require_once 'db.php';

// 6. Защита закрытых страниц
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя для отображения
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>BugHunter - Панель</title>
</head>
<body>
    <h2>Добро пожаловать в BugHunter!</h2>
    <p>Вы авторизованы как: <strong><?= htmlspecialchars($user['email']) ?></strong></p>
    <p>ID вашей сессии: <?= session_id() ?></p>
    
    <!-- 7. Выход -->
    <a href="logout.php"><button>Выйти из системы</button></a>
</body>
</html>