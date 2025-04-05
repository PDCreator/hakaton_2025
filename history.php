<?php
// history.php
session_start();
require 'includes/db.php';
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: index.php"); // Замените на путь к вашей странице входа
    exit();
}
// Получаем всю историю изменений с присоединением имени пользователя
$stmt = $pdo->query("
    SELECT h.*, u.fio 
    FROM task_history h 
    JOIN users u ON h.user_id = u.id 
    ORDER BY h.changed_at DESC
");
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>История изменений</title>
    <link rel="stylesheet" href="css/styles.css">
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
            <li><a href="index.php">Главная</a></li>
            <li><a href="articles/articles.php">Полезная информация</a></li>
            <li><a href="tasks.php">Задачи</a></li>
            <li><a href="admin.php">Администрирование</a></li>
        </ul>
    </nav>

    <h2>📜 История изменений</h2>

    <?php if (count($history) === 0): ?>
        <p>История изменений пуста.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Дата изменения</th>
                    <th>Название задачи</th>
                    <th>Событие</th>
                    <th>Пользователь</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $record): ?>
                    <tr>
                        <td><?php echo $record['changed_at']; ?></td>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td><?php echo htmlspecialchars($record['event']); ?></td>
                        <td><?php echo htmlspecialchars($record['fio']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
