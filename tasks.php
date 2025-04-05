<?php
session_start();
require 'includes/db.php';
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: index.php"); // Замените на путь к вашей странице входа
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $task_id = $_POST['task_id'];
        $new_status = $_POST['new_status'];
        $task = $pdo->prepare("SELECT title FROM tasks WHERE id = :id");
        $task->execute(['id' => $task_id]);
        $task_title = $task->fetchColumn();

        $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id")->execute([
            'status' => $new_status,
            'id' => $task_id
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assignee_fio = $_POST['assignee'];
    $priority = $_POST['priority'];
    $status = $_GET['status'] ?? 'Текущие';
    $allowed_statuses = ['Текущие', 'Отложенные'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'Текущие';
    }

    $created_at = date('Y-m-d H:i:s');
    $due_date = $_POST['due_date'];

    // Получаем ID назначенного пользователя по его ФИО
    $stmtAssignee = $pdo->prepare("SELECT id FROM users WHERE fio = :fio");
    $stmtAssignee->execute(['fio' => $assignee_fio]);
    $assignee_id = $stmtAssignee->fetchColumn();

    // Получаем ID текущего пользователя из сессии
    $username = $_SESSION['user'];
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE login = :username");
    $stmtUser->execute(['username' => $username]);
    $user_id = $stmtUser->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assignee, priority, status, created_at, due_date) 
        VALUES (:title, :description, :assignee, :priority, :status, :created_at, :due_date)");
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'assignee' => $assignee_id,
        'priority' => $priority,
        'status' => $status,
        'created_at' => $created_at,
        'due_date' => $due_date
    ]);

    // Получаем ID только что вставленной задачи
    $task_id = $pdo->lastInsertId();

    // Вставляем запись в историю
    $stmtHistory = $pdo->prepare("INSERT INTO task_history (task_id, user_id, event, title, changed_at) 
        VALUES (:task_id, :user_id, :event, :title, NOW())");
    $stmtHistory->execute([
        'task_id' => $task_id,
        'user_id' => $user_id,
        'event' => 'Создание задачи',
        'title' => $title
    ]);
}

function getTasksByStatus($pdo, $status) {
    $query = "
        SELECT tasks.*, users.fio AS assignee_name
        FROM tasks
        LEFT JOIN users ON tasks.assignee = users.id
        WHERE tasks.status = :status
    ";

    $params = ['status' => $status];

    if (!empty($_GET['search_title'])) {
        $query .= " AND tasks.title LIKE :title";
        $params['title'] = '%' . $_GET['search_title'] . '%';
    }

    if (!empty($_GET['search_date'])) {
        $query .= " AND DATE(tasks.created_at) = :created";
        $params['created'] = $_GET['search_date'];
    }

    if (!empty($_GET['search_assignee'])) {
        $query .= " AND users.fio LIKE :fio";
        $params['fio'] = '%' . $_GET['search_assignee'] . '%';
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$current_tasks = getTasksByStatus($pdo, 'Текущие');
$delayed_tasks = getTasksByStatus($pdo, 'Отложенные');
$completed_tasks = getTasksByStatus($pdo, 'Выполненные');

// Получаем список всех пользователей
$users = $pdo->query("SELECT fio FROM users")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <script src="https://cdn.tiny.cloud/1/m2091v96csqx8le5h2smjv6va8o2okgd50ahor84g1wbi9os/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: 'textarea[name="description"]',
        plugins: 'lists image link code',
        toolbar: 'undo redo | bold italic underline | numlist bullist | image link | code',
        menubar: false
    });
    </script>

    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Задачи</title>
    <style>
        .task-card {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 10px;
            position: relative;
            cursor: pointer;
        }

        .task-card .menu-button {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .context-menu {
            display: none;
            position: absolute;
            top: 20px;         /* немного отступ сверху */
            right: 35px;       /* появляется справа сверху */
            background: #fff;
            border: 1px solid #ccc;
            z-index: 100;
            padding: 5px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            min-width: 160px;
        }

        .context-menu button { 
            display: block; 
            background: black; 
            color: white; 
            border: none; 
            padding: 5px; 
            cursor: pointer; 
            text-align: left; 
            width: 100%; 
        }
    </style>
    <script>
        function toggleMenu(id) {
            const targetMenu = document.getElementById('menu-' + id);
            const isVisible = targetMenu.style.display === 'block';

            // Сначала скрываем все меню
            document.querySelectorAll('.context-menu').forEach(menu => menu.style.display = 'none');

            // Если меню не было видно — показываем его
            if (!isVisible) {
                targetMenu.style.display = 'block';
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('menu-button')) {
                document.querySelectorAll('.context-menu').forEach(menu => menu.style.display = 'none');
            }
        });
        function toggleDetails(card) {
            const details = card.querySelector('.task-details');
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
        }
    </script>
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
    <?php
        $status = $_GET['status'] ?? 'Текущие';
        $titles = [
            'Текущие' => 'Текущие задачи',
            'Отложенные' => 'Отложенные задачи',
            'Выполненные' => 'Выполненные задачи'
        ];
        ?>
        <h2><?php echo $titles[$status] ?? 'Задачи'; ?></h2>


    <div class="tabs">
        <a href="?status=Текущие">Текущие задачи (<?php echo count($current_tasks); ?>)</a>
        <a href="?status=Отложенные">Отложенные задачи (<?php echo count($delayed_tasks); ?>)</a>
        <a href="?status=Выполненные">Выполненные задачи</a>
        <a href="history.php">📜 История изменений</a>
    </div>
    
    <?php if ($status !== 'Выполненные'): ?>
        <form method="POST">
            <input type="hidden" name="create_task" value="1">
            <input type="text" name="title" placeholder="Название задачи" required>
            <textarea id="description" name="description" placeholder="Описание задачи"></textarea>

            <input type="text" name="assignee" placeholder="Ответственный (ФИО)" list="assignees" required>
            <datalist id="assignees">
                <?php foreach ($users as $fio): ?>
                    <option value="<?php echo htmlspecialchars($fio); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <select name="priority" required>
                <option value="Низкий">Низкий</option>
                <option value="Средний">Средний</option>
                <option value="Высокий">Высокий</option>
            </select>
            <?php $today = date('Y-m-d'); ?>
                <input type="date" name="due_date" min="<?php echo $today; ?>" required>
            <button type="submit">Создать задачу</button>
        </form>
    <?php endif; ?>

    <div class="task-list">
        <!-- 🔍 Форма поиска -->
        <form method="GET" style="margin: 20px 0;">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET['status'] ?? 'Текущие'); ?>">
    
    <input type="text" name="search_title" placeholder="Поиск по названию"
        value="<?php echo htmlspecialchars($_GET['search_title'] ?? ''); ?>">

    <input type="date" name="search_date" placeholder="Дата создания"
        value="<?php echo htmlspecialchars($_GET['search_date'] ?? ''); ?>">

    <input type="text" name="search_assignee" placeholder="Ответственный (ФИО)"
        value="<?php echo htmlspecialchars($_GET['search_assignee'] ?? ''); ?>">

    <button type="submit">🔍 Поиск</button>
    <a href="tasks.php?status=<?php echo urlencode($_GET['status'] ?? 'Текущие'); ?>" style="margin-left: 10px;">
        ❌ Сбросить фильтр
    </a>
</form>

    <?php
        function formatFriendlyDate($dateStr, $withTime = false) {
            $months = [
                1 => 'января', 2 => 'февраля', 3 => 'марта',
                4 => 'апреля', 5 => 'мая', 6 => 'июня',
                7 => 'июля', 8 => 'августа', 9 => 'сентября',
                10 => 'октября', 11 => 'ноября', 12 => 'декабря'
            ];

            $timestamp = strtotime($dateStr);
            $day = date('j', $timestamp);
            $month = $months[(int)date('n', $timestamp)];
            $year = date('Y', $timestamp);

            $formatted = "$day $month $year";

            if ($withTime) {
                $time = date('H:i', $timestamp);
                $formatted .= " в $time";
            }

            return $formatted;
        }
    ?>


    <?php 
        $status = $_GET['status'] ?? 'Текущие';
        $tasks = $status === 'Отложенные' ? $delayed_tasks : ($status === 'Выполненные' ? $completed_tasks : $current_tasks);        
        foreach ($tasks as $task): ?>
            <div class="task-card" onclick="toggleDetails(this)">
    <div class="task-summary">
        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
        <p><strong>Ответственный:</strong> <?php echo htmlspecialchars($task['assignee_name'] ?? 'Неизвестно'); ?></p>
        <p><strong>Срок сдачи:</strong> <?php echo formatFriendlyDate($task['due_date']); ?></p>

        <button class="menu-button" onclick="event.stopPropagation(); toggleMenu(<?php echo $task['id']; ?>)">⋮</button>
        <div class="context-menu" id="menu-<?php echo $task['id']; ?>">
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <?php if ($status === 'Текущие'): ?>
                    <button name="new_status" value="Отложенные">Отложить</button>
                    <button name="new_status" value="Выполненные">Выполнить</button>
                <?php elseif ($status === 'Отложенные'): ?>
                    <button name="new_status" value="Текущие">Вернуть в работу</button>
                    <button name="new_status" value="Выполненные">Выполнить</button>
                <?php elseif ($status === 'Выполненные'): ?>
                    <button name="new_status" value="Текущие">Вернуть в работу</button>
                <?php endif; ?>
                <button formaction="edit.php" formmethod="GET" name="id" value="<?php echo $task['id']; ?>">Редактировать</button>
                <button formaction="delete.php" formmethod="POST" name="id" value="<?php echo $task['id']; ?>">Удалить</button>
            </form>
        </div>
    </div>

    <div class="task-details" style="display: none;">
        <p><strong>Описание:</strong> <?php echo $task['description']; ?></p>
        <p><strong>Дата создания:</strong> <?php echo formatFriendlyDate($task['created_at'], true); ?></p>
        <p><strong>Приоритет:</strong> <?php echo $task['priority']; ?></p>
    </div>
</div>
        <?php endforeach; ?>
    </div>
</body>
</html>
