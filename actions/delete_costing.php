<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);
if ($data && isset($data['project_id'])) {
    $project_id = (int)$data['project_id'];
    
    $stmt = $conn->prepare("DELETE FROM project_breakdown WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error."]);
    }
}
