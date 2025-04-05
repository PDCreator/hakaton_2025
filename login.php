<?php
session_start();
require 'includes/db.php';

$error = ""; // Инициализация переменной для ошибок

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'], $_POST['password'])) {
        $login = $_POST['login'];
        $password = md5($_POST['password']); // Хешируем пароль

        // Проверка логина и пароля
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login AND password = :password");
        $stmt->execute(['login' => $login, 'password' => $password]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $_SESSION['user'] = $login;
            $_SESSION['fio'] = $user['fio']; // Сохраняем ФИО пользователя
            $_SESSION['role'] = $user['role']; // Сохраняем роль пользователя
            header('Location: index.php'); // Перенаправление на главную страницу
            exit;
        }
        } else {
            $error = "Некорректные данные. Проверьте логин и пароль."; // Сообщение об ошибке
        }
    } else {
        $error = "Пожалуйста, заполните все поля."; // Если поля пустые
    }

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Вход</title>
</head>
<body>
    <form id="loginForm" method="POST">
        <label for="login">Логин:</label>
        <input type="text" id="login" name="login" required>
        
        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Вход</button>
        <p><?php if (!empty($error)) echo $error; ?></p> <!-- Вывод сообщения об ошибке -->
        <a href="register.php">Регистрация</a>
    </form>
</body>
</html>