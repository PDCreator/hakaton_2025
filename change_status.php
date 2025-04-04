<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];

    // Получаем старый статус и название задачи
    $stmt = $pdo->prepare("SELECT status, title FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        #http_response_code(404);
        echo "Задача не найдена";
        exit;
    }

    $old_status = $task['status'];
    $title = $task['title'];

    // Обновляем статус задачи
    $update = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $update->execute([$new_status, $task_id]);

    // Сохраняем в историю изменений
    $user_id = $_SESSION['user_id'] ?? null; // Убедись, что пользователь залогинен
    $event = "Смена статуса с \"$old_status\" на \"$new_status\"";
    $date = date('Y-m-d H:i:s');

    $history = $pdo->prepare("INSERT INTO task_history (task_id, title, user_id, event, changed_at) VALUES (?, ?, ?, ?, ?)");
    $history->execute([$task_id, $title, $user_id, $event, $date]);

    echo "OK";
}
?>
