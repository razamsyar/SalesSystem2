<?php
require 'db_connection.php'; // Include the connection file if needed

// Check if a user is logged in (optional, based on your use case)
session_start();
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Roti Sri Bakery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        header {
            background-color: #343a40;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        nav {
            display: flex;
            justify-content: center;
            background-color: #495057;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 1rem;
            padding: 1rem;
        }
        nav a:hover {
            background-color: #343a40;
            border-radius: 5px;
        }
        .content {
            text-align: center;
            padding: 2rem;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to Roti Sri Bakery</h1>
        <p>Delicious pastries since the 1980s</p>
    </header>
    <nav>
        <a href="registration.php">Register</a>
        <a href="login.php">Login</a>
        <a href="sales.php">Sales</a>
        <a href="inventory.php">Inventory</a>
        <a href="production.php">Production</a>
    </nav>
    <div class="content">
        <h2>Your Favorite Bakery in Alor Star</h2>
        <p>Explore our online services and enjoy our freshly baked goods delivered to your doorstep!</p>
        <?php if ($user): ?>
            <p>Welcome back, <?php echo htmlspecialchars($user['fullname']); ?>!</p>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <p>Please <a href="login.php">Login</a> or <a href="registeration.php">Register</a> to access more features.</p>

        <?php endif; ?>
    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Roti Sri Bakery. All Rights Reserved.</p>
    </footer>
</body>
</html>