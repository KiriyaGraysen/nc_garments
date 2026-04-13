<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $id = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
    $name = $data['full_name'];
    $contact = $data['contact_number'] ?? null;
    $address = $data['address'] ?? null;

    if ($id) {
        $stmt = $conn->prepare("UPDATE customer SET full_name=?, contact_number=?, address=? WHERE customer_id=?");
        $stmt->bind_param("sssi", $name, $contact, $address, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO customer (full_name, contact_number, address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $contact, $address);
    }

    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => $conn->error]);
}