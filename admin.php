<?php
session_start();
require 'includes/db.php';

// Проверка, вошел ли пользователь в систему
if (!isset($_SESSION['user'])) {
    die("У вас нет доступа к этому модулю.");
}

// Получение роли пользователя из базы данных
$stmt = $pdo->prepare("SELECT role FROM users WHERE login = :login");
$stmt->execute(['login' => $_SESSION['user']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'Администратор') {
    die("У вас нет доступа к этому модулю.");
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
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete']]);
    header('Location: admin.php');
    exit;
}

// Обработка изменения пароля
if (isset($_POST['changePassword'])) {
    $newPassword = md5($_POST['newPassword']);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->execute(['password' => $newPassword, 'id' => $_POST['userId']]);
    header('Location: admin.php');
    exit;
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
        input[type="text"], input[type="date"], textarea, select {
            display: block;
            margin: 10px 0;
            padding: 10px;
            width: 80%;
            max-width: 400px; /* Ограничение максимальной ширины */
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

<form method="POST" action="admin.php">
        <input type="text" name="filterLogin" placeholder="Логин" value="<?php echo htmlspecialchars($filterLogin); ?>">
        <input type="text" name="filterFio" placeholder="ФИО" value="<?php echo htmlspecialchars($filterFio); ?>">
        <select name="filterRole">
            <option value="">Все роли</option>
            <option value="Пользователь" <?php if ($filterRole == 'Пользователь') echo 'selected'; ?>>Пользователь</option>
            <option value="Администратор" <?php if ($filterRole == 'Администратор') echo 'selected'; ?>>Администратор</option>
        </select>
        <input type="date" name="filterDate" value="<?php echo htmlspecialchars($filterDate); ?>">
        <button type="submit" name="filter">Фильтровать</button>
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
                        <a href="admin.php?delete=<?php echo $user['id']; ?>">Удалить</a>
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
                <label for="confirmPassword">Подтверждение пароля:</label>
                <input type="password" name="confirmPassword" required>
                <button type="submit" name="changePassword">Сохранить</button>
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