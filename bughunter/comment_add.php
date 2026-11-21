<?php
// Добавление комментария к ошибке
require_once __DIR__ . '/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Неверный метод запроса.');
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$bugId = filter_input(INPUT_POST, 'bug_id', FILTER_VALIDATE_INT);
if (!$bugId) {
    http_response_code(400);
    exit('Некорректный идентификатор ошибки.');
}

// Ошибка с таким id должна существовать
$check = $pdo->prepare('SELECT id FROM bugs WHERE id = :id');
$check->execute([':id' => $bugId]);
if (!$check->fetch()) {
    http_response_code(404);
    exit('Ошибка не найдена.');
}

// trim() блокирует пустые и пробельные комментарии
$text = trim((string)($_POST['text'] ?? ''));
if ($text === '' || preg_match('/^[\s\x{00A0}]*$/u', $text)) {
    $_SESSION['flash_error'] = 'Комментарий не может быть пустым.';
    header('Location: bug_view.php?id=' . $bugId . '#comments');
    exit;
}
if (textLength($text) > 2000) {
    $_SESSION['flash_error'] = 'Комментарий не должен превышать 2000 символов.';
    header('Location: bug_view.php?id=' . $bugId . '#comments');
    exit;
}

// Автор берётся строго из сессии
$stmt = $pdo->prepare(
    'INSERT INTO comments (bug_id, user_id, text, created_at)
     VALUES (:bid, :uid, :txt, :now)'
);
$stmt->execute([
    ':bid' => $bugId,
    ':uid' => $_SESSION['user_id'],
    ':txt' => $text,
    ':now' => date('Y-m-d H:i:s'),
]);

$_SESSION['flash'] = 'Комментарий добавлен.';
header('Location: bug_view.php?id=' . $bugId . '#comments');
exit;
