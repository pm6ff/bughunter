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

// Комментарии (JOIN users)
$cStmt = $pdo->prepare(
    'SELECT c.text, c.created_at, u.email AS author
     FROM comments c
     JOIN users u ON u.id = c.user_id
     WHERE c.bug_id = :id ORDER BY c.created_at ASC, c.id ASC'
);
$cStmt->execute([':id' => $id]);
$comments = $cStmt->fetchAll(PDO::FETCH_ASSOC);

// История смены статусов (JOIN users)
$hStmt = $pdo->prepare(
    'SELECT h.old_status, h.new_status, h.created_at, u.email AS author
     FROM bug_history h
     JOIN users u ON u.id = h.user_id
     WHERE h.bug_id = :id ORDER BY h.created_at ASC, h.id ASC'
);
$hStmt->execute([':id' => $id]);
$history = $hStmt->fetchAll(PDO::FETCH_ASSOC);

$role = currentRole($pdo);
$nextStatuses = availableStatuses($pdo, $bug['status']);
$flash = $_SESSION['flash'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);
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

    <?php if ($flash): ?><p style="color: green;"><?= h($flash) ?></p><?php endif; ?>
    <?php if ($flashError): ?><p style="color: red;"><?= h($flashError) ?></p><?php endif; ?>

    <h2><?= h($bug['title']) ?></h2>
    <p><strong>Проект:</strong> <?= h($bug['project_name'] ?? 'Проект удалён') ?></p>
    <p><strong>Приоритет:</strong> <?= h($bug['priority']) ?></p>
    <p><strong>Статус:</strong> <?= h(statusLabel($bug['status'])) ?> (<?= h($bug['status']) ?>)</p>
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

    <h3>Смена статуса</h3>
    <p>Ваша роль: <strong><?= h($role) ?></strong></p>
    <?php if ($nextStatuses): ?>
        <form method="post" action="status_change.php">
            <input type="hidden" name="bug_id" value="<?= (int)$bug['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
            <label for="new_status">Новый статус:</label>
            <select id="new_status" name="new_status" required>
                <?php foreach ($nextStatuses as $st): ?>
                    <option value="<?= h($st) ?>"><?= h(statusLabel($st)) ?> (<?= h($st) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Изменить статус</button>
        </form>
    <?php else: ?>
        <p>Для вашей роли из статуса «<?= h(statusLabel($bug['status'])) ?>» переходы недоступны.</p>
    <?php endif; ?>

    <h3 id="history">История изменений статуса</h3>
    <?php if (!$history): ?>
        <p>Изменений статуса пока не было.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead><tr><th>Дата и время</th><th>Кто изменил</th><th>Было</th><th>Стало</th></tr></thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= h($row['created_at']) ?></td>
                    <td><?= h($row['author']) ?></td>
                    <td><?= h(statusLabel($row['old_status'])) ?></td>
                    <td><?= h(statusLabel($row['new_status'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3 id="comments">Комментарии (<?= count($comments) ?>)</h3>
    <?php if (!$comments): ?>
        <p>Комментариев пока нет.</p>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
            <div style="border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;">
                <strong><?= h($c['author']) ?></strong>
                <small>— <?= h($c['created_at']) ?></small>
                <p><?= nl2br(h($c['text'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="comment_add.php">
        <input type="hidden" name="bug_id" value="<?= (int)$bug['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
        <label for="text">Новый комментарий</label><br>
        <textarea id="text" name="text" rows="4" cols="60" maxlength="2000" required></textarea><br>
        <button type="submit">Отправить</button>
    </form>
</body>
</html>
