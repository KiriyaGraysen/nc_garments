<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $id = !empty($data['admin_id']) ? (int)$data['admin_id'] : null;
    $name = trim($data['full_name']);
    $email = trim($data['email']);
    $username = trim($data['username']);
    $role = trim($data['role']);
    $password = $data['password'] ?? '';

    $actor_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    // Check if username already exists (excluding the current user being edited)
    $check = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? AND admin_id != ?");
    $check_id = $id ?? 0;
    $check->bind_param("si", $username, $check_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username already exists."]);
        exit();
    }

    // 🚨 START TRANSACTION
    $conn->begin_transaction();

    try {
        if ($id) {
            // ==========================================
            // FLOW 1: UPDATE EXISTING ACCOUNT (DELTA LOG)
            // ==========================================
            
            // 1. Fetch "Before" Snapshot
            $old_stmt = $conn->prepare("SELECT full_name, email, username, role FROM admin WHERE admin_id = ?");
            $old_stmt->bind_param("i", $id);
            $old_stmt->execute();
            $old_data = $old_stmt->get_result()->fetch_assoc();

            // 2. Execute Update
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin SET full_name=?, email=?, username=?, role=?, password_hash=? WHERE admin_id=?");
                $stmt->bind_param("sssssi", $name, $email, $username, $role, $hashed, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admin SET full_name=?, email=?, username=?, role=? WHERE admin_id=?");
                $stmt->bind_param("ssssi", $name, $email, $username, $role, $id);
            }
            $stmt->execute();

            // 3. Calculate Deltas and Log
            if ($actor_id > 0 && $old_data) {
                $changes = [];
                
                if ($old_data['full_name'] !== $name) $changes[] = ['field' => 'Full Name', 'old' => $old_data['full_name'], 'new' => $name];
                if ($old_data['email'] !== $email) $changes[] = ['field' => 'Email Address', 'old' => $old_data['email'], 'new' => $email];
                if ($old_data['username'] !== $username) $changes[] = ['field' => 'Username', 'old' => '@' . $old_data['username'], 'new' => '@' . $username];
                if ($old_data['role'] !== $role) $changes[] = ['field' => 'System Role', 'old' => strtoupper($old_data['role']), 'new' => strtoupper($role)];
                
                // Securely log if a password was changed without revealing the password
                if (!empty($password)) {
                    $changes[] = ['field' => 'Account Password', 'old' => '********', 'new' => 'Reset / Changed'];
                }

                // Only log if something actually changed
                if (!empty($changes)) {
                    $change_count = count($changes);

                    $log_payload = json_encode([
                        'is_detailed' => true,
                        'type' => 'update_comparison',
                        'summary' => "Modified $change_count field(s) in staff account.",
                        'project' => $name . " (@" . $username . ")", // Maps to the header of the modal
                        'changes' => $changes
                    ]);

                    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'admin', ?, ?)");
                    $log_stmt->bind_param("iis", $actor_id, $id, $log_payload);
                    $log_stmt->execute();
                }
            }

        } else {
            // ==========================================
            // FLOW 2: CREATE NEW ACCOUNT
            // ==========================================
            
            if (empty($password)) {
                echo json_encode(["status" => "error", "message" => "Password is required for new accounts."]);
                exit();
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin (full_name, email, username, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssss", $name, $email, $username, $role, $hashed);
            $stmt->execute();
            
            $new_id = $conn->insert_id;

            // Log the Creation
            if ($actor_id > 0) {
                $role_upper = strtoupper($role);
                $description = "Provisioned new staff account: $name (@$username) with role $role_upper.";

                $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'CREATE', 'admin', ?, ?)");
                $log_stmt->bind_param("iis", $actor_id, $new_id, $description);
                $log_stmt->execute();
            }
        }

        // 🚨 COMMIT TRANSACTION: Everything succeeded!
        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        // 🚨 ROLLBACK TRANSACTION: An error occurred, revert changes
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload received."]);
}