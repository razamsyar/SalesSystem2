<?php
require_once 'db_connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process the form data
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $contact = htmlspecialchars(trim($_POST['contact']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));
    $role = $_POST['role'];
    $employee_id = isset($_POST['employee_id']) ? htmlspecialchars(trim($_POST['employee_id'])) : null;

    // Additional validation for Supervisor and Clerk
    if (($role === 'Supervisor' || $role === 'Clerk') && empty($employee_id)) {
        $error = "Employee ID is required for Supervisor and Clerk roles.";
    } else {
        // Insert into the database
        $stmt = $conn->prepare("INSERT INTO guest (fullname, contact, email, password, type, employee_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fullname, $contact, $email, password_hash($password, PASSWORD_DEFAULT), $role, $employee_id);
        $stmt->execute();
        // Redirect or show success message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleEmployeeId() {
            const role = document.querySelector('select[name="role"]').value;
            const employeeIdField = document.getElementById('employee_id_field');
            employeeIdField.style.display = (role === 'Supervisor' || role === 'Clerk') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2>Registration Form</h2>
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
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" name="role" onchange="toggleEmployeeId()" required>
                    <option value="">Select Role</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Clerk">Clerk</option>
                    <option value="Guest">Guest</option>
                </select>
            </div>
            <div class="mb-3" id="employee_id_field" style="display: none;">
                <label for="employee_id" class="form-label">Employee ID</label>
                <input type="text" class="form-control" name="employee_id" placeholder="Enter Employee ID">
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>