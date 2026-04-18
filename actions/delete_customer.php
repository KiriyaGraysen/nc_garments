<?php
require_once('../config/database.php');

// Ensure session is started so we can track WHICH admin did this
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['customer_id'])) {
    $id = (int)$data['customer_id'];
    
    // 1. FETCH CUSTOMER DETAILS BEFORE ARCHIVING (For a better log description)
    $c_stmt = $conn->prepare("SELECT full_name FROM customer WHERE customer_id = ?");
    $c_stmt->bind_param("i", $id);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result()->fetch_assoc();
    $customer_name = $c_res ? $c_res['full_name'] : "Unknown Customer";
    
    // 2. EXECUTE THE ARCHIVE UPDATE
    $stmt = $conn->prepare("UPDATE customer SET is_archived = 1 WHERE customer_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        
        // 3. LOG THE ACTIVITY
        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            $action = 'ARCHIVE';
            $target_table = 'customer';
            
            // Format the ID to match your UI (e.g., CUST-0004)
            $formatted_id = "CUST-" . str_pad($id, 4, '0', STR_PAD_LEFT);
            $description = "Archived customer profile for $customer_name ($formatted_id).";
            
            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $id, $description);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
}