<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require the PHPMailer files from your unzipped folder
require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

require_once('../config/database.php');

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Only Superadmins can perform this action
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Superadmin privileges required.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['target_admin_id']) || empty($data['superadmin_password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    exit;
}

$target_id = (int)$data['target_admin_id'];
$superadmin_pass = $data['superadmin_password'];
$superadmin_id = (int)$_SESSION['admin_id'];

// Prevent a superadmin from resetting their own password via this modal
if ($target_id === $superadmin_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot reset your own password here. Please use the Settings page.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. VERIFY SUPERADMIN PASSWORD
    $verify_stmt = $conn->prepare("SELECT password_hash FROM admin WHERE admin_id = ?");
    $verify_stmt->bind_param("i", $superadmin_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        throw new Exception("Superadmin account not found in database.");
    }
    
    $superadmin_data = $verify_result->fetch_assoc();
    
    // Check if the typed password matches the encrypted hash in the database
    if (!password_verify($superadmin_pass, $superadmin_data['password_hash'])) {
        throw new Exception("Incorrect password. Identity verification failed.");
    }

    // 2. GET TARGET USER INFO (We need the email now!)
    $target_stmt = $conn->prepare("SELECT username, full_name, email FROM admin WHERE admin_id = ?");
    $target_stmt->bind_param("i", $target_id);
    $target_stmt->execute();
    $target_result = $target_stmt->get_result();

    if ($target_result->num_rows === 0) {
        throw new Exception("Target account does not exist.");
    }
    $target_user = $target_result->fetch_assoc();

    // 3. GENERATE A SECURE RANDOM PASSWORD
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$*';
    $new_password = substr(str_shuffle($chars), 0, 10);
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 4. UPDATE THE DATABASE
    $update_stmt = $conn->prepare("UPDATE admin SET password_hash = ? WHERE admin_id = ?");
    $update_stmt->bind_param("si", $hashed_password, $target_id);
    $update_stmt->execute();

    // 5. SEND THE EMAIL USING PHPMAILER
    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS (UPDATE THESE!) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                         // Enable SMTP authentication
        $mail->Username   = 'kiriyaokazaki56@gmail.com';       // SMTP username
        $mail->Password   = 'ylap xqtb kqsa wkre';          // SMTP password (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587;                          // TCP port to connect to

        // --- RECIPIENTS ---
        $mail->setFrom('noreply@ncgarments.com', 'NC Garments Admin'); // What the user sees as the sender
        $mail->addAddress($target_user['email'], $target_user['full_name']); // Add the target user

        // --- CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = 'Security Notice: Your Password Has Been Reset';
        
        // HTML Body
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                <h2 style='color: #db2777; margin-bottom: 20px;'>NC Garments Security Notice</h2>
                <p>Hello <b>{$target_user['full_name']}</b>,</p>
                <p>Your password for the NC Garments system has been securely reset by a System Administrator.</p>
                <div style='background-color: #f9fafb; padding: 15px; border-radius: 6px; margin: 20px 0; text-align: center;'>
                    <p style='margin: 0; font-size: 14px; color: #6b7280;'>Your new temporary password is:</p>
                    <p style='margin: 10px 0 0 0; font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #111827;'>{$new_password}</p>
                </div>
                <p style='color: #ef4444; font-size: 14px;'><strong>Action Required:</strong> Please log in using this temporary password and immediately navigate to your Account Settings to change it to something secure.</p>
                <hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                <p style='font-size: 12px; color: #9ca3af;'>This is an automated message. Please do not reply to this email.</p>
            </div>
        ";
        
        // Plain text fallback
        $mail->AltBody = "Hello {$target_user['full_name']},\n\nYour password for the NC Garments system has been securely reset.\n\nYour new temporary password is: {$new_password}\n\nPlease log in and change this password immediately in your Account Settings.\n\nBest regards,\nNC Garments Team";

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Database updated, but email failed to send. Mailer Error: {$mail->ErrorInfo}");
    }

    // 6. LOG THE SECURITY EVENT
    $log_desc = "Triggered a secure password reset for " . $target_user['full_name'] . " (@" . $target_user['username'] . "). A new password was emailed to them.";
    
    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'admin', ?, ?)");
    $log_stmt->bind_param("iis", $superadmin_id, $target_id, $log_desc);
    $log_stmt->execute();

    $conn->commit();

    // 🚨 We no longer return the password here. Just a success message!
    echo json_encode([
        'status' => 'success',
        'message' => "A new random password has been securely generated and emailed to {$target_user['email']}."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>