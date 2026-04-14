<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['admin_id'])) {
    $id = (int)$data['admin_id'];
    
    $stmt = $conn->prepare("UPDATE admin SET is_archived = 0 WHERE admin_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error"]);
}