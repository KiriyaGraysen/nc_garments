<?php
require_once('../config/database.php');

header('Content-Type: application/json');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$admin_id = (int)$_SESSION['admin_id'];

// 1. FETCH SALE MASTER DETAILS BEFORE VOIDING
$sale_stmt = $conn->prepare("SELECT total_amount, payment_method, reference_number FROM retail_sale WHERE sale_id = ?");
$sale_stmt->bind_param("i", $sale_id);
$sale_stmt->execute();
$sale_res = $sale_stmt->get_result()->fetch_assoc();

if (!$sale_res) {
    echo json_encode(['status' => 'error', 'message' => 'Sale not found.']);
    exit;
}

$total_amount = $sale_res['total_amount'];
$payment_method = $sale_res['payment_method'];

// 2. FETCH ALL ITEMS FOR THE DETAILED JSON AUDIT LOG
$items_stmt = $conn->prepare("
    SELECT rsi.product_id, rsi.quantity, rsi.unit_price, rsi.subtotal, pp.product_name, pp.size
    FROM retail_sale_item rsi
    JOIN premade_product pp ON rsi.product_id = pp.product_id
    WHERE rsi.sale_id = ?
");
$items_stmt->bind_param("i", $sale_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$log_items = [];
$restore_data = []; // Store IDs and quantities safely before deletion

while ($row = $items_result->fetch_assoc()) {
    $restore_data[] = [
        'product_id' => $row['product_id'],
        'quantity' => $row['quantity']
    ];
    $product_name = $row['product_name'] . ' (' . $row['size'] . ')';
    $log_items[] = [
        'name' => $product_name,
        'qty' => $row['quantity'],
        'unit_cost' => $row['unit_price'],
        'total' => $row['subtotal']
    ];
}

// Start a transaction because we are modifying multiple tables!
$conn->begin_transaction();

try {
    // 3. RESTORE INVENTORY
    $restore_stmt = $conn->prepare("UPDATE premade_product SET current_stock = current_stock + ? WHERE product_id = ?");
    foreach ($restore_data as $item) {
        $restore_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $restore_stmt->execute();
    }

    // 4. DELETE THE ITEMS FROM BREAKDOWN TABLE
    $delete_items = $conn->prepare("DELETE FROM retail_sale_item WHERE sale_id = ?");
    $delete_items->bind_param("i", $sale_id);
    $delete_items->execute();

    // 5. DELETE THE MAIN SALE RECORD
    $delete_sale = $conn->prepare("DELETE FROM retail_sale WHERE sale_id = ?");
    $delete_sale->bind_param("i", $sale_id);
    $delete_sale->execute();

    // 6. 🚨 LOG THE DETAILED VOID ACTIVITY
    $action = 'VOID';
    $target_table = 'retail_sale'; // Maps to the "Payments" tab
    $formatted_sale = "#SALE-" . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
    
    $summary = "Voided retail sale ($formatted_sale) via $payment_method. Physical inventory restored.";

    // Package the items into our detailed modal JSON format
    $log_payload = json_encode([
        'is_detailed' => true,
        'summary' => $summary,
        'project' => "Voided POS Sale",
        'total_value' => $total_amount,
        'items' => $log_items
    ]);

    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $sale_id, $log_payload);
    $log_stmt->execute();

    // If everything worked perfectly, commit the changes to the database
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Sale voided and inventory restored.']);

} catch (Exception $e) {
    // If anything fails, rollback so we don't accidentally corrupt the inventory!
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}