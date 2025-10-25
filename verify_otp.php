<?php
session_start();  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userOtp = $_POST['otp'];

    if (isset($_SESSION['otp'])) {
        if ($userOtp == $_SESSION['otp']) {
            echo "✅ OTP verified successfully!";
           
        } else {
            echo "❌ Invalid OTP. Please try again.";
        }
    } else {
        echo "⚠️ OTP session not found. Please request a new OTP.";
    }
} else {
    echo "❌ Invalid request method.";
}
?>
