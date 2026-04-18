<?php
// actions/save_costing.php
require_once('../config/database.php');

// Ensure session is started so we know who updated the costing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $project_id = (int)$data['project_id'];
    $agreed_price = (float)$data['agreed_price'];
    $materials = $data['materials'] ?? []; 
    
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    $conn->begin_transaction();

    try {
        // ==========================================
        // 1. CAPTURE "BEFORE" SNAPSHOT FOR AUDIT
        // ==========================================
        $p_stmt = $conn->prepare("SELECT project_name, agreed_price FROM project WHERE project_id = ?");
        $p_stmt->bind_param("i", $project_id);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result()->fetch_assoc();
        
        $project_name = $p_res ? $p_res['project_name'] : "Unknown Project";
        $old_agreed_price = $p_res ? (float)$p_res['agreed_price'] : 0.0;

        $old_mats_stmt = $conn->prepare("
            SELECT rm.material_name, pb.quantity_used, pb.unit_cost, pb.total_cost
            FROM project_breakdown pb
            JOIN raw_material rm ON pb.material_id = rm.material_id
            WHERE pb.project_id = ?
            ORDER BY rm.material_name ASC
        ");
        $old_mats_stmt->bind_param("i", $project_id);
        $old_mats_stmt->execute();
        $old_mats_res = $old_mats_stmt->get_result();

        $old_materials = [];
        $old_total_cost = 0;
        while ($row = $old_mats_res->fetch_assoc()) {
            $old_materials[] = $row;
            $old_total_cost += (float)$row['total_cost'];
        }
        
        // This Boolean determines if it's a CREATE or an UPDATE
        $is_new_costing = (count($old_materials) === 0);

        // ==========================================
        // 2. EXECUTE THE DATABASE UPDATES
        // ==========================================
        $update_proj = $conn->prepare("UPDATE project SET agreed_price = ? WHERE project_id = ?");
        $update_proj->bind_param("di", $agreed_price, $project_id);
        $update_proj->execute();

        $existing_mats = [];
        $get_existing = $conn->prepare("SELECT material_id FROM project_breakdown WHERE project_id = ?");
        $get_existing->bind_param("i", $project_id);
        $get_existing->execute();
        $res = $get_existing->get_result();
        while($row = $res->fetch_assoc()) {
            $existing_mats[] = $row['material_id'];
        }

        $incoming_mats = [];
        foreach ($materials as $item) {
            $incoming_mats[] = (int)$item['material_id'];
        }

        $to_delete = array_diff($existing_mats, $incoming_mats);
        if (!empty($to_delete)) {
            $del_stmt = $conn->prepare("DELETE FROM project_breakdown WHERE project_id = ? AND material_id = ?");
            foreach ($to_delete as $del_id) {
                $del_stmt->bind_param("ii", $project_id, $del_id);
                $del_stmt->execute();
            }
        }

        $check_stmt = $conn->prepare("SELECT breakdown_id FROM project_breakdown WHERE project_id = ? AND material_id = ?");
        $update_stmt = $conn->prepare("UPDATE project_breakdown SET quantity_used = ?, unit_cost = ?, total_cost = ? WHERE project_id = ? AND material_id = ?");
        $insert_stmt = $conn->prepare("INSERT INTO project_breakdown (project_id, material_id, quantity_used, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");

        foreach ($materials as $item) {
            $mat_id = (int)$item['material_id'];
            $qty = (float)$item['quantity']; 
            $cost = (float)$item['unit_price'];
            $total = $qty * $cost; 

            $check_stmt->bind_param("ii", $project_id, $mat_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            if ($check_res->num_rows > 0) {
                $update_stmt->bind_param("dddii", $qty, $cost, $total, $project_id, $mat_id);
                $update_stmt->execute();
            } else {
                $insert_stmt->bind_param("iiddd", $project_id, $mat_id, $qty, $cost, $total);
                $insert_stmt->execute();
            }
        }

        // ==========================================
        // 3. CAPTURE "AFTER" SNAPSHOT & LOG IT
        // ==========================================
        if ($admin_id > 0) {
            $formatted_prj = "PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
            $target_table = 'project_breakdown';
            
            // Fetch the newly saved materials
            $old_mats_stmt->execute(); // Re-run the exact same query from Step 1
            $new_mats_res = $old_mats_stmt->get_result();
            
            $new_materials = [];
            $new_total_cost = 0;
            $receipt_items = []; // Format specifically for the receipt view
            
            while ($row = $new_mats_res->fetch_assoc()) {
                $new_materials[] = $row;
                $new_total_cost += (float)$row['total_cost'];
                
                $receipt_items[] = [
                    'name' => $row['material_name'],
                    'qty' => (float)$row['quantity_used'],
                    'unit_cost' => (float)$row['unit_cost'],
                    'total' => (float)$row['total_cost']
                ];
            }

            // 🟢 SCENARIO: FIRST TIME CREATION (RECEIPT MODE)
            if ($is_new_costing) {
                $item_count = count($receipt_items);
                $summary = "Created initial Bill of Materials ($item_count items). Agreed price set to ₱ " . number_format($agreed_price, 2) . ".";

                $log_payload = json_encode([
                    'is_detailed' => true,
                    'summary' => $summary,
                    'project' => $formatted_prj . ' - ' . $project_name,
                    'total_value' => $new_total_cost,
                    'items' => $receipt_items
                ]);

                $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'CREATE', ?, ?, ?)");
                $log_stmt->bind_param("isis", $admin_id, $target_table, $project_id, $log_payload);
                $log_stmt->execute();

            // 🟠 SCENARIO: UPDATING EXISTING COSTING (DELTA MODE)
            } else {
                $changes = [];
                
                if ($old_agreed_price !== $agreed_price) {
                    $changes[] = [
                        'field' => 'Agreed Billing Price', 
                        'old' => '₱ ' . number_format($old_agreed_price, 2), 
                        'new' => '₱ ' . number_format($agreed_price, 2)
                    ];
                }
                
                if ($old_total_cost !== $new_total_cost) {
                    $changes[] = [
                        'field' => 'Total Materials Cost', 
                        'old' => '₱ ' . number_format($old_total_cost, 2), 
                        'new' => '₱ ' . number_format($new_total_cost, 2)
                    ];
                }

                // Helper to format the material array into a clean multi-line string
                $format_mat = function($m) { return "• " . $m['material_name'] . ": " . $m['quantity_used'] . " @ ₱" . number_format($m['unit_cost'], 2); };
                
                $old_mat_str = implode("\n", array_map($format_mat, $old_materials));
                $new_mat_str = implode("\n", array_map($format_mat, $new_materials));

                if ($old_mat_str !== $new_mat_str) {
                    $changes[] = [
                        'field' => 'Bill of Materials', 
                        'old' => empty($old_mat_str) ? 'None' : $old_mat_str, 
                        'new' => empty($new_mat_str) ? 'None' : $new_mat_str
                    ];
                }

                // Only log if they actually changed something!
                if (!empty($changes)) {
                    $log_payload = json_encode([
                        'is_detailed' => true,
                        'type' => 'update_comparison', // Triggers the Before & After UI!
                        'summary' => "Modified the project's costing breakdown and/or price.",
                        'project' => $formatted_prj . ' - ' . $project_name,
                        'changes' => $changes
                    ]);

                    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', ?, ?, ?)");
                    $log_stmt->bind_param("isis", $admin_id, $target_table, $project_id, $log_payload);
                    $log_stmt->execute();
                }
            }
        }

        // 🚨 COMMIT TRANSACTION: Everything succeeded!
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Costing & Price saved successfully!"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to save: " . $e->getMessage()]);
    }
}