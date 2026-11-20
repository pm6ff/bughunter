<?php
require_once 'db.php';
requireLogin(); // Только для авторизованных

// Исправлено: u.login заменено на u.email, так как в вашей БД нет поля login
$sql = "SELECT p.id, p.name, p.description, p.created_at, u.email AS author, p.owner_id 
        FROM projects p 
        JOIN users u ON u.id = p.owner_id 
        ORDER BY p.created_at DESC";
$projects = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Проекты BugHunter</title>
</head>
<body>
    <h2>Список проектов</h2>
    <p>
        <a href="project_create.php">+ Создать новый проект</a> | 
        <a href="index.php">На главную</a> |
        <a href="logout.php">Выйти из системы</a>
    </p>

    <table border="1" cellpadding="10" style="margin-top: 20px; border-collapse: collapse;">
        <tr>
            <th>ID</th><th>Название</th><th>Описание</th><th>Автор (Email)</th><th>Дата</th><th>Действия</th>
        </tr>
        <?php foreach ($projects as $p): ?>
        <tr>
            <!-- 11. Безопасный вывод (htmlspecialchars) -->
            <td><?= (int)$p['id'] ?></td>
            <td><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($p['description'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($p['author'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($p['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <a href="project_view.php?id=<?= (int)$p['id'] ?>">Просмотр</a>
                
                <!-- Права: Редактировать и удалять может только автор или админ -->
                <?php if ($_SESSION['user_id'] == $p['owner_id'] || isAdmin($pdo)): ?>
                    | <a href="project_edit.php?id=<?= (int)$p['id'] ?>">Редактировать</a> |
                    
                    <!-- Форма для удаления (метод POST + CSRF) -->
                    <form method="POST" action="project_delete.php" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот проект?');">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" style="color: red; background: none; border: none; cursor: pointer; text-decoration: underline; padding: 0; font-family: inherit; font-size: inherit;">Удалить</button>
                    </form>
                    
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>