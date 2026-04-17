<?php
require_once('../config/database.php');

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['sale_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sale ID is required.']);
    exit;
}

$sale_id = (int)$data['sale_id'];

// Start a transaction because we are modifying multiple tables!
$conn->begin_transaction();

try {
    // 1. Find all items associated with this sale
    $items_stmt = $conn->prepare("SELECT product_id, quantity FROM retail_sale_item WHERE sale_id = ?");
    $items_stmt->bind_param("i", $sale_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();

    // 2. Loop through each item and RESTORE the inventory
    $restore_stmt = $conn->prepare("UPDATE premade_product SET current_stock = current_stock + ? WHERE product_id = ?");
    
    $qty = 0;
    $prod_id = 0;
    $restore_stmt->bind_param("di", $qty, $prod_id);

    while ($row = $items_result->fetch_assoc()) {
        $qty = $row['quantity'];
        $prod_id = $row['product_id'];
        $restore_stmt->execute();
    }

    // 3. Delete the items from the breakdown table
    $delete_items = $conn->prepare("DELETE FROM retail_sale_item WHERE sale_id = ?");
    $delete_items->bind_param("i", $sale_id);
    $delete_items->execute();

    // 4. Finally, delete the main sale record itself
    $delete_sale = $conn->prepare("DELETE FROM retail_sale WHERE sale_id = ?");
    $delete_sale->bind_param("i", $sale_id);
    $delete_sale->execute();

    // If everything worked perfectly, commit the changes to the database
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Sale voided and inventory restored.']);

} catch (Exception $e) {
    // If anything fails, rollback so we don't accidentally corrupt the inventory!
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}