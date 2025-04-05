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


if (isset($_GET['id'])) {
    $articleId = $_GET['id'];

    // Получение информации о статье
    $stmt = $pdo->prepare("SELECT title FROM articles WHERE id = :id");
    $stmt->execute(['id' => $articleId]);
    $article = $stmt->fetch();

    if ($article) {
        // Помечаем статью как удалённую
        $stmt = $pdo->prepare("UPDATE articles SET deleted = 1 WHERE id = :id");
        $stmt->execute(['id' => $articleId]);

        // Запись в историю изменений
        $stmtHistory = $pdo->prepare("INSERT INTO article_history (article_id, user_id, event, change_date) VALUES (:article_id, :user_id, 'удаление', NOW())");
        $stmtHistory->execute(['article_id' => $articleId, 'user_id' =>  $user['id']]);

        // Перенаправление на страницу со списком статей
        $_SESSION['message'] = "Статья '{$article['title']}' удалена.";
        header('Location: articles.php');
        exit();
    } else {
        echo "Статья не найдена.";
    }
} else {
    echo "Статья не найдена.";
}
?>