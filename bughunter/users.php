<?php
// Управление ролями пользователей (только администратор)
require_once __DIR__ . '/db.php';
requireLogin();

if (!isAdmin($pdo)) {
    http_response_code(403);
    exit('Доступ только для администратора.');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $role = $_POST['role'] ?? '';

    if (!$userId || !in_array($role, BUG_ROLES, true)) {
        $error = 'Некорректные данные.';
    } elseif ($userId === (int)$_SESSION['user_id']) {
        $error = 'Нельзя изменить собственную роль (защита от потери доступа).';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $userId]);
        $message = $stmt->rowCount() ? 'Роль обновлена.' : 'Пользователь не найден.';
    }
}

$users = $pdo->query('SELECT id, email, role, created_at FROM users ORDER BY id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Пользователи — BugHunter</title>
</head>
<body>
    <h1>Роли пользователей</h1>
    <p><a href="index.php">На главную</a> | <a href="bugs.php">Ошибки</a></p>

    <?php if ($message): ?><p style="color: green;"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color: red;"><?= h($error) ?></p><?php endif; ?>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead><tr><th>ID</th><th>Email</th><th>Роль</th><th>Изменить</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= h($u['email']) ?></td>
                <td><?= h($u['role']) ?></td>
                <td>
                    <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                        —
                    <?php else: ?>
                    <form method="post" action="users.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <select name="role">
                            <?php foreach (BUG_ROLES as $r): ?>
                                <option value="<?= h($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= h($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Сохранить</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
