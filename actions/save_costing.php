<?php
// actions/save_costing.php
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $project_id = (int)$data['project_id'];
    $agreed_price = (float)$data['agreed_price'];
    $materials = $data['materials'] ?? []; 

    $conn->begin_transaction();

    try {
        // 1. Update the agreed_price in the main project table
        $update_proj = $conn->prepare("UPDATE project SET agreed_price = ? WHERE project_id = ?");
        $update_proj->bind_param("di", $agreed_price, $project_id);
        $update_proj->execute();

        // 2. Figure out which materials are currently in the database for this project
        $existing_mats = [];
        $get_existing = $conn->prepare("SELECT material_id FROM project_breakdown WHERE project_id = ?");
        $get_existing->bind_param("i", $project_id);
        $get_existing->execute();
        $res = $get_existing->get_result();
        while($row = $res->fetch_assoc()) {
            $existing_mats[] = $row['material_id'];
        }

        // 3. Extract the incoming material IDs from the JavaScript payload
        $incoming_mats = [];
        foreach ($materials as $item) {
            $incoming_mats[] = (int)$item['material_id'];
        }

        // 4. Find materials that were deleted on the frontend and remove ONLY them
        $to_delete = array_diff($existing_mats, $incoming_mats);
        if (!empty($to_delete)) {
            $del_stmt = $conn->prepare("DELETE FROM project_breakdown WHERE project_id = ? AND material_id = ?");
            foreach ($to_delete as $del_id) {
                $del_stmt->bind_param("ii", $project_id, $del_id);
                $del_stmt->execute();
            }
        }

        // 5. Prepare statements for Check, Update, and Insert
        $check_stmt = $conn->prepare("SELECT breakdown_id FROM project_breakdown WHERE project_id = ? AND material_id = ?");
        $update_stmt = $conn->prepare("UPDATE project_breakdown SET quantity_used = ?, unit_cost = ?, total_cost = ? WHERE project_id = ? AND material_id = ?");
        $insert_stmt = $conn->prepare("INSERT INTO project_breakdown (project_id, material_id, quantity_used, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");

        // 6. Loop through incoming materials and either UPDATE or INSERT
        foreach ($materials as $item) {
            $mat_id = (int)$item['material_id'];
            
            // Note: Upgraded quantity to (float) in case you use partial measurements like 1.5 yards!
            $qty = (float)$item['quantity']; 
            $cost = (float)$item['unit_price'];
            $total = $qty * $cost; 

            // Check if it already exists
            $check_stmt->bind_param("ii", $project_id, $mat_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            if ($check_res->num_rows > 0) {
                // It exists! Just update the values, keeping the breakdown_id intact.
                $update_stmt->bind_param("dddii", $qty, $cost, $total, $project_id, $mat_id);
                $update_stmt->execute();
            } else {
                // It's brand new! Insert it.
                $insert_stmt->bind_param("iiddd", $project_id, $mat_id, $qty, $cost, $total);
                $insert_stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Costing & Price saved successfully!"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to save: " . $e->getMessage()]);
    }
}