<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $pdo->prepare("INSERT INTO articles (title, content, user_id) VALUES (:title, :content, :user_id)");
    $stmt->execute(['title' => $title, 'content' => $content, 'user_id' => $_SESSION['user_id']]);
}

$articles = $pdo->query("SELECT * FROM articles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Полезная информация</title>
</head>
<body>
<nav>
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="articles.php">Полезная информация</a></li>
            <li><a href="tasks.php">Задачи</a></li>
            <li><a href="admin.php">Администрирование</a></li>
        </ul>
    </nav>
    <h2>Статьи</h2>
    <form method="POST">
        <input type="text" name="title" placeholder="Название статьи" required>
        <textarea name="content" placeholder="Содержание статьи" required></textarea>
        <button type="submit">Создать статью</button>
    </form>
    <div>
        <?php foreach ($articles as $article): ?>
            <h3><?php echo $article['title']; ?></h3>
            <p><?php echo $article['content']; ?></p>
        <?php endforeach; ?>
    </div>
</body>
</html>