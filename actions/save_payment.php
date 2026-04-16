<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $project_id = (int)$data['project_id'];
    $amount = (float)$data['amount_paid'];
    $method = $data['payment_method'];
    
    // Make sure empty strings become actual NULLs for the database
    $ref = empty($data['reference_number']) ? null : trim($data['reference_number']);
    $date = date('Y-m-d H:i:s');
    
    // Grab the logged-in admin (fallback to 1 for testing)
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 1; 

    // Insert statement now perfectly matches your table structure!
    $stmt = $conn->prepare("INSERT INTO payment (project_id, processed_by_admin, amount_paid, payment_method, reference_number, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
    
    // i = integer, i = integer, d = double(decimal), s = string, s = string, s = string
    $stmt->bind_param("iidsss", $project_id, $admin_id, $amount, $method, $ref, $date);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload received."]);
}