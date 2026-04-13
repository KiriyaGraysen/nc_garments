<?php
// actions/get_project_details.php
require_once('../config/database.php');

if (isset($_GET['project_id'])) {
    $project_id = (int)$_GET['project_id'];
    
    // 1. Get Main Project Info
    $stmt = $conn->prepare("
        SELECT p.*, c.full_name as customer_name, c.contact_number, 
               pr.product_name as internal_product, pr.size as internal_size
        FROM project p
        LEFT JOIN customer c ON p.customer_id = c.customer_id
        LEFT JOIN premade_product pr ON p.produced_product_id = pr.product_id
        WHERE p.project_id = ?
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    
    // 2. Get Standard Sizing
    $size_stmt = $conn->prepare("SELECT size_label, quantity FROM project_sizing WHERE project_id = ?");
    $size_stmt->bind_param("i", $project_id);
    $size_stmt->execute();
    $sizes = $size_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 3. Get Custom Measurements
    $meas_stmt = $conn->prepare("SELECT body_part, measurement_value, unit FROM project_measurement WHERE project_id = ?");
    $meas_stmt->bind_param("i", $project_id);
    $meas_stmt->execute();
    $measurements = $meas_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        "status" => "success", 
        "project" => $project,
        "sizes" => $sizes,
        "measurements" => $measurements
    ]);
}