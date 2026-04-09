<?php
session_start();
header('Content-Type: application/json');

require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => true,
        "message" => "Invalid request method."
    ]);
    exit();
}

$json_data = file_get_contents('php://input');
$request = json_decode($json_data, true);

$input_username = $request['username'] ?? '';
$input_password = $request['password'] ?? '';

if (empty($input_username) || empty($input_password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in both username and password.'
    ]);
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
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['full_name'] = $admin['full_name'];
        $_SESSION['role'] = $admin['role'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 'message' => 'Invalid username or password.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 'message' => 'Invalid username or password.'
    ]);
}

$stmt->close();
$conn->close();