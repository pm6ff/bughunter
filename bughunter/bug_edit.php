<?php
require_once __DIR__ . '/db.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Некорректный идентификатор ошибки.');
}

$stmt = $pdo->prepare('SELECT * FROM bugs WHERE id = :id');
$stmt->execute(['id' => $id]);
$bug = $stmt->fetch();

if (!$bug) {
    http_response_code(404);
    exit('Ошибка не найдена.');
}
if (!canManageBug($pdo, $bug)) {
    http_response_code(403);
    exit('У вас нет прав на изменение этой ошибки.');
}

$errors = [];
$priorities = ['Critical', 'High', 'Medium', 'Low'];

$title = $bug['title'];
$description = $bug['description'] ?? '';
$priority = $bug['priority'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? '';

    if ($title === '' || textLength($title) > 200) {
        $errors[] = 'Название обязательно и должно содержать не более 200 символов.';
    }
    if (textLength($description) > 10000) {
        $errors[] = 'Описание не должно превышать 10000 символов.';
    }
    if (!in_array($priority, $priorities, true)) {
        $errors[] = 'Недопустимый приоритет.';
    }

    if (!$errors) {
        $update = $pdo->prepare(
            'UPDATE bugs
             SET title = :title, description = :description, priority = :priority,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $update->execute([
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id
        ]);
        header('Location: bug_view.php?id=' . $id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Редактирование ошибки — BugHunter</title>
</head>
<body>
    <h1>Редактирование ошибки #<?= (int)$id ?></h1>
    <p><a href="bug_view.php?id=<?= (int)$id ?>">Отмена</a></p>

    <?php if ($errors): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="bug_edit.php?id=<?= (int)$id ?>">
        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

        <label for="title">Название</label><br>
        <input id="title" name="title" type="text" maxlength="200" required value="<?= h($title) ?>"><br><br>

        <label for="description">Описание</label><br>
        <textarea id="description" name="description" rows="6" maxlength="10000"><?= h($description) ?></textarea><br><br>

        <label for="priority">Приоритет</label><br>
        <select id="priority" name="priority">
            <?php foreach ($priorities as $item): ?>
                <option value="<?= h($item) ?>" <?= $priority === $item ? 'selected' : '' ?>><?= h($item) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <p><strong>Статус:</strong> <?= h(statusLabel($bug['status'])) ?> — меняется только на странице ошибки по правилам жизненного цикла.</p>

        <button type="submit">Сохранить изменения</button>
    </form>
</body>
</html>
