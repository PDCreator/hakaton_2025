<?php
session_start();
require '../includes/db.php'; // Обновленный путь
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: ../index.php"); // Замените на путь к вашей странице входа
    exit();
}

// Получение статьи
$articleId = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
$stmt->execute(['id' => $articleId]);
$article = $stmt->fetch();

$username = $_SESSION['user'];
$stmtUser = $pdo->prepare("SELECT id, fio FROM users WHERE login = :username");
$stmtUser->execute(['username' => $username]);
$user = $stmtUser->fetch();


if (!$article) {
    header('Location: articles.php');
    exit();
}

// Обработка редактирования статьи
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_FILES['image']['name'];

    if (empty($title)) {
        $error = "Название обязательно.";
    } else {
        // Загрузка изображения
        if ($image) {
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$image");
            $stmt = $pdo->prepare("UPDATE articles SET title = :title, content = :content, image = :image WHERE id = :id");
            $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image, 'id' => $articleId]);
        } else {
            $stmt = $pdo->prepare("UPDATE articles SET title = :title, content = :content WHERE id = :id");
            $stmt->execute(['title' => $title, 'content' => $content, 'id' => $articleId]);
        }

        // Запись в историю изменений
        $stmt = $pdo->prepare("INSERT INTO article_history (article_id, user_id, event) VALUES (:article_id, :user_id, 'изменение')");
        $stmt->execute(['article_id' => $articleId, 'user_id' => $user['id']]);

        header('Location: articles.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать статью</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.tiny.cloud/1/m2091v96csqx8le5h2smjv6va8o2okgd50ahor84g1wbi9os/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: 'textarea[name="content"]',
        plugins: 'lists image link code',
        toolbar: 'undo redo | bold italic underline | numlist bullist | image link | code',
        menubar: false
    });
    </script>
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
    <h1>Редактирование статьи</h1>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Название:</label>
        <input class="edit_input" type="text" id="title" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required>
        <span><?php echo $error; ?></span>

        <label for="content">Содержимое:</label>
        <textarea id="content" name="content"><?php echo htmlspecialchars($article['content']); ?></textarea>

        <label for="image">Изображение:</label>
        <input class=""type="file" id="image" name="image">

        <button class="button-74" type="submit">Сохранить</button>
    </form>
</body>
</html>