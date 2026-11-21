<?php
require_once __DIR__ . '/db.php';
requireLogin();

// Фильтр по статусу (значение проверяется по белому списку)
$filter = $_GET['status'] ?? '';
if (!is_string($filter) || !array_key_exists($filter, BUG_STATUSES)) {
    $filter = '';
}

$sql = "SELECT b.id, b.title, b.priority, b.status, b.created_at, b.created_by, b.project_id,
               p.name AS project_name, p.owner_id, u.email AS author,
               (SELECT COUNT(*) FROM comments c WHERE c.bug_id = b.id) AS comments_count,
               (SELECT MAX(h.created_at) FROM bug_history h WHERE h.bug_id = b.id) AS status_changed_at
        FROM bugs b
        LEFT JOIN projects p ON p.id = b.project_id
        LEFT JOIN users u ON u.id = b.created_by"
        . ($filter !== '' ? " WHERE b.status = :status" : "") .
        " ORDER BY b.created_at DESC, b.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($filter !== '' ? ['status' => $filter] : []);
$bugs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ошибки — BugHunter</title>
</head>
<body>
    <h1>Список ошибок</h1>
    <p>
        <a href="bug_create.php">+ Зарегистрировать ошибку</a> |
        <a href="projects.php">Проекты</a> |
        <a href="index.php">На главную</a> |
        <a href="logout.php">Выйти</a>
    </p>

    <form method="get" action="bugs.php">
        <label for="status">Фильтр по статусу:</label>
        <select id="status" name="status">
            <option value="">Все</option>
            <?php foreach (BUG_STATUSES as $code => $label): ?>
                <option value="<?= h($code) ?>" <?= $filter === $code ? 'selected' : '' ?>><?= h($label) ?> (<?= h($code) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Показать</button>
    </form>

    <?php if (!$bugs): ?>
        <p>Ошибок пока нет. Зарегистрируйте первую ошибку.</p>
    <?php else: ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th><th>Название</th><th>Проект</th>
                    <th>Приоритет</th><th>Статус</th><th>Смена статуса</th><th>Комм.</th><th>Автор</th>
                    <th>Дата создания</th><th>Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bugs as $bug): ?>
                <tr>
                    <td><?= (int)$bug['id'] ?></td>
                    <td><?= h($bug['title']) ?></td>
                    <td><?= h($bug['project_name'] ?? 'Проект удалён') ?></td>
                    <td><?= h($bug['priority']) ?></td>
                    <td><?= h(statusLabel($bug['status'])) ?></td>
                    <td><?= h($bug['status_changed_at'] ?? '—') ?></td>
                    <td><?= (int)$bug['comments_count'] ?></td>
                    <td><?= h($bug['author'] ?? 'Неизвестен') ?></td>
                    <td><?= h($bug['created_at']) ?></td>
                    <td>
                        <a href="bug_view.php?id=<?= (int)$bug['id'] ?>">Открыть</a>
                        <?php if (canManageBug($pdo, $bug)): ?>
    | <a href="bug_edit.php?id=<?= (int)$bug['id'] ?>">Изменить</a>
<?php endif; ?>

<?php if (isAdmin($pdo)): ?>
    | <form action="bug_delete.php" method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?= (int)$bug['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
        <button type="submit" onclick="return confirm('Вы действительно хотите удалить эту ошибку?')">
            Удалить
        </button>
    </form>
<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
