<?php
// actions/save_costing.php
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $project_id = (int)$data['project_id'];
    $agreed_price = (float)$data['agreed_price']; // NEW: Get the agreed price
    $materials = $data['materials']; 

    $conn->begin_transaction();

    try {
        // 1. Update the agreed_price in the main project table
        $update_proj = $conn->prepare("UPDATE project SET agreed_price = ? WHERE project_id = ?");
        $update_proj->bind_param("di", $agreed_price, $project_id);
        $update_proj->execute();

        // 2. Clear old breakdown
        $delete_stmt = $conn->prepare("DELETE FROM project_breakdown WHERE project_id = ?");
        $delete_stmt->bind_param("i", $project_id);
        $delete_stmt->execute();

        // 3. Insert new breakdown
        $insert_stmt = $conn->prepare("
            INSERT INTO project_breakdown (project_id, material_id, quantity_used, unit_cost, total_cost) 
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($materials as $item) {
            $mat_id = (int)$item['material_id'];
            $qty = (int)$item['quantity']; 
            $cost = (float)$item['unit_price'];
            $total = $qty * $cost; 

            $insert_stmt->bind_param("iiidd", $project_id, $mat_id, $qty, $cost, $total);
            $insert_stmt->execute();
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Costing & Price saved successfully!"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to save: " . $e->getMessage()]);
    }
}