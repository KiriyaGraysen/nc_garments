<?php
require_once('../config/database.php');

// Ensure session is started so we can track WHICH admin did this
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['admin_id'])) {
    $target_id = (int)$data['admin_id'];
    
    // Security check: Prevent users from archiving themselves
    if (isset($_SESSION['admin_id']) && $target_id === $_SESSION['admin_id']) {
        echo json_encode(["status" => "error", "message" => "You cannot archive your own account."]);
        exit();
    }

    // 1. FETCH STAFF DETAILS BEFORE ARCHIVING (For a detailed log description)
    $fetch_stmt = $conn->prepare("SELECT full_name, username, role FROM admin WHERE admin_id = ?");
    $fetch_stmt->bind_param("i", $target_id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result()->fetch_assoc();
    
    $staff_name = $res ? $res['full_name'] : "Unknown Staff";
    $staff_username = $res ? $res['username'] : "unknown";
    $staff_role = $res ? strtoupper($res['role']) : "STAFF";

    // 2. EXECUTE THE ARCHIVE UPDATE
    $stmt = $conn->prepare("UPDATE admin SET is_archived = 1 WHERE admin_id = ?");
    $stmt->bind_param("i", $target_id);
    
    if ($stmt->execute()) {
        
        // 3. LOG THE ACTIVITY
        if (isset($_SESSION['admin_id'])) {
            $actor_id = $_SESSION['admin_id'];
            $action = 'ARCHIVE';
            $target_table = 'admin';
            
            // Build a clean, highly specific description
            $description = "Archived staff account: $staff_name (@$staff_username) - Role: $staff_role.";
            
            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $actor_id, $action, $target_table, $target_id, $description);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
}