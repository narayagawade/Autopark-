<?php
session_start();
require_once "db_config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $otp = trim($_POST['otp']);

    // Verify OTP (if you are storing OTP in session or db)
    if (!isset($_SESSION['otp']) || $_SESSION['otp'] != $otp) {
        echo "<script>alert('❌ Invalid OTP');window.location.href='login.php';</script>";
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Signup successful! Please login.');window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('❌ Signup failed: " . $stmt->error . "');window.location.href='login.php';</script>";
    }
    $stmt->close();
    $conn->close();
}
?>
