<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// 1. Require database first (This automatically starts our secure session!)
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit();
}

$json_data = file_get_contents('php://input');
$request = json_decode($json_data, true);

// 🚨 UPDATED: Accept 'identifier' which handles both Username or Email
$input_identifier = $request['identifier'] ?? '';
$input_password = $request['password'] ?? '';

if (empty($input_identifier) || empty($input_password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in both username/email and password.']);
    exit();
}

// 🚨 SECURITY FIX: Check BOTH username OR email, and ensure the account is active/not archived!
$stmt = $conn->prepare("
    SELECT admin_id, full_name, password_hash, role
    FROM admin
    WHERE (username = ? OR email = ?) AND is_archived = 0 AND status = 'active'
");

// Bind the identifier twice: once for the username check, once for the email check
$stmt->bind_param("ss", $input_identifier, $input_identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    
    if(password_verify($input_password, $admin['password_hash'])) {
        
        // Force the browser to accept the new secure session token
        session_regenerate_id(true); 
        
        // 1. Update the Last Login timestamp
        $last_login = date("Y-m-d H:i:s");
        $update_stmt = $conn->prepare("UPDATE admin SET last_login = ? WHERE admin_id = ?");
        $update_stmt->bind_param("si", $last_login, $admin['admin_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // 2. Set Session Variables
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['full_name'] = $admin['full_name'];
        $_SESSION['role'] = $admin['role'];
        
        // 3. 🚨 LOG THE LOGIN ACTIVITY
        $action = 'LOGIN';
        $target_table = 'admin'; // This triggers your "Security" tab filter
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $description = "User authenticated and started a secure session from IP: $ip_address.";
        
        $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("issis", $admin['admin_id'], $action, $target_table, $admin['admin_id'], $description);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Login successful.']);
    } else {
        // We use the same generic error message to prevent "Username Enumeration" attacks
        echo json_encode(['success' => false, 'message' => 'Invalid credentials or account disabled.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials or account disabled.']);
}

$stmt->close();
$conn->close();