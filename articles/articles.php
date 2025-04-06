<?php
session_start();
require '../includes/db.php'; // Обновленный путь

// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    // Если пользователь не авторизован, редирект на страницу авторизации
    header("Location: ../index.php");
    exit();
}

// Получение всех статей, которые не удалены, с последним пользователем, который взаимодействовал с каждой статьей
$stmt = $pdo->query("
    SELECT a.*, u.fio, (
        SELECT u2.fio 
        FROM article_history h 
        JOIN users u2 ON h.user_id = u2.id 
        WHERE h.article_id = a.id 
        ORDER BY h.change_date DESC 
        LIMIT 1
    ) AS last_editor 
    FROM articles a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.deleted = 0 
    ORDER BY a.created_at DESC
");
$articles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статьи</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const articleTiles = document.querySelectorAll('.article-tile');

            articleTiles.forEach(tile => {
                tile.addEventListener('click', function(e) {
                    // Предотвращаем срабатывание при клике на кнопки внутри плитки
                    if (e.target.closest('.edit-button') || e.target.closest('.delete-button')) return;

                    const isExpanded = tile.classList.contains('expanded');

                    // Свернуть все плитки
                    articleTiles.forEach(t => t.classList.remove('expanded'));

                    // Если клик был по уже развернутой — не разворачиваем снова
                    if (!isExpanded) {
                        tile.classList.add('expanded');
                        const tileTop = tile.getBoundingClientRect().top + window.scrollY;
                        window.scrollTo({ top: tileTop - 100, behavior: 'smooth' }); // 100 — отступ сверху
                    }

                    e.stopPropagation();
                });
            });

            // Свернуть все при клике вне плитки
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.article-tile')) {
                    articleTiles.forEach(t => t.classList.remove('expanded'));
                }
            });
        });
    </script>

    <nav>
        <ul>
            <li><a href="../index.php">Главная</a></li>
            <li><a href="../articles/articles.php">Полезная информация</a></li>
            <li><a href="../tasks.php">Задачи</a></li>
            <li><a href="../admin.php">Администрирование</a></li>
        </ul>
    </nav>

    <h1>Список статей</h1>

    <!-- Кнопки для создания статьи и просмотра истории изменений -->
    <div class="action-buttons">
        <a href="create_article.php" class="button-74">Создать статью</a>
        <a href="history.php" class="button-74">История изменений</a>
    </div>

    <div class="articles">
        <?php if (empty($articles)): ?>
            <p>Нет статей для отображения.</p>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
            <div class="article-tile" data-article-id="<?php echo $article['id']; ?>">
            <h2><?php echo htmlspecialchars($article['title']); ?></h2>
            <p>Дата создания: <?php echo htmlspecialchars($article['created_at']); ?></p>
            <p>Последний редактор: <?php echo htmlspecialchars($article['last_editor'] ?: $article['fio']); ?></p>
            <?php if ($article['image']): ?>
                <img src="../uploads/<?php echo htmlspecialchars($article['image']); ?>" alt="Изображение статьи" class="article-image" />
            <?php endif; ?>
            <div class="article-full-content">
                <?php echo nl2br(htmlspecialchars($article['content'] ?? 'Описание отсутствует.')); ?>
            </div>
            <div class="article-actions">
                <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="edit-button">Редактировать</a>
                <a href="delete_article.php?id=<?php echo $article['id']; ?>" class="delete-button" onclick="return confirm('Вы уверены, что хотите удалить эту статью?');">Удалить</a>
            </div>

    </div>
<?php endforeach; ?>

        <?php endif; ?>
    </div>
</body>


</html>