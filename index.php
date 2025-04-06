<?php
session_start();
require 'includes/db.php';

$errorLogin = ""; // Инициализация переменной для ошибок авторизации
$errorRegister = ""; // Инициализация переменной для ошибок регистрации
$showRegisterForm = false; // Флаг для показа формы регистрации

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy(); // Уничтожаем сессию
    header('Location: index.php'); // Перенаправляем на главную страницу
    exit;
}

// Обработка авторизации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'], $_POST['password'])) {
    $login = $_POST['login'];
    $password = md5($_POST['password']); // Хешируем пароль

    // Проверка логина и пароля
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login AND password = :password");
    $stmt->execute(['login' => $login, 'password' => $password]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        $_SESSION['user'] = $login;
        $_SESSION['fio'] = $user['fio']; // Сохраняем ФИО пользователя
        header('Location: index.php'); // Перенаправление на главную страницу
        exit;
    } else {
        $errorLogin = "Некорректные данные. Проверьте логин и пароль."; // Сообщение об ошибке
    }
}

// Обработка регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['regLogin'], $_POST['fio'], $_POST['regPassword'], $_POST['confirmPassword'])) {
    $regLogin = $_POST['regLogin'];
    $fio = $_POST['fio'];
    $regPassword = $_POST['regPassword']; // Не хешируем сразу
    $confirmPassword = $_POST['confirmPassword'];

    if (strlen($regPassword) < 8) {
        $errorRegister = "Пароль слишком короткий."; // Сообщение об ошибке
        $showRegisterForm = true; // Показываем форму регистрации
    } elseif ($regPassword !== $confirmPassword) {
        $errorRegister = "Пароли не совпадают."; // Проверка на совпадение паролей
        $showRegisterForm = true; // Показываем форму регистрации
    } else {
        $regPassword = md5($regPassword); // Хешируем пароль

        // Проверка на существование пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
        $stmt->execute(['login' => $regLogin]);

        if ($stmt->rowCount() > 0) {
            $errorRegister = "Такой пользователь уже существует."; // Сообщение об ошибке
            $showRegisterForm = true; // Показываем форму регистрации
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
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Главная страница</title>
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
    
    <h1>Добро пожаловать в Веб-органайзер</h1>

    <?php if (isset($_SESSION['fio'])): ?>
        <p>Здравствуйте, <?php echo htmlspecialchars($_SESSION['fio']); ?>!</p>
        <button class="button-74" onclick="location.href='?logout'">Выйти</button>
    <?php else: ?>
        <p>Пожалуйста, войдите в систему.</p>
        
        <div id="loginForm">
            <form method="POST" action="index.php">
                <label for="login">Логин:</label>
                <input class="main_input" type="text" id="login" name="login" required>
                
                <label for="password">Пароль:</label>
                <input class="main_input" type="password" id="password" name="password" required>

                <button class="button-74" type="submit">Вход</button>
                <p><?php if (!empty($errorLogin)) echo $errorLogin; ?></p> <!-- Сообщение об ошибке -->
            </form>
            <button class="button-74" onclick="toggleRegisterForm()">Зарегистрироваться</button>
        </div>

        <div id="registerForm" style="display: <?php echo $showRegisterForm ? 'block' : 'none'; ?>;">
            <h2>Регистрация</h2>
            <form method="POST" action="index.php">
                <label for="regLogin">Логин:</label>
                <input class="main_input" type="text" id="regLogin" name="regLogin" required pattern="[a-zA-Z]+">

                <label for="fio">ФИО:</label>
                <input class="main_input" type="text" id="fio" name="fio" required pattern="[А-Яа-яЁё\s]+">

                <label for="regPassword">Пароль:</label>
                <input class="main_input" type="password" id="regPassword" name="regPassword" required minlength="8">

                <label for="confirmPassword">Подтверждение пароля:</label>
                <input class="main_input" type="password" id="confirmPassword" name="confirmPassword" required>

                <button class="button-74" type="submit">Регистрация</button>
                <p><?php if (!empty($errorRegister)) echo $errorRegister; ?></p> <!-- Сообщение об ошибке -->
            </form>
        </div>
    <?php endif; ?>

    <script>
        function toggleRegisterForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            if (loginForm.style.display !== 'none') {
                loginForm.style.display = 'none';  // Скрыть форму авторизации
                registerForm.style.display = 'block'; // Показать форму регистрации
            } else {
                loginForm.style.display = 'block'; // Показать форму авторизации
                registerForm.style.display = 'none'; // Скрыть форму регистрации
            }
        }
    </script>
</body>
</html>