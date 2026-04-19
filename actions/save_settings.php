<?php
// actions/save_settings.php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once('../config/database.php');

// Must be logged in to change settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $admin_id = (int)$_SESSION['admin_id'];
    $full_name = trim($data['full_name']);
    $email = trim($data['email']);
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';

    if (empty($full_name) || empty($email)) {
        echo json_encode(["status" => "error", "message" => "Full Name and Email are required."]);
        exit();
    }

    // 1. Ensure the new email isn't already taken by ANOTHER admin
    $check_email = $conn->prepare("SELECT admin_id FROM admin WHERE email = ? AND admin_id != ?");
    $check_email->bind_param("si", $email, $admin_id);
    $check_email->execute();
    if ($check_email->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "This email address is already associated with another account."]);
        exit();
    }
    $check_email->close();

    // 2. Fetch current user data (to check old password and log changes)
    $stmt = $conn->prepare("SELECT full_name, email, password_hash FROM admin WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $conn->begin_transaction();

    try {
        $changes = []; // Track what changed for the activity log

        // 3. Handle Password Change Logic (If requested)
        if (!empty($new_password)) {
            // They want to change the password, so verify the old one first!
            if (!password_verify($current_password, $user['password_hash'])) {
                echo json_encode(["status" => "error", "message" => "The Current Password you entered is incorrect."]);
                exit();
            }

            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_pass = $conn->prepare("UPDATE admin SET password_hash = ? WHERE admin_id = ?");
            $update_pass->bind_param("si", $hashed_password, $admin_id);
            $update_pass->execute();
            $update_pass->close();
            
            $changes[] = ['field' => 'Password', 'old' => '********', 'new' => '******** (Updated)'];
        }

        // 4. Update Personal Details
        if ($user['full_name'] !== $full_name || $user['email'] !== $email) {
            $update_info = $conn->prepare("UPDATE admin SET full_name = ?, email = ? WHERE admin_id = ?");
            $update_info->bind_param("ssi", $full_name, $email, $admin_id);
            $update_info->execute();
            $update_info->close();

            if ($user['full_name'] !== $full_name) $changes[] = ['field' => 'Full Name', 'old' => $user['full_name'], 'new' => $full_name];
            if ($user['email'] !== $email) $changes[] = ['field' => 'Email Address', 'old' => $user['email'], 'new' => $email];
            
            // Update the live session so the header changes immediately
            $_SESSION['full_name'] = $full_name;
        }

        // 5. Log the Activity
        if (!empty($changes)) {
            $change_count = count($changes);
            $log_payload = json_encode([
                'is_detailed' => true,
                'type' => 'update_comparison',
                'summary' => "Updated $change_count personal setting(s).",
                'project' => "Personal Account", 
                'changes' => $changes
            ]);

            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'admin', ?, ?)");
            $log_stmt->bind_param("iis", $admin_id, $admin_id, $log_payload);
            $log_stmt->execute();
            $log_stmt->close();
        }

        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "System error: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload received."]);
}