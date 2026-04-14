<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $id = !empty($data['admin_id']) ? (int)$data['admin_id'] : null;
    $name = $data['full_name'];
    $email = $data['email'];
    $username = $data['username'];
    $role = $data['role'];
    $password = $data['password'] ?? '';

    // Check if username already exists (excluding the current user being edited)
    $check = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? AND admin_id != ?");
    $check_id = $id ?? 0;
    $check->bind_param("si", $username, $check_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username already exists."]);
        exit();
    }

    if ($id) {
        // Edit existing account
        if (!empty($password)) {
            // Update with new password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET full_name=?, email=?, username=?, role=?, password_hash=? WHERE admin_id=?");
            $stmt->bind_param("sssssi", $name, $email, $username, $role, $hashed, $id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE admin SET full_name=?, email=?, username=?, role=? WHERE admin_id=?");
            $stmt->bind_param("ssssi", $name, $email, $username, $role, $id);
        }
    } else {
        // Create new account
        if (empty($password)) {
            echo json_encode(["status" => "error", "message" => "Password is required for new accounts."]);
            exit();
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (full_name, email, username, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssss", $name, $email, $username, $role, $hashed);
    }

    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => "Database error."]);
}