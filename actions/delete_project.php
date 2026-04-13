<?php
// actions/delete_project.php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $project_id = (int)$data['project_id'];
    
    // Soft Delete: Update the flag instead of dropping the row
    $stmt = $conn->prepare("UPDATE project SET is_archived = 1 WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error."]);
    }
}