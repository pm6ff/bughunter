<?php
require 'db.php';

try {
    echo "<h2>Список багов</h2>";
    $query = "
        SELECT b.id, b.title, b.status, u.username AS author, p.name AS project 
        FROM bugs b
        JOIN users u ON b.author_id = u.id
        JOIN projects p ON b.project_id = p.id
    ";
    $bugs = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>"; print_r($bugs); echo "</pre>";

    echo "<h2>Проверка ограничений (ошибки)</h2>";
    try {
        $pdo->exec("INSERT INTO users (username, email, role) VALUES ('ivan_dev', 'new@mail.com', 'tester')");
    } catch (PDOException $e) {
        echo "<p style='color:red;'>UNIQUE нарушен: " . $e->getMessage() . "</p>";
    }

    try {
        $pdo->exec("INSERT INTO bugs (title, status, project_id, author_id) VALUES ('Test', 'open', 9999, 1)");
    } catch (PDOException $e) {
        echo "<p style='color:red;'>FK нарушен: " . $e->getMessage() . "</p>";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
