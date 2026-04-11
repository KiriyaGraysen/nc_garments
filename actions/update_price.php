<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);
if ($data && isset($data['project_id']) && isset($data['new_price'])) {
    $project_id = (int)$data['project_id'];
    $new_price = (float)$data['new_price'];
    
    $stmt = $conn->prepare("UPDATE project SET agreed_price = ? WHERE project_id = ?");
    $stmt->bind_param("di", $new_price, $project_id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error."]);
    }
}
?>