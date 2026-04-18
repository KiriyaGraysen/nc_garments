<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['admin_id'])) {
    $target_id = (int)$data['admin_id'];
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    // 1. FETCH STAFF DETAILS BEFORE RESTORING
    $fetch_stmt = $conn->prepare("SELECT full_name, username, role FROM admin WHERE admin_id = ?");
    $fetch_stmt->bind_param("i", $target_id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result()->fetch_assoc();
    
    $staff_name = $res ? $res['full_name'] : "Unknown Staff";
    $staff_username = $res ? $res['username'] : "unknown";
    $staff_role = $res ? strtoupper($res['role']) : "STAFF";

    // 2. EXECUTE THE RESTORE UPDATE
    $stmt = $conn->prepare("UPDATE admin SET is_archived = 0 WHERE admin_id = ?");
    $stmt->bind_param("i", $target_id);
    
    if ($stmt->execute()) {
        
        // 3. 🚨 LOG THE SECURITY ACTIVITY
        if ($admin_id > 0) {
            $action = 'RESTORE';
            $target_table = 'admin'; // Filters into the Security tab
            
            $description = "Restored staff account: $staff_name (@$staff_username) - Role: $staff_role. Access has been re-enabled.";
            
            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $target_id, $description);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
}