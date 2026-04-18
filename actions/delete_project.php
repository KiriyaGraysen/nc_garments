<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $project_id = (int)$data['project_id'];

    // 1. FETCH PROJECT DETAILS BEFORE ARCHIVING (For a better log description)
    $fetch_stmt = $conn->prepare("
        SELECT p.project_name, c.full_name 
        FROM project p 
        LEFT JOIN customer c ON p.customer_id = c.customer_id 
        WHERE p.project_id = ?
    ");
    $fetch_stmt->bind_param("i", $project_id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result()->fetch_assoc();
    
    $project_name = $res ? $res['project_name'] : "Unknown Project";
    $client_name = ($res && !empty($res['full_name'])) ? $res['full_name'] : "Internal Restock";

    // 2. EXECUTE THE SOFT DELETE (ARCHIVE)
    $stmt = $conn->prepare("UPDATE project SET is_archived = 1 WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    
    if($stmt->execute()) {
        
        // 3. LOG THE ACTIVITY
        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            $action = 'ARCHIVE';
            $target_table = 'project';
            
            // Format the ID nicely to match your UI (e.g., PRJ-2026-005)
            $formatted_prj = "PRJ-2026-" . str_pad($project_id, 3, '0', STR_PAD_LEFT);
            $description = "Archived project: $project_name ($formatted_prj) for $client_name.";
            
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