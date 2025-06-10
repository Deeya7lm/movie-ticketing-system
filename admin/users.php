<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $id = (int)$_POST['id'];
                // Don't allow deleting self
                if ($id === $_SESSION['user_id']) {
                    $error = "You cannot delete your own account!";
                } else {
                    if ($conn->query("DELETE FROM users WHERE id = $id")) {
                        $success = "User deleted successfully!";
                    } else {
                        $error = "Error deleting user: " . $conn->error;
                    }
                }
                break;

            case 'toggle_role':
                $id = (int)$_POST['id'];
                $new_role = $_POST['role'] === 'admin' ? 'user' : 'admin';
                
                // Don't allow removing own admin rights
                if ($id === $_SESSION['user_id'] && $new_role !== 'admin') {
                    $error = "You cannot remove your own admin rights!";
                } else {
                    if ($conn->query("UPDATE users SET role = '$new_role' WHERE id = $id")) {
                        $success = "User role updated successfully!";
                    } else {
                        $error = "Error updating user role: " . $conn->error;
                    }
                }
                break;
        }
    }
}

// Get all users except current admin
$users = $conn->query("
    SELECT id, username, email, role, created_at,
    (SELECT COUNT(*) FROM bookings WHERE user_id = users.id) as total_bookings
    FROM users 
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - CineSwift Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2>CineSwift Admin</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="movies.php">Manage Movies</a></li>
                    <li><a href="showtimes.php">Manage Showtimes</a></li>
                    <li><a href="bookings.php">View Bookings</a></li>
                    <li><a href="users.php" class="active">Manage Users</a></li>
                    <li><a href="../index.php">Back to Site</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <h1>Manage Users</h1>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Total Bookings</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'success' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['total_bookings']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_role">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                            <button type="submit" class="btn primary" onclick="return confirm('Are you sure you want to change this user\'s role?')">
                                                Make <?php echo $user['role'] === 'admin' ? 'User' : 'Admin'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <em>Current User</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
