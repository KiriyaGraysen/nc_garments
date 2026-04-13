<?php
require_once('../config/database.php');
if (isset($_GET['customer_id'])) {
    $id = (int)$_GET['customer_id'];
    
    // Get Customer Info
    $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    // Get Projects & their balances
    $proj_stmt = $conn->prepare("
        SELECT project_id, project_name, agreed_price, 
               COALESCE((SELECT SUM(amount_paid) FROM payment WHERE project_id = p.project_id), 0) as total_paid
        FROM project p WHERE customer_id = ? AND is_archived = 0 ORDER BY project_id DESC
    ");
    $proj_stmt->bind_param("i", $id);
    $proj_stmt->execute();
    $projects = $proj_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get Payment History
    $pay_stmt = $conn->prepare("
        SELECT pay.*, p.project_name 
        FROM payment pay 
        JOIN project p ON pay.project_id = p.project_id 
        WHERE p.customer_id = ? ORDER BY pay.payment_date DESC LIMIT 10
    ");
    $pay_stmt->bind_param("i", $id);
    $pay_stmt->execute();
    $payments = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["status" => "success", "customer" => $customer, "projects" => $projects, "payments" => $payments]);
}