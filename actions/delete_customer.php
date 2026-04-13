<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['customer_id'])) {
    $id = (int)$data['customer_id'];
    $stmt = $conn->prepare("UPDATE customer SET is_archived = 1 WHERE customer_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error"]);
}