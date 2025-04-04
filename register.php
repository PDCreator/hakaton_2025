<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $regLogin = $_POST['login'];
    $fio = $_POST['fio'];
    $regPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
    $stmt->execute(['login' => $regLogin]);

    if ($stmt->rowCount() == 0 && $regPassword === $confirmPassword) {
        $stmt = $pdo->prepare("INSERT INTO users (login, fio, password, role) VALUES (:login, :fio, :password, 'Пользователь')");
        $stmt->execute(['login' => $regLogin, 'fio' => $fio, 'password' => md5($regPassword)]);
        header('Location: login.php');
        exit;
    } else {
        $error = "Ошибка регистрации.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Регистрация</title>
</head>
<body>
    <form id="registrationForm" method="POST">
        <label for="regLogin">Логин:</label>
        <input type="text" id="regLogin" name="login" required pattern="[a-zA-Z]+">

        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio" required pattern="[А-Яа-яЁё\s]+">

        <label for="regPassword">Пароль:</label>
        <input type="password" id="regPassword" name="password" required pattern="[a-zA-Z0-9!@#$%^&*]+">

        <label for="confirmPassword">Подтверждение пароля:</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required>

        <button type="submit">Регистрация</button>
        <p><?php if (isset($error)) echo $error; ?></p>
    </form>
</body>
</html>