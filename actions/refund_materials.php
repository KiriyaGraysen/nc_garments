<?php
// actions/refund_materials.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['project_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing Project ID"]);
    exit;
}

$project_id = (int)$data['project_id'];

// 1. Get the materials to refund
$stmt = $conn->prepare("SELECT material_id, quantity_used FROM project_breakdown WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->begin_transaction();
try {
    // 2. Add stock back (Notice the '+')
    $update_stock = $conn->prepare("UPDATE raw_material SET current_stock = current_stock + ? WHERE material_id = ?");
    $bind_qty = 0;
    $bind_id = 0;
    $update_stock->bind_param("di", $bind_qty, $bind_id);
    
    foreach ($materials as $mat) {
        $bind_qty = $mat['quantity_used'];
        $bind_id = $mat['material_id'];
        $update_stock->execute();
    }

    // 3. Rollback the project status
    $update_proj = $conn->prepare("UPDATE project SET progress = 'not started' WHERE project_id = ?");
    $update_proj->bind_param("i", $project_id);
    $update_proj->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Materials refunded and project reset!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Refund failed: " . $e->getMessage()]);
}