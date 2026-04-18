<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id']) && isset($data['new_price'])) {
    $project_id = (int)$data['project_id'];
    $new_price = (float)$data['new_price'];
    
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    // 1. FETCH "BEFORE" DATA FOR THE AUDIT LOG
    $old_stmt = $conn->prepare("SELECT project_name, agreed_price FROM project WHERE project_id = ?");
    $old_stmt->bind_param("i", $project_id);
    $old_stmt->execute();
    $old_data = $old_stmt->get_result()->fetch_assoc();
    
    if (!$old_data) {
        echo json_encode(["status" => "error", "message" => "Project not found."]);
        exit;
    }

    $project_name = $old_data['project_name'];
    $old_price = (float)$old_data['agreed_price'];

    // 2. EXECUTE THE UPDATE
    $stmt = $conn->prepare("UPDATE project SET agreed_price = ? WHERE project_id = ?");
    $stmt->bind_param("di", $new_price, $project_id);
    
    if($stmt->execute()) {
        
        // 3. 🚨 LOG THE ACTIVITY (Only if the price actually changed!)
        if ($admin_id > 0 && $old_price !== $new_price) {
            $action = 'UPDATE';
            $target_table = 'project';
            $formatted_prj = "PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
            
            // Format numbers beautifully for the modal
            $old_formatted = '₱ ' . number_format($old_price, 2);
            $new_formatted = '₱ ' . number_format($new_price, 2);
            
            // Build the Delta JSON Payload
            $log_payload = json_encode([
                'is_detailed' => true,
                'type' => 'update_comparison',
                'summary' => "Adjusted the agreed billing price.",
                'project' => $formatted_prj . ' - ' . $project_name,
                'changes' => [
                    [
                        'field' => 'Agreed Price', 
                        'old' => $old_formatted, 
                        'new' => $new_formatted
                    ]
                ]
            ]);

            // Insert into the audit log
            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $project_id, $log_payload);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload."]);
}