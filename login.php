<?php
require_once 'security.php';
secure_session_start();
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['last_attempt'] = time();
    $_SESSION['login_attempts']++;
    
    $stmt = $conn->prepare("SELECT * FROM guest WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password using proper hashing
        if (password_verify($password . $user['salt'], $user['password'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['user'] = $user;
            $_SESSION['last_activity'] = time();
            $_SESSION['created'] = time();
            
            // Add CSRF token
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            
            switch($user['type']) {
                case 1:
                    header("Location: supervisor_dashboard.php");
                    break;
                case 2:
                    header("Location: clerk_dashboard.php");
                    break;
                case 3:
                    header("Location: guest_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        }
    }
    $_SESSION['error'] = "Invalid email or password.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .form-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo">
                <h2>Roti Sri Bakery</h2>
                <p class="text-muted">Welcome back</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            
            <div class="text-center mt-3">
            <p>Don't have an account? <a href="registration.php">Register here</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>