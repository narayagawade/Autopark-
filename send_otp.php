<?php
session_start();  // ✅ Always at the top

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Get email from POST or session
$user_email = $_POST['email'] ?? $_SESSION['user_email'];

$otp = rand(100000, 999999);  // ✅ Generate OTP

$_SESSION['otp'] = $otp;  // ✅ Store OTP in session

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';  // or your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'aaditaygawade01@gmail.com';  // your email
    $mail->Password = 'apqf fqwz hxpc iedf';     // app-specific password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipient
    $mail->setFrom('your_email@gmail.com', 'Autopark');
    $mail->addAddress($user_email);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body    = "Your OTP is <b>$otp</b>";

    $mail->send();
    echo "OTP sent successfully to $user_email";
} catch (Exception $e) {
    echo "Failed to send OTP. Error: {$mail->ErrorInfo}";
}
?>
