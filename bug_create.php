<?php
require_once __DIR__ . '/db.php';
requireLogin();

$errors = [];
$title = '';
$description = '';
$projectId = 0;
$priority = 'Medium';
$priorities = ['Critical', 'High', 'Medium', 'Low'];

$projects = $pdo->query('SELECT id, name FROM projects ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $projectId = filter_var($_POST['project_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $priority = $_POST['priority'] ?? 'Medium';

    if ($title === '' || textLength($title) > 200) {
        $errors[] = 'Название обязательно и должно содержать не более 200 символов.';
    }
    if (textLength($description) > 10000) {
        $errors[] = 'Описание не должно превышать 10000 символов.';
    }
    if (!in_array($priority, $priorities, true)) {
        $errors[] = 'Выбран недопустимый приоритет.';
    }

    $projectStmt = $pdo->prepare('SELECT id FROM projects WHERE id = :id');
    $projectStmt->execute(['id' => $projectId]);
    if (!$projectId || !$projectStmt->fetch()) {
        $errors[] = 'Выберите существующий проект.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO bugs (project_id, title, description, priority, status, created_by, created_at)
             VALUES (:project_id, :title, :description, :priority, :status, :created_by, :created_at)'
        );
        $stmt->execute([
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => 'Новая',
            'created_by' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        header('Location: bug_view.php?id=' . $pdo->lastInsertId());
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новая ошибка — BugHunter</title>
</head>
<body>
    <h1>Регистрация ошибки</h1>
    <p><a href="bugs.php">К списку ошибок</a></p>

    <?php if ($errors): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!$projects): ?>
        <p>Сначала создайте хотя бы один проект: <a href="project_create.php">Создать проект</a>.</p>
    <?php else: ?>
    <form method="post" action="bug_create.php">
        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

        <label for="title">Название ошибки</label><br>
        <input id="title" name="title" type="text" maxlength="200" required
               value="<?= h($title) ?>"><br><br>

        <label for="description">Описание</label><br>
        <textarea id="description" name="description" rows="6" maxlength="10000"><?= h($description) ?></textarea><br><br>

        <label for="project_id">Проект</label><br>
        <select id="project_id" name="project_id" required>
            <option value="">Выберите проект</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?= (int)$project['id'] ?>" <?= $projectId === (int)$project['id'] ? 'selected' : '' ?>>
                    <?= h($project['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="priority">Приоритет</label><br>
        <select id="priority" name="priority" required>
            <?php foreach ($priorities as $item): ?>
                <option value="<?= h($item) ?>" <?= $priority === $item ? 'selected' : '' ?>><?= h($item) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Зарегистрировать ошибку</button>
    </form>
    <?php endif; ?>
</body>
</html>
