<?php
session_start();
include "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // MySQL mysqli syntax
  $stmt = $conn->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 1) {
    $row = $res->fetch_assoc();
    if (password_verify($password, $row['password'])) {
      $_SESSION['admin_id'] = $row['id'];    
      $_SESSION['role'] = "admin";
      $_SESSION['user_id'] = $row['id'];
      $_SESSION['username'] = $row['username'];

      header("Location: admin_dashboard.php");
      exit();
    } else {
      $error = "Invalid password!";
    }
  } else {
    $error = "Admin not found!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FlowGuard Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/modern-design.css">
  <style>
    :root {
      --primary: #0F4C5C;      /* Deep Teal */
      --secondary: #1CA7EC;    /* Aqua Blue */
      --accent: #4F772D;       /* Olive Green */
      --bg-body: #F4F4F4;      /* Light Neutral */
      --surface: #FFFFFF;      /* Pure White */
      --text-main: #1E1E1E;    /* Charcoal */
      --text-light: #555555;
      --status-err: #E63946;
      --radius: 0px;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      overflow: hidden;
      background: var(--bg-body);
    }

    /* Left Panel: Art/Brand */
    .brand-panel {
      background-color: var(--text-main); /* Darker for Admin */
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: #fff;
      padding: 40px;
      overflow: hidden;
    }

    /* Bauhaus Geometric Patterns */
    .shape {
      position: absolute;
      opacity: 0.1;
      pointer-events: none;
    }
    .shape-circle {
      width: 500px;
      height: 500px;
      border-radius: 50%;
      border: 40px solid #fff;
      top: -150px;
      left: 10%;
    }
    .shape-rect {
      width: 150px;
      height: 100vh;
      background: var(--primary);
      top: 0;
      right: 15%;
      transform: skewX(-20deg);
      opacity: 0.2;
    }

    /* Content within brand panel */
    .brand-content {
      position: relative;
      z-index: 2;
      text-align: center;
      animation: fadeIn 1s ease-out;
    }

    .brand-logo {
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
    }

    .brand-title {
      font-size: 3.5rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -2px;
      margin: 0;
      line-height: 1;
    }
    .brand-subtitle {
      font-size: 1.2rem;
      font-weight: 500;
      margin-top: 10px;
      opacity: 0.9;
      color: var(--secondary);
    }

    /* Right Panel: Login Form */
    .login-panel {
      display: flex;
      justify-content: center;
      align-items: center;
      background: var(--surface);
      position: relative;
    }

    .login-wrapper {
      width: 100%;
      max-width: 420px;
      padding: 40px;
      animation: slideIn 0.8s ease-out;
    }

    .login-header {
      margin-bottom: 40px;
    }
    .login-header h2 {
      font-size: 2rem;
      font-weight: 800;
      color: var(--text-main);
      margin: 0 0 10px 0;
      text-transform: uppercase;
      letter-spacing: -1px;
    }
    .login-header p {
      color: var(--text-light);
      margin: 0;
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 24px;
      position: relative;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 700;
      font-size: 0.85rem;
      color: var(--text-main);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .form-group input {
      width: 100%;
      padding: 16px;
      font-size: 1rem;
      background: var(--bg-body);
      border: 3px solid transparent;
      border-bottom: 3px solid #ddd;
      color: var(--text-main);
      font-weight: 600;
      transition: all 0.3s;
      outline: none;
    }
    .form-group input:focus {
      background: #fff;
      border-bottom-color: var(--primary);
      box-shadow: 0 10px 20px -10px rgba(15, 76, 92, 0.1);
    }

    .btn-login {
      width: 100%;
      padding: 16px;
      background: var(--primary); /* Deep Teal for Admin Button */
      color: #fff;
      font-size: 1rem;
      font-weight: 800;
      text-transform: uppercase;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      letter-spacing: 1px;
      margin-top: 10px;
    }
    .btn-login:hover {
      background: var(--text-main);
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .error-msg {
      margin-top: 20px;
      color: var(--status-err);
      font-weight: 600;
      font-size: 0.9rem;
      min-height: 24px;
    }

    .back-link {
        position: absolute;
        top: 30px;
        right: 30px;
        color: var(--text-light);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
        transition: color 0.2s;
    }
    .back-link:hover { color: var(--primary); }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(30px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* Mobile Responsive */
    @media (max-width: 900px) {
      body { grid-template-columns: 1fr; overflow-y: auto; }
      .brand-panel { display: none; }
      .login-panel { align-items: flex-start; padding-top: 60px; }
      .back-link { top: 20px; right: 20px; }
    }
  </style>
</head>
<body>

  <!-- Left Panel: Brand -->
  <div class="brand-panel">
    <!-- Geometric decorative shapes -->
    <div class="shape shape-circle"></div>
    <div class="shape shape-rect"></div>

    <div class="brand-content">
      <svg class="brand-logo" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2Z" fill="#FFFFFF" fill-opacity="0.2" />
        <path d="M12 5.5C14.37 5.5 16.59 7.11 17.5 9.25C17.81 9.94 17.5 10.75 16.88 11.12C16.25 11.5 15.42 11.25 15 10.62C14.33 9.17 12.81 8 11 8C8.79 8 7 9.79 7 12C7 14.21 8.79 16 11 16H12V14.5L15 16.5L12 18.5V17H11C7.69 17 5 14.31 5 11C5 7.69 7.69 5 11 5L12 5.5Z" fill="#fff" />
        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 10.8359 21.802 9.72063 21.434 8.68808C19.9571 11.0543 17.1523 12.5 14 12.5C10.8477 12.5 8.04289 11.0543 6.56601 8.68808C6.19796 9.72063 6 10.8359 6 12C6 17.5228 10.4772 22 12 22Z" fill="#A8DADC" />
      </svg>
      <h1 class="brand-title">FlowGuard</h1>
      <p class="brand-subtitle">Administrator Portal</p>
    </div>
  </div>

  <!-- Right Panel: Login Form -->
  <div class="login-panel">
    <a href="index.php" class="back-link">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5"></path>
        <path d="M12 19l-7-7 7-7"></path>
      </svg>
      Home
    </a>

    <div class="login-wrapper">
      <div class="login-header">
        <h2>Admin Login</h2>
        <p>Restricted access for system administrators</p>
      </div>

      <form method="POST" action="">
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Enter username" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Enter password" required>
        </div>

        <button type="submit" class="btn-login">Login</button>

        <?php if (!empty($error)): ?>
          <div class="error-msg">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

</body>
</html>