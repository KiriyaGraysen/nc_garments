<?php
require_once('../config/database.php');

// Ensure session is started so we can track WHICH admin did this
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['item_id']) && isset($data['item_type'])) {
    $id = (int)$data['item_id'];
    $type = $data['item_type']; // 'raw_material' or 'premade_product'

    // 1. FETCH ITEM DETAILS BEFORE ARCHIVING (For a better log description)
    $item_name = "Unknown Item";
    $item_sku = "N/A";
    
    if ($type === 'raw_material') {
        $fetch_stmt = $conn->prepare("SELECT material_name, sku FROM raw_material WHERE material_id = ?");
    } else {
        $fetch_stmt = $conn->prepare("SELECT product_name, sku FROM premade_product WHERE product_id = ?");
    }
    
    $fetch_stmt->bind_param("i", $id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result()->fetch_assoc();
    
    if ($res) {
        // Grab the name dynamically depending on which table we queried
        $item_name = isset($res['material_name']) ? $res['material_name'] : $res['product_name'];
        $item_sku = $res['sku'];
    }

    // 2. EXECUTE THE ARCHIVE UPDATE
    if ($type === 'raw_material') {
        $stmt = $conn->prepare("UPDATE raw_material SET is_archived = 1 WHERE material_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE premade_product SET is_archived = 1 WHERE product_id = ?");
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        
        // 3. LOG THE ACTIVITY
        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            $action = 'ARCHIVE';
            $target_table = $type; 
            
            // Format the label nicely based on the table name
            $type_label = ($type === 'raw_material') ? 'Raw Material' : 'Premade Product';
            $description = "Archived $type_label: $item_name (SKU: $item_sku).";
            
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