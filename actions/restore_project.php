<?php
// actions/restore_project.php
require_once('../config/database.php');

// Ensure session is started to track who is restoring the project
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $project_id = (int)$data['project_id'];
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    // 1. FETCH PROJECT NAME BEFORE RESTORING
    $fetch_stmt = $conn->prepare("SELECT project_name FROM project WHERE project_id = ?");
    $fetch_stmt->bind_param("i", $project_id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result()->fetch_assoc();
    $project_name = $res ? $res['project_name'] : "Unknown Project";

    // 2. EXECUTE THE RESTORATION
    $stmt = $conn->prepare("UPDATE project SET is_archived = 0 WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    
    if($stmt->execute()) {
        
        // 3. 🚨 LOG THE ACTIVITY
        if ($admin_id > 0) {
            $action = 'RESTORE';
            $target_table = 'project'; 
            $formatted_prj = "PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
            
            $description = "Restored project from archives: $project_name ($formatted_prj). It is now back in the active list.";
            
            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $project_id, $description);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
}