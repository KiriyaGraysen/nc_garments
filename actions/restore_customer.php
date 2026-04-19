<?php
require_once('../config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Customer ID is missing.']);
    exit;
}

$customer_id = (int)$data['customer_id'];
$admin_id = (int)$_SESSION['admin_id'];

$conn->begin_transaction();

try {
    // 1. Fetch customer name for the activity log
    $stmt = $conn->prepare("SELECT full_name FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Customer not found.");
    }
    $customer = $result->fetch_assoc();
    $customer_name = $customer['full_name'];

    // 2. Restore the customer
    $update_stmt = $conn->prepare("UPDATE customer SET is_archived = 0 WHERE customer_id = ?");
    $update_stmt->bind_param("i", $customer_id);
    $update_stmt->execute();

    // 3. Log the action
    $log_desc = "Restored customer: " . $customer_name . " (ID: CUST-" . str_pad($customer_id, 4, '0', STR_PAD_LEFT) . ") from archives.";
    
    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'RESTORE', 'customer', ?, ?)");
    $log_stmt->bind_param("iis", $admin_id, $customer_id, $log_desc);
    $log_stmt->execute();

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Customer restored successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}