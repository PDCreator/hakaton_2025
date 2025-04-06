<?php
session_start();
require 'includes/db.php';

// Проверка, вошел ли пользователь в систему
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Редирект на страницу входа
    exit();
}

// Получение роли пользователя из базы данных
$stmt = $pdo->prepare("SELECT role FROM users WHERE login = :login");
$stmt->execute(['login' => $_SESSION['user']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'Администратор') {
    header("Location: index.php"); // Редирект на страницу входа
    exit();
}

// Получение списка пользователей
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();

// Обработка фильтрации
$filterLogin = '';
$filterFio = '';
$filterRole = '';
$filterDate = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $filterLogin = $_POST['filterLogin'] ?? '';
    $filterFio = $_POST['filterFio'] ?? '';
    $filterRole = $_POST['filterRole'] ?? '';
    $filterDate = $_POST['filterDate'] ?? '';

    $query = "SELECT * FROM users WHERE login LIKE :login AND fio LIKE :fio";
    
    // Массив для параметров
    $params = [
        'login' => "%$filterLogin%",
        'fio' => "%$filterFio%"
    ];

    if ($filterRole) {
        $query .= " AND role = :role";
        $params['role'] = $filterRole;
    }
    if ($filterDate) {
        $query .= " AND registration_date = :date";
        $params['date'] = $filterDate;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
}

// Обработка удаления пользователя
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $_POST['delete']]);
    header('Location: admin.php');
    exit;
}

// Обработка смены пароля
if (isset($_POST['changePassword'])) {
    $userId = $_POST['userId'];
    $newPassword = $_POST['newPassword'];
    $currentPassword = md5($_POST['confirmPassword']); // Хешируем текущий пароль

    // Проверка текущего пароля
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $userData = $stmt->fetch();

    if ($userData['password'] !== $currentPassword) {
        $errorMessage = "Текущий пароль введен неверно"; // Сообщение об ошибке
        echo "<script>window.onload = function() { 
            document.getElementById('changePasswordModal').style.display = 'block'; 
            document.getElementById('passwordError').innerText = '$errorMessage'; 
        };</script>";
    } else {
        $hashedPassword = md5($newPassword);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);
        header('Location: admin.php');
        exit;
    }
}

// Обработка редактирования пользователя
if (isset($_POST['editUser'])) {
    $stmt = $pdo->prepare("UPDATE users SET login = :login, fio = :fio, role = :role WHERE id = :id");
    $stmt->execute([
        'login' => $_POST['login'],
        'fio' => $_POST['fio'],
        'role' => $_POST['role'],
        'id' => $_POST['userId']
    ]);
    header('Location: admin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Администрирование</title>
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
        input {

        }
        
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-container input, .filter-container select {
            margin-right: 10px; /* Отступ между полями фильтра */
            margin-bottom: 0; /* Убираем отступ снизу */
        }
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
        .error-message {
            color: red;
            margin-top: 10px;
        }
  .form1 {
    max-width: 1000px;
    display: flex;
}

.form1 input[type="text"], 
.form1 input[type="date"], 
.form1 select {
    flex: 1; /* Поля занимают равное пространство */
}

.form1 button {
    flex: 0 0 calc(20% - 30px); /* Кнопка занимает меньше места на 30px */
    margin-left: 10px; /* Отступ между кнопкой и предыдущими полями */
}

        .in1{
            margin-right: 15px;
        }
        .in2{
            margin-left: 15px;
            margin-right: 15px;
        }
        .in3{
            margin-right: 15px;
            width:250px;
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

    <h1>Управление пользователями</h1>

    <form class = "form1" method="POST" action="admin.php" class="filter-container">
        <input class= "in1" type="text" name="filterLogin" placeholder="Логин" value="<?php echo htmlspecialchars($filterLogin); ?>">
        <input class= "in3" type="text" name="filterFio" placeholder="ФИО" value="<?php echo htmlspecialchars($filterFio); ?>">
        <select name="filterRole">
            <option class= "in1" value="">Все роли</option>
            <option value="Пользователь" <?php if ($filterRole == 'Пользователь') echo 'selected'; ?>>Пользователь</option>
            <option value="Администратор" <?php if ($filterRole == 'Администратор') echo 'selected'; ?>>Администратор</option>
        </select>
        <input class= "in2" type="date" name="filterDate" value="<?php echo htmlspecialchars($filterDate); ?>">
        <button class="button-75" type="submit" name="filter">Фильтровать</button>
    </form>

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
                    <td><?php echo htmlspecialchars($user['login']); ?></td>
                    <td><?php echo htmlspecialchars($user['fio']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['registration_date']); ?></td>
                    <td>
                        <button onclick="openEditModal('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['login']); ?>', '<?php echo htmlspecialchars($user['fio']); ?>')">Редактировать</button>
                        <form method="POST" action="admin.php" style="display:inline;">
                            <input type="hidden" name="delete" value="<?php echo $user['id']; ?>">
                            <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?');">Удалить</button>
                        </form>
                        <button onclick="openChangePasswordModal(<?php echo $user['id']; ?>)">Сменить пароль</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Модальное окно редактирования пользователя -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span onclick="closeModal('editUserModal')" style="cursor:pointer; float:right;">&times;</span>
            <h2>Редактировать пользователя</h2>
            <form id="editUserForm" method="POST" action="admin.php">
                <input type="hidden" name="userId" id="editUserId">
                <label for="login">Логин:</label>
                <input type="text" id="editLogin" name="login" required>
                <label for="fio">ФИО:</label>
                <input type="text" id="editFio" name="fio" required>
                <label for="role">Роль:</label>
                <select id="editRole" name="role" required>
                    <option value="Пользователь">Пользователь</option>
                    <option value="Администратор">Администратор</option>
                </select>
                <button type="submit" name="editUser">Сохранить</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно смены пароля -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span onclick="closeModal('changePasswordModal')" style="cursor:pointer; float:right;">&times;</span>
            <h2>Сменить пароль</h2>
            <form id="changePasswordForm" method="POST" action="admin.php">
                <input type="hidden" name="userId" id="changeUserId">
                <label for="newPassword">Новый пароль:</label>
                <input type="password" name="newPassword" required>
                <label for="confirmPassword">Текущий пароль:</label>
                <input type="password" name="confirmPassword" required>
                <button type="submit" name="changePassword">Сохранить</button>
                <p id="passwordError" class="error-message"></p> <!-- Сообщение об ошибке -->
            </form>
        </div>
    </div>

    <script>
        function openEditModal(userId, login, fio) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editLogin').value = login; // Заполнение поля логина
            document.getElementById('editFio').value = fio; // Заполнение поля ФИО
            document.getElementById('editUserModal').style.display = 'block';
        }

        function openChangePasswordModal(userId) {
            document.getElementById('changeUserId').value = userId;
            document.getElementById('changePasswordModal').style.display = 'block';
            document.getElementById('passwordError').innerText = ''; // Сброс сообщения об ошибке
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