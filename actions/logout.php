<?php
// actions/logout.php

// 1. Require database first (This automatically starts our secure session!)
require_once('../config/database.php');

// 2. 🚨 LOG THE LOGOUT ACTIVITY (Must be done BEFORE destroying the session!)
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

// 3. Clear all session variables
$_SESSION = array();

// 4. Kill the session cookie completely to prevent hijacking
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Destroy the session completely
session_destroy();

// 6. Redirect back to the login screen
header("Location: ../login.php");
exit();