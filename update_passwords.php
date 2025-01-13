<?php
require_once 'db_connection.php';

// Function to update password for a user
function updateUserPassword($conn, $user_id, $plaintext_password) {
    $salt = bin2hex(random_bytes(32));
    $hashed_password = password_hash($plaintext_password . $salt, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE guest SET password = ?, salt = ? WHERE id = ?");
    $stmt->bind_param("ssi", $hashed_password, $salt, $user_id);
    return $stmt->execute();
}

// Update default passwords
$default_passwords = [
    1 => 'supervisor123',  // For Supervisor
    2 => 'clerk123',      // For Clerk1
    3 => 'clerk123'       // For Clerk2
];

foreach ($default_passwords as $user_id => $password) {
    if (updateUserPassword($conn, $user_id, $password)) {
        echo "Updated password for user ID: $user_id\n";
    } else {
        echo "Failed to update password for user ID: $user_id\n";
    }
}
?> 