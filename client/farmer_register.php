<?php
session_start();
include "config.php"; // expects $conn = new mysqli(...)

$error = "";
$success = "";

// initialize form values so the form can be re-populated on error
$form = [
    'username'  => '',
    'email'     => '',
    'full_name' => '',
    'phone'     => '',
    'role'      => 'farmer',   // default role
    'status'    => 'active'
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // collect + trim
    $form['username']  = trim($_POST['username'] ?? '');
    $password          = trim($_POST['password'] ?? '');
    $confirm           = trim($_POST['confirm'] ?? '');
    $form['email']     = trim($_POST['email'] ?? '');
    $form['full_name'] = trim($_POST['full_name'] ?? '');
    $form['phone']     = trim($_POST['phone'] ?? '');
    // allow role/status only if provided (otherwise keep defaults)
    $form['role']      = trim($_POST['role'] ?? $form['role']);
    $form['status']    = trim($_POST['status'] ?? $form['status']);

    // Basic validation
    if ($form['username'] === '' || $form['email'] === '' || $password === '' || $confirm === '') {
        $error = "Please fill required fields: Username, Email and Passwords.";
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^[a-zA-Z0-9_\-\.@]{3,100}$/', $form['username'])) {
        $error = "Username may include letters, numbers, @, ., _ and - (min 3 chars).";
    } elseif ($form['phone'] !== '' && !preg_match('/^[0-9+()\\-\\s]{6,20}$/', $form['phone'])) {
        $error = "Phone number looks invalid (digits, +, - allowed).";
    } else {
        // Normalize role/status to allowed values (defensive)
        $allowed_roles = ['farmer','admin'];
        $allowed_status = ['active','inactive'];
        if (!in_array($form['role'], $allowed_roles, true)) $form['role'] = 'farmer';
        if (!in_array($form['status'], $allowed_status, true)) $form['status'] = 'active';

        // Hash password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email exists (MySQL mysqli)
        $stmt = $conn->prepare("SELECT id FROM farmers WHERE username = ? OR email = ? LIMIT 1");
        if (!$stmt) {
            $error = "Server error (DB prepare).";
        } else {
            $stmt->bind_param("ss", $form['username'], $form['email']);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $error = "Username or email already taken!";
            } else {
                // Insert into DB (MySQL mysqli)
                $ins = $conn->prepare("INSERT INTO farmers (username, email, full_name, phone, role, status, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                if (!$ins) {
                    $error = "Server error (insert prepare).";
                } else {
                    $ins->bind_param(
                        "sssssss",
                        $form['username'],
                        $form['email'],
                        $form['full_name'],
                        $form['phone'],
                        $form['role'],
                        $form['status'],
                        $hashed
                    );
                    if ($ins->execute()) {
                        $success = "Account created successfully! <a href='farmer_login.php' style='color:#0F4C5C; text-decoration:underline;'>Login here</a>";
                        // clear form values after success (but keep success visible)
                        $form = [
                            'username'  => '',
                            'email'     => '',
                            'full_name' => '',
                            'phone'     => '',
                            'role'      => 'farmer',
                            'status'    => 'active'
                        ];
                    } else {
                        $error = "Error creating account! Please try again later.";
                    }
                    $ins->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FlowGuard Register</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Bauhaus / Industrial Theme */
    :root {
      --primary: #0F4C5C;      /* Deep Teal */
      --secondary: #1CA7EC;    /* Aqua Blue */
      --accent: #4F772D;       /* Olive Green */
      --bg-body: #F4F4F4;      /* Light Neutral */
      --surface: #FFFFFF;      /* Pure White */
      --text-main: #1E1E1E;    /* Charcoal */
      --text-light: #555555;
      
      --status-err: #E63946;
      --status-ok: #2A9D8F;
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
      background-color: var(--primary);
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
    .shape { position: absolute; opacity: 0.1; pointer-events: none; }
    .shape-circle {
      width: 400px;
      height: 400px;
      border-radius: 50%;
      border: 60px solid #fff;
      top: -100px;
      left: -100px;
    }
    .shape-line {
      width: 600px;
      height: 40px;
      background: #fff;
      transform: rotate(45deg);
      bottom: 10%;
      right: -100px;
    }

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
    }

    /* Right Panel: Form */
    .register-panel {
      display: flex;
      justify-content: center;
      align-items: center;
      background: var(--surface);
      position: relative;
      overflow-y: auto; /* Allow scrolling for longer form */
    }

    .register-wrapper {
      width: 100%;
      max-width: 500px;
      padding: 40px;
      animation: slideIn 0.8s ease-out;
      margin: auto 0; /* Center vertically if space allows */
    }

    .register-header { margin-bottom: 30px; }
    .register-header h2 {
      font-size: 2rem;
      font-weight: 800;
      color: var(--text-main);
      margin: 0 0 10px 0;
      text-transform: uppercase;
      letter-spacing: -1px;
    }
    .register-header p {
      color: var(--text-light);
      margin: 0;
      font-size: 0.95rem;
    }

    .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }
    @media (max-width: 600px) { .form-row { flex-direction: column; gap: 0; margin: 0; } }

    .form-group {
      flex: 1;
      margin-bottom: 20px;
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
      padding: 14px;
      font-size: 0.95rem;
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
    .form-group input::placeholder { color: #aaa; font-weight: 400; }

    .btn-submit {
      width: 100%;
      padding: 16px;
      background: var(--text-main);
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
    .btn-submit:hover {
      background: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .error-msg {
      margin-top: 20px;
      color: var(--status-err);
      font-weight: 600;
      font-size: 0.9rem;
      padding: 10px;
      background: #FFF5F5;
      border-left: 4px solid var(--status-err);
    }

    .success-msg {
      margin-top: 20px;
      color: var(--status-ok);
      font-weight: 600;
      font-size: 0.9rem;
      padding: 10px;
      background: #F0FAF9;
      border-left: 4px solid var(--status-ok);
    }

    .footer-links {
      margin-top: 30px;
      text-align: center;
      font-size: 0.9rem;
      color: var(--text-light);
    }
    .footer-links a { color: var(--primary); text-decoration: none; font-weight: 700; }
    .footer-links a:hover { text-decoration: underline; }

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
        z-index: 10;
    }
    .back-link:hover { color: var(--primary); }

    /* Animations */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes slideIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }

    /* Mobile Responsive */
    @media (max-width: 900px) {
      body { grid-template-columns: 1fr; overflow-y: auto; }
      .brand-panel { display: none; }
      .register-panel { align-items: flex-start; padding-top: 60px; height: auto; min-height: 100vh; }
      .back-link { top: 20px; right: 20px; }
    }
  </style>
</head>
<body>

  <!-- Left Panel: Brand -->
  <div class="brand-panel">
    <div class="shape shape-circle"></div>
    <div class="shape shape-line"></div>
    
    <div class="brand-content">
      <svg class="brand-logo" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2Z" fill="#FFFFFF" fill-opacity="0.2" />
         <path d="M12 5.5C14.37 5.5 16.59 7.11 17.5 9.25C17.81 9.94 17.5 10.75 16.88 11.12C16.25 11.5 15.42 11.25 15 10.62C14.33 9.17 12.81 8 11 8C8.79 8 7 9.79 7 12C7 14.21 8.79 16 11 16H12V14.5L15 16.5L12 18.5V17H11C7.69 17 5 14.31 5 11C5 7.69 7.69 5 11 5L12 5.5Z" fill="#fff" />
         <path d="M12 22C17.5228 22 22 17.5228 22 12C22 10.8359 21.802 9.72063 21.434 8.68808C19.9571 11.0543 17.1523 12.5 14 12.5C10.8477 12.5 8.04289 11.0543 6.56601 8.68808C6.19796 9.72063 6 10.8359 6 12C6 17.5228 10.4772 22 12 22Z" fill="#A8DADC" />
      </svg>
      <h1 class="brand-title">Join Us</h1>
      <p class="brand-subtitle">Create your FlowGuard account</p>
    </div>
  </div>

  <!-- Right Panel: Register Form -->
  <div class="register-panel">
    <a href="index.php" class="back-link">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5"></path>
        <path d="M12 19l-7-7 7-7"></path>
      </svg>
      Home
    </a>

    <div class="register-wrapper">
      <div class="register-header">
        <h2>Register</h2>
        <p>Start managing your irrigation smarter</p>
      </div>

      <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input id="username" name="username" type="text" required placeholder="Choose a username" value="<?= htmlspecialchars($form['username']) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input id="email" name="email" type="email" required placeholder="you@example.com" value="<?= htmlspecialchars($form['email']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" type="text" placeholder="Your Name" value="<?= htmlspecialchars($form['full_name']) ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input id="phone" name="phone" type="text" placeholder="+91..." value="<?= htmlspecialchars($form['phone']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input id="password" name="password" type="password" required placeholder="Enter password">
            </div>

            <div class="form-group">
                <label for="confirm">Confirm *</label>
                <input id="confirm" name="confirm" type="password" required placeholder="Re-enter password">
            </div>
        </div>

        <button type="submit" class="btn-submit">Create Account</button>

        <?php if (!empty($error)): ?>
          <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
          <div class="success-msg"><?= $success ?></div>
        <?php endif; ?>
      </form>

      <div class="footer-links">
        <p>Already have an account? <a href="farmer_login.php">Login here</a></p>
      </div>
    </div>
  </div>

</body>
</html>
