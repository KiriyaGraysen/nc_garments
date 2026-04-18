<?php
require_once('../config/database.php');

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['admin_id'])) {
    $target_id = (int)$data['admin_id'];
    $new_status = $data['new_status']; // 'active' or 'deactivated'
    $actor_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
    
    // 1. Prevent self-lockout
    if ($target_id === $actor_id) {
        echo json_encode(["status" => "error", "message" => "You cannot revoke your own access."]);
        exit();
    }

    // 2. FETCH STAFF DETAILS & OLD STATUS BEFORE UPDATE
    $fetch_stmt = $conn->prepare("SELECT full_name, username, status FROM admin WHERE admin_id = ?");
    $fetch_stmt->bind_param("i", $target_id);
    $fetch_stmt->execute();
    $staff = $fetch_stmt->get_result()->fetch_assoc();
    
    if (!$staff) {
        echo json_encode(["status" => "error", "message" => "Staff not found."]);
        exit();
    }

    $staff_name = $staff['full_name'];
    $old_status = $staff['status'];

    // 3. EXECUTE THE UPDATE
    $stmt = $conn->prepare("UPDATE admin SET status = ? WHERE admin_id = ?");
    $stmt->bind_param("si", $new_status, $target_id);
    
    if ($stmt->execute()) {
        
        // 4. 🚨 LOG THE SECURITY ACTIVITY
        if ($actor_id > 0 && $old_status !== $new_status) {
            $action = 'UPDATE';
            $target_table = 'admin'; // Routes to the Security tab
            
            // Format for your smart regex: "from [old] to [new]"
            $description = "Changed access status for $staff_name (@{$staff['username']}) from $old_status to $new_status.";
            
            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $actor_id, $action, $target_table, $target_id, $description);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload."]);
}