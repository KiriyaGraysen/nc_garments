<?php
require_once('../config/database.php');

// Security check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data && !empty($data['cart'])) {
    $admin_id = (int)$_SESSION['admin_id'];
    $payment_method = trim($data['payment_method']);
    $reference_number = empty($data['reference_number']) ? null : trim($data['reference_number']);
    $total_amount = (float)$data['total_amount'];
    
    if(empty($payment_method)) {
        echo json_encode(["status" => "error", "message" => "Payment method is required."]);
        exit;
    }

    // 🔥 START TRANSACTION: Ensure all or nothing happens
    $conn->begin_transaction();

    try {
        // 1. Create the Master Receipt (retail_sale)
        $stmt1 = $conn->prepare("INSERT INTO retail_sale (processed_by_admin, total_amount, payment_method, reference_number) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("idss", $admin_id, $total_amount, $payment_method, $reference_number);
        $stmt1->execute();
        
        $sale_id = $conn->insert_id; // Grab the newly created Sale ID

        // 2. Prepare statements for the loop
        $stmt_item = $conn->prepare("INSERT INTO retail_sale_item (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE premade_product SET current_stock = current_stock - ? WHERE product_id = ?");
        $stmt_name = $conn->prepare("SELECT product_name, size FROM premade_product WHERE product_id = ?");

        $log_items = []; // Array to hold details for the Activity Log

        // 3. Loop through the cart to save items, deduct stock, and build the log snapshot
        foreach ($data['cart'] as $item) {
            $product_id = (int)$item['id'];
            $qty = (int)$item['qty'];
            $price = (float)$item['price'];
            $subtotal = $qty * $price;

            // Save the line item (locking in the historical price)
            $stmt_item->bind_param("iiidd", $sale_id, $product_id, $qty, $price, $subtotal);
            $stmt_item->execute();

            // Deduct the physical inventory
            $stmt_stock->bind_param("ii", $qty, $product_id);
            $stmt_stock->execute();

            // Fetch the exact product name and size for the audit log
            $stmt_name->bind_param("i", $product_id);
            $stmt_name->execute();
            $res_name = $stmt_name->get_result()->fetch_assoc();
            $product_name = $res_name ? $res_name['product_name'] . ' (' . $res_name['size'] . ')' : 'Unknown Product';

            // Add to our detailed log array
            $log_items[] = [
                'name' => $product_name,
                'qty' => $qty,
                'unit_cost' => $price,
                'total' => $subtotal
            ];
        }

        // 4. 🚨 LOG THE ACTIVITY BEFORE COMMITTING
        $action = 'CREATE';
        $target_table = 'retail_sale';
        
        $formatted_sale_id = "#SALE-" . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
        $summary = "Processed POS retail sale ($formatted_sale_id) via $payment_method.";
        
        // Build the JSON payload matching your System Activity modal structure
        $log_payload = json_encode([
            'is_detailed' => true,
            'summary' => $summary,
            'project' => "Retail POS Checkout", // Maps to the top left corner of the modal
            'total_value' => $total_amount,
            'items' => $log_items
        ]);

        $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("issis", $admin_id, $action, $target_table, $sale_id, $log_payload);
        $log_stmt->execute();

        // 🔥 COMMIT TRANSACTION: Everything succeeded, save it permanently!
        $conn->commit();
        echo json_encode(["status" => "success", "sale_id" => $sale_id]);

    } catch (Exception $e) {
        // Something broke! Roll back the database so we don't lose money or stock.
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Transaction Failed: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Cart is empty or invalid payload."]);
}