<header>
    <nav>
        <div class="logo">
            <h1>CineSwift</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" <?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'class="active"' : ''; ?>>Home</a></li>
            <li><a href="movies.php" <?php echo ($_SERVER['PHP_SELF'] == '/movies.php') ? 'class="active"' : ''; ?>>Movies</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li><a href="bookings.php" <?php echo ($_SERVER['PHP_SELF'] == '/bookings.php') ? 'class="active"' : ''; ?>>All Bookings</a></li>
                    <li><a href="my-bookings.php" <?php echo ($_SERVER['PHP_SELF'] == '/my-bookings.php') ? 'class="active"' : ''; ?>>My Bookings</a></li>
                    <li><a href="admin/" <?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'class="active"' : ''; ?>>Admin Panel</a></li>
                <?php else: ?>
                    <li><a href="my-bookings.php" <?php echo ($_SERVER['PHP_SELF'] == '/my-bookings.php') ? 'class="active"' : ''; ?>>My Bookings</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" <?php echo ($_SERVER['PHP_SELF'] == '/login.php') ? 'class="active"' : ''; ?>>Login</a></li>
            <?php endif; ?>
        </ul>
        <div class="menu-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
</header>
