<?php
// backend/save_costing.php
require_once('../config/database.php');

// Ensure we are receiving a POST request with a JSON payload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the JSON data sent by JavaScript
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $project_id = (int)$data['project_id'];
    $materials = $data['materials']; // Array of the materials from our modal

    // Start a secure database transaction
    $conn->begin_transaction();

    try {
        // 1. Clear the old breakdown for this project
        $delete_stmt = $conn->prepare("DELETE FROM project_breakdown WHERE project_id = ?");
        $delete_stmt->bind_param("i", $project_id);
        $delete_stmt->execute();

        // 2. Prepare the insert statement for the new breakdown
        $insert_stmt = $conn->prepare("
            INSERT INTO project_breakdown (project_id, material_id, quantity_used, unit_cost, total_cost) 
            VALUES (?, ?, ?, ?, ?)
        ");

        // 3. Loop through the JavaScript array and insert each row
        foreach ($materials as $item) {
            $mat_id = (int)$item['material_id'];
            $qty = (int)$item['quantity']; // Quantity is an INT in your database schema!
            $cost = (float)$item['unit_price'];
            
            // Calculate the total in PHP
            $total = $qty * $cost; 

            // THE FIX IS HERE: "iiidd" stands for Int, Int, Int, Double, Double
            $insert_stmt->bind_param("iiidd", $project_id, $mat_id, $qty, $cost, $total);
            $insert_stmt->execute();
        }

        // Commit the changes to the database
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Costing saved successfully!"]);

    } catch (Exception $e) {
        // Rollback on failure
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to save costing: " . $e->getMessage()]);
    }
}