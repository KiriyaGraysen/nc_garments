<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['item_id']) && isset($data['item_type'])) {
    $id = (int)$data['item_id'];
    $type = $data['item_type'];
    
    if ($type === 'raw_material') {
        $stmt = $conn->prepare("UPDATE raw_material SET is_archived = 0 WHERE material_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE premade_product SET is_archived = 0 WHERE product_id = ?");
    }
    
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}