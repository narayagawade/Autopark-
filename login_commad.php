<?php
session_start();
include("db_config.php");

// ✅ Block new login if already logged in
if (isset($_SESSION['role'])) {
    echo "<script>alert('⚠ Someone is already logged in on this browser. Please logout first.');window.location.href='login.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // ✅ Hardcoded Admin login
    $admin_email = "aaditaygawade01@gmail.com";
    $admin_password = "aadi@#2006";

    if ($email === $admin_email && $password === $admin_password && $role === "admin") {
        $_SESSION['email'] = $admin_email;
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = "Admin";
        header("Location: admin_dashboard.php");
        exit();
    }

    // 🔒 Only user or owner
    if ($role === 'user' || $role === 'owner') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role=?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['username'] = $row['name'];
                $_SESSION['role'] = $row['role'];

                if ($role == 'user') {
                    header("Location: user_dashboard.php");
                } else {
                    $_SESSION['owner_id'] = $row['id'];
                    header("Location: owner_dashboard.php");
                }
                exit();
            }
        }
    }

    // ❌ Failure
    echo "<script>alert('❌ Invalid login credentials.');window.location.href='login.php';</script>";
    exit();
} else {
    echo "<script>alert('❌ Invalid request.');window.location.href='login.php';</script>";
    exit();
}
?>
