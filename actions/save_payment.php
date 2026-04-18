<?php
require_once('../config/database.php');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check: Must be logged in to process payments!
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $project_id = (int)$data['project_id'];
    $amount = (float)$data['amount_paid'];
    $method = trim($data['payment_method']);
    
    // Make sure empty strings become actual NULLs for the database
    $ref = empty($data['reference_number']) ? null : trim($data['reference_number']);
    $date = date('Y-m-d H:i:s');
    $admin_id = (int)$_SESSION['admin_id'];

    // Basic validation
    if ($amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Amount must be greater than zero."]);
        exit;
    }
    if (empty($method)) {
        echo json_encode(["status" => "error", "message" => "Payment method is required."]);
        exit;
    }

    // 1. FETCH PROJECT DETAILS FOR THE LOG
    $p_stmt = $conn->prepare("SELECT project_name FROM project WHERE project_id = ?");
    $p_stmt->bind_param("i", $project_id);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result()->fetch_assoc();
    $project_name = $p_res ? $p_res['project_name'] : "Unknown Project";

    // 2. INSERT PAYMENT
    $stmt = $conn->prepare("INSERT INTO payment (project_id, processed_by_admin, amount_paid, payment_method, reference_number, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsss", $project_id, $admin_id, $amount, $method, $ref, $date);

    if ($stmt->execute()) {
        $payment_id = $conn->insert_id; // Grab the new payment ID

        // 3. LOG THE FINANCIAL ACTIVITY
        $action = 'CREATE';
        $target_table = 'payment'; // This perfectly routes it to your "Payments" tab
        
        $formatted_prj = "#PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
        $formatted_amount = number_format($amount, 2);
        $ref_text = $ref ? " (Ref: $ref)" : "";
        
        $description = "Recorded payment of ₱ $formatted_amount via $method$ref_text for $project_name ($formatted_prj).";

        $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $payment_id, $description);
        $log_stmt->execute();

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload received."]);
}