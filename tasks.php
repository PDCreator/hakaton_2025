<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assignee = $_POST['assignee'];
    $priority = $_POST['priority'];
    $status = 'Текущая';
    $created_at = date('Y-m-d H:i:s');
    $due_date = $_POST['due_date'];
    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assignee, priority, status, created_at, due_date) VALUES (:title, :description, :assignee, :priority, :status, :created_at, :due_date)");
    $stmt->execute(['title' => $title, 'description' => $description, 'assignee' => $assignee, 'priority' => $priority, 'status' => $status, 'created_at' => $created_at, 'due_date' => $due_date]);
}

$current_tasks = $pdo->query("SELECT * FROM tasks WHERE status='Текущие'")->fetchAll();
$delayed_tasks = $pdo->query("SELECT * FROM tasks WHERE status='Отложенные'")->fetchAll();
$completed_tasks = $pdo->query("SELECT * FROM tasks WHERE status='Выполненные'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Задачи</title>
</head>
<script>
let selectedTaskId = null;
let currentStatus = "<?= $_GET['status'] ?? 'Текущие' ?>";

function openContextMenu(event, taskId) {
    event.preventDefault();
    selectedTaskId = taskId;

    const menu = document.getElementById('contextMenu');
    menu.style.top = event.pageY + 'px';
    menu.style.left = event.pageX + 'px';
    menu.style.display = 'block';

    // Показать/скрыть пункты по статусу
    menu.querySelectorAll('li').forEach(item => item.style.display = 'block');
    if (currentStatus === 'Текущие') {
        menu.querySelector("li[onclick*='Текущие']").style.display = 'none';
    } else if (currentStatus === 'Отложенные') {
        menu.querySelector("li[onclick*='Отложенные']").style.display = 'none';
    } else if (currentStatus === 'Выполненные') {
        menu.querySelector("li[onclick*='Выполненные']").style.display = 'none';
    }
}

document.addEventListener('click', () => {
    document.getElementById('contextMenu').style.display = 'none';
});

function changeStatus(newStatus) {
    fetch('change_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `task_id=${selectedTaskId}&status=${newStatus}`
    }).then(() => location.reload());
}

function deleteTask() {
    if (confirm("Вы уверены, что хотите удалить задачу?")) {
        fetch('delete_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `task_id=${selectedTaskId}`
        }).then(() => location.reload());
    }
}

function editTask() {
    window.location.href = `edit_task.php?id=${selectedTaskId}`;
}
</script>


<body>
    <nav>
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="articles.php">Полезная информация</a></li>
            <li><a href="tasks.php">Задачи</a></li>
            <li><a href="admin.php">Администрирование</a></li>
        </ul>
    </nav>
    <nav>
        <a href="?module=tasks">📋 Задачи</a>
        <a href="?module=reports">📊 Отчеты</a>
        <a href="?module=settings">⚙️ Настройки</a>
    </nav>
    
    <h2>Задачи</h2>
    
    <div class="tabs">
        <a href="?status=Текущие">Текущие задачи (<?php echo count($current_tasks); ?>)</a>
        <a href="?status=Отложенные">Отложенные задачи (<?php echo count($delayed_tasks); ?>)</a>
        <a href="?status=Выполненные">Выполненные задачи</a>
    </div>

    <form method="POST">
        <input type="text" name="title" placeholder="Название задачи" required>
        <textarea name="description" placeholder="Описание задачи" required></textarea>
        <input type="text" name="assignee" placeholder="Ответственный" required>
        <select name="priority" required>
            <option value="Низкий">Низкий</option>
            <option value="Средний">Средний</option>
            <option value="Высокий">Высокий</option>
        </select>
        <input type="date" name="due_date" required>
        <button type="submit">Создать задачу</button>
    </form>

    <div class="task-list">
    <?php 
    $tasks = $_GET['status'] == 'Отложенные' ? $delayed_tasks : ($_GET['status'] == 'Выполненные' ? $completed_tasks : $current_tasks);
    foreach ($tasks as $task): ?>
        <div class="task-card" data-id="<?= $task['id']; ?>" oncontextmenu="openContextMenu(event, <?= $task['id']; ?>)">
            <h3><?= $task['title']; ?></h3>
            <p><strong>Описание:</strong> <?= $task['description']; ?></p>
            <p><strong>Ответственный:</strong> <?= $task['assignee']; ?></p>
            <p><strong>Дата создания:</strong> <?= $task['created_at']; ?></p>
            <p><strong>Плановая дата решения:</strong> <?= $task['due_date']; ?></p>
            <p><strong>Приоритет:</strong> <?= $task['priority']; ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Контекстное меню -->
<div id="contextMenu" class="context-menu">
    <ul>
        <li onclick="changeStatus(selectedTaskId, 'Отложенные')">Отложить</li>
        <li onclick="changeStatus('Выполненные')">Выполнить</li>
        <li onclick="changeStatus('Текущие')">Вернуть в работу</li>
        <li onclick="editTask()">Редактировать</li>
        <li onclick="deleteTask()">Удалить</li>
    </ul>
</div>

<a href="history.php" class="history-link">🕒 История изменений</a>

</body>
</html>
