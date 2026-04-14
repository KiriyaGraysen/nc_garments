<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $type = $data['item_type']; 
    $id = !empty($data['item_id']) ? (int)$data['item_id'] : null;
    $sku = $data['sku'];
    $name = $data['name'];
    $stock = (int)$data['stock'];
    $price = (float)$data['price'];
    $alert = (int)$data['alert'];

    if ($type === 'raw_material') {
        $uom = $data['uom'];
        if ($id) {
            // Smart Update: Shifts current_price to last_price automatically!
            $stmt = $conn->prepare("UPDATE raw_material SET sku=?, material_name=?, current_stock=?, unit_of_measure=?, last_price=current_price, current_price=?, min_stock_alert=? WHERE material_id=?");
            $stmt->bind_param("ssisdii", $sku, $name, $stock, $uom, $price, $alert, $id);
        } else {
            // On insert, last_price is set to be the same as current_price initially
            $stmt = $conn->prepare("INSERT INTO raw_material (sku, material_name, current_stock, unit_of_measure, current_price, last_price, min_stock_alert) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisddi", $sku, $name, $stock, $uom, $price, $price, $alert);
        }
    } else {
        $size = $data['size'];
        if ($id) {
            $stmt = $conn->prepare("UPDATE premade_product SET sku=?, product_name=?, current_stock=?, size=?, selling_price=?, min_stock_alert=? WHERE product_id=?");
            $stmt->bind_param("ssisdii", $sku, $name, $stock, $size, $price, $alert, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO premade_product (sku, product_name, current_stock, size, selling_price, min_stock_alert) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisdi", $sku, $name, $stock, $size, $price, $alert);
        }
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}