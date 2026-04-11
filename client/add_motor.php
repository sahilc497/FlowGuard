<?php
session_start();
include "config.php";

// 🔒 Require farmer login
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}

$error = "";
$success = "";

/* =========================
   FORM HANDLER
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $motor_name = trim($_POST['motor_name'] ?? "");
    $farmer_id  = $_SESSION['farmer_id'];

    if (!empty($motor_name)) {
        // Default initialized to OFF
        $stmt = $conn->prepare("INSERT INTO motors (motor_name, status, farmer_id) VALUES (?, 'OFF', ?)");
        $stmt->bind_param("si", $motor_name, $farmer_id);

        if ($stmt->execute()) {
            $success = "MOTOR INITIALIZED SUCCESSFULLY";
        } else {
            $error = "SYSTEM ERROR: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "INPUT REQUIRED: MOTOR NAME";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>FlowGuard | Add Motor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

  <style>
/* --- Global Theme from farmer_dashboard.php --- */
:root {
    /* Palette */
    --primary: #0F4C5C;      /* Deep Teal */
    --secondary: #1CA7EC;    /* Aqua Blue */
    --accent: #4F772D;       /* Olive Green (Agri) */
    --bg-body: #F4F4F4;      /* Light Neutral */
    --surface: #FFFFFF;      /* Pure White */
    --text-main: #1E1E1E;    /* Charcoal */
    --text-light: #555555;
    
    /* Status Colors */
    --status-ok: #2A9D8F;    /* Success Green */
    --status-warn: #F4A261;  /* Amber */
    --status-err: #E63946;   /* Alert Red */
    --status-off: #999999;
    
    /* Borders & spacing */
    --border-width: 2px;
    --border-color: #1E1E1E; /* High contrast border */
    --radius: 0px;           /* Sharp corners */
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }

body {
  display: grid;
  grid-template-rows: auto 1fr auto; /* Header - Content - Footer */
  background-color: var(--bg-body);
  /* Cool dot pattern enhancement */
  background-image: radial-gradient(var(--text-light) 1px, transparent 1px);
  background-size: 20px 20px; 
  color: var(--text-main);
  font-family: 'Inter', sans-serif;
}

/* --- Standard Header --- */
.header {
  background: var(--primary);
  color: var(--surface);
  padding: 0 40px; /* Match existing dashboard padding */
  height: 70px;
  display: flex;
  justify-content: space-between;
  align-items: stretch;
  border-bottom: 4px solid var(--accent);
}
.header-brand { display: flex; align-items: center; gap: 14px; }
.header h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--surface);
    letter-spacing: -0.5px;
    text-transform: uppercase;
    margin: 0;
}
.nav-links { display: flex; align-items: stretch; }
.nav-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-weight: 700;
    padding: 0 20px;
    display: flex;
    align-items: center;
    transition: all 0.2s;
    border-left: 1px solid rgba(255,255,255,0.1);
    text-transform: uppercase;
    font-size: 14px;
}
.nav-links a:hover {
    background: var(--secondary);
    color: var(--text-main);
}

/* --- Content Shell --- */
.page-shell {
    max-width: 1100px;
    width: 100%;
    margin: 0 auto;
    padding: 40px 20px;
}

/* --- Standard Footer --- */
footer {
    background: var(--text-main);
    color: var(--surface);
    padding: 24px;
    text-align: center;
    border-top: 4px solid var(--accent);
    font-weight: 500;
    font-size: 0.9rem;
}

/* --- Responsive Layout --- */
@media (max-width: 900px) {
  .header { padding: 0 20px; }
  .content-grid { grid-template-columns: 1fr !important; }
}


/* --- "Cool Bauhaus" Styles for Add Motor Form --- */

    /* Bauhaus Card styling */
    .bauhaus-card {
        background: var(--surface);
        border: 4px solid var(--border-color);
        box-shadow: 12px 12px 0px rgba(15, 76, 92, 0.2);
        padding: 40px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .bauhaus-card:hover {
        transform: translate(-2px, -2px);
        box-shadow: 16px 16px 0px rgba(15, 76, 92, 0.2);
    }

    /* Geometric Decorations */
    .deco-circle {
        position: absolute;
        width: 150px;
        height: 150px;
        background: var(--secondary);
        border-radius: 50%;
        top: -50px;
        right: -50px;
        opacity: 0.2;
        z-index: 0;
    }
    .deco-line {
        position: absolute;
        width: 200%;
        height: 20px;
        background: var(--accent);
        transform: rotate(-45deg);
        bottom: 20px;
        left: -50%;
        opacity: 0.2;
        z-index: 0;
    }

    .card-header h2 {
        font-size: 1.8rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0 0 20px 0;
        position: relative;
        z-index: 1;
        letter-spacing: -1px;
        color: var(--primary);
        border-bottom: 2px solid var(--bg-body);
        padding-bottom: 10px;
    }

    /* Form Inputs */
    .input-group { position: relative; z-index: 1; margin-bottom: 30px; }
    
    .input-label {
        display: block;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.9rem;
        margin-bottom: 8px;
        color: var(--text-main);
        letter-spacing: 0.5px;
    }

    .cool-input {
        width: 100%;
        padding: 16px;
        font-size: 1.2rem;
        font-family: 'Space Mono', monospace;
        font-weight: 700;
        border: 3px solid var(--text-light);
        background: var(--bg-body);
        color: var(--text-main);
        outline: none;
        transition: all 0.2s;
    }
    
    .cool-input:focus {
        border-color: var(--primary);
        background: var(--surface);
        box-shadow: 5px 5px 0px var(--secondary);
    }

    /* Buttons */
    .btn-action {
        width: 100%;
        padding: 18px;
        font-size: 1.1rem;
        font-weight: 900;
        text-transform: uppercase;
        border: 3px solid var(--text-main);
        background: var(--secondary);
        color: var(--text-main);
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 6px 6px 0px var(--text-main);
        position: relative;
        z-index: 1;
    }
    
    .btn-action:hover {
        transform: translate(-4px, -4px);
        box-shadow: 10px 10px 0px var(--text-main);
        background: var(--primary);
        color: var(--surface);
        border-color: var(--primary);
    }

    .btn-cancel {
        display: block;
        text-align: center;
        margin-top: 15px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-light);
        text-decoration: none;
        position: relative;
        z-index: 1;
        font-size: 0.9rem;
    }
    .btn-cancel:hover { color: var(--status-err); text-decoration: underline; }

    /* Success/Error Banners */
    .status-banner {
        padding: 15px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 25px;
        position: relative;
        z-index: 1;
        border: 2px solid rgba(0,0,0,0.1);
        animation: slideDown 0.4s ease-out;
    }
    .status-success { background: var(--status-ok); color: #fff; }
    .status-error { background: var(--status-err); color: #fff; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Right Panel specific */
    .guide-card {
        background: var(--primary); 
        color: #fff;
        border-color: var(--text-main); 
    }
    .guide-card .deco-circle { background: #fff; opacity: 0.1; }
    
    .guide-text {
        font-size: 1rem;
        line-height: 1.6;
        font-weight: 500;
        position: relative;
        z-index: 1;
        border-left: 4px solid var(--secondary);
        padding-left: 15px;
        margin-top: 20px;
    }

  </style>
</head>
<body>

<!-- Standardized Header -->
<header class="header">
  <div class="header-brand">
    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M12 2C9 5 6 6 4 7v9c0 1 0 3 2 4 2 1 6 1 10 0 3-1 3-4 3-5V7c-2-1-5-2-7-5z" fill="#fff" opacity="0.12"></path>
      <circle cx="12" cy="10" r="3" fill="#fff"></circle>
    </svg>
    <h1>FlowGuard</h1>
  </div>

  <nav class="nav-links">
    <a href="farmer_dashboard.php">Dashboard</a>
    <a href="profile.php">Profile</a>
    <a href="farmer_login.php">Logout</a>
  </nav>
</header>

<div class="page-shell">
    <div class="content-grid" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; align-items: start;">
        
        <!-- LEFT PANEL: ACTION -->
        <div class="bauhaus-card">
            <div class="deco-circle"></div>
            <div class="deco-line"></div>

            <div class="card-header">
                <div style="font-size: 0.9rem; font-weight: 700; color: var(--accent); text-transform: uppercase; margin-bottom: 5px;">System Configuration</div>
                <h2>Add New Motor</h2>
            </div>

            <?php if ($success): ?>
                <div class="status-banner status-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="status-banner status-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="input-group">
                    <label for="motor_name" class="input-label">Identifier / Name</label>
                    <input type="text" id="motor_name" name="motor_name" class="cool-input" placeholder="FIELD-PUMP-01" required>
                </div>

                <div class="input-group" style="margin-top: -15px; margin-bottom: 30px;">
                    <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;">
                        Wait 30s after adding for system sync.
                    </div>
                </div>

                <button type="submit" class="btn-action">
                    Initialize Motor
                </button>
                <a href="farmer_dashboard.php" class="btn-cancel">Cancel Operation</a>
            </form>
        </div>

        <!-- RIGHT PANEL: INFO (ENGLISH ONLY) -->
        <div class="bauhaus-card guide-card">
            <div class="deco-circle" style="left: -50px; top: unset; bottom: -50px;"></div>
            
            <div class="card-header">
                <h2 style="color: #fff; border-bottom-color: rgba(255,255,255,0.2);">Protocols</h2>
            </div>

            <div style="position: relative; z-index: 2;">
                <!-- Language switcher removed as per user request -->

                <div class="guide-content">
                    <h4 style="text-transform: uppercase; color: var(--secondary); margin-bottom: 10px;">Safety Verification</h4>
                    <p class="guide-text">Verify all electrical connections strictly before initialization. Default motor state is <strong>OFF</strong>.</p>
                </div>

                <div class="guide-content" style="margin-top: 30px;">
                    <h4 style="text-transform: uppercase; color: var(--secondary); margin-bottom: 10px;">Adding New Hardware</h4>
                    <p class="guide-text">Ensure the motor ID matches your physical hardware controller configuration. Incorrect naming may delay synchronization.</p>
                </div>

            </div>
            
            <div style="margin-top: 40px; position: relative; z-index: 1;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                </svg>
            </div>
        </div>

    </div>
</div>

<!-- Footer -->
<footer>
  <p>© 2026 FlowGuard. All rights reserved • Promoting Sustainable Water Use in Agriculture</p>
</footer>

</body>
</html>
