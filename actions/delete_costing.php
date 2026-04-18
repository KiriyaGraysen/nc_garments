<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $project_id = (int)$data['project_id'];

    // 1. FETCH THE DATA *BEFORE* WE DELETE IT
    $p_stmt = $conn->prepare("SELECT project_name FROM project WHERE project_id = ?");
    $p_stmt->bind_param("i", $project_id);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result()->fetch_assoc();
    $project_name = $p_res ? $p_res['project_name'] : "Unknown Project";

    $b_stmt = $conn->prepare("
        SELECT rm.material_name, pb.quantity_used, pb.unit_cost, pb.total_cost
        FROM project_breakdown pb
        JOIN raw_material rm ON pb.material_id = rm.material_id
        WHERE pb.project_id = ?
    ");
    $b_stmt->bind_param("i", $project_id);
    $b_stmt->execute();
    $b_res = $b_stmt->get_result();

    $deleted_items = [];
    $total_value = 0;
    while ($row = $b_res->fetch_assoc()) {
        $deleted_items[] = [
            'name' => $row['material_name'],
            'qty' => $row['quantity_used'],
            'unit_cost' => $row['unit_cost'],
            'total' => $row['total_cost']
        ];
        $total_value += $row['total_cost'];
    }
    $deleted_count = count($deleted_items);

    // 2. EXECUTE THE DELETION
    $stmt = $conn->prepare("DELETE FROM project_breakdown WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    
    if($stmt->execute()) {
        
        // 3. SAVE THE STRUCTURED JSON LOG
        if (isset($_SESSION['admin_id']) && $deleted_count > 0) {
            $admin_id = $_SESSION['admin_id'];
            $formatted_prj = "PRJ-2026-" . str_pad($project_id, 3, '0', STR_PAD_LEFT);
            $summary = "Cleared costing breakdown for $formatted_prj ($deleted_count items removed).";
            
            // We package the exact snapshot into a JSON string
            $log_payload = json_encode([
                'is_detailed' => true,
                'summary' => $summary,
                'project' => $formatted_prj . ' - ' . $project_name,
                'total_value' => $total_value,
                'items' => $deleted_items
            ]);

            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'DELETE', 'project_breakdown', ?, ?)");
            $log_stmt->bind_param("iis", $admin_id, $project_id, $log_payload);
            $log_stmt->execute();
        }

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}