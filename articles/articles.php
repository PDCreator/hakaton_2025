<?php
session_start();
require '../includes/db.php'; // Обновленный путь
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: ../index.php"); // Замените на путь к вашей странице входа
    exit();
}
// Получение всех статей, которые не удалены
$stmt = $pdo->query("SELECT a.*, u.fio FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.deleted = 0 ORDER BY a.created_at DESC");
$articles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статьи</title>
    <link rel="stylesheet" href="../css/styles.css">
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

    <h1>Список статей</h1>

    <!-- Кнопки для создания статьи и просмотра истории изменений -->
    <div class="action-buttons">
        <a href="create_article.php" class="button">Создать статью</a>
        <a href="history.php" class="button">История изменений</a>
    </div>

    <div class="articles">
        <?php if (empty($articles)): ?>
            <p>Нет статей для отображения.</p>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <div class="article-tile">
                    <h2><?php echo htmlspecialchars($article['title']); ?></h2>
                    <p>Дата создания: <?php echo htmlspecialchars($article['created_at']); ?></p>
                    <p>Автор: <?php echo htmlspecialchars($article['fio']); ?></p>
                    <?php if ($article['image']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($article['image']); ?>" alt="Изображение статьи" style="max-width: 100%; height: auto;" />
                    <?php endif; ?>
                    <a href="edit_article.php?id=<?php echo $article['id']; ?>">Редактировать</a>
                    <a href="delete_article.php?id=<?php echo $article['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить эту статью?');">Удалить</a> 
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>