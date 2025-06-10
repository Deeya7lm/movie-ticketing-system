<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if username and password fields are empty

    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Please fill up the information';
    } else {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $query = "SELECT id, username, password, role FROM users WHERE username = '$username'";
        $result = $conn->query($query);

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit();
            }
        }
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CineSwift</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="form-container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password">
            </div>
            
            <button type="submit" class="form-submit">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>
