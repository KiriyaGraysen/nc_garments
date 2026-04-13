<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// 1. Require database first (This automatically starts our 30-day secure session!)
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit();
}

$json_data = file_get_contents('php://input');
$request = json_decode($json_data, true);

$input_username = $request['username'] ?? '';
$input_password = $request['password'] ?? '';

if (empty($input_username) || empty($input_password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in both username and password.']);
    exit();
}

$stmt = $conn->prepare("
    SELECT admin_id, full_name, password_hash, role
    FROM admin
    WHERE username = ?
");
$stmt->bind_param("s", $input_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    
    if(password_verify($input_password, $admin['password_hash'])) {
        
        // Force the browser to accept the new secure 30-day token
        session_regenerate_id(true); 
        
        $last_login = date("Y-m-d H:i:s");
        $update_stmt = $conn->prepare("UPDATE admin SET last_login = ? WHERE admin_id = ?");
        $update_stmt->bind_param("si", $last_login, $admin['admin_id']);
        $update_stmt->execute();
        
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['full_name'] = $admin['full_name'];
        $_SESSION['role'] = $admin['role'];
        
        echo json_encode(['success' => true, 'message' => 'Login successful.']);
        $update_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
}

$stmt->close();
$conn->close();