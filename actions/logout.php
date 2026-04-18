<?php
session_start();
require_once('../config/database.php');

// 1. 🚨 LOG THE LOGOUT ACTIVITY (Must be done BEFORE destroying the session!)
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $action = 'LOGOUT';
    $target_table = 'admin'; // Keeps this in the "Security" tab
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $description = "User safely logged out and ended their secure session from IP: $ip_address.";

    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $admin_id, $description);
    $log_stmt->execute();
    $log_stmt->close();
}

// 2. Clear all session variables
session_unset();

// 3. Destroy the session completely
session_destroy();

// 4. Redirect back to the login screen
header("Location: ../login.php");
exit();
?>