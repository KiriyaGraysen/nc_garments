<?php
require_once('../config/database.php');

if (isset($_GET['project_id'])) {
    $project_id = (int)$_GET['project_id'];
    
    $stmt = $conn->prepare("SELECT material_id, quantity_used, unit_cost FROM project_breakdown WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $result]);
}
?>