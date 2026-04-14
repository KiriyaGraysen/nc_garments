<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['admin_id'])) {
    $id = (int)$data['admin_id'];
    $new_status = $data['new_status']; // 'active' or 'deactivated'
    
    // Prevent the admin from accidentally locking themselves out!
    if ($id === $_SESSION['admin_id']) {
        echo json_encode(["status" => "error", "message" => "You cannot revoke your own access."]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE admin SET status = ? WHERE admin_id = ?");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error"]);
}