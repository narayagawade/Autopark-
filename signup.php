<?php
session_start();
include("db_config.php"); // Adjust this if your DB file name is different

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = $_POST['name'];
    $email       = $_POST['email'];
    $password    = $_POST['password'];
    $role        = $_POST['role'];
    $entered_otp = $_POST['otp'];

    // ✅ OTP Check
    if (!isset($_SESSION['otp']) || $entered_otp != $_SESSION['otp']) {
        echo "<script>alert('❌ Invalid OTP. Please try again.'); window.history.back();</script>";
        exit;
    }

    // ✅ Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Check if email already exists
    $check_query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('⚠️ Email already registered. Please login.'); window.location.href='login.html';</script>";
        exit;
    }

    // ✅ Insert new user
    $insert_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Signup successful! You can now login.'); window.location.href='login.html';</script>";
        unset($_SESSION['otp']); // Clear OTP after use
    } else {
        echo "<script>alert('❌ Failed to register user. Please try again.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('❌ Invalid request method.'); window.history.back();</script>";
}
?>
