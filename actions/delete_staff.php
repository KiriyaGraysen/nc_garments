<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['admin_id'])) {
    $id = (int)$data['admin_id'];
    
    if ($id === $_SESSION['admin_id']) {
        echo json_encode(["status" => "error", "message" => "You cannot delete your own account."]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE admin SET is_archived = 1 WHERE admin_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error"]);
}