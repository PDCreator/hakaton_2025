<?php
session_start();
require 'includes/db.php';

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy(); // Уничтожаем сессию
    header('Location: index.php'); // Перенаправляем на главную страницу
    exit;
}

// Обработка авторизации
$errorLogin = ""; // Инициализация переменной для ошибок авторизации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'], $_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];

    if (strlen($password) < 8) {
        $errorLogin = "Пароль слишком короткий."; // Сообщение об ошибке
    } else {
        $password = md5($password); // Хешируем пароль

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
}

// Обработка регистрации
$errorRegister = ""; // Инициализация переменной для ошибок регистрации
$showRegisterModal = false; // Флаг для показа модального окна регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['regLogin'], $_POST['fio'], $_POST['regPassword'], $_POST['confirmPassword'])) {
    $regLogin = $_POST['regLogin'];
    $fio = $_POST['fio'];
    $regPassword = $_POST['regPassword']; // Не хешируем сразу
    $confirmPassword = $_POST['confirmPassword'];

    if (strlen($regPassword) < 8) {
        $errorRegister = "Пароль слишком короткий."; // Сообщение об ошибке
        $showRegisterModal = true; // Показываем модальное окно
    } elseif ($regPassword !== $confirmPassword) {
        $errorRegister = "Пароли не совпадают."; // Проверка на совпадение паролей
        $showRegisterModal = true; // Показываем модальное окно
    } else {
        $regPassword = md5($regPassword); // Хешируем пароль

        // Проверка на существование пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
        $stmt->execute(['login' => $regLogin]);

        if ($stmt->rowCount() > 0) {
            $errorRegister = "Такой пользователь уже существует."; // Сообщение об ошибке
            $showRegisterModal = true; // Показываем модальное окно
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
    <style>
                .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color:#fbeee0;
            margin: 15% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 400px;
            border-radius: 15px; /* Закругление углов модального окна */
        }
        .modal-content input {
            width: calc(100% - 20px); /* Ширина полей с учетом отступов */
            padding: 10px; /* Отступ внутри полей */
            margin-bottom: 15px; /* Отступ между полями */
            border: 1px solid #ccc; /* Граница полей */
            border-radius: 5px; /* Закругление углов полей */
        }
        .modal-content button {
            border-radius: 50px; /* Закругление углов кнопок */
            margin-left: 130px;
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
    
    <h1>Добро пожаловать в Веб-органайзер</h1>

    <?php if (isset($_SESSION['fio'])): ?>
        <p>Здравствуйте, <?php echo htmlspecialchars($_SESSION['fio']); ?>!</p>
        <button class="button-74" onclick="location.href='?logout'">Выйти</button>
    <?php else: ?>
        <p>Пожалуйста, войдите в систему.</p>
        <button class="button-74" onclick="openModal('loginModal')">Авторизация</button>
        <button class="button-74" onclick="openModal('registerModal')">Регистрация</button>
    <?php endif; ?>

    <!-- Модальное окно для авторизации -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span onclick="closeModal('loginModal')" style="cursor:pointer; float:right;">&times;</span>
            <h2>Авторизация</h2>
            <form method="POST" action="index.php">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required>
                
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required minlength="8">
                <p id="passwordError" style="color: red; display: none;">Пароль слишком короткий.</p>

                <button class="button-74" type="submit">Вход</button>
                <p><?php if (!empty($errorLogin)) echo $errorLogin; ?></p> <!-- Сообщение об ошибке -->
            </form>
        </div>
    </div>

    <!-- Модальное окно для регистрации -->
    <div id="registerModal" class="modal" style="<?php if ($showRegisterModal) echo 'display: block;'; ?>">
        <div class="modal-content">
            <span onclick="closeModal('registerModal')" style="cursor:pointer; float:right;">&times;</span>
            <h2>Регистрация</h2>
            <form method="POST" action="index.php">
                <label for="regLogin">Логин:</label>
                <input type="text" id="regLogin" name="regLogin" required pattern="[a-zA-Z]+">

                <label for="fio">ФИО:</label>
                <input type="text" id="fio" name="fio" required pattern="[А-Яа-яЁё\s]+">

                <label for="regPassword">Пароль:</label>
                <input type="password" id="regPassword" name="regPassword" required minlength="8">
                <p id="regPasswordError" style="color: red; display: none;">Пароль слишком короткий.</p>

                <label for="confirmPassword">Подтверждение пароля:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>

                <button class="button-74" type="submit">Регистрация</button>
                <p><?php if (!empty($errorRegister)) echo $errorRegister; ?></p> <!-- Сообщение об ошибке -->
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>