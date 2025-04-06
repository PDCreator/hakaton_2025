<?php
session_start();
require '../includes/db.php'; // Путь к файлу подключения к базе данных

// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php"); // Перенаправление на страницу входа
    exit();
}

// Получение истории изменений
$stmt = $pdo->query("
    SELECT h.*, a.title, u.fio, a.deleted 
    FROM article_history h 
    JOIN articles a ON h.article_id = a.id 
    LEFT JOIN users u ON h.user_id = u.id 
    ORDER BY h.change_date DESC
");

$history = $stmt->fetchAll();

// Создание массива для хранения последнего события "удаление" для каждой статьи
$latestDeletions = [];
foreach ($history as $entry) {
    if ($entry['event'] === 'удаление') {
        // Сохраняем только самую последнюю запись об удалении для каждой статьи
        if (!isset($latestDeletions[$entry['article_id']]) || strtotime($entry['change_date']) > strtotime($latestDeletions[$entry['article_id']]['change_date'])) {
            $latestDeletions[$entry['article_id']] = $entry;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>История изменений</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
    </style>
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
                    <?php if ($entry['deleted'] == 1 && isset($latestDeletions[$entry['article_id']]) && $latestDeletions[$entry['article_id']]['change_date'] === $entry['change_date']): ?>
                        <form method="POST" action="restore_article.php">
                            <input type="hidden" name="restore_id" value="<?php echo $entry['article_id']; ?>">
                            <button type="submit">Восстановить</button>
                        </form>
                    <?php elseif ($entry['event'] === 'восстановление'): ?>
                        
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>