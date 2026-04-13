<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $project_id = (int)$data['project_id'];
    $amount = (float)$data['amount_paid'];
    $method = $data['payment_method'];
    $ref = $data['reference_number'] ?? null;
    $date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO payment (project_id, amount_paid, payment_method, reference_number, payment_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $project_id, $amount, $method, $ref, $date);

    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => $conn->error]);
}