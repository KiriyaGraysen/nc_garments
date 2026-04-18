<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['item_id']) && isset($data['item_type'])) {
    $id = (int)$data['item_id'];
    $type = $data['item_type'];
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    // 1. FETCH ITEM DETAILS BEFORE RESTORING (To create a clear log entry)
    if ($type === 'raw_material') {
        $fetch = $conn->prepare("SELECT material_name as name, sku FROM raw_material WHERE material_id = ?");
        $table = 'raw_material';
        $label = 'Raw Material';
    } else {
        $fetch = $conn->prepare("SELECT product_name as name, sku FROM premade_product WHERE product_id = ?");
        $table = 'premade_product';
        $label = 'Premade Product';
    }
    
    $fetch->bind_param("i", $id);
    $fetch->execute();
    $item = $fetch->get_result()->fetch_assoc();
    $item_name = $item ? $item['name'] : "Unknown Item";
    $sku = $item ? $item['sku'] : "N/A";

    // 2. EXECUTE THE RESTORATION
    if ($type === 'raw_material') {
        $stmt = $conn->prepare("UPDATE raw_material SET is_archived = 0 WHERE material_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE premade_product SET is_archived = 0 WHERE product_id = ?");
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        
        // 3. LOG THE ACTIVITY
        if ($admin_id > 0) {
            $action = 'RESTORE';
            // We use the specific table name so it filters into the "Inventory" tab
            $target_table = $table; 
            
            $description = "Restored $label from archives: $item_name (SKU: $sku). Item is now active.";
            
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