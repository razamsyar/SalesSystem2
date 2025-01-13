<?php
require_once 'security.php';
secure_session_start();
check_user_type(3); // Ensure user is a guest
require_once 'db_connection.php';

// Initialize error and success messages
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $fullname = htmlspecialchars(trim($_POST['fullname']), ENT_QUOTES, 'UTF-8');
    $contact = htmlspecialchars(trim($_POST['contact']), ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $current_password = $_POST['current_password'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    try {
        // Start transaction
        $conn->begin_transaction();

        // Verify current password for ANY profile update
        $stmt = $conn->prepare("SELECT password, salt FROM guest WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user']['id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!verify_password($current_password, $user['password'], $user['salt'])) {
            throw new Exception("Current password is incorrect.");
        }

        // Check if email exists for other users
        $stmt = $conn->prepare("SELECT id FROM guest WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $_SESSION['user']['id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists for another user.");
        }

        // Verify current password if changing password
        if (!empty($current_password)) {
            $stmt = $conn->prepare("SELECT password, salt FROM guest WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user']['id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!verify_password($current_password, $user['password'], $user['salt'])) {
                throw new Exception("Current password is incorrect.");
            }

            // Validate new password
            if (empty($new_password)) {
                throw new Exception("New password cannot be empty.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }

            if (strlen($new_password) < 8 || 
                !preg_match("/[A-Z]/", $new_password) || 
                !preg_match("/[a-z]/", $new_password) || 
                !preg_match("/[0-9]/", $new_password)) {
                throw new Exception("Password must be at least 8 characters and include uppercase, lowercase, and numbers.");
            }

            // Generate new salt and hash for new password
            $salt = bin2hex(random_bytes(32));
            $hashed_password = hash_password($new_password, $salt);

            // Update user with new password
            $stmt = $conn->prepare("UPDATE guest SET fullname = ?, contact = ?, address = ?, email = ?, password = ?, salt = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $fullname, $contact, $address, $email, $hashed_password, $salt, $_SESSION['user']['id']);
        } else {
            // Update user without changing password
            $stmt = $conn->prepare("UPDATE guest SET fullname = ?, contact = ?, address = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $fullname, $contact, $address, $email, $_SESSION['user']['id']);
        }

        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user']['fullname'] = $fullname;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['contact'] = $contact;
            $_SESSION['user']['address'] = $address;

            $conn->commit();
            $success = "Profile updated successfully!";
        } else {
            throw new Exception("Error updating profile.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .password-requirements ul {
            padding-left: 1.2rem;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="guest_dashboard.php">Roti Sri Bakery</a>
            <a href="guest_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php 
                                try {
                                    if (!isset($error)) {
                                        throw new Exception('Error variable not set');
                                    }
                                    
                                    // Define allowed error messages for profile editing
                                    $allowed_errors = [
                                        'CURRENT_PASSWORD_INCORRECT' => 'Current password is incorrect.',
                                        'EMAIL_EXISTS' => 'Email already exists for another user.',
                                        'PASSWORD_EMPTY' => 'New password cannot be empty.',
                                        'PASSWORD_MISMATCH' => 'New passwords do not match.',
                                        'PASSWORD_REQUIREMENTS' => 'Password must be at least 8 characters and include uppercase, lowercase, and numbers.',
                                        'UPDATE_FAILED' => 'Failed to update profile.',
                                        'INVALID_INPUT' => 'Please check your input and try again.'
                                    ];
                                    
                                    // Convert exception message to error code
                                    $error_code = array_search($error, $allowed_errors) ?: 'UNKNOWN_ERROR';
                                    
                                    // Map error code to safe message
                                    $safe_message = $allowed_errors[$error_code] ?? 'An error occurred. Please try again.';
                                        
                                    echo htmlspecialchars($safe_message, ENT_QUOTES, 'UTF-8');
                                } catch (Exception $e) {
                                    // Log error securely without exposing details
                                    error_log('Profile Edit Error: ' . $e->getMessage());
                                    // Show generic error message
                                    echo htmlspecialchars('An error occurred. Please try again.', ENT_QUOTES, 'UTF-8');
                                }
                            ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="fullname" 
                                       value="<?php echo htmlspecialchars($_SESSION['user']['fullname']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="contact" 
                                       value="<?php echo htmlspecialchars($_SESSION['user']['contact']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($_SESSION['user']['address']); ?></textarea>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required autocomplete="off">
                                <small class="text-muted">Required to make any changes to your profile</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" autocomplete="off">
                                <div class="password-requirements">
                                    Password must contain:
                                    <ul>
                                        <li>At least 8 characters</li>
                                        <li>At least one uppercase letter</li>
                                        <li>At least one lowercase letter</li>
                                        <li>At least one number</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" autocomplete="off">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="guest_dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $_SESSION['csrf_token']; ?>">
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html> 