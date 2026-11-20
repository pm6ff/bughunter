<?php
require_once 'db.php';
requireLogin();

// Только POST-запрос
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Метод не разрешен. Используйте POST.");
}

// CSRF проверка
verifyCsrfToken($_POST['csrf_token'] ?? '');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { exit("Неверный ID"); }

// Проверка прав: владелец или админ
$stmt = $pdo->prepare("SELECT owner_id FROM projects WHERE id = :id");
$stmt->execute(['id' => $id]);
$project = $stmt->fetch();

if (!$project) { exit("Проект не найден"); }

if ($_SESSION['user_id'] != $project['owner_id'] && !isAdmin($pdo)) {
    http_response_code(403);
    exit("У вас нет прав на удаление этого проекта.");
}

// Проверка наличия связанных багов (если таблица bugs существует)
// Предполагается, что у вас есть таблица bugs с полем project_id. 
// Если её нет, этот блок можно закомментировать.
try {
    $checkBugs = $pdo->prepare("SELECT COUNT(*) FROM bugs WHERE project_id = :id");
    $checkBugs->execute(['id' => $id]);
    if ((int)$checkBugs->fetchColumn() > 0) {
        die("Удаление запрещено: в проекте есть привязанные баги (bugs).");
    }
} catch (PDOException $e) {
    // Если таблицы bugs еще нет, пропускаем проверку
}

// Удаление
$del = $pdo->prepare("DELETE FROM projects WHERE id = :id");
$del->execute(['id' => $id]);

// Возврат на список
header("Location: projects.php");
exit;
?>