<?php
session_start();
require '../includes/db.php'; // Путь к файлу подключения к базе данных
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: ../index.php"); // Замените на путь к вашей странице входа
    exit();
}
// Получение истории изменений
$stmt = $pdo->query("
    SELECT h.*, a.title, a.deleted, u.fio 
    FROM article_history h 
    JOIN articles a ON h.article_id = a.id 
    LEFT JOIN users u ON h.user_id = u.id 
    ORDER BY h.change_date DESC
");

$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>История изменений</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
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
<h1>История изменений статей</h1>
<table>
    <thead>
        <tr>
            <th>Дата изменения</th>
            <th>Пользователь</th>
            <th>Название статьи</th>
            <th>Тип события</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($history as $entry): ?>
            <tr>
                <td><?php echo htmlspecialchars($entry['change_date']); ?></td>
                <td><?php echo htmlspecialchars($entry['fio']); ?></td>
                <td><?php echo htmlspecialchars($entry['title']); ?></td>
                <td><?php echo htmlspecialchars($entry['event']); ?></td>
                <td>
                    <?php if ($entry['event'] == 'удаление' && isset($entry['deleted']) && $entry['deleted'] == 1 && strtotime($entry['change_date']) >= strtotime('-7 days')): ?>
                        <form method="POST" action="restore_article.php">
                            <input type="hidden" name="restore_id" value="<?php echo $entry['article_id']; ?>">
                            <button type="submit">Восстановить</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>