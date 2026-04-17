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

// We wrap everything in a transaction so dates, status, and inventory all sync perfectly
$conn->begin_transaction();

try {
    // ==========================================
    // 1. HANDLE NOTES UPDATE
    // ==========================================
    if (isset($data['overdue_notes'])) {
        $notes = $data['overdue_notes'];
        $update = $conn->prepare("UPDATE project SET overdue_notes = ? WHERE project_id = ?");
        $update->bind_param("si", $notes, $project_id);
        
        if ($update->execute()) {
            $conn->commit();
            echo json_encode(["status" => "success"]);
        } else {
            throw new Exception("Failed to update notes.");
        }
        exit;
    }

    // ==========================================
    // 2. HANDLE PROGRESS & INVENTORY UPDATE
    // ==========================================
    if (isset($data['progress'])) {
        $new_progress = $data['progress'];
        
        // A. Fetch current data
        $stmt = $conn->prepare("SELECT progress, start_date, produced_product_id, quantity FROM project WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $curr = $stmt->get_result()->fetch_assoc();
        
        $old_progress = $curr['progress'];
        $start_date = $curr['start_date'];
        $produced_id = $curr['produced_product_id'];
        $qty = (int)$curr['quantity'];
        
        $finish_date = null;
        $status = 'active';

        // B. Auto-Date & Status Logic
        if ($new_progress === 'not started') {
            $start_date = null; // Wipe the start date if rolled back completely
        } elseif (empty($start_date)) {
            $start_date = date('Y-m-d'); // Stamp the start date today if just starting
        }

        if ($new_progress === 'done' || $new_progress === 'released') {
            $finish_date = date('Y-m-d'); 
            $status = 'completed'; // Move to completed tab
        } elseif ($new_progress === 'cancelled') {
            $status = 'cancelled';
        }

        // C. Internal Restock Inventory Logic
        $stock_action = 'none';
        $stock_message = '';

        if (!empty($produced_id)) {
            // Fetch product info for the UI success message
            $prod_stmt = $conn->prepare("SELECT product_name, size FROM premade_product WHERE product_id = ?");
            $prod_stmt->bind_param("i", $produced_id);
            $prod_stmt->execute();
            $prod_data = $prod_stmt->get_result()->fetch_assoc();
            $prod_name = $prod_data['product_name'] . " (" . $prod_data['size'] . ")";

            // SCENARIO 1: Moving to 'Done' (Add to POS Inventory)
            if ($new_progress === 'done' && $old_progress !== 'done') {
                $update_stock = $conn->prepare("UPDATE premade_product SET current_stock = current_stock + ? WHERE product_id = ?");
                $update_stock->bind_param("di", $qty, $produced_id);
                $update_stock->execute();
                
                $stock_action = 'added';
                $stock_message = "Successfully added {$qty} pcs of {$prod_name} directly to your POS inventory.";
            } 
            // SCENARIO 2: Reverting from 'Done' (Remove from POS Inventory to prevent ghost sales)
            elseif ($old_progress === 'done' && $new_progress !== 'done') {
                $update_stock = $conn->prepare("UPDATE premade_product SET current_stock = current_stock - ? WHERE product_id = ?");
                $update_stock->bind_param("di", $qty, $produced_id);
                $update_stock->execute();

                $stock_action = 'deducted';
                $stock_message = "⚠️ Reverted progress from 'Done'. Removed {$qty} pcs of {$prod_name} from the POS inventory.";
            }
        }

        // D. Update the main project record
        $update = $conn->prepare("UPDATE project SET progress = ?, status = ?, start_date = ?, finish_date = ? WHERE project_id = ?");
        $update->bind_param("ssssi", $new_progress, $status, $start_date, $finish_date, $project_id);
        
        if ($update->execute()) {
            $conn->commit();
            echo json_encode([
                "status" => "success",
                "stock_action" => $stock_action,
                "stock_message" => $stock_message
            ]);
        } else {
            throw new Exception("Failed to update project progress.");
        }
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}