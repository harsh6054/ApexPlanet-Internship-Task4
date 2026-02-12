<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "blog");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$success = "";
$role = $_SESSION['role'] ?? 'editor';

/* ---------------- ADD POST ---------------- */
if (isset($_POST['add']) && ($role === 'admin' || $role === 'editor')) {

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {

        $stmt = $conn->prepare(
            "INSERT INTO posts (title, content) VALUES (?, ?)"
        );

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $title, $content);

        if ($stmt->execute()) {
            $success = "Post added successfully";
        } else {
            die("Execute failed: " . $stmt->error);
        }
    } else {
        $success = "Title and Content cannot be empty";
    }
}

/* ---------------- DELETE POST ---------------- */
if (isset($_GET['delete']) && $role === 'admin') {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

/* ---------------- UPDATE POST ---------------- */
if (isset($_POST['update']) && ($role === 'admin' || $role === 'editor')) {

    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {

        $stmt = $conn->prepare(
            "UPDATE posts SET title = ?, content = ? WHERE id = ?"
        );

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssi", $title, $content, $id);
        $stmt->execute();

        $success = "Post updated successfully";
    }
}

/* ---------------- EDIT FETCH ---------------- */
$editPost = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editPost = $stmt->get_result()->fetch_assoc();
}

/* ---------------- SEARCH ---------------- */
$search = "";
$showPosts = false;
$result = null;

if (isset($_GET['search']) && $_GET['search'] !== "") {

    $search = trim($_GET['search']);
    $showPosts = true;

    $stmt = $conn->prepare(
        "SELECT * FROM posts WHERE title LIKE ? ORDER BY id DESC"
    );

    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();

    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Posts</title>
    <link rel="stylesheet" href="css/post.css?v=11">
</head>
<body>

<?php if ($success) { ?>
<script>
alert("<?php echo $success; ?>");
</script>
<?php } ?>

<div class="navbar">
    <div class="nav-left">
        <h2>BlogApp</h2>
    </div>

    <div class="nav-center">
        <form method="get" class="nav-search">
            <input type="text" name="search"
                   placeholder="Search post by title..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="nav-right">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
        <a href="index.php?logout=1" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">

    <h2><?php echo $editPost ? "Edit Post" : "Add New Post"; ?></h2>

    <form method="post" onsubmit="return validatePost()">

        <?php if ($editPost) { ?>
            <input type="hidden" name="id" value="<?php echo $editPost['id']; ?>">
        <?php } ?>

        <input type="text" name="title" id="title"
               placeholder="Post Title"
               value="<?php echo htmlspecialchars($editPost['title'] ?? ''); ?>" required>

        <textarea name="content" id="content"
                  placeholder="Post Content" required><?php
            echo htmlspecialchars($editPost['content'] ?? '');
        ?></textarea>

        <button type="submit" name="<?php echo $editPost ? 'update' : 'add'; ?>">
            <?php echo $editPost ? 'Update Post' : 'Add Post'; ?>
        </button>

    </form>

</div>

<?php if ($showPosts) { ?>
<div class="search-section">
    <h2>Search Results</h2>

    <div class="post-row">
        <?php
        if ($result->num_rows === 0) {
            echo "<p>No posts found</p>";
        }

        while ($row = $result->fetch_assoc()) {
        ?>

            <div class="post">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p><?php echo htmlspecialchars($row['content']); ?></p>

                <div class="actions">
                    <a href="?edit=<?php echo $row['id']; ?>">Edit</a>

                    <?php if ($role === 'admin') { ?>
                        <a href="?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Delete this post?')">
                           Delete
                        </a>
                    <?php } ?>
                </div>
            </div>

        <?php } ?>
    </div>
</div>
<?php } ?>

<footer class="footer">
    © 2026 BlogApp. Harshvardhan Patil.
</footer>

<script>
function validatePost() {

    const title = document.getElementById('title');
    const content = document.getElementById('content');

    if (title.value.trim().length < 3 || content.value.trim().length < 3) {
        alert("Title and content must be at least 3 characters");
        return false;
    }

    return true;
}
</script>

</body>
</html>