<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $type = $data['item_type']; 
    $id = !empty($data['item_id']) ? (int)$data['item_id'] : null;
    $sku = trim($data['sku']);
    $name = trim($data['name']);
    $stock = (int)$data['stock']; // Still needed for CREATE mode
    $price = (float)$data['price'];
    $alert = (int)$data['alert'];

    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
    $table_name = ($type === 'raw_material') ? 'raw_material' : 'premade_product';
    $type_label = ($type === 'raw_material') ? 'Raw Material' : 'Premade Product';

    // 🚨 START TRANSACTION
    $conn->begin_transaction();

    try {
        if ($id) {
            // ==========================================
            // FLOW 1: UPDATE EXISTING ITEM (DELTA LOG)
            // ==========================================
            
            // 1. Fetch "Before" Snapshot
            if ($type === 'raw_material') {
                $uom = trim($data['uom']);
                $old_stmt = $conn->prepare("SELECT sku, material_name as name, unit_of_measure as variant, current_price as price, min_stock_alert as alert FROM raw_material WHERE material_id=?");
            } else {
                $size = trim($data['size']);
                $old_stmt = $conn->prepare("SELECT sku, product_name as name, size as variant, selling_price as price, min_stock_alert as alert FROM premade_product WHERE product_id=?");
            }
            
            $old_stmt->bind_param("i", $id);
            $old_stmt->execute();
            $old_data = $old_stmt->get_result()->fetch_assoc();

            // 2. Execute Update (🚨 STOCK IS NOT UPDATED HERE)
            if ($type === 'raw_material') {
                // Smart Update: Shifts current_price to last_price automatically!
                $stmt = $conn->prepare("UPDATE raw_material SET sku=?, material_name=?, unit_of_measure=?, last_price=current_price, current_price=?, min_stock_alert=? WHERE material_id=?");
                $stmt->bind_param("sssdii", $sku, $name, $uom, $price, $alert, $id);
            } else {
                $stmt = $conn->prepare("UPDATE premade_product SET sku=?, product_name=?, size=?, selling_price=?, min_stock_alert=? WHERE product_id=?");
                $stmt->bind_param("sssdii", $sku, $name, $size, $price, $alert, $id);
            }
            $stmt->execute();

            // 3. Calculate Deltas and Log
            if ($admin_id > 0 && $old_data) {
                $changes = [];
                
                if ($old_data['sku'] !== $sku) $changes[] = ['field' => 'SKU', 'old' => $old_data['sku'], 'new' => $sku];
                if ($old_data['name'] !== $name) $changes[] = ['field' => 'Item Name', 'old' => $old_data['name'], 'new' => $name];
                if ((float)$old_data['price'] !== $price) $changes[] = ['field' => 'Price / Cost', 'old' => '₱ ' . number_format($old_data['price'], 2), 'new' => '₱ ' . number_format($price, 2)];
                if ((int)$old_data['alert'] !== $alert) $changes[] = ['field' => 'Alert Threshold', 'old' => $old_data['alert'], 'new' => $alert];
                
                $variant_val = ($type === 'raw_material') ? $uom : $size;
                $variant_label = ($type === 'raw_material') ? 'Unit of Measure' : 'Size';
                if ($old_data['variant'] !== $variant_val) $changes[] = ['field' => $variant_label, 'old' => $old_data['variant'] ?: 'None', 'new' => $variant_val ?: 'None'];

                // Only log if something actually changed
                if (!empty($changes)) {
                    $change_count = count($changes);

                    $log_payload = json_encode([
                        'is_detailed' => true,
                        'type' => 'update_comparison',
                        'summary' => "Modified $change_count field(s) in $type_label record.",
                        'project' => $sku . ' - ' . $name,
                        'changes' => $changes
                    ]);

                    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', ?, ?, ?)");
                    $log_stmt->bind_param("isis", $admin_id, $table_name, $id, $log_payload);
                    $log_stmt->execute();
                }
            }

        } else {
            // ==========================================
            // FLOW 2: CREATE NEW ITEM
            // ==========================================
            
            if ($type === 'raw_material') {
                $uom = trim($data['uom']);
                $stmt = $conn->prepare("INSERT INTO raw_material (sku, material_name, current_stock, unit_of_measure, current_price, last_price, min_stock_alert) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisddi", $sku, $name, $stock, $uom, $price, $price, $alert);
            } else {
                $size = trim($data['size']);
                $stmt = $conn->prepare("INSERT INTO premade_product (sku, product_name, current_stock, size, selling_price, min_stock_alert) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisdi", $sku, $name, $stock, $size, $price, $alert);
            }
            $stmt->execute();
            
            $new_id = $conn->insert_id;

            // Log the Creation
            if ($admin_id > 0) {
                $description = "Added new $type_label: $name (SKU: $sku) with initial stock of $stock.";

                $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'CREATE', ?, ?, ?)");
                $log_stmt->bind_param("isis", $admin_id, $table_name, $new_id, $description);
                $log_stmt->execute();
            }
        }

        // 🚨 COMMIT TRANSACTION: Everything succeeded!
        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        // 🚨 ROLLBACK TRANSACTION: An error occurred, revert changes
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload received."]);
}