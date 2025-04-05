<?php
session_start();
require '../includes/db.php'; // Путь к файлу подключения к базе данных
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: ../index.php"); // Замените на путь к вашей странице входа
    exit();
}
$username = $_SESSION['user'];
$stmtUser = $pdo->prepare("SELECT id, fio FROM users WHERE login = :username");
$stmtUser->execute(['username' => $username]);
$user = $stmtUser->fetch();

if (isset($_POST['restore_id'])) {
    $restoreId = $_POST['restore_id'];

    // Восстановление статьи
    $stmt = $pdo->prepare("UPDATE articles SET deleted = 0 WHERE id = :id");
    $stmt->execute(['id' => $restoreId]);

    // Запись в историю восстановлений
    $stmtHistory = $pdo->prepare("INSERT INTO article_history (article_id, user_id, event, change_date) VALUES (:article_id, :user_id, 'восстановление', NOW())");
    $stmtHistory->execute(['article_id' => $restoreId, 'user_id' => $user['id']]);

    // Перенаправление на страницу истории
    header('Location: history.php');
    exit();
} else {
    header('Location: history.php'); // Перенаправление, если нет ID для восстановления
    exit();
}
?>