<?php
// actions/update_forgotten_password.php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token']) || !isset($data['new_password'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request. Missing data."]);
    exit();
}

$token = $data['token'];
$new_password = $data['new_password'];

if (strlen($new_password) < 6) {
    echo json_encode(["status" => "error", "message" => "Your new password must be at least 6 characters long."]);
    exit();
}

// 1. Verify the token one last time before making changes
$stmt = $conn->prepare("SELECT admin_id FROM admin WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active' AND is_archived = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(["status" => "error", "message" => "This reset link is invalid or has expired."]);
    $stmt->close();
    exit();
}

$user = $result->fetch_assoc();
$admin_id = $user['admin_id'];
$stmt->close();

$conn->begin_transaction();

try {
    // 2. Hash the new password securely
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 3. Update the password AND wipe out the token so it can't be used again
    $update_stmt = $conn->prepare("UPDATE admin SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE admin_id = ?");
    $update_stmt->bind_param("si", $hashed_password, $admin_id);
    $update_stmt->execute();
    $update_stmt->close();

    // 4. Log the security activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $description = "User securely reset their password via email recovery link from IP: $ip_address.";
    
    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'admin', ?, ?)");
    $log_stmt->bind_param("iis", $admin_id, $admin_id, $description);
    $log_stmt->execute();
    $log_stmt->close();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Password updated successfully."]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "System error. Please try again later."]);
}