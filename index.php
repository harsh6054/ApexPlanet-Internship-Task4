<?php
$conn = new mysqli("localhost", "root", "", "blog");
if ($conn->connect_error) {
    die("Database Connection Failed");
}

session_start();

$register_error = "";
$login_error = "";

if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];   // ROLE ADDED

    if (strlen($username) < 3 || strlen($password) < 3) {
        $register_error = "Username & Password must be at least 3 characters!";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $register_error = "Username already exists!";
        } else {

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare(
                "INSERT INTO users (username,password,role) VALUES (?,?,?)"
            );
            $insert->bind_param("sss", $username,$passwordHash,$role);
            $insert->execute();

            $register_error = "Registration successful! Please login.";
            echo "<script>window.onload=function(){showLogin();}</script>";
        }

        $stmt->close();
    }
}

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT username,password,role FROM users WHERE username=?"
    );
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password,$user['password'])) {

        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];   // ROLE SESSION

        header("Location: post.php");
        exit();

    } else {
        $login_error = "Invalid username or password!";
    }

    $stmt->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>User Authentication</title>
<link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php if (!isset($_SESSION['user'])) { ?>

<nav class="navbar">
    <div class="nav-left">
        <span class="logo">BlogApp</span>
    </div>
    <div class="nav-right">
        <a href="view_posts.php" class="btn-post">Show Posts</a>
    </div>
</nav>

<div class="container">

<div id="loginForm">
<h2>Login</h2>

<?php if ($login_error) echo "<p class='error'>$login_error</p>"; ?>

<form method="post" onsubmit="return validateAuth(this)">

<div class="input-group">
<input type="text" name="username" required>
<label>Username</label>
</div>

<div class="input-group">
<input type="password" name="password" required>
<label>Password</label>
</div>

<button name="login">Login</button>

</form>

<div class="switch">
Not registered? <a onclick="showRegister()">Create account</a>
</div>
</div>

<div id="registerForm">
<h2>Register</h2>

<?php if ($register_error) echo "<p class='error'>$register_error</p>"; ?>

<form method="post" onsubmit="return validateAuth(this)">

<div class="input-group">
<input type="text" name="username" required>
<label>Username</label>
</div>

<div class="input-group">
<input type="password" name="password" required>
<label>Password</label>
</div>

<div class="input-group">
<select name="role" class="role-select" required>
<option value="">Select Role</option>
<option value="admin">Admin</option>
<option value="user">User</option>
</select>
</div>

<button name="register">Register</button>

</form>

<div class="switch">
Already have an account? <a onclick="showLogin()">Login</a>
</div>
</div>

</div>

<footer class="footer">
<p>© 2026 BlogApp. Harshvardhan Patil.</p>
</footer>

<?php } ?>

<script>
function showRegister(){
    loginForm.style.display="none";
    registerForm.style.display="block";
}
function showLogin(){
    registerForm.style.display="none";
    loginForm.style.display="block";
}

function validateAuth(f){

    let inputs = f.querySelectorAll("input,select");

    for(let i of inputs){

        if(i.value.trim().length < 1){
            alert("All fields required");
            return false;
        }

        if(i.name!="role" && i.value.trim().length < 3){
            alert("Minimum 3 characters required");
            return false;
        }
    }

    return true;
}
</script>

</body>
</html>
