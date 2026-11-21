<?php
require_once 'db.php';
requireLogin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 10. CSRF-защита
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    // 8. Серверная валидация
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = "Введите название проекта.";
    }

    if (empty($errors)) {
        // 9. PDO prepare + execute
        $stmt = $pdo->prepare("
            INSERT INTO projects (name, description, created_at, owner_id) 
            VALUES (:name, :desc, :created, :owner)
        ");
        $stmt->execute([
            'name' => $name,
            'desc' => $desc,
            'created' => date('Y-m-d H:i:s'),
            'owner' => $_SESSION['user_id'] // owner_id из сессии (защита от подмены)
        ]);
        $success = "Проект успешно создан! <a href='projects.php'>Вернуться к списку</a>";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Создание проекта</title></head>
<body>
    <h2>Создать проект</h2>
    <?php if ($errors): ?>
        <div style="color:red;"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="color:green;"><p><?= $success ?></p></div>
    <?php endif; ?>

    <form method="POST">
        <!-- 10. CSRF-токен -->
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <label>Название:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"><br><br>
        
        <label>Описание:</label><br>
        <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea><br><br>
        
        <button type="submit">Создать</button>
    </form>
</body>
</html>