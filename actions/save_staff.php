<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require the PHPMailer files
require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

require_once('../config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $id = !empty($data['admin_id']) ? (int)$data['admin_id'] : null;
    $name = trim($data['full_name']);
    $email = trim($data['email']);
    $username = trim($data['username']);
    $role = trim($data['role']);

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

            // 2. Execute Update (No password logic here anymore!)
            $stmt = $conn->prepare("UPDATE admin SET full_name=?, email=?, username=?, role=? WHERE admin_id=?");
            $stmt->bind_param("ssssi", $name, $email, $username, $role, $id);
            $stmt->execute();

            // 3. Calculate Deltas and Log
            if ($actor_id > 0 && $old_data) {
                $changes = [];
                
                if ($old_data['full_name'] !== $name) $changes[] = ['field' => 'Full Name', 'old' => $old_data['full_name'], 'new' => $name];
                if ($old_data['email'] !== $email) $changes[] = ['field' => 'Email Address', 'old' => $old_data['email'], 'new' => $email];
                if ($old_data['username'] !== $username) $changes[] = ['field' => 'Username', 'old' => '@' . $old_data['username'], 'new' => '@' . $username];
                if ($old_data['role'] !== $role) $changes[] = ['field' => 'System Role', 'old' => strtoupper($old_data['role']), 'new' => strtoupper($role)];
                
                // Only log if something actually changed
                if (!empty($changes)) {
                    $change_count = count($changes);

                    $log_payload = json_encode([
                        'is_detailed' => true,
                        'type' => 'update_comparison',
                        'summary' => "Modified $change_count field(s) in staff account.",
                        'project' => $name . " (@" . $username . ")", 
                        'changes' => $changes
                    ]);

                    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'admin', ?, ?)");
                    $log_stmt->bind_param("iis", $actor_id, $id, $log_payload);
                    $log_stmt->execute();
                }
            }

            $conn->commit();
            echo json_encode(["status" => "success"]);

        } else {
            // ==========================================
            // FLOW 2: CREATE NEW ACCOUNT & EMAIL PASSWORD
            // ==========================================
            
            // 1. Generate Secure Random Password
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$*';
            $new_password = substr(str_shuffle($chars), 0, 10);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // 2. Insert into Database
            $stmt = $conn->prepare("INSERT INTO admin (full_name, email, username, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssss", $name, $email, $username, $role, $hashed_password);
            $stmt->execute();
            
            $new_id = $conn->insert_id;

            // 3. Send Email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // --- SERVER SETTINGS (UPDATE THESE!) ---
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';             
                $mail->SMTPAuth   = true;                         
                $mail->Username   = 'kiriyaokazaki56@gmail.com';       
                $mail->Password   = 'ylap xqtb kqsa wkre';          
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port       = 587;                          

                // --- RECIPIENTS ---
                $mail->setFrom('noreply@ncgarments.com', 'NC Garments Admin');
                $mail->addAddress($email, $name); 

                // --- CONTENT ---
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to NC Garments - Your Account Details';
                
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                        <h2 style='color: #db2777; margin-bottom: 20px;'>Welcome to NC Garments</h2>
                        <p>Hello <b>{$name}</b>,</p>
                        <p>An administrator has provisioned a new <strong>" . strtoupper($role) . "</strong> account for you in the NC Garments system.</p>
                        
                        <div style='background-color: #f9fafb; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                            <p style='margin: 0; font-size: 14px; color: #6b7280;'>Your Login Username:</p>
                            <p style='margin: 5px 0 15px 0; font-size: 18px; font-weight: bold; color: #111827;'>{$username}</p>
                            
                            <p style='margin: 0; font-size: 14px; color: #6b7280;'>Your Temporary Password:</p>
                            <p style='margin: 5px 0 0 0; font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #111827;'>{$new_password}</p>
                        </div>
                        
                        <p style='color: #ef4444; font-size: 14px;'><strong>Action Required:</strong> Please log in using this temporary password and immediately navigate to your Account Settings to change it.</p>
                        <hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #9ca3af;'>This is an automated message. Please do not reply to this email.</p>
                    </div>
                ";
                
                $mail->AltBody = "Hello {$name},\n\nAn administrator has provisioned a new {$role} account for you.\n\nUsername: {$username}\nTemporary Password: {$new_password}\n\nPlease log in and change this password immediately in your Account Settings.\n\nBest regards,\nNC Garments Team";

                $mail->send();
            } catch (Exception $e) {
                // If email fails, we throw an exception to roll back the user creation so we don't have an inaccessible account.
                throw new Exception("Account created but email failed to send. Rolled back. Mailer Error: {$mail->ErrorInfo}");
            }

            // 4. Log the Creation
            if ($actor_id > 0) {
                $role_upper = strtoupper($role);
                $description = "Provisioned new staff account: $name (@$username) with role $role_upper. Login details emailed.";

                $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'CREATE', 'admin', ?, ?)");
                $log_stmt->bind_param("iis", $actor_id, $new_id, $description);
                $log_stmt->execute();
            }

            $conn->commit();
            
            // Sending back a generic success message since the email handled the delivery
            echo json_encode([
                "status" => "success", 
                "generated_password" => null // Intentionally null so it doesn't show on screen
            ]);
        }

    } catch (Exception $e) {
        // 🚨 ROLLBACK TRANSACTION: An error occurred, revert changes
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "System error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload received."]);
}