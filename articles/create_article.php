<?php
session_start();
require '../includes/db.php'; // Обновленный путь
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: ../index.php"); // Замените на путь к вашей странице входа
    exit();
}
// Проверка, вошел ли пользователь в систему
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Перенаправление на страницу входа
    exit();
}

// Получение информации о текущем пользователе
$username = $_SESSION['user'];
$stmtUser = $pdo->prepare("SELECT id, fio FROM users WHERE login = :username");
$stmtUser->execute(['username' => $username]);
$user = $stmtUser->fetch();

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_FILES['image']['name'];

    // Проверка на пустое название
    if (empty($title)) {
        $error = "Название обязательно.";
    } else {
        // Загрузка изображения
        if ($image) {
            $targetDir = "../uploads/"; // Папка для загрузки изображений
            $targetFile = $targetDir . basename($image);

            // Проверка на успешную загрузку
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Успешная загрузка
            } else {
                $error = "Ошибка загрузки изображения.";
            }
        }

        // Вставка статьи в базу
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, image, user_id) VALUES (:title, :content, :image, :user_id)");
        $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image, 'user_id' => $user['id']]);

        // Запись в историю изменений
        $articleId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO article_history (article_id, user_id, event) VALUES (:article_id, :user_id, 'создание')");
        $stmt->execute(['article_id' => $articleId, 'user_id' => $user['id']]);

        // Перенаправление на страницу со статьями
        header('Location: articles.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать статью</title>
    <link rel="stylesheet" href="../css/styles.css"> <!-- Обновленный путь -->
</head>
<body>
<nav>
        <ul>
            <li><a href="../index.php">Главная</a></li>
            <li><a href="../articles/articles.php">Полезная информация</a></li>
            <li><a href="../tasks.php">Задачи</a></li>
            <li><a href="../admin.php">Администрирование</a></li>
        </ul>
    </nav>
    <h1>Создание новой статьи</h1>
    <?php if ($user): ?>
        <p>Автор: <?php echo htmlspecialchars($user['fio']); ?></p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Название:</label>
        <input type="text" id="title" name="title" required>
        <span style="color:red;"><?php echo $error; ?></span>

        <label for="content">Содержимое:</label>
        <textarea id="content" name="content"></textarea>

        <label for="image">Изображение:</label>
        <input type="file" id="image" name="image">

        <button type="submit">Сохранить</button>
    </form>
</body>
</html>