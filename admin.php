<?php
session_start();
require 'includes/db.php';

if ($_SESSION['role'] !== 'Администратор') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Логика для управления пользователями: добавление, редактирование, удаление
}

$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Администрирование</title>
</head>
<body>
    <h2>Управление пользователями</h2>
    <table>
        <thead>
            <tr>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['login']; ?></td>
                    <td><?php echo $user['fio']; ?></td>
                    <td><?php echo $user['role']; ?></td>
                    <td><?php echo $user['registration_date']; ?></td>
                    <td>
                        <button>Редактировать</button>
                        <button>Удалить</button>
                        <button>Сменить пароль</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>