<?php
require_once('../config/database.php');

// Grab the ID instead of the name
$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 1; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    
    $file = $_FILES['backup_file'];
    $filename = $file['name'];
    
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($file_ext !== 'sql') {
        echo json_encode(["status" => "error", "message" => "Invalid file type. Please upload a .sql file."]);
        exit;
    }

    $sql_contents = file_get_contents($file['tmp_name']);
    
    $bytes = filesize($file['tmp_name']);
    $file_size = number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes < 1048576) $file_size = number_format($bytes / 1024, 2) . ' KB';

    if ($conn->multi_query($sql_contents)) {
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        // INSERT successful log using admin_id
        $stmt = $conn->prepare("INSERT INTO backup_log (filename, file_size, action_type, admin_id, status) VALUES (?, ?, 'Restore', ?, 'Successful')");
        $stmt->bind_param("ssi", $filename, $file_size, $admin_id);
        $stmt->execute();

        echo json_encode(["status" => "success"]);
    } else {
        // INSERT failed log using admin_id
        $stmt = $conn->prepare("INSERT INTO backup_log (filename, file_size, action_type, admin_id, status) VALUES (?, ?, 'Restore', ?, 'Failed')");
        $stmt->bind_param("ssi", $filename, $file_size, $admin_id);
        $stmt->execute();

        echo json_encode(["status" => "error", "message" => "Database restore failed: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No file uploaded."]);
}