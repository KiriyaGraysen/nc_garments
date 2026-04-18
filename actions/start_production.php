<?php
require_once('../config/database.php');

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['project_id'])) {
    echo json_encode(["status" => "error", "message" => "Project ID missing."]);
    exit;
}

$project_id = (int)$data['project_id'];
$force_start = isset($data['force_start']) ? (bool)$data['force_start'] : false;
$target_phase = isset($data['target_phase']) ? $data['target_phase'] : 'cutting';
$admin_id = (int)$_SESSION['admin_id'];

// 1. Fetch all required materials and project name
$stmt = $conn->prepare("
    SELECT pb.material_id, pb.quantity_used as required_qty, pb.unit_cost, pb.total_cost,
           rm.current_stock, rm.material_name, p.project_name
    FROM project_breakdown pb 
    JOIN raw_material rm ON pb.material_id = rm.material_id 
    JOIN project p ON pb.project_id = p.project_id
    WHERE pb.project_id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$res = $stmt->get_result();
$materials = $res->fetch_all(MYSQLI_ASSOC);

$project_name = !empty($materials) ? $materials[0]['project_name'] : "Unknown Project";

// 2. Check for deficits (if not force-starting)
if (!$force_start) {
    $shortages = [];
    foreach ($materials as $mat) {
        if (($mat['current_stock'] - $mat['required_qty']) < 0) {
            $missing = abs($mat['current_stock'] - $mat['required_qty']);
            $shortages[] = "{$mat['material_name']} (Short by: {$missing})";
        }
    }
    if (count($shortages) > 0) {
        echo json_encode(["status" => "warning", "shortages" => $shortages]);
        exit;
    }
}

// 3. Begin Production Transaction
$conn->begin_transaction();

try {
    $update_stock = $conn->prepare("UPDATE raw_material SET current_stock = current_stock - ? WHERE material_id = ?");
    $log_items = [];
    $total_disbursement_value = 0;

    foreach ($materials as $mat) {
        $update_stock->bind_param("di", $mat['required_qty'], $mat['material_id']);
        $update_stock->execute();

        // Build items list for the audit log modal
        $log_items[] = [
            'name' => $mat['material_name'],
            'qty' => (float)$mat['required_qty'],
            'unit_cost' => (float)$mat['unit_cost'],
            'total' => (float)$mat['total_cost']
        ];
        $total_disbursement_value += (float)$mat['total_cost'];
    }

    // Update Project Status
    $update_project = $conn->prepare("UPDATE project SET progress = ?, start_date = CURRENT_DATE() WHERE project_id = ?");
    $update_project->bind_param("si", $target_phase, $project_id);
    $update_project->execute();

    // 🚨 4. LOG THE ACTIVITY (Receipt Mode)
    $formatted_prj = "PRJ-" . str_pad($project_id, 4, '0', STR_PAD_LEFT);
    $phase_label = strtoupper($target_phase);
    
    $summary = "Production started ($phase_label). Materials have been disbursed from inventory.";

    $log_payload = json_encode([
        'is_detailed' => true,
        'summary' => $summary,
        'project' => $formatted_prj . ' - ' . $project_name,
        'total_value' => $total_disbursement_value,
        'items' => $log_items
    ]);

    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'inventory_adjustments', ?, ?)");
    $log_stmt->bind_param("iis", $admin_id, $project_id, $log_payload);
    $log_stmt->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Production started! Materials deducted."]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Database transaction failed: " . $e->getMessage()]);
}