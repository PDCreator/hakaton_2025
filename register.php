<?php
session_start();
require 'includes/db.php';

$error = ""; // Инициализация переменной для ошибок

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'], $_POST['fio'], $_POST['password'], $_POST['confirmPassword'])) {
        $regLogin = $_POST['login'];
        $fio = $_POST['fio'];
        $regPassword = md5($_POST['password']); // Хешируем пароль
        $confirmPassword = $_POST['confirmPassword'];

        // Проверка на существование пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
        $stmt->execute(['login' => $regLogin]);

        if ($stmt->rowCount() > 0) {
            $error = "Такой пользователь уже существует."; // Сообщение об ошибке
        } elseif ($regPassword !== md5($confirmPassword)) {
            $error = "Пароли не совпадают."; // Проверка на совпадение паролей
        } else {
            // Вставка данных в базу
            $stmt = $pdo->prepare("INSERT INTO users (login, fio, password, role) VALUES (:login, :fio, :password, 'Пользователь')");
            $stmt->execute(['login' => $regLogin, 'fio' => $fio, 'password' => $regPassword]);

            // Автоматическая авторизация
            $_SESSION['user'] = $regLogin;
            $_SESSION['fio'] = $fio; // Сохраняем ФИО пользователя
            header('Location: index.php'); // Перенаправление на главную страницу
            exit;
        }
    } else {
        $error = "Пожалуйста, заполните все поля."; // Если поля пустые
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
    <form method="POST" action="register.php">
        <label for="regLogin">Логин:</label>
        <input type="text" id="regLogin" name="login" required pattern="[a-zA-Z]+">

        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio" required pattern="[А-Яа-яЁё\s]+">

        <label for="regPassword">Пароль:</label>
        <input type="password" id="regPassword" name="password" required pattern="[a-zA-Z0-9!@#$%^&*]+">

        <label for="confirmPassword">Подтверждение пароля:</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required>

        <button type="submit">Регистрация</button>
        <p><?php if (!empty($error)) echo $error; ?></p> <!-- Вывод сообщения об ошибке -->
    </form>
</body>
</html>