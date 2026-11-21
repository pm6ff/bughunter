<?php
require_once 'db.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit("Неверный ID"); }

// Получаем проект
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute(['id' => $id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) { http_response_code(404); exit("Проект не найден"); }

// 7. Серверная проверка прав доступа
if ($_SESSION['user_id'] != $project['owner_id'] && !isAdmin($pdo)) {
    http_response_code(403);
    exit("У вас нет прав на редактирование этого проекта.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') $errors[] = "Введите название.";

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE projects SET name = :name, description = :desc WHERE id = :id");
        $upd->execute(['name' => $name, 'desc' => $desc, 'id' => $id]);
        header("Location: projects.php"); // Редирект после успеха
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Редактирование</title></head>
<body>
    <h2>Редактировать проект</h2>
    <?php if ($errors): ?><div style="color:red;"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <label>Название:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>"><br><br>
        <label>Описание:</label><br>
        <textarea name="description"><?= htmlspecialchars($project['description']) ?></textarea><br><br>
        <button type="submit">Сохранить изменения</button>
    </form>
    <br><a href="projects.php">Отмена</a>
</body>
</html>