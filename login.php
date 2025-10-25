<?php
session_start();
require_once "db_config.php";

// ✅ Handle signup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $otp = trim($_POST['otp']);

    // OTP check (if using session OTP)
    if (!isset($_SESSION['otp']) || $_SESSION['otp'] != $otp) {
        echo "<script>alert('❌ Invalid OTP');window.location.href='login.php';</script>";
        exit;
    }

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
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login / Signup</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <style>
    :root {
      --bg-light: #f0f2f5;
      --bg-dark: #1e1e2f;
      --card-light: #ffffff;
      --card-dark: #2d2d3a;
      --text-light: #000000;
      --text-dark: #ffffff;
      --primary: #4c83ff;
      --primary-hover: #3a6fdd;
    }

    * { box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      background: var(--bg-light);
      color: var(--text-light);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      transition:0.3s, color 0.3s;
    }

    .dark-mode {
      background: var(--bg-dark);
      color: var(--text-dark);
    }

    .container {
      width: 900px;
      max-width: 100%;
      height: 520px;
      display: flex;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      overflow: hidden;
      background: var(--card-light);
      transition:0.3s, color 0.3s;
    }

    .dark-mode .container {
      background: var(--card-dark);
    }

    .left-panel, .right-panel {
      flex: 1;
      padding: 40px;
    }

    .left-panel { display: flex; flex-direction: column; justify-content: center; }
    .right-panel {
      background: linear-gradient(135deg, #4c83ff, #6fa3ff);
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .form { display: flex; flex-direction: column; }
    .form h2 { margin-bottom: 20px; }

    .input-group {
      position: relative;
    }

    .form input,
    .form select {
      width: 100%;
      padding: 10px 35px 10px 40px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
      background-color: white;
    }

    .dark-mode .form input,
    .dark-mode .form select {
      background-color: #3c3c4c;
      border: 1px solid #666;
      color: white;
    }

    .input-group i {
      position: absolute;
      top: 10px;
      left: 10px;
      color: gray;
    }

    .input-group .toggle-password {
      right: 10px;
      left: auto;
    }

    .submit-btn {
      padding: 12px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      transition: 0.3s;
    }

    .submit-btn:hover {
      background: var(--primary-hover);
    }

    .toggle {
      margin-top: 10px;
      color: #555;
      cursor: pointer;
      text-align: center;
    }

    .dark-mode .toggle { color: #aaa; }
    .hidden { display: none; }

    .right-panel h1 { font-size: 32px; }
    .right-panel p { font-size: 18px; margin-top: 10px; }

    .mode-toggle {
      position: absolute;
      top: 20px;
      right: 30px;
      font-size: 20px;
      cursor: pointer;
      color: #444;
    }

    .dark-mode .mode-toggle { color: #fff; }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        height: auto;
        width: 95%;
        border-radius: 10px;
      }

      .left-panel, .right-panel {
        width: 100%;
        padding: 30px;
      }
    }
  </style>
</head>

<body>
<div class="mode-toggle" onclick="toggleMode()">
  <i id="modeIcon" class="fas fa-moon"></i>
</div>

<div class="container">
  <div class="left-panel">
    <!-- LOGIN FORM -->
    <form id="loginForm" class="form" action="login_commad.php" method="post">
      <h2>Login</h2>
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required />
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" id="loginPassword" required />
        <i class="fas fa-eye toggle-password" onclick="togglePassword('loginPassword', this)"></i>
      </div>
      <div class="input-group">
        <i class="fas fa-user-tag"></i>
        <select name="role" required>
          <option value="">Select Role</option>
          <option value="user">User</option>
          <option value="owner">Owner</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <button type="submit" class="submit-btn">Login</button>
      <div class="toggle" onclick="showSignup()">Don't have an account? Signup</div>
    </form>

    <!-- SIGNUP FORM -->
    <form id="signupForm" class="form hidden" action="login.php" method="post">
      <h2>Signup</h2>
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="name" placeholder="Full Name" required />
      </div>
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required />
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" id="signupPassword" required />
        <i class="fas fa-eye toggle-password" onclick="togglePassword('signupPassword', this)"></i>
      </div>
      <div class="input-group">
        <i class="fas fa-user-tag"></i>
        <select name="role" required>
          <option value="">Select Role</option>
          <option value="user">User</option>
          <option value="owner">Owner</option>
        </select>
      </div>
      <button type="button" class="submit-btn" onclick="sendOTP()">Send OTP</button>
      <div class="input-group">
        <i class="fas fa-key"></i>
        <input type="text" name="otp" placeholder="Enter OTP" required />
      </div>
      <button type="submit" name="signup" class="submit-btn">Signup</button>
      <div class="toggle" onclick="showLogin()">Already have an account? Login</div>
    </form>
  </div>

  <div class="right-panel">
    <h1>Welcome to Auto Park</h1>
    <p>Secure your parking spot anytime, anywhere.</p>
  </div>
</div>

<script>
  function showSignup() {
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('signupForm').classList.remove('hidden');
  }

  function showLogin() {
    document.getElementById('signupForm').classList.add('hidden');
    document.getElementById('loginForm').classList.remove('hidden');
  }

  function togglePassword(fieldId, eyeIcon) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
      field.type = 'text';
      eyeIcon.classList.remove('fa-eye');
      eyeIcon.classList.add('fa-eye-slash');
    } else {
      field.type = 'password';
      eyeIcon.classList.remove('fa-eye-slash');
      eyeIcon.classList.add('fa-eye');
    }
  }

  function sendOTP() {
    const email = document.querySelector('#signupForm input[name="email"]').value;
    if (!email) {
      alert('Please enter your email to receive OTP.');
      return;
    }

    fetch('send_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.text())
    .then(data => alert(data))
    .catch(err => {
      console.error(err);
      alert('Failed to send OTP.');
    });
  }

  function toggleMode() {
    document.body.classList.toggle('dark-mode');
    const icon = document.getElementById('modeIcon');
    if (document.body.classList.contains('dark-mode')) {
      icon.classList.remove('fa-moon');
      icon.classList.add('fa-sun');
    } else {
      icon.classList.remove('fa-sun');
      icon.classList.add('fa-moon');
    }
  }
</script>

</body>
</html>
