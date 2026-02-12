<?php
$conn = new mysqli("localhost", "root", "", "blog");
if ($conn->connect_error) {
    die("Database connection failed");
}

$limit = 3; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM posts");
$totalRow = $totalQuery->fetch_assoc();
$totalPosts = $totalRow['total'];
$totalPages = ceil($totalPosts / $limit);

$stmt = $conn->prepare("SELECT * FROM posts ORDER BY id DESC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>All Blog Posts</title>
    <link rel="stylesheet" href="css/view_post.css?v=4">
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <span class="logo">BlogApp</span>
    </div>
    <div class="nav-right">
        <a href="index.php" class="btn-post">Login</a>
    </div>
</nav>

<div class="container">
    <h2>All Blog Posts</h2>

    <?php if ($result->num_rows == 0): ?>
        <p class="no-posts">No posts available</p>
    <?php endif; ?>

    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="post-card">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
        </div>
    <?php endwhile; ?>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>"
                   class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    © 2026 BlogApp. Harshvardhan Patil.
</footer>

</body>
</html>
