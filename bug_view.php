<?php
require_once __DIR__ . '/db.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Некорректный идентификатор ошибки.');
}

$stmt = $pdo->prepare(
    'SELECT b.*, p.name AS project_name, u.email AS author
     FROM bugs b
     LEFT JOIN projects p ON p.id = b.project_id
     LEFT JOIN users u ON u.id = b.created_by
     WHERE b.id = :id'
);
$stmt->execute(['id' => $id]);
$bug = $stmt->fetch();

if (!$bug) {
    http_response_code(404);
    exit('Ошибка не найдена.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($bug['title']) ?> — BugHunter</title>
</head>
<body>
    <h1>Карточка ошибки #<?= (int)$bug['id'] ?></h1>
    <p><a href="bugs.php">← К списку ошибок</a></p>

    <h2><?= h($bug['title']) ?></h2>
    <p><strong>Проект:</strong> <?= h($bug['project_name'] ?? 'Проект удалён') ?></p>
    <p><strong>Приоритет:</strong> <?= h($bug['priority']) ?></p>
    <p><strong>Статус:</strong> <?= h($bug['status']) ?></p>
    <p><strong>Автор:</strong> <?= h($bug['author'] ?? 'Неизвестен') ?></p>
    <p><strong>Создана:</strong> <?= h($bug['created_at']) ?></p>
    <?php if (!empty($bug['updated_at'])): ?>
        <p><strong>Изменена:</strong> <?= h($bug['updated_at']) ?></p>
    <?php endif; ?>
    <h3>Описание</h3>
    <p><?= nl2br(h($bug['description'] ?? 'Описание отсутствует.')) ?></p>

    <?php if (canManageBug($pdo, $bug)): ?>
        <p><a href="bug_edit.php?id=<?= (int)$bug['id'] ?>">Редактировать ошибку</a></p>
    <?php endif; ?>
</body>
</html>
