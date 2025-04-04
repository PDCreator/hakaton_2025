<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assignee = $_POST['assignee'];
    $priority = $_POST['priority'];

    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assignee, priority) VALUES (:title, :description, :assignee, :priority)");
    $stmt->execute(['title' => $title, 'description' => $description, 'assignee' => $assignee, 'priority' => $priority]);
}

$tasks = $pdo->query("SELECT * FROM tasks")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Задачи</title>
</head>
<body>
    <h2>Задачи</h2>
    <form method="POST">
        <input type="text" name="title" placeholder="Название задачи" required>
        <textarea name="description" placeholder="Описание задачи" required></textarea>
        <input type="text" name="assignee" placeholder="Ответственный" required>
        <select name="priority" required>
            <option value="Низкий">Низкий</option>
            <option value="Средний">Средний</option>
            <option value="Высокий">Высокий</option>
        </select>
        <button type="submit">Создать задачу</button>
    </form>
    <div>
        <?php foreach ($tasks as $task): ?>
            <h3><?php echo $task['title']; ?></h3>
            <p><?php echo $task['description']; ?></p>
        <?php endforeach; ?>
    </div>
</body>
</html>