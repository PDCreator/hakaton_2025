<?php
// edit.php
session_start();
require 'includes/db.php';
// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: index.php"); // Замените на путь к вашей странице входа
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assignee_fio = $_POST['assignee'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];

    $image = $task['image']; // по умолчанию текущее изображение

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['image']['name']);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = $_FILES['image']['name'];
        } else {
            echo "Ошибка загрузки изображения.";
        }
    }


    $username = $_SESSION['user'];
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE login = :username");
    $stmtUser->execute(['username' => $username]);
    $user_id = $stmtUser->fetchColumn();

    $stmtAssignee = $pdo->prepare("SELECT id FROM users WHERE fio = :fio");
    $stmtAssignee->execute(['fio' => $assignee_fio]);
    $assignee_id = $stmtAssignee->fetchColumn();

    if ($id && $user_id && $assignee_id) {
        $stmt = $pdo->prepare("UPDATE tasks SET title = :title, description = :description, assignee = :assignee, priority = :priority, due_date = :due_date, image = :image WHERE id = :id");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'assignee' => $assignee_id,
            'priority' => $priority,
            'due_date' => $due_date,
            'image' => $image,
            'id' => $id
        ]);

        $pdo->prepare("INSERT INTO task_history (task_id, user_id, event, title, changed_at) VALUES (:task_id, :user_id, :event, :title, NOW())")
            ->execute([
                'task_id' => $id,
                'user_id' => $user_id,
                'event' => 'Редактирование задачи',
                'title' => $title
            ]);

        header("Location: tasks.php");
        exit;
    } else {
        echo "Ошибка: отсутствуют данные ID задачи или ID пользователя.";
    }
} else {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT tasks.*, users.fio AS assignee_name FROM tasks LEFT JOIN users ON tasks.assignee = users.id WHERE tasks.id = :id");
        $stmt->execute(['id' => $id]);
        $task = $stmt->fetch();
    } else {
        echo "Ошибка: не передан ID задачи.";
        exit;
    }
}
// Получаем список всех пользователей
$users = $pdo->query("SELECT fio FROM users")->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать задачу</title>
    <script src="https://cdn.tiny.cloud/1/m2091v96csqx8le5h2smjv6va8o2okgd50ahor84g1wbi9os/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        tinymce.init({
            selector: '#description',
            menubar: false,
            plugins: 'lists link image',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link image',
            height: 300
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('form').addEventListener('submit', function(e) {
                const content = tinymce.get("description").getContent({ format: 'text' }).trim();
                if (content === "") {
                    alert("Пожалуйста, заполните описание задачи.");
                    e.preventDefault();
                }
            });
        });
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
    <h2>Редактировать задачу</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
        <input type="text" name="title" placeholder="Название задачи" value="<?php echo htmlspecialchars($task['title']); ?>" required><br>
        <textarea id="description" name="description" placeholder="Описание задачи"><?php echo htmlspecialchars($task['description']); ?></textarea><br>
        <input type="text" name="assignee" placeholder="Ответственный" list="assignees" value="<?php echo htmlspecialchars($task['assignee_fio'] ?? ''); ?>" required>
        <datalist id="assignees">
            <?php foreach ($users as $fio): ?>
                <option value="<?php echo htmlspecialchars($fio); ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <select name="priority" required>
            <option value="Низкий" <?php if ($task['priority'] === 'Низкий') echo 'selected'; ?>>Низкий</option>
            <option value="Средний" <?php if ($task['priority'] === 'Средний') echo 'selected'; ?>>Средний</option>
            <option value="Высокий" <?php if ($task['priority'] === 'Высокий') echo 'selected'; ?>>Высокий</option>
        </select><br>
        <input type="date" name="due_date" value="<?php echo $task['due_date']; ?>" required><br>
        <label for="image">Новое изображение:</label>
        <input type="file" name="image" id="image"><br>
        <button class="button-74" type="submit">Сохранить изменения</button>
    </form>
</body>
</html>
