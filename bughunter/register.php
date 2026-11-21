<?php
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // 2. Валидация email и пароля
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Пароли не совпадают.";
    }

    // Проверка на существующий email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Пользователь с таким email уже зарегистрирован.";
        }
    }

    // Сохранение в БД
    if (empty($errors)) {
        // 3. password_hash при регистрации
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 8. PDO (подготовленный запрос), пароль не хранится в открытом виде
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, 'customer')");
        try {
            $stmt->execute(['email' => $email, 'hash' => $hash]);
            $success = "Регистрация успешна! <a href='login.php'>Войти</a>";
        } catch (PDOException $e) {
            $errors[] = "Ошибка при регистрации: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>BugHunter - Регистрация</title>
</head>
<body>
    <h2>Регистрация в BugHunter</h2>

    <?php if ($errors): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>

    <!-- 1. Форма с method=POST -->
    <form method="POST" action="register.php">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        
        <label>Пароль (мин. 6 символов):</label><br>
        <input type="password" name="password" required><br><br>
        
        <label>Подтвердите пароль:</label><br>
        <input type="password" name="password_confirm" required><br><br>
        
        <button type="submit">Зарегистрироваться</button>
    </form>
    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
</body>
</html>