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
$admin_id = (int)$_SESSION['admin_id'];

// 1. FETCH DETAILS BEFORE DELETION (For the Audit Log)
$fetch_stmt = $conn->prepare("
    SELECT p.amount_paid, p.payment_method, p.reference_number, prj.project_name, prj.project_id
    FROM payment p
    JOIN project prj ON p.project_id = prj.project_id
    WHERE p.payment_id = ?
");
$fetch_stmt->bind_param("i", $payment_id);
$fetch_stmt->execute();
$res = $fetch_stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['status' => 'error', 'message' => 'Payment not found.']);
    exit;
}

$amount = $res['amount_paid'];
$method = $res['payment_method'];
$ref = $res['reference_number'];
$project_name = $res['project_name'];
$project_id = $res['project_id'];

// 2. DELETE PAYMENT
$stmt = $conn->prepare("DELETE FROM payment WHERE payment_id = ?");
$stmt->bind_param("i", $payment_id);

if ($stmt->execute()) {
    
    // 3. LOG THE VOID ACTIVITY
    $action = 'VOID';
    $target_table = 'payment';
    $formatted_prj = "#PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
    $formatted_amount = number_format($amount, 2);
    $ref_text = $ref ? " (Ref: $ref)" : "";

    $description = "Voided payment of ₱ $formatted_amount via $method$ref_text for $project_name ($formatted_prj).";

    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $payment_id, $description);
    $log_stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Payment successfully voided.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();