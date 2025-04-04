<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Проверка логина и пароля
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login AND password = :password");
    $stmt->execute(['login' => $login, 'password' => md5($password)]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['user'] = $login;
        header('Location: index.php');
        exit;
    } else {
        $error = "Неверный логин или пароль.";
    }
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
        <p><?php if (isset($error)) echo $error; ?></p>
        <a href="register.php">Регистрация</a>
    </form>
</body>
</html>