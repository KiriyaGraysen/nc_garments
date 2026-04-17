<?php
// actions/process_recovery.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('../config/database.php');

// 1. Load PHPMailer Manually
// Adjust these paths if your PHPMailer-master folder is located somewhere else!
require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recovery_id']) || empty(trim($data['recovery_id']))) {
    echo json_encode(["status" => "error", "message" => "Please enter a username or email."]);
    exit;
}

$recovery_id = trim($data['recovery_id']);

// 2. Find the user by either username OR email
$stmt = $conn->prepare("SELECT admin_id, full_name, email FROM admin WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $recovery_id, $recovery_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Security: Don't tell hackers if an email exists or not
    echo json_encode(["status" => "success"]);
    exit;
}

$user = $result->fetch_assoc();

// 3. Generate a secure, random token and set expiration
$token = bin2hex(random_bytes(32)); 
$expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

// Save token to database
$update_stmt = $conn->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE admin_id = ?");
$update_stmt->bind_param("ssi", $token, $expires, $user['admin_id']);
$update_stmt->execute();

// 4. Construct the reset link (UPDATE THIS TO YOUR ACTUAL LOCALHOST URL)
$reset_link = "http://localhost/nc-garments/reset-password.php?token=" . $token;

// 5. Send the Email using PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'kiriyaokazaki56@gmail.com';     // 🚨 CHANGE THIS
    $mail->Password   = 'ylap xqtb kqsa wkre';   // 🚨 CHANGE THIS (Use Gmail App Password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('no-reply@ncgarments.com', 'NC Garments ERP');
    $mail->addAddress($user['email'], $user['full_name']); 

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request - NC Garments';
    
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <h2 style='color: #db2777; text-align: center;'>Password Reset Request</h2>
            <p>Hello <strong>{$user['full_name']}</strong>,</p>
            <p>We received a request to reset the password for your NC Garments ERP account.</p>
            <p>Please click the button below to securely set a new password. This link will expire in 1 hour.</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$reset_link}' style='background-color: #db2777; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Reset My Password</a>
            </div>
            <p style='font-size: 12px; color: #666;'>If you did not request this, please ignore this email or contact the Superadmin.</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #999; text-align: center;'>NC Garments Enterprise System</p>
        </div>
    ";

    $mail->AltBody = "Hello {$user['full_name']},\n\nCopy and paste this link into your browser to reset your password: {$reset_link}\n\nThis link expires in 1 hour.\n\n- NC Garments System";

    $mail->send();
    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}