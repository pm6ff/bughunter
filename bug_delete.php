<?php
require_once __DIR__ . '/db.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Неверный метод запроса.');
}

if (!isAdmin($pdo)) {
    http_response_code(403);
    exit('У вас нет прав для удаления ошибок.');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('Некорректный ID ошибки.');
}

$stmt = $pdo->prepare('DELETE FROM bugs WHERE id = :id');
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit('Ошибка не найдена.');
}

header('Location: bugs.php');
exit;
