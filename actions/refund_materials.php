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
$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

// 1. Get the materials to refund and project name for the log
$stmt = $conn->prepare("
    SELECT pb.material_id, pb.quantity_used, pb.unit_cost, pb.total_cost, rm.material_name, p.project_name
    FROM project_breakdown pb
    JOIN raw_material rm ON pb.material_id = rm.material_id
    JOIN project p ON pb.project_id = p.project_id
    WHERE pb.project_id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$project_name = !empty($materials) ? $materials[0]['project_name'] : "Unknown Project";

$conn->begin_transaction();
try {
    // 2. Add stock back
    $update_stock = $conn->prepare("UPDATE raw_material SET current_stock = current_stock + ? WHERE material_id = ?");
    
    $log_items = [];
    $total_refund_value = 0;

    foreach ($materials as $mat) {
        $qty = (float)$mat['quantity_used'];
        $m_id = (int)$mat['material_id'];
        
        $update_stock->bind_param("di", $qty, $m_id);
        $update_stock->execute();

        // Build items for the audit log modal
        $log_items[] = [
            'name' => $mat['material_name'],
            'qty' => $qty,
            'unit_cost' => (float)$mat['unit_cost'],
            'total' => (float)$mat['total_cost']
        ];
        $total_refund_value += (float)$mat['total_cost'];
    }

    // 3. Rollback the project status
    $update_proj = $conn->prepare("UPDATE project SET progress = 'not started', start_date = NULL WHERE project_id = ?");
    $update_proj->bind_param("i", $project_id);
    $update_proj->execute();

    // 4. 🚨 LOG THE ACTIVITY (Receipt/Return Slip Mode)
    if ($admin_id > 0) {
        $formatted_prj = "PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
        $summary = "Materials refunded and returned to inventory. Project progress reset to 'Not Started'.";

        $log_payload = json_encode([
            'is_detailed' => true,
            'summary' => $summary,
            'project' => $formatted_prj . ' - ' . $project_name,
            'total_value' => $total_refund_value,
            'items' => $log_items
        ]);

        $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'RESTORE', 'inventory_adjustments', ?, ?)");
        $log_stmt->bind_param("iis", $admin_id, $project_id, $log_payload);
        $log_stmt->execute();
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Materials refunded and project reset!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Refund failed: " . $e->getMessage()]);
}