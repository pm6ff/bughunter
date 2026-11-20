<?php
require_once 'db.php';

$errors = [];

// Если уже авторизован — редирект на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Валидация
    if (empty($email) || empty($password)) {
        $errors[] = "Заполните все поля.";
    } else {
        // Ищем пользователя по email
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. password_verify при входе
        if ($user && password_verify($password, $user['password_hash'])) {
            // 5. $_SESSION['user_id'] после успешного входа
            $_SESSION['user_id'] = $user['id'];
            session_regenerate_id(true); // защита от session fixation
            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Неверный email или пароль.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>BugHunter - Вход</title>
</head>
<body>
    <h2>Вход в BugHunter</h2>

    <?php if ($errors): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- 1. Форма с method=POST -->
    <form method="POST" action="login.php">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        
        <label>Пароль:</label><br>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Войти</button>
    </form>
    <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
</body>
</html>