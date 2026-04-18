<?php
require_once('../config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['item_id']) || !isset($data['item_type']) || !isset($data['action']) || !isset($data['qty']) || empty($data['reason'])) {
    echo json_encode(["status" => "error", "message" => "Missing required data."]);
    exit;
}

$id = (int)$data['item_id'];
$type = $data['item_type'];
$action = $data['action']; // 'add' or 'deduct'
$qty = (float)$data['qty'];
$reason = trim($data['reason']);
$admin_id = (int)$_SESSION['admin_id'];

$conn->begin_transaction();

try {
    // 1. Fetch current details to create a clear log
    if ($type === 'raw_material') {
        $table = 'raw_material';
        $id_column = 'material_id';
        $name_column = 'material_name';
        $fetch_stmt = $conn->prepare("SELECT material_name as name, current_stock FROM raw_material WHERE material_id = ?");
    } else {
        $table = 'premade_product';
        $id_column = 'product_id';
        $name_column = 'product_name';
        $fetch_stmt = $conn->prepare("SELECT product_name as name, current_stock FROM premade_product WHERE product_id = ?");
    }

    $fetch_stmt->bind_param("i", $id);
    $fetch_stmt->execute();
    $item = $fetch_stmt->get_result()->fetch_assoc();

    if (!$item) {
        throw new Exception("Item not found.");
    }

    $old_stock = (float)$item['current_stock'];
    $item_name = $item['name'];

    // 2. Perform the adjustment
    if ($action === 'add') {
        $new_stock = $old_stock + $qty;
        $operator = "+";
    } else {
        $new_stock = $old_stock - $qty;
        $operator = "-";
    }

    $update_stmt = $conn->prepare("UPDATE $table SET current_stock = ? WHERE $id_column = ?");
    $update_stmt->bind_param("di", $new_stock, $id);
    $update_stmt->execute();

    // 3. Create the Audit Log (Using your dynamic Receipt JSON)
    $log_action = 'UPDATE';
    $summary = "Manual Stock Adjustment ($action) for $item_name. Reason: $reason";
    
    $changes = [
        ['field' => 'Stock Level', 'old' => $old_stock, 'new' => "$new_stock ($operator$qty)"]
    ];

    $log_payload = json_encode([
        'is_detailed' => true,
        'type' => 'update_comparison',
        'summary' => $summary,
        'project' => 'Direct Adjustment',
        'changes' => $changes
    ]);

    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, 'inventory_adjustments', ?, ?)");
    $log_stmt->bind_param("isis", $admin_id, $log_action, $id, $log_payload);
    $log_stmt->execute();

    $conn->commit();
    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}