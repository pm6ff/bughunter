<?php
require_once __DIR__ . '/db.php';
requireLogin();

$sql = "SELECT b.id, b.title, b.priority, b.status, b.created_at, b.created_by, b.project_id,
               p.name AS project_name, p.owner_id, u.email AS author
        FROM bugs b
        LEFT JOIN projects p ON p.id = b.project_id
        LEFT JOIN users u ON u.id = b.created_by
        ORDER BY b.created_at DESC, b.id DESC";
$bugs = $pdo->query($sql)->fetchAll();
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

    <?php if (!$bugs): ?>
        <p>Ошибок пока нет. Зарегистрируйте первую ошибку.</p>
    <?php else: ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th><th>Название</th><th>Проект</th>
                    <th>Приоритет</th><th>Статус</th><th>Автор</th>
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
                    <td><?= h($bug['status']) ?></td>
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
