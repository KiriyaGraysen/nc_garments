<?php
require_once('../config/database.php');

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

// 1. Fetch all required materials for this specific project
// 🚨 FIX: Changed pb.quantity to pb.quantity_used so the database doesn't crash!
$stmt = $conn->prepare("
    SELECT pb.material_id, pb.quantity_used as required_qty, rm.current_stock, rm.material_name 
    FROM project_breakdown pb 
    JOIN raw_material rm ON pb.material_id = rm.material_id 
    WHERE pb.project_id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$materials_res = $stmt->get_result();

// Safety Net: If the SQL fails, send a clean error back to JavaScript
if (!$materials_res) {
    echo json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]);
    exit;
}

$materials = $materials_res->fetch_all(MYSQLI_ASSOC);

// 2. Check for deficits (if we haven't clicked 'Force Start' yet)
if (!$force_start) {
    $shortages = [];
    foreach ($materials as $mat) {
        $resulting_stock = $mat['current_stock'] - $mat['required_qty'];
        if ($resulting_stock < 0) {
            // Calculate exactly how much they are short
            $missing = abs($resulting_stock);
            $shortages[] = "{$mat['material_name']} (Short by: {$missing})";
        }
    }

    // If there are shortages, pause and send a warning back to JavaScript!
    if (count($shortages) > 0) {
        echo json_encode([
            "status" => "warning", 
            "shortages" => $shortages
        ]);
        exit;
    }
}

// 3. If we have enough stock, OR if the user clicked "Force Start", proceed!
$conn->begin_transaction();

try {
    $update_stock = $conn->prepare("UPDATE raw_material SET current_stock = current_stock - ? WHERE material_id = ?");
    
    // 🚨 THE FIX: Declare variables and bind them ONCE outside the loop
    $bind_qty = 0;
    $bind_id = 0;
    $update_stock->bind_param("di", $bind_qty, $bind_id);
    
    foreach ($materials as $mat) {
        // Just update the variables, and execute!
        $bind_qty = $mat['required_qty'];
        $bind_id = $mat['material_id'];
        $update_stock->execute();
    }

    // Update the project to signify it has officially started!
    $update_project = $conn->prepare("UPDATE project SET progress = 'cutting' WHERE project_id = ?");
    $update_project->bind_param("i", $project_id);
    $update_project->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Production started! Materials deducted."]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Database transaction failed: " . $e->getMessage()]);
}
?>