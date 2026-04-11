<?php
session_start();
include "config.php";

// ------------------ MQTT CONFIG (from environment variables) ------------------
// Using HiveMQ Cloud (TLS) for server-side best-effort publish (kept as before)
$mqtt_host = 'ssl://' . (getenv('MQTT_BROKER') ?: '42197e61510145d38e4efb73f59a7f6a.s1.eu.hivemq.cloud');
$mqtt_port = (int)(getenv('MQTT_PORT') ?: 8883);
$mqtt_username = getenv('MQTT_USERNAME') ?: '';
$mqtt_password = getenv('MQTT_PASSWORD') ?: '';

// Topic format used when publishing motor commands (kept for backward compat if needed)
$mqtt_topic_prefix = 'flowguard';

// -------------------------------------------------------------------------------

// Redirect if not logged in
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}

$farmer_id = intval($_SESSION['farmer_id']);

// ---------- lightweight MQTT publish helper (MQTT 3.1.1, QoS 0) ----------
function mqtt_encode_length($len) {
    $str = '';
    do {
        $digit = $len % 128;
        $len = intdiv($len, 128);
        if ($len > 0) {
            $digit = $digit | 0x80;
        }
        $str .= chr($digit);
    } while ($len > 0);
    return $str;
}

/**
 * Publish a message to an MQTT broker (QoS 0).
 *
 * @param string $host
 * @param int    $port
 * @param string $clientId
 * @param string $topic
 * @param string $message
 * @param string|null $username
 * @param string|null $password
 * @param int    $timeout_secs
 * @return array ['ok' => bool, 'err' => string|null]
 */
function publish_mqtt_simple($host, $port, $clientId, $topic, $message, $username = null, $password = null, $timeout_secs = 5) {
    $ret = ['ok' => false, 'err' => null];

    // open socket (supports ssl:// prefix in $host for TLS)
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout_secs);
    if (!$fp) {
        $ret['err'] = "Socket open failed: ($errno) $errstr";
        return $ret;
    }
    stream_set_timeout($fp, $timeout_secs);

    // Build CONNECT packet
    $protocolName = pack('n', 4) . 'MQTT'; // "MQTT"
    $protocolLevel = chr(4); // MQTT 3.1.1
    $connectFlags = 0;
    if ($username !== null) $connectFlags |= (1 << 7);
    if ($password !== null) $connectFlags |= (1 << 6);
    // Clean session flag = 1
    $connectFlags |= 0x02;
    $keepalive = pack('n', 60);

    $payload = pack('n', strlen($clientId)) . $clientId;
    if ($username !== null) {
        $payload .= pack('n', strlen($username)) . $username;
    }
    if ($password !== null) {
        $payload .= pack('n', strlen($password)) . $password;
    }

    $variableHeader = $protocolName . $protocolLevel . chr($connectFlags) . $keepalive;
    $remaining = strlen($variableHeader . $payload);
    $connectPacket = chr(0x10) . mqtt_encode_length($remaining) . $variableHeader . $payload;

    // send CONNECT
    if (fwrite($fp, $connectPacket) === false) {
        fclose($fp);
        $ret['err'] = "Failed to send CONNECT";
        return $ret;
    }

    // read CONNACK (2 bytes fixed + 2 bytes payload for MQTT 3.1.1)
    $connack = fread($fp, 4);
    if ($connack === false || strlen($connack) < 4) {
        fclose($fp);
        $ret['err'] = "No CONNACK received or truncated";
        return $ret;
    }
    $byte1 = ord($connack[0]);
    $byte2 = ord($connack[1]);
    $ack_rc = ord($connack[3]);
    if ($byte1 != 0x20 || $byte2 != 0x02 || $ack_rc !== 0) {
        fclose($fp);
        $ret['err'] = "CONNACK rejected (rc=$ack_rc)";
        return $ret;
    }

    // Build PUBLISH packet (QoS 0)
    $topicpart = pack('n', strlen($topic)) . $topic;
    $payload = $message;
    $remaining = strlen($topicpart) + strlen($payload);
    $fixedHeader = chr(0x30) . mqtt_encode_length($remaining);
    $publishPacket = $fixedHeader . $topicpart . $payload;

    // send PUBLISH
    if (fwrite($fp, $publishPacket) === false) {
        fclose($fp);
        $ret['err'] = "Failed to send PUBLISH";
        return $ret;
    }

    // Close socket (QoS 0 requires no PUBACK)
    fclose($fp);
    $ret['ok'] = true;
    return $ret;
}
// ----------------------------------------------------------------------

// ===== AJAX handler: update motor status (called via fetch below) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Simple JSON API response
    header('Content-Type: application/json; charset=utf-8');

    $motor_id = isset($_POST['motor_id']) ? intval($_POST['motor_id']) : 0;
    $status   = isset($_POST['status']) ? strtoupper(trim($_POST['status'])) : '';

    if ($motor_id <= 0 || ($status !== 'ON' && $status !== 'OFF')) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid parameters']);
        exit();
    }

    // Update only if belongs to logged-in farmer
    // Update only if belongs to logged-in farmer
    $stmt = $conn->prepare("UPDATE motors SET status = ? WHERE id = ? AND farmer_id = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $status, $motor_id, $farmer_id);
        $ok = $stmt->execute();
        $stmt->close();
        
        if ($ok) {
            // Motor status updated successfully

            // Publish plain command to the ESP32's control topic (matching ESP32 subscribe)
            // ESP32 (online) expects simple "ON" or "OFF" on topic esp32_1/relay/control
            // We keep this server publish as best-effort (in case browser MQTT is unavailable)
            $topic = 'esp32_1/relay/control';
            $payload = $status; // "ON" or "OFF"

            // client id must be unique-ish per connection
            $clientId = 'flowguard-server-' . getmypid() . '-' . dechex(mt_rand(0, 0xffff));

            $mqttResult = publish_mqtt_simple($mqtt_host, $mqtt_port, $clientId, $topic, $payload, $mqtt_username, $mqtt_password);

            if ($mqttResult['ok']) {
                echo json_encode(['ok' => true, 'status' => $status, 'mqtt' => true]);
            } else {
                // publish failed but DB updated: return ok true with mqtt flag false and include message for debugging
                $resp = ['ok' => true, 'status' => $status, 'mqtt' => false, 'mqtt_err' => $mqttResult['err']];
                if (isset($firebase_err)) $resp['firebase_err'] = $firebase_err;
                echo json_encode($resp);
            }
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Update failed: ' . $conn->error]);
        }
    } else {
        echo json_encode(['ok' => false, 'msg' => 'DB Prepare failed: ' . $conn->error]);
    }
    exit();
}

// ===== Page load: fetch farmer + motor =====
$error = '';
$success = '';

// fetch farmer (optional for display)
// fetch farmer (optional for display)
$farmer = null;
$farmerStmt = $conn->prepare("SELECT id, username AS name FROM farmers WHERE id = ?");
if ($farmerStmt) {
    $farmerStmt->bind_param("i", $farmer_id);
    $farmerStmt->execute();
    $res = $farmerStmt->get_result();
    if ($res) {
        $farmer = $res->fetch_assoc();
    }
    $farmerStmt->close();
} else {
    $error = "DB Error: " . $conn->error;
}

// fetch the latest motor for this farmer
// fetch the latest motor for this farmer
$motor = null;
$motorStmt = $conn->prepare("SELECT * FROM motors WHERE farmer_id = ? ORDER BY id ASC LIMIT 1");
if ($motorStmt) {
    $motorStmt->bind_param("i", $farmer_id);
    $motorStmt->execute();
    $res = $motorStmt->get_result();
    if ($res) {
        $motor = $res->fetch_assoc();
    }
    $motorStmt->close();
} else {
    $error = "DB Error: " . $conn->error;
}

if (!$motor) {
    // default fallback (keeps existing UI consistent)
    $motor = [
        'id' => 0,
        'motor_name' => 'No Motor Assigned',
        'status' => 'OFF',
        'runtime_seconds' => 0,
        'water_used' => 0,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>FlowGuard | Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <style>
:root {
  /* Color Scheme - Bauhaus Industrial */
  --primary: #0F4C5C;        /* Dark Teal */
  --secondary: #1CA7EC;      /* Bright Cyan */
  --accent: #4F772D;         /* Olive Green */
  --bg-body: #F4F4F4;        /* Light Gray */
  --surface: #FFFFFF;        /* White */
  --text-main: #1E1E1E;      /* Charcoal */
  --text-light: #555555;     /* Medium Gray */
  
  /* Status Colors */
  --status-ok: #2A9D8F;      /* Success Green */
  --status-warn: #F4A261;    /* Warning Orange */
  --status-err: #E63946;     /* Error Red */
  --status-off: #999999;     /* Off Gray */
  
  /* Borders & Spacing */
  --border-width: 2px;
  --border-color: #1E1E1E;   /* High Contrast */
  --radius: 0px;             /* Sharp Corners */
}

body {
  display: grid;
  grid-template-rows: auto 1fr auto;
  background-color: var(--bg-body);
  color: var(--text-main);
  font-family: 'Inter', sans-serif;
  
  /* Cool Bauhaus Dot Grid */
  background-image: radial-gradient(var(--text-light) 1px, transparent 1px);
  background-size: 20px 20px;
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Header */
.header {
  background: var(--primary);
  color: var(--surface);
  padding: 0 40px;
  height: 70px;
  display: flex;
  justify-content: space-between;
  align-items: stretch;
  border-bottom: 4px solid var(--accent);
}
.header div:first-child { display: flex; align-items: center; }
.header h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--surface);
    letter-spacing: -0.5px;
    text-transform: uppercase;
    margin-left: 10px;
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

/* Main content */
main {
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 40px 20px;
  overflow: auto;
}

/* Grid */
.grid-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  align-items: start;
}

/* Card - Flat, geometric with interaction */
.card {
  background: var(--surface);
  border-radius: var(--radius);
  padding: 30px;
  box-shadow: 6px 6px 0px rgba(0,0,0,0.1); /* Hard shadow */
  border: var(--border-width) solid var(--border-color);
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
  border-top: 6px solid var(--secondary); /* Pop of color */
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 8px 8px 0px rgba(0,0,0,0.15);
}

/* Glassmorphism replacement -> Flat solid */
.card.kpi-panel {
    background: var(--surface);
    border: var(--border-width) solid var(--border-color);
    box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
    border-top: 6px solid var(--accent);
}

.card h2 {
    color: var(--primary);
    margin-bottom: 20px;
    font-weight: 800;
    font-size: 1.25rem;
    text-transform: uppercase;
    border-bottom: 2px solid var(--bg-body);
    padding-bottom: 10px;
}

@keyframes pulse-green {
  0% { box-shadow: 0 0 0 0 rgba(42, 157, 143, 0.7); }
  70% { box-shadow: 0 0 0 6px rgba(42, 157, 143, 0); }
  100% { box-shadow: 0 0 0 0 rgba(42, 157, 143, 0); }
}

/* motor card */
.motor-control-card {
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 15px;
    background: #FAFAFA; /* Slightly off-white */
}
.motor-name {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -1px;
}
.status-text {
    font-size: 18px;
    font-weight: 800;
    margin: 5px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 8px 12px;
    display: inline-block;
    border: 2px solid;
    width: fit-content;
}
.status-text.on {
    color: var(--status-ok);
    border-color: var(--status-ok);
    background: rgba(42, 157, 143, 0.1);
    animation: pulse-green 2s infinite;
}
.status-text.off {
    color: var(--text-light);
    border-color: var(--text-light);
    background: rgba(0,0,0,0.05);
}

/* toggle - Heavy industrial switch */
.toggle-switch-large {
    position: relative;
    display: inline-block;
    width: 120px;
    height: 60px;
}
.toggle-switch-large input { display: none; }
.slider-large {
    position: absolute;
    inset: 0;
    background: #E0E0E0;
    border-radius: var(--radius); /* Rectangular */
    transition: 0.1s;
    border: 2px solid var(--border-color);
    cursor: pointer;
}
.slider-large::before {
    content: "OFF";
    position: absolute;
    width: 50%;
    height: 100%;
    left: 0;
    top: 0;
    background: var(--text-light);
    transition: 0.1s;
    color: white;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border-right: 2px solid var(--border-color);
}

input:checked + .slider-large {
    background: var(--surface);
}
input:checked + .slider-large::before {
    content: "ON";
    transform: translateX(100%);
    background: var(--primary);
    border-left: 2px solid var(--border-color);
    border-right: none;
}

/* small meta row */
.meta-row {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 15px;
}
.meta-pill {
    background: var(--surface);
    color: var(--text-light);
    padding: 10px 14px;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 12px;
    border: 2px solid #E0E0E0;
    text-transform: uppercase;
    font-family: 'Space Mono', monospace; /* Data font */
}
.meta-pill strong { color: var(--primary); font-size: 14px; font-family: 'Space Mono', monospace; }

/* connection badge - Rectangular label */
.conn-badge {
    position: absolute;
    right: 30px;
    top: 30px;
    padding: 8px 16px;
    border-radius: var(--radius);
    font-weight: 700;
    font-size: 11px;
    color: var(--surface);
    background: var(--status-off);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: 'Space Mono', monospace;
}
.conn-badge.connected { background: var(--status-ok); }
.conn-badge.disconnected { background: var(--status-err); }

/* Buttons inside card - Unified Bauhaus Style */
.btn-link, a[href^="sens1.php"] {
    display: inline-block;
    background: var(--text-main) !important;
    color: var(--surface) !important;
    border: none !important;
    border-radius: 0px !important; /* Squared */
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 24px !important;
    box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
    transition: all 0.2s;
    font-weight: 800;
    font-size: 13px;
    text-decoration: none;
}
.btn-link:hover, a[href^="sens1.php"]:hover {
    transform: translateY(2px);
    box-shadow: 2px 2px 0px rgba(0,0,0,0.2);
    text-decoration: none;
}

/* footer */
footer {
    background: var(--text-main);
    color: var(--surface);
    padding: 24px;
    text-align: center;
    border-top: 4px solid var(--accent);
    font-weight: 500;
    font-size: 0.9rem;
}

/* responsive */
@media (max-width: 900px) {
  .grid-container { grid-template-columns: 1fr; }
  main { padding: 20px; }
  .header { padding: 0 20px; }
}
  </style>

  </style>
</head>
<body>

  <!-- mqtt.js from unpkg -->
  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
  
  <!-- Firebase SDK -->
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

<!-- Header -->
<header class="header">
  <div style="display:flex;align-items:center;gap:14px">
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

<!-- Main -->
<main>
  <div class="grid-container">
    <!-- Motor control card -->
    <div class="card motor-control-card" aria-live="polite">
      <div class="conn-badge disconnected" id="connBadge">FireBase: Connecting...</div>

      <h2>Motor Control</h2>

      <!-- Motor name (from DB) -->
      <div class="motor-name" id="motorName"><?= htmlspecialchars($motor['motor_name']); ?></div>

      <!-- status text -->
      <?php $stat = strtoupper($motor['status']); ?>
      <div id="motorStatusText" class="status-text <?= strtolower($stat); ?>">
        MOTOR IS <?= $stat; ?>
      </div>

      <!-- toggle (initial checked based on DB) -->
      <label class="toggle-switch-large" title="Toggle motor">
        <input type="checkbox" id="motorToggle" <?= $stat === 'ON' ? 'checked' : ''; ?> aria-label="Motor toggle">
        <span class="slider-large" aria-hidden="true"></span>
      </label>

      <div class="meta-row">
        <div class="meta-pill">ID: <strong style="margin-left:8px"><?= intval($motor['id']); ?></strong></div>
        <div class="meta-pill">Runtime: <strong style="margin-left:8px"><?= intval($motor['runtime_seconds']); ?>s</strong></div>
        <div class="meta-pill">Water used: <strong style="margin-left:8px"><?= intval($motor['water_used']); ?>L</strong></div>
      </div>

      <div style="margin-top:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <a class="btn-link" href="add_motor.php" style="display:inline-block;background:var(--green-main);color:#fff;padding:12px 24px;border-radius:0px;text-decoration:none;font-weight:800;text-transform:uppercase;box-shadow:4px 4px 0px rgba(0,0,0,0.2);font-size:13px;">➕ Add New Motor</a>
        <a href="sens1.php" style="display:inline-block;color:var(--surface);padding:12px 24px;background:var(--text-main);text-decoration:none;font-weight:800;text-transform:uppercase;box-shadow:4px 4px 0px rgba(0,0,0,0.2);font-size:13px;">STATS</a>
        <a href="finance.php" style="display:inline-block;color:var(--surface);padding:12px 24px;background:var(--text-main);text-decoration:none;font-weight:800;text-transform:uppercase;border:none;box-shadow:4px 4px 0px rgba(0,0,0,0.2);transition:all 0.2s;font-size:13px;">CALCULATE</a>
      </div>
    </div>

    <!-- A second card could show more details / controls -->
    <div class="card">
      <h2>Motor Operation & Safety Guidelines</h2>

      <p style="color:#234;line-height:1.6;margin-bottom:12px">
        This dashboard allows real-time monitoring and control of irrigation motors registered under your account.
        All actions performed here are securely recorded in the FlowGuard database.
      </p>

      <ul style="color:#234;line-height:1.7;padding-left:18px;margin-bottom:14px">
        <li>
          <strong>Motor Status Control:</strong>
          Toggle the switch to safely turn the motor <strong>ON</strong> or <strong>OFF</strong>.
          The status is instantly updated in the system.
        </li>
        <li>
          <strong>Runtime Tracking:</strong>
          Motor runtime and water usage data help identify overuse, inefficiency, or potential leaks.
        </li>
        <li>
          <strong>Leak Detection Support:</strong>
          Abnormal runtime or unexpected water usage can indicate leakage or pipe damage.
        </li>
        <li>
          <strong>Data Reliability:</strong>
          All readings are linked to your motor ID and farmer account for accuracy and audit purposes.
        </li>
      </ul>

      <p style="font-size:13px;color:#456">
        Operator: <strong><?= htmlspecialchars($farmer['name'] ?? 'Farmer'); ?></strong><br>
        System: <strong>FlowGuard Smart Irrigation Platform</strong>
      </p>
    </div>

  </div>
</main>

<!-- Footer -->
<footer>
  <p>© 2026 FlowGuard. All rights reserved • Promoting Sustainable Water Use in Agriculture</p>
</footer>

<script>
(function(){
  const motorToggle = document.getElementById('motorToggle');
  const motorStatusText = document.getElementById('motorStatusText');
  const motorNameEl = document.getElementById('motorName');
  const connBadge = document.getElementById('connBadge');

  // motor id and farmer id from PHP rendered into JS (safe int)
  const motorId = <?= intval($motor['id']); ?>;
  const farmerId = <?= intval($farmer_id); ?>;

  function setStatusUI(status){
    const s = (status || '').toUpperCase();
    motorStatusText.textContent = 'MOTOR IS ' + s;
    motorStatusText.classList.remove('on','off');
    motorStatusText.classList.add(s === 'ON' ? 'on' : 'off');
    motorToggle.checked = (s === 'ON');
  }

  // MQTT browser client (mqtt.js) - uses HiveMQ Cloud websocket endpoint
  // Client ID set to "motor" as requested (note: duplicates may disconnect other clients with same id)
  const mqttOptions = {
    username: "hivemq.webclient.1758715810298",
    password: "Y*9.%m2vj!rKJyLB1X0q",
    clientId: "motor",
    clean: true,
    reconnectPeriod: 3000
  };

  // connect over WebSockets (wss); path /mqtt typically used by HiveMQ Cloud
  const wsUrl = 'wss://f5bc2a85c9c747488d7ab174eaa1faaf.s1.eu.hivemq.cloud:8884/mqtt';
  const client = mqtt.connect(wsUrl, mqttOptions);

  client.on('connect', () => {
    console.log('✅ MQTT (browser) connected');
    connBadge.textContent = 'FireBase: Connected';
    connBadge.classList.remove('disconnected');
    connBadge.classList.add('connected');

    // subscribe to possible relay status topics (both esp32_1 and esp32_2 used around your system)
    client.subscribe('esp32_1/relay/status', (err) => {
      if (err) console.warn('Subscribe esp32_1 failed', err);
    });
    client.subscribe('esp32_2/relay/status', (err) => {
      if (err) console.warn('Subscribe esp32_2 failed', err);
    });
  });

  client.on('reconnect', () => {
    console.log('🔄 MQTT reconnecting...');
    connBadge.textContent = 'mqtt: Reconnecting...';
    connBadge.classList.remove('connected');
    connBadge.classList.add('disconnected');
  });

  client.on('offline', () => {
    console.log('⚠️ MQTT offline');
    connBadge.textContent = 'MQTT: Offline';
    connBadge.classList.remove('connected');
    connBadge.classList.add('disconnected');
  });

  client.on('error', (err) => {
    console.error('MQTT error', err);
    connBadge.textContent = 'MQTT: Error';
    connBadge.classList.remove('connected');
    connBadge.classList.add('disconnected');
  });

  client.on('message', (topic, message) => {
    const msg = message.toString();
    console.log('📩 MQTT', topic, msg);

    if (topic === 'esp32_1/relay/control' || topic === 'esp32_1/relay/control') {
      // Accept "ON"/"OFF" or "1"/"0" from various gateways
      if (msg === 'ON' || msg === '1') {
        setStatusUI('ON');
      } else if (msg === 'OFF' || msg === '0') {
        setStatusUI('OFF');
      } else {
        // If message is JSON or verbose, try to parse
        try {
          const j = JSON.parse(msg);
          if (j && j.status) setStatusUI(j.status);
        } catch(e) {
          // ignore parse error
        }
      }
    }
  });

  // initial UI already set by server; keep function for reuse
  if (!motorId || motorId <= 0) {
    // disable toggle if no motor present
    motorToggle.disabled = true;
    motorStatusText.textContent = 'NO MOTOR ASSIGNED';
    motorStatusText.classList.remove('on','off');
  } else {
    // attach event
    motorToggle.addEventListener('change', function(){
      const newStatus = motorToggle.checked ? 'ON' : 'OFF';

      // immediate optimistic UI change
      setStatusUI(newStatus);

      // 1) Publish via browser MQTT (fast, direct)
      try {
        client.publish('esp32_1/relay/control', newStatus);
        console.log('📤 Browser MQTT published:', newStatus);
      } catch (e) {
        console.warn('Browser MQTT publish error', e);
      }

      // 2) Also update server DB and server-side MQTT publish (best-effort) via POST
      const form = new URLSearchParams();
      form.append('update_status', '1');
      form.append('motor_id', motorId);
      form.append('status', newStatus);

      fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString()
      })
      .then(r => r.json())
      .then(json => {
        if (!json || !json.ok) {
          // revert UI and alert user
          const revert = (newStatus === 'ON') ? 'OFF' : 'ON';
          setStatusUI(revert);
          console.error('Update failed', json && json.msg ? json.msg : json);
          alert('Failed to update motor status on server.');
        } else {
          // server confirms; ensure UI matches returned status
          setStatusUI(json.status);
          // optional: show a subtle note if server-side MQTT publish failed
          if (json.mqtt === false) {
            console.warn('Server MQTT publish failed:', json.mqtt_err || 'unknown');
            // Don't block user; this only means server couldn't connect, but browser already published
          }
        }
      })
      .catch(err => {
        const revert = (newStatus === 'ON') ? 'OFF' : 'ON';
        setStatusUI(revert);
        console.error(err);
        alert('Network error while updating motor status.');
      });
    });
  }
})();
</script>

</body>
</html>
