<?php
// Смена статуса ошибки: валидация, проверка прав, карта переходов, транзакция + bug_history
require_once __DIR__ . '/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Неверный метод запроса.');
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

// 1–3. Получаем данные и ищем запись
$id = filter_input(INPUT_POST, 'bug_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['new_status'] ?? '';
if (!$id || !is_string($newStatus) || !array_key_exists($newStatus, BUG_STATUSES)) {
    http_response_code(400);
    exit('Некорректные данные запроса.');
}

$stmt = $pdo->prepare('SELECT * FROM bugs WHERE id = :id');
$stmt->execute([':id' => $id]);
$bug = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bug) {
    http_response_code(404);
    exit('Ошибка не найдена.');
}

// 5. Карта допустимых переходов
$allowed = allowedTransitions();
$oldStatus = $bug['status'];
if (!in_array($newStatus, $allowed[$oldStatus] ?? [], true)) {
    http_response_code(400);
    exit('Недопустимый переход статуса.');
}

// 4. Проверка прав роли ($_SESSION['role'] обновляется из БД)
$role = currentRole($pdo);
$roleAllowed = roleTransitions($role);
if (!in_array($newStatus, $roleAllowed[$oldStatus] ?? [], true)) {
    http_response_code(403);
    exit('У вашей роли нет прав на этот переход.');
}

// 6–7. Транзакция: UPDATE bugs + INSERT bug_history
$now = date('Y-m-d H:i:s');
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare('UPDATE bugs SET status = :new, updated_at = :now WHERE id = :id AND status = :old');
    $upd->execute([':new' => $newStatus, ':now' => $now, ':id' => $id, ':old' => $oldStatus]);
    if ($upd->rowCount() !== 1) {
        throw new RuntimeException('Статус уже был изменён другим пользователем.');
    }

    $hist = $pdo->prepare(
        'INSERT INTO bug_history (bug_id, user_id, old_status, new_status, created_at)
         VALUES (:bid, :uid, :old, :new, :now)'
    );
    $hist->execute([
        ':bid' => $id, ':uid' => $_SESSION['user_id'],
        ':old' => $oldStatus, ':new' => $newStatus, ':now' => $now,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ошибка смены статуса: ' . $e->getMessage());
    http_response_code(409);
    exit('Не удалось изменить статус. Обновите страницу и повторите.');
}

$_SESSION['flash'] = 'Статус изменён: ' . statusLabel($oldStatus) . ' → ' . statusLabel($newStatus);
header('Location: bug_view.php?id=' . $id);
exit;
