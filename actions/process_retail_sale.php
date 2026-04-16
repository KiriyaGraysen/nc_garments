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

        // 3. Loop through the cart to save items AND deduct stock
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
        }

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