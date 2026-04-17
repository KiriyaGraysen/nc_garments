<?php
require_once('../config/database.php');

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['payment_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Payment ID is missing.']);
    exit;
}

$payment_id = (int)$data['payment_id'];

// Simply delete the payment record. 
// Because the customer's balance is calculated dynamically on the fly using SUM() across your system, 
// deleting this record instantly restores their balance to what it should be!
$stmt = $conn->prepare("DELETE FROM payment WHERE payment_id = ?");
$stmt->bind_param("i", $payment_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Payment successfully voided.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();