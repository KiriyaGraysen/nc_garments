<?php
// actions/update_progress.php
require_once('../config/database.php');
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['project_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$project_id = (int)$data['project_id'];
$admin_id = (int)$_SESSION['admin_id'];

// We wrap everything in a transaction so dates, status, and inventory all sync perfectly
$conn->begin_transaction();

try {
    // FETCH PROJECT CONTEXT FOR LOGGING
    $p_stmt = $conn->prepare("SELECT project_name, progress, overdue_notes, produced_product_id, quantity, start_date FROM project WHERE project_id = ?");
    $p_stmt->bind_param("i", $project_id);
    $p_stmt->execute();
    $curr = $p_stmt->get_result()->fetch_assoc();

    if (!$curr) throw new Exception("Project not found.");

    $project_name = $curr['project_name'];
    $old_progress = $curr['progress'];
    $old_notes = $curr['overdue_notes'] ?: 'None';
    $start_date = $curr['start_date'];
    $produced_id = $curr['produced_product_id'];
    $qty = (int)$curr['quantity'];

    $changes = []; // To track deltas for the audit log

    // ==========================================
    // 1. HANDLE NOTES UPDATE
    // ==========================================
    if (isset($data['overdue_notes'])) {
        $notes = trim($data['overdue_notes']);
        
        if ($old_notes !== $notes) {
            $update = $conn->prepare("UPDATE project SET overdue_notes = ? WHERE project_id = ?");
            $update->bind_param("si", $notes, $project_id);
            $update->execute();

            $changes[] = ['field' => 'Overdue Notes', 'old' => $old_notes, 'new' => empty($notes) ? 'Cleared' : $notes];
        }
    }

    // ==========================================
    // 2. HANDLE PROGRESS & INVENTORY UPDATE
    // ==========================================
    $stock_action = 'none';
    $stock_message = '';

    if (isset($data['progress'])) {
        $new_progress = $data['progress'];
        
        if ($old_progress !== $new_progress) {
            $finish_date = null;
            $status = 'active';

            // Auto-Date & Status Logic
            if ($new_progress === 'not started') {
                $start_date = null; 
            } elseif (empty($start_date)) {
                $start_date = date('Y-m-d'); 
            }

            if ($new_progress === 'done' || $new_progress === 'released') {
                $finish_date = date('Y-m-d'); 
                $status = 'completed';
            } elseif ($new_progress === 'cancelled') {
                $status = 'cancelled';
            }

            // Internal Restock Inventory Logic
            if (!empty($produced_id)) {
                $prod_stmt = $conn->prepare("SELECT product_name, size FROM premade_product WHERE product_id = ?");
                $prod_stmt->bind_param("i", $produced_id);
                $prod_stmt->execute();
                $prod_data = $prod_stmt->get_result()->fetch_assoc();
                $prod_name = $prod_data['product_name'] . " (" . $prod_data['size'] . ")";

                if ($new_progress === 'done' && $old_progress !== 'done') {
                    $update_stock = $conn->prepare("UPDATE premade_product SET current_stock = current_stock + ? WHERE product_id = ?");
                    $update_stock->bind_param("di", $qty, $produced_id);
                    $update_stock->execute();
                    $stock_message = "Successfully added {$qty} pcs of {$prod_name} to POS inventory.";
                    $changes[] = ['field' => 'POS Inventory', 'old' => 'Pending Production', 'new' => "+$qty pcs ($prod_name)"];
                } 
                elseif ($old_progress === 'done' && $new_progress !== 'done') {
                    $update_stock = $conn->prepare("UPDATE premade_product SET current_stock = current_stock - ? WHERE product_id = ?");
                    $update_stock->bind_param("di", $qty, $produced_id);
                    $update_stock->execute();
                    $stock_message = "Reverted 'Done' status. Removed {$qty} pcs from POS.";
                    $changes[] = ['field' => 'POS Inventory', 'old' => 'Stocked', 'new' => "-$qty pcs (Reverted)"];
                }
            }

            // Update record
            $update = $conn->prepare("UPDATE project SET progress = ?, status = ?, start_date = ?, finish_date = ? WHERE project_id = ?");
            $update->bind_param("ssssi", $new_progress, $status, $start_date, $finish_date, $project_id);
            $update->execute();

            $changes[] = ['field' => 'Production Progress', 'old' => strtoupper($old_progress), 'new' => strtoupper($new_progress)];
        }
    }

    // 3. 🚨 LOG THE ACTIVITY (DELTA LOG)
    if (!empty($changes)) {
        $formatted_prj = "PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
        
        $log_payload = json_encode([
            'is_detailed' => true,
            'type' => 'update_comparison',
            'summary' => "Updated production stage and project milestones.",
            'project' => $formatted_prj . ' - ' . $project_name,
            'changes' => $changes
        ]);

        $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'project', ?, ?)");
        $log_stmt->bind_param("iis", $admin_id, $project_id, $log_payload);
        $log_stmt->execute();
    }

    $conn->commit();
    echo json_encode([
        "status" => "success",
        "stock_message" => $stock_message
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}