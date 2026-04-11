<?php
session_start();
include "config.php";

// Require admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/*
 * admin_dashboard.php
 * Ready-to-paste file compatible with your schema:
 * - farmers (id, username, full_name, email, phone, ...)
 * - motors (id, farmer_id, motor_name, status, runtime_seconds, water_used, ...)
 * - sensor_readings (farmer_id, motor_id, device_id, water_flow, created_at, ...)
 * - groundwater (id, timestamp, level_per_day, volume)
 *
 * Notes:
 * - Today's usage is computed from sensor_readings.created_at
 * - Motor names come from motors.motor_name
 */

// 1. Fetch groundwater level
$groundwaterValue = 0;
// Remove try-catch as mysqli doesn't throw PDOException
$gwQ = $conn->query("SELECT level_per_day FROM groundwater ORDER BY timestamp DESC LIMIT 1");
if ($gwQ) {
    $gwR = $gwQ->fetch_assoc();
    if ($gwR && isset($gwR['level_per_day'])) {
        $groundwaterValue = $gwR['level_per_day'];
    }
}

// 2. Fetch motors
$motors = [];
$mQ = $conn->query("SELECT id, farmer_id, motor_name, status, COALESCE(runtime_seconds,0) AS runtime_seconds, COALESCE(water_used,0) AS water_used FROM motors");
if ($mQ) {
    while ($m = $mQ->fetch_assoc()) {
        $motors[] = [
            'id' => (int)$m['id'],
            'farmer_id' => (int)$m['farmer_id'],
            'location' => $m['motor_name'] ?? '',
            'status' => $m['status'] ?? 'OFF',
            'runtime_seconds' => (int)$m['runtime_seconds'],
            'water_used' => (float)$m['water_used']
        ];
    }
}

// 3. Compute today's water usage per farmer
$users_usage = [];
$usageSql = "
    SELECT 
      f.id AS farmer_id,
      f.username AS farmer_name,
      COALESCE(m.motor_name, '') AS device_id,
      COALESCE(SUM(sr.water_flow), 0) AS total_usage
    FROM farmers f
    LEFT JOIN motors m ON m.farmer_id = f.id
    LEFT JOIN sensor_readings sr 
      ON sr.farmer_id = f.id
      AND DATE(sr.created_at) = CURRENT_DATE
    GROUP BY f.id, f.username, m.motor_name
    ORDER BY total_usage DESC
";
$uQ = $conn->query($usageSql);
if ($uQ) {
    while ($u = $uQ->fetch_assoc()) {
        $users_usage[] = [
            'farmer_id' => (int)$u['farmer_id'],
            'farmer_name' => $u['farmer_name'],
            'device_id' => $u['device_id'],
            'total_usage' => (float)$u['total_usage']
        ];
    }
}

// 4. Groundwater history
$gw_history = [];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$limit = min($limit, 1000);

$gwhQ = $conn->prepare("SELECT id, timestamp, level_per_day, volume FROM groundwater ORDER BY timestamp DESC LIMIT ?");
if ($gwhQ) {
    $gwhQ->bind_param("i", $limit);
    $gwhQ->execute();
    $res = $gwhQ->get_result();
    while ($row = $res->fetch_assoc()) {
        $gw_history[] = $row;
    }
    $gwhQ->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>FlowGuard — Admin Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    /* Bauhaus / Industrial Theme */
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

    * { box-sizing: border-box; }
    html, body {
        height: 100%;
        margin: 0;
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        -webkit-font-smoothing: antialiased
    }
    
    /* Remove old background/gradients */
    body::before, body::after { display: none; }
    body { background: var(--bg-body); }

    .topbar {
      background: var(--primary);
      color: var(--surface);
      padding: 0 32px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 4px solid var(--accent);
    }
    .brand { display: flex; flex-direction: column; }
    .brand .logo {
      font-size: 24px;
      font-weight: 800;
      color: var(--surface);
      letter-spacing: -0.5px;
      text-transform: uppercase;
    }
    .brand .subtitle {
        font-size: 11px;
        color: rgba(255,255,255,0.7);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .top-actions { display: flex; gap: 20px; align-items: center }
    .top-actions a { color: var(--surface); text-decoration: none; font-weight: 600 }
    .btn-logout {
        background: var(--surface);
        border: none;
        padding: 8px 16px;
        border-radius: var(--radius);
        color: var(--primary) !important;
        font-weight: 700;
        transition: transform 0.1s;
        text-decoration: none;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    .btn-logout:hover {
        background: var(--secondary);
        color: var(--text-main) !important;
    }

    .nav-stripe {
      background: var(--surface);
      padding: 0 32px;
      display: flex;
      gap: 0;
      align-items: stretch;
      font-weight: 700;
      border-bottom: var(--border-width) solid var(--border-color);
      height: 50px;
    }
    .nav-item {
        color: var(--text-light);
        font-size: 0.9em;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        padding: 0 20px;
        text-transform: uppercase;
        border-right: 1px solid #E0E0E0;
        cursor: pointer;
        text-decoration: none;
    }
    .nav-item:first-child { border-left: 1px solid #E0E0E0; }
    .nav-item:hover {
        background: var(--bg-body);
        color: var(--primary);
        text-decoration: none;
    }
    .nav-item.active {
        color: var(--surface);
        background: var(--text-main);
        position: relative;
        text-decoration: none;
    }
    .nav-item.active::after { display: none; }

    .page {
      max-width: 1260px;
      margin: 40px auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 24px;
    }

    .content {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    /* Card - Flat, geometric */
    .card {
      background: var(--surface);
      border-radius: var(--radius);
      padding: 24px;
      box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
      border: var(--border-width) solid var(--border-color);
    }
    
    /* KPI Summary - Solid block */
    .card.kpi {
        background: var(--surface);
        border: var(--border-width) solid var(--border-color);
        box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
    }

    .card h2 {
      margin: 0 0 20px 0;
      color: var(--primary);
      font-size: 1.25rem;
      font-weight: 800;
      text-transform: uppercase;
      border-bottom: 2px solid var(--bg-body);
      padding-bottom: 10px;
    }
    .card p { color: var(--text-light); margin: 0; line-height: 1.6 }

    .card.full { grid-column: 1 / -1; width: 100% }

    table { width: 100%; border-collapse: collapse; font-size: 0.9rem }
    th, td { padding: 12px; border-bottom: 1px solid #E0E0E0; text-align: left }
    th {
        font-weight: 800;
        color: var(--text-main);
        background: #F0F0F0;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.8px;
        border-bottom: 2px solid var(--border-color);
    }
    td strong { color: var(--primary); font-weight: 700; }

    .status-dot { display: inline-block; width: 12px; height: 12px; border-radius: 0; margin-right: 8px; }
    .status-dot.on { background: var(--status-ok); }
    .status-dot.off { background: var(--status-err); }

    .sidebar { display: flex; flex-direction: column; gap: 24px }
    
    .search-box input {
        width: 100%;
        padding: 12px 16px;
        border-radius: var(--radius);
        border: 2px solid #E0E0E0;
        background: var(--bg-body);
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }
    .search-box input:focus {
        border-color: var(--primary);
        outline: none;
    }
    .search-box .btn {
        display: block;
        width: 100%;
        padding: 12px;
        margin-top: 16px;
        border-radius: var(--radius);
        background: var(--text-main);
        color: #fff;
        text-align: center;
        text-decoration: none;
        font-weight: 700;
        text-transform: uppercase;
        transition: transform 0.1s;
    }
    .search-box .btn:hover {
        background: var(--primary);
        transform: translateY(-2px);
    }

    .alert {
        background: #FFF5F5;
        border: 2px solid var(--status-err);
        padding: 16px;
        border-radius: var(--radius);
        color: var(--status-err);
        font-weight: 600;
        font-size: 0.9em;
        margin-top: 10px;
    }

    .gauge {
        font-size: 3.5rem;
        font-weight: 800;
        color: var(--secondary);
        text-align: center;
        letter-spacing: -2px;
    }
    .small { font-size: 0.85rem; color: var(--text-light); font-weight: 600; text-transform: uppercase; }

    @media (max-width: 1100px) {
      .page { grid-template-columns: 1fr 320px; padding: 18px }
    }
    @media (max-width: 900px) {
      .page { grid-template-columns: 1fr; padding: 16px }
    }

    .footer {
      background: var(--text-main);
      color: var(--surface);
      padding: 48px 24px;
      margin-top: 48px;
      border-top: 4px solid var(--accent);
    }

    .footer-container {
      max-width: 1260px;
      margin: auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 32px;
    }

    .footer h3 {
      margin: 0 0 16px 0;
      font-size: 1rem;
      font-weight: 700;
      color: var(--surface);
      text-transform: uppercase;
    }

    .footer p,
    .footer a {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.7);
      line-height: 1.8;
      text-decoration: none;
    }

    .footer a:hover {
      color: var(--secondary);
    }

    .footer-bottom {
      text-align: center;
      margin-top: 40px;
      padding-top: 24px;
      border-top: 1px solid rgba(255,255,255,0.1);
      font-size: 0.8rem;
      color: rgba(255,255,255,0.5);
    }


  </style>
</head>
<body>
  <!-- Topbar -->
  <div class="topbar">
    <div class="brand">
      <span class="logo">FlowGuard</span>
      <span class="subtitle">Administration</span>
    </div>
    <div class="top-actions">
      <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
      <a href="index.php" class="btn-logout">Logout</a>
    </div>
  </div>

  <!-- Nav Stripe -->
  <div class="nav-stripe">
    <a href="#dashboard" class="nav-item active" onclick="scrollToSection('dashboard')">Dashboard</a>
    <a href="#farmers-section" class="nav-item" onclick="scrollToSection('farmers-section')">Farmers</a>
    <a href="#motors-section" class="nav-item" onclick="scrollToSection('motors-section')">Motors</a>
    <a href="#sensors-section" class="nav-item" onclick="scrollToSection('sensors-section')">Sensors</a>
    <a href="#groundwater-section" class="nav-item" onclick="scrollToSection('groundwater-section')">Groundwater</a>
  </div>

  <main class="page" role="main">
      <!-- Dashboard Section -->
      <section id="dashboard" class="card full" aria-labelledby="usage-title">
        <h2 id="usage-title">Users' Water Usage (Today)</h2>
        <div style="overflow:auto">
          <table aria-describedby="usage-desc">
            <thead>
              <tr><th style="min-width:110px">User ID</th><th>Username</th><th>Device / Motor</th><th style="min-width:160px">Total Water Used (L)</th></tr>
            </thead>
            <tbody>
              <?php if (count($users_usage) === 0): ?>
                <tr><td colspan="4" class="small">No usage data available for today.</td></tr>
              <?php else: ?>
                <?php foreach ($users_usage as $u): ?>
                  <tr>
                    <td><?= (int)$u['farmer_id'] ?></td>
                    <td><?= htmlspecialchars($u['farmer_name']) ?></td>
                    <td><?= htmlspecialchars($u['device_id']) ?></td>
                    <td><?= number_format($u['total_usage'], 2) ?> L</td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- motor monitoring (card) -->
      <section id="motors-section" class="card" aria-labelledby="motor-title">
        <h2 id="motor-title">Motor Monitoring</h2>
        <div style="overflow:auto">
          <table id="motor-table" class="motor-table" aria-describedby="motor-desc">
            <thead><tr><th style="min-width:60px">ID</th><th>Motor / Device</th><th>Status</th><th style="min-width:140px">Runtime</th><th style="min-width:140px">Water Used</th></tr></thead>
            <tbody><!-- JS will populate --></tbody>
          </table>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
          <div class="small">Total water (all motors): <strong id="totalWater">0 L</strong></div>
          <div class="small" id="motor-desc">Data from motors table</div>
        </div>
      </section>

      <!-- Alerts & Groundwater -->
      <section id="groundwater-section" class="card" aria-labelledby="alerts-title">
        <h2 id="alerts-title">Alerts & Groundwater</h2>
        <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
          <div style="flex:1;min-width:180px">
            <div class="small">Latest groundwater level</div>
            <div class="gauge" aria-live="polite"><?= htmlspecialchars($groundwaterValue) ?></div>
            <div class="small" style="text-align:center;margin-top:6px">level per day</div>
          </div>

          <div style="flex:2;min-width:240px">
            <div class="small">Active Alerts</div>
            <div id="alerts" class="alerts" style="margin-top:8px">
              <div id="noAlerts" class="small">No alerts at this time.</div>
            </div>
          </div>
        </div>
      </section>

      <!-- groundwater history -->
      <section class="card full" aria-labelledby="gw-title">
        <h2 id="gw-title">Groundwater History</h2>
        <div style="overflow:auto">
          <table class="gw-table">
            <thead><tr><th style="min-width:70px">ID</th><th>Timestamp</th><th>Level per day</th><th>Volume</th></tr></thead>
            <tbody>
              <?php if (count($gw_history) === 0): ?>
                <tr><td colspan="4" class="small">No groundwater history found.</td></tr>
              <?php else: ?>
                <?php foreach ($gw_history as $gh): ?>
                  <tr>
                    <td><?= htmlspecialchars($gh['id']) ?></td>
                    <td><?= htmlspecialchars($gh['timestamp']) ?></td>
                    <td><?= htmlspecialchars($gh['level_per_day']) ?></td>
                    <td><?= htmlspecialchars($gh['volume']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Farmers Management -->
      <section id="farmers-section" class="card full" aria-labelledby="farmers-title">
        <h2 id="farmers-title">Farmers Management</h2>
        <p class="small" style="color: var(--text-light); margin: 12px 0;">Manage farmer accounts, monitor their water usage, and track device assignments.</p>
        <div style="overflow:auto">
          <table class="gw-table">
            <thead><tr><th style="min-width:60px">ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead>
            <tbody>
              <tr><td colspan="5" class="small">No farmers data available.</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Sensors Monitoring -->
      <section id="sensors-section" class="card full" aria-labelledby="sensors-title">
        <h2 id="sensors-title">Sensors Monitoring</h2>
        <p class="small" style="color: var(--text-light); margin: 12px 0;">Real-time monitoring of all IoT sensors deployed across the system, including temperature, humidity, soil moisture, and water flow readings.</p>
        <div style="overflow:auto">
          <table class="gw-table">
            <thead><tr><th style="min-width:60px">ID</th><th>Device</th><th>Parameter</th><th>Latest Value</th><th>Timestamp</th></tr></thead>
            <tbody>
              <tr><td colspan="5" class="small">No sensor data available.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- Right sidebar -->
    <aside class="sidebar" role="complementary">
      <section class="card search-box" aria-labelledby="search-title">
        <h2 id="search-title">Quick Search</h2>
        <input type="search" placeholder="Search articles..." />
        <a class="btn" href="#">Search</a>
      </section>

      <section class="card" aria-labelledby="alerts-side">
        <h2 id="alerts-side">Trending News </h2>
        <div style="max-height:260px;overflow:auto;padding-right:6px">
          <div class="card" style="margin:0;border-radius:8px;padding:12px;background:#f9fff8;border:1px solid rgba(34,150,80,0.06)">
            <p class="small">New government subsidy announced for micro-irrigation systems across various states. Read more</p>
          </div>

          <div class="card" style="margin-top:12px;border-radius:8px;padding:12px;background:#fffaf6;border:1px solid rgba(255,160,90,0.06)">
            <p class="small">Expert warns of looming water scarcity challenge for the summer season.</p>
          </div>
        </div>
      </section>

      <section class="card" aria-labelledby="help">
        <h2 id="help">Support</h2>
        <p class="small">Contact system administrator or check the logs if you see unexpected alerts. Click <a href="#" style="color:var(--accent-700);font-weight:700">here</a> to view logs.</p>
      </section>
    </aside>
  </main>
  <footer role="contentinfo" style="background: var(--color-text-primary); color: var(--color-white); padding: var(--spacing-2xl) var(--spacing-lg); margin-top: var(--spacing-2xl); border-top: 1px solid var(--color-border);">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
      <p style="opacity: 0.8; font-size: 0.9rem; margin: 0;">© <?= date('Y') ?> FlowGuard — Smart Water Monitoring System. All rights reserved.</p>
    </div>
</footer>


  <script>
    // Motor data from PHP
    const motors = <?= json_encode($motors, JSON_HEX_TAG) ?> || [];

    const motorTableBody = document.querySelector('#motor-table tbody');
    const totalWaterEl = document.getElementById('totalWater');
    const alertsContainer = document.getElementById('alerts');
    const noAlerts = document.getElementById('noAlerts');

    // threshold: 8 hours in seconds
    const OVERRUN_THRESHOLD = 8 * 60 * 60;

    function formatRuntime(seconds) {
      seconds = Number(seconds) || 0;
      const h = Math.floor(seconds / 3600);
      const m = Math.floor((seconds % 3600) / 60);
      const s = seconds % 60;
      return `${h}h ${m}m ${s}s`;
    }

    function renderMotors() {
      motorTableBody.innerHTML = '';
      let totalWater = 0;

      motors.forEach(m => {
        const tr = document.createElement('tr');
        const statusClass = (m.status && m.status.toUpperCase() === 'ON') ? 'on' : 'off';
        tr.innerHTML = `
          <td><strong>${m.id}</strong></td>
          <td>${m.location || '—'}</td>
          <td><span class="status-dot ${statusClass}"></span>${(m.status || 'OFF')}</td>
          <td>${formatRuntime(m.runtime_seconds)}</td>
          <td>${Number(m.water_used || 0).toLocaleString()} L</td>
        `;
        motorTableBody.appendChild(tr);
        totalWater += Number(m.water_used || 0);
      });

      totalWaterEl.textContent = `${totalWater.toLocaleString()} L`;
    }

    function updateAlerts() {
      alertsContainer.innerHTML = '';
      let found = false;
      motors.forEach(m => {
        if ((m.runtime_seconds || 0) > OVERRUN_THRESHOLD) {
          found = true;
          const div = document.createElement('div');
          div.className = 'alert';
          div.innerHTML = `<strong>Motor Over-run:</strong> Motor <strong>${m.id}</strong> (${m.location || 'unknown'}) running for ${formatRuntime(m.runtime_seconds)}.`;
          alertsContainer.appendChild(div);
        }
      });
      if (!found) {
        alertsContainer.appendChild(noAlerts);
      }
    }

    // initial render
    renderMotors();
    updateAlerts();

    // Navigation scroll functionality
    function scrollToSection(sectionId) {
      event.preventDefault();
      const section = document.getElementById(sectionId);
      if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Update active nav state
        document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
        event.target.classList.add('active');
      }
    }

    // Update active nav item on scroll
    window.addEventListener('scroll', () => {
      const sections = ['dashboard', 'motors-section', 'groundwater-section', 'farmers-section', 'sensors-section'];
      let current = sections[0];
      
      sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section && section.offsetTop <= window.scrollY + 100) {
          current = sectionId;
        }
      });
      
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        const href = item.getAttribute('href');
        if (href === '#' + current) {
          item.classList.add('active');
        }
      });
    });
  </script>
</body>
</html>
