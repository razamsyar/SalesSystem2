<?php
require_once 'db_connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process the form data
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $contact = htmlspecialchars(trim($_POST['contact']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));
    $confirm_password = htmlspecialchars(trim($_POST['confirm_password']));
    $address = htmlspecialchars(trim($_POST['address']));
    $role = htmlspecialchars(trim($_POST['role']));
    $employee_id = htmlspecialchars(trim($_POST['employee_id']));

    // Determine the type based on the role
    $type = null;
    if ($role === 'Supervisor') {
        $type = 1; // Type 1 for Supervisor
    } elseif ($role === 'Clerk') {
        $type = 2; // Type 2 for Clerk
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Password validation
        if (strlen($password) < 8 || 
            !preg_match("/[A-Z]/", $password) || 
            !preg_match("/[a-z]/", $password) || 
            !preg_match("/[0-9]/", $password)) {
            $error = "Password must contain at least 8 characters, one uppercase letter, one lowercase letter, and one number.";
        } else if (empty($employee_id)) {
            $error = "Employee ID is required.";
        } else {
            // Check if the Employee ID and Full Name match and are not assigned
            $stmt = $conn->prepare("SELECT * FROM employee_ids WHERE employee_id = ? AND fullname = ? AND assigned = 0");
            $stmt->bind_param("ss", $employee_id, $fullname);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // First check if email already exists
                $check_email = $conn->prepare("SELECT email FROM guest WHERE email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                $email_result = $check_email->get_result();

                if ($email_result->num_rows > 0) {
                    $error = "Email address already exists. Please use a different email.";
                } else {
                    // Insert into the guest table
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO guest (fullname, contact, email, password, type, address) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssis", $fullname, $contact, $email, $hashed_password, $type, $address);
                    
                    if ($stmt->execute()) {
                        header("Location: login.php");
                        exit();
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            } else {
                $error = "Employee ID is invalid or already assigned.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .registration-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .registration-container h2 {
        color: #2c3e50;
        margin-bottom: 30px;
        font-weight: 600;
        position: relative;
        padding-bottom: 10px;
    }

    .registration-container h2:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: #007bff;
        border-radius: 2px;
    }

    .form-label {
        font-weight: 500;
        color: #34495e;
    }

    .form-control, .form-select {
        border-radius: 8px;
        padding: 12px;
        border: 1px solid #dde1e5;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }

    .btn-primary {
        padding: 12px;
        font-weight: 500;
        border-radius: 8px;
        background: #007bff;
        border: none;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .alert {
        border-radius: 8px;
        padding: 15px;
    }

    .mt-3 p {
        color: #666;
        text-align: center;
    }

    .mt-3 a {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .mt-3 a:hover {
        color: #0056b3;
        text-decoration: underline;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .registration-container {
            margin: 15px;
            padding: 20px;
        }
    }

    /* Custom styling for the employee ID field in registrationStaff.php */
    #employee_id_field {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }

    /* Role select styling */
    .form-select {
        background-color: white;
        cursor: pointer;
    }

    /* Password field styling */
    input[type="password"] {
        letter-spacing: 0.1em;
    }

    /* Textarea styling */
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
</style>
</head>
<body>
    <div class="container mt-5">
        <div class="registration-container">
            <h2 class="text-center">Staff Registration</h2>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="fullname" required>
                </div>
                <div class="mb-3">
                    <label for="contact" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" name="contact" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Clerk">Clerk</option>
                    </select>
                </div>
                <div class="mb-3" id="employee_id_field">
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <input type="text" class="form-control" name="employee_id" required placeholder="Enter Employee ID">
                </div>
                <button type="submit" class="btn btn-primary w-100">
    <i class="bi bi-person-plus"></i> Register
</button>
            </form>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="mt-3">
            <p><i class="bi bi-box-arrow-in-right"></i> Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 