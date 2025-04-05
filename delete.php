<?php
// delete.php
session_start();
require 'includes/db.php';
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: index.php"); // Замените на путь к вашей странице входа
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Получаем ID пользователя по логину (например, 'admin')
    $username = $_SESSION['user'];
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE login = :username");
    $stmtUser->execute(['username' => $username]);
    $user_id = $stmtUser->fetchColumn();

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT title FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $title = $stmt->fetchColumn();

        $pdo->prepare("DELETE FROM tasks WHERE id = :id")->execute(['id' => $id]);

        $pdo->prepare("INSERT INTO task_history (task_id, user_id, event, title, changed_at) VALUES (:task_id, :user_id, :event, :title, NOW())")
            ->execute([
                'task_id' => $id,
                'user_id' => $user_id,
                'event' => 'Удаление задачи',
                'title' => $title
            ]);
    } else {
        echo "Ошибка: отсутствует ID пользователя.";
        exit;
    }
}

header("Location: tasks.php");
exit;