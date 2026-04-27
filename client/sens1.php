<?php
  session_start();
  if (!isset($_SESSION['farmer_id'])) {
      header("Location: farmer_login.php");
      exit();
  }

  /* ========== LOAD ENVIRONMENT VARIABLES ========== */
  require_once __DIR__ . '/env-loader.php';

  /* ========== OPTIONAL: INCLUDE CONFIG (firebase admin instance etc) ========== */
  // if you have a server-side config with $firebaseDatabase (Firebase Admin SDK), include it
  @include_once __DIR__ . '/config.php';

  /* ========== DB CONNECTION ========== */
  $conn = new mysqli("localhost", "root", "", "flowguard");
  if ($conn->connect_error) {
      http_response_code(500);
      die("DB Connection Failed");
  }

  // ===================== NEW CODE: Water usage threshold (SESSION) + Twilio call =====================
  // Twilio Credentials (loaded from .env)
  $ACCOUNT_SID = getEnv('TWILIO_ACCOUNT_SID');
  $AUTH_TOKEN  = getEnv('TWILIO_AUTH_TOKEN');
  $FROM_NUMBER = getEnv('TWILIO_FROM_NUMBER');

  // NEW CODE: Save threshold (form post)
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["set_water_usage_threshold"])) {
      $t = $_POST["water_usage_threshold_liters"] ?? "";
      $t = is_numeric($t) ? floatval($t) : null;

      if ($t !== null && $t >= 0) {
          $_SESSION["water_usage_threshold_liters"] = $t;
          // NEW CODE: reset one-time call lock when threshold is updated
          unset($_SESSION["water_usage_threshold_call_sent"]);
          unset($_SESSION["water_usage_threshold_breached_at"]);
      }

      // NEW CODE: avoid form resubmission
      header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
      exit();
  }

  // NEW CODE: Helper to fetch farmer phone (if column exists)
  function get_farmer_phone_if_available($conn, $farmer_id) {
      $phone = "";
      $hasPhoneCol = false;
      if ($res = $conn->query("SHOW COLUMNS FROM `farmers` LIKE 'phone'")) {
          if ($res->num_rows > 0) $hasPhoneCol = true;
          $res->free();
      }
      if (!$hasPhoneCol) return "";

      if ($stmt = $conn->prepare("SELECT phone FROM farmers WHERE id = ? LIMIT 1")) {
          $stmt->bind_param("i", $farmer_id);
          $stmt->execute();
          $phoneDb = null; // Initialize to avoid unassigned variable warning
          $stmt->bind_result($phoneDb);
          if ($stmt->fetch()) $phone = trim((string)$phoneDb);
          $stmt->close();
      }
      return $phone;
  }

  // NEW CODE: AJAX handler to check threshold + trigger one-time call
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["check_water_threshold"])) {
      header("Content-Type: application/json; charset=utf-8");

      $threshold = isset($_SESSION["water_usage_threshold_liters"]) ? floatval($_SESSION["water_usage_threshold_liters"]) : null;
      $total = $_POST["total_water_liters"] ?? "";
      $total = is_numeric($total) ? floatval($total) : null;

      if ($threshold === null || $total === null) {
          echo json_encode(["ok" => false, "msg" => "Missing threshold or total water usage"]);
          exit();
      }

      $alreadySent = !empty($_SESSION["water_usage_threshold_call_sent"]);
      if ($total <= $threshold) {
          echo json_encode(["ok" => true, "breach" => false, "sent" => $alreadySent]);
          exit();
      }

      if ($alreadySent) {
          echo json_encode(["ok" => true, "breach" => true, "sent" => true]);
          exit();
      }

      // Use hardcoded phone number for alerts
      $TO_NUMBER = "+919765635635";  // Alert phone number

      // NEW CODE: Trigger Twilio voice call (one-time per breach until threshold is reset)
      $twiml = '<Response><Say voice="alice">Alert. Water usage has exceeded the set threshold of '
          . htmlspecialchars(number_format($threshold, 2), ENT_QUOTES)
          . ' liters. Current usage is '
          . htmlspecialchars(number_format($total, 2), ENT_QUOTES)
          . ' liters.</Say></Response>';

      $url = "https://api.twilio.com/2010-04-01/Accounts/$ACCOUNT_SID/Calls.json";
      $data = http_build_query([
          "To"    => $TO_NUMBER,
          "From"  => $FROM_NUMBER,
          "Twiml" => $twiml
      ]);

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_USERPWD, $ACCOUNT_SID . ":" . $AUTH_TOKEN);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
      // curl_close($ch); // Deprecated in PHP 8.5+

      if ($curlErr) {
          echo json_encode(["ok" => false, "msg" => "cURL Error: " . $curlErr]);
          exit();
      }
      if ($httpCode < 200 || $httpCode >= 300) {
          echo json_encode(["ok" => false, "msg" => "Twilio Error", "twilio_response" => $response]);
          exit();
      }

      $_SESSION["water_usage_threshold_call_sent"] = 1;
      $_SESSION["water_usage_threshold_breached_at"] = time();

      echo json_encode(["ok" => true, "breach" => true, "sent" => true]);
      exit();
  }
  // ===================== END NEW CODE ================================================================

  // ===================== NEW CODE: Read-only JSON data API (for analytics/reports) ===================
  // Provides sensor_readings for the logged-in farmer without altering existing page logic.
  if (isset($_GET["data_api"])) {
      header("Content-Type: application/json; charset=utf-8");

      $farmerIdApi = intval($_SESSION["farmer_id"]);
      $range = isset($_GET["range"]) ? strtolower(trim($_GET["range"])) : "all";
      $rangeSql = "";
      if ($range === "24h" || $range === "24hours" || $range === "day") {
          $rangeSql = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) ";
          $range = "24h";
      } elseif ($range === "week" || $range === "7d" || $range === "weekly") {
          $rangeSql = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
          $range = "week";
      } else {
          $range = "all";
      }

      $sql = "SELECT id, created_at, temperature, humidity, soil_moisture, water_flow, motor_status
              FROM sensor_readings
              WHERE farmer_id = ? {$rangeSql}
              ORDER BY created_at DESC
              LIMIT 300";

      $data = [];
      if ($stmtApi = $conn->prepare($sql)) {
          $stmtApi->bind_param("i", $farmerIdApi);
          if ($stmtApi->execute()) {
              $resApi = $stmtApi->get_result();
              while ($row = $resApi->fetch_assoc()) {
                  $data[] = [
                      "id"            => intval($row["id"]),
                      "timestamp"     => $row["created_at"],
                      "temperature"   => isset($row["temperature"]) ? floatval($row["temperature"]) : null,
                      "humidity"      => isset($row["humidity"]) ? floatval($row["humidity"]) : null,
                      "soil_moisture" => isset($row["soil_moisture"]) ? floatval($row["soil_moisture"]) : null,
                      "water_flow"    => isset($row["water_flow"]) ? floatval($row["water_flow"]) : null,
                      "motor_status"  => $row["motor_status"] ?? null,
                  ];
              }
          }
          $stmtApi->close();
      }

      echo json_encode([
          "ok" => true,
          "range" => $range,
          "count" => count($data),
          "data" => $data,
      ]);
      exit();
  }

  /* ========== DYNAMIC DEVICES (from DB) ========== */
  $motors = [];
  $farmer_id_for_device = intval($_SESSION['farmer_id']);
  if ($stmt_dev = $conn->prepare("SELECT id, motor_name FROM motors WHERE farmer_id = ?")) {
      $stmt_dev->bind_param("i", $farmer_id_for_device);
      $stmt_dev->execute();
      $res_dev = $stmt_dev->get_result();
      while ($row = $res_dev->fetch_assoc()) {
          $mqttBase = rtrim($row['motor_name'], "/#");
          if ($mqttBase === "") $mqttBase = "esp32_" . $row['id'];
          
          $motors[] = [
              'id' => $row['id'],
              'name' => $row['motor_name'],
              'topic_base' => $mqttBase,
              'id_safe' => preg_replace('/[^a-zA-Z0-9_-]/', '_', $mqttBase)
          ];
      }
      $stmt_dev->close();
  }

  // Fallback if no motors found
  if (empty($motors)) {
      $motors[] = [
          'id' => 1,
          'name' => 'Motor 1',
          'topic_base' => 'esp32_1',
          'id_safe' => 'esp32_1'
      ];
  }
  
  // Default values for header (backward compatibility or first motor)
  $mqttTopicBase = $motors[0]['topic_base'];
  $deviceIdSafe = $motors[0]['id_safe'];
  $displayMotorName = $motors[0]['name'];

  /* ========== AJAX SAVE SENSOR DATA ========== */
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_sensor"])) {
      $stmt = $conn->prepare(
          "INSERT INTO sensor_readings
          (farmer_id, motor_id, device_id, temperature, humidity, soil_moisture, water_flow, motor_status)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
      );

      if (!$stmt) {
          http_response_code(500);
          echo json_encode(["status" => "error", "msg" => "Prepare failed"]);
          exit();
      }

      $farmer_id = intval($_SESSION["farmer_id"]);
      $motor_id = intval($_POST["motor_id"] ?? 1);
      $device_id = trim($_POST["device_id"] ?? $mqttTopicBase);
      $temperature = is_numeric($_POST["temperature"]) ? floatval($_POST["temperature"]) : null;
      $humidity = is_numeric($_POST["humidity"]) ? floatval($_POST["humidity"]) : null;
      $soil = is_numeric($_POST["soil"]) ? floatval($_POST["soil"]) : null;
      $flow = is_numeric($_POST["flow"]) ? floatval($_POST["flow"]) : null; // expected to be L/min (flow rate)
      $motor = trim($_POST["motor"] ?? "OFF");

      $stmt->bind_param(
          "iisdddds",
          $farmer_id,
          $motor_id,
          $device_id,
          $temperature,
          $humidity,
          $soil,
          $flow,
          $motor
      );

      $ok = $stmt->execute();
      if ($ok) {
          try {
              if (isset($firebaseDatabase) && $firebaseDatabase) {
                  $fbPayload = [
                      'farmer_id'    => $farmer_id,
                      'motor_id'     => $motor_id,
                      'device_id'    => $device_id,
                      'temperature'  => $temperature,
                      'humidity'     => $humidity,
                      'soil_moisture'=> $soil,
                      'water_flow'   => $flow,
                      'motor_status' => $motor,
                      'created_at'   => date('c'),
                      'timestamp'    => time()
                  ];
                  $ref = "sensor_readings/" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $device_id);
                  $firebaseDatabase->getReference($ref)->push($fbPayload);
              }
          } catch (Exception $e) {
              $firebase_err = $e->getMessage();
          }

          $response = ["status" => "saved"];
          if (isset($firebase_err)) $response["firebase_err"] = $firebase_err;
          echo json_encode($response);
      } else {
          http_response_code(500);
          echo json_encode(["status" => "error", "msg" => $stmt->error]);
      }

      $stmt->close();
      exit();
  }
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>FlowGuard | Smart Water Monitoring</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>


    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
      /* ========================================
         BAUHAUS / INDUSTRIAL - FlowGuard Dashboard
         Sharp Corners, Hard Shadows, High Contrast
         ======================================== */

      :root {
        --primary: #0F4C5C;        /* Deep Teal */
        --secondary: #1CA7EC;      /* Bright Cyan */
        --accent: #4F772D;         /* Olive Green */
        --bg-body: #F4F4F4;        /* Light Gray */
        --surface: #FFFFFF;        /* Pure White */
        --text-main: #1E1E1E;      /* Charcoal */
        --text-light: #555555;     /* Medium Gray */
        
        --status-ok: #2A9D8F;      /* Success Green */
        --status-warn: #F4A261;    /* Warning Orange */
        --status-err: #E63946;     /* Error Red */
        --status-off: #999999;
        
        --border-width: 2px;
        --border-color: #1E1E1E;   /* High Contrast */
        --radius: 0px;             /* Sharp Corners */
      }

      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      html, body {
        height: 100%;
      }

      body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        -webkit-font-smoothing: antialiased;
        display: flex;
        flex-direction: column;
        background-image: radial-gradient(var(--text-light) 1px, transparent 1px);
        background-size: 20px 20px;
      }

      /* Header */
      .navbar {
        background: var(--primary);
        color: var(--surface);
        padding: 0 40px;
        height: 70px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 4px solid var(--accent);
        position: relative;
        z-index: 100;
      }

      .brand {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .brand h1 {
        font-size: 24px;
        font-weight: 800;
        color: var(--surface);
        letter-spacing: -0.5px;
        text-transform: uppercase;
        margin: 0;
      }

      .brand svg {
        width: 32px;
        height: 32px;
      }

      .nav-links {
        display: flex;
        gap: 0;
        align-items: center;
      }

      .nav-links a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-weight: 700;
        padding: 0 20px;
        display: flex;
        align-items: center;
        height: 70px;
        transition: all 0.2s;
        border-left: 1px solid rgba(255,255,255,0.1);
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
      }

      .nav-links a:hover {
        background: var(--secondary);
        color: var(--text-main);
      }

      /* Main content */
      main {
        flex: 1;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        gap: 30px;
      }

      /* Grid */
      .content-row {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 30px;
        align-items: start;
      }

      /* Card */
      .sensor-card, .threshold-section, .article-section {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 30px;
        box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
        border: var(--border-width) solid var(--border-color);
        transition: transform 0.2s, box-shadow 0.2s;
      }

      .sensor-card:hover,
      .threshold-section:hover,
      .article-section:hover {
        transform: translateY(-2px);
        box-shadow: 8px 8px 0px rgba(0,0,0,0.15);
      }

      .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--bg-body);
      }

      .card-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        margin: 0;
      }

      .card-body {
        padding-top: 0;
      }

      /* Data rows */
      .data-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #E0E0E0;
        font-size: 14px;
      }

      .data-row:last-child {
        border-bottom: none;
      }

      .data-left {
        color: var(--text-light);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .value {
        font-weight: 800;
        color: var(--text-main);
        font-family: 'Space Mono', monospace;
        font-size: 16px;
      }

      /* Status badges */
      .status-on {
        background: rgba(42, 157, 143, 0.1);
        color: var(--status-ok);
        padding: 8px 12px;
        border: 2px solid var(--status-ok);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .status-off {
        background: rgba(0,0,0,0.05);
        color: var(--text-light);
        padding: 8px 12px;
        border: 2px solid var(--text-light);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      /* Buttons */
      .back-btn {
        background: var(--text-main);
        color: var(--surface);
        border: none;
        padding: 10px 20px;
        border-radius: var(--radius);
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .back-btn:hover {
        background: var(--primary);
        transform: translateY(2px);
        box-shadow: 2px 2px 0px rgba(0,0,0,0.2);
      }

      /* Device pill */
      .device-pill {
        background: var(--bg-body);
        color: var(--primary);
        padding: 6px 12px;
        border-radius: var(--radius);
        font-weight: 700;
        font-size: 11px;
        border: 2px solid var(--border-color);
        font-family: 'Space Mono', monospace;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      /* Hint / Advisory */
      .hint, .advice-list li {
        margin-top: 20px;
        padding: 16px;
        background: var(--surface);
        border: 2px solid var(--border-color);
        border-radius: var(--radius);
        color: var(--text-light);
        font-size: 13px;
        line-height: 1.6;
        font-weight: 500;
      }

      .advice-list {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .advice-list li {
        margin-bottom: 12px;
        padding-left: 16px;
        border-left: 3px solid var(--accent);
      }

      .advice-list li:last-child {
        margin-bottom: 0;
      }

      .advice-list li strong {
        color: var(--primary);
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
      }

      /* Article / sidebar */
      .article-section {
        position: sticky;
        top: 100px;
        max-height: calc(100vh - 150px);
        overflow-y: auto;
      }

      .article-header {
        margin-bottom: 20px;
      }

      .article-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        margin: 0 0 8px 0;
      }

      .article-meta {
        font-size: 11px;
        color: var(--text-light);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .article-content {
        line-height: 1.7;
        color: var(--text-light);
        font-size: 13px;
      }

      .pill {
        display: inline-block;
        background: var(--primary);
        color: var(--surface);
        padding: 6px 10px;
        border-radius: var(--radius);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-right: 6px;
        margin-bottom: 12px;
      }

      /* Quick checks */
      .quick-checks {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 2px solid var(--border-color);
      }

      .quick-checks strong {
        color: var(--primary);
        font-size: 11px;
        display: block;
        margin-bottom: 12px;
        font-weight: 700;
        text-transform: uppercase;
      }

      .quick-checks-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      .quick-checks-list small {
        background: var(--bg-body);
        padding: 8px 10px;
        border-radius: var(--radius);
        font-size: 12px;
        color: var(--text-light);
        border: 2px solid #E0E0E0;
        font-weight: 600;
        display: block;
        transition: all 0.2s;
      }

      .quick-checks-list small:hover {
        background: var(--primary);
        color: var(--surface);
        border-color: var(--primary);
      }

      /* Water threshold section */
      .threshold-section {
        margin-bottom: 20px;
      }

      .threshold-section input[type="number"] {
        border: 2px solid #E0E0E0;
        border-radius: var(--radius);
        padding: 10px 12px;
        font-family: 'Space Mono', monospace;
        font-size: 13px;
        outline: none;
        background: var(--bg-body);
        color: var(--text-main);
        font-weight: 600;
        width: 140px;
        transition: border-color 0.2s;
      }

      .threshold-section input[type="number"]:focus {
        border-color: var(--primary);
        background: var(--surface);
      }

      .threshold-section button {
        width: 100%;
        margin-top: 16px;
        padding: 12px 16px;
        background: var(--text-main);
        color: var(--surface);
        border: none;
        border-radius: var(--radius);
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .threshold-section button:hover {
        background: var(--primary);
        transform: translateY(2px);
        box-shadow: 2px 2px 0px rgba(0,0,0,0.2);
      }

      /* Quick links */
      .quick-links-section {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
      }

      .quick-links-section .back-btn {
        flex: 1;
        min-width: 120px;
        justify-content: center;
      }

      /* Footer */
      footer {
        padding: 24px;
        background: var(--text-main);
        color: var(--surface);
        border-top: 4px solid var(--accent);
        text-align: center;
        font-size: 0.9rem;
        font-weight: 500;
      }

      /* Scrollbar styling */
      .article-section::-webkit-scrollbar {
        width: 6px;
      }

      .article-section::-webkit-scrollbar-track {
        background: transparent;
      }

      .article-section::-webkit-scrollbar-thumb {
        background: #D0D0D0;
        border-radius: 3px;
      }

      .article-section::-webkit-scrollbar-thumb:hover {
        background: #999;
      }

      /* Responsive */
      @media (max-width: 1000px) {
        .content-row {
          grid-template-columns: 1fr;
        }
        
        .article-section {
          position: relative;
          top: auto;
        }
      }

      @media (max-width: 768px) {
        main {
          padding: 20px;
        }

        .navbar {
          padding: 0 20px;
        }

        .nav-links a {
          padding: 0 14px;
          font-size: 11px;
        }
      }
      /* Responsive */
      @media (max-width: 1000px) {
        .content-row {
          grid-template-columns: 1fr;
        }
        
        .article-section {
          position: relative;
          top: auto;
        }
      }

      @media (max-width: 768px) {
        main {
          padding: 20px;
        }

        .navbar {
          padding: 0 20px;
        }

        .nav-links a {
          padding: 0 14px;
          font-size: 11px;
        }

        .content-row {
          gap: 24px;
        }

        .article-section {
          max-height: none;
          position: static;
          top: auto;
        }

        .sensor-card {
          padding: 24px;
        }

        .card-header {
          flex-direction: column;
          align-items: flex-start;
          gap: 16px;
        }

        .card-actions {
          width: 100%;
          justify-content: space-between;
        }
      }

      @media (max-width: 640px) {
        .navbar {
          padding: 0 16px;
          height: 60px;
        }

        .brand h1 {
          font-size: 16px;
        }

        .nav-links {
          gap: 16px;
        }

        .nav-links a {
          font-size: 12px;
        }

        main {
          padding: 24px 16px;
          gap: 24px;
        }

        .sensor-card {
          padding: 20px;
        }

        .card-title {
          font-size: 18px;
        }

        .value {
          font-size: 16px;
        }

        .quick-links-section {
          flex-direction: column;
        }

        .quick-links-section .back-btn {
          min-width: auto;
        }
      }
    </style>
  </head>
  <body>
    <nav class="navbar">
      <div class="brand">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
          <path d="M12 2C9 5 6 6 4 7v9c0 1 0 3 2 4 2 1 6 1 10 0 3-1 3-4 3-5V7c-2-1-5-2-7-5z" fill="#fff" opacity="0.8"></path>
          <circle cx="12" cy="10" r="3" fill="#fff"></circle>
        </svg>
        <h1>FlowGuard</h1>
      </div>

      <div class="nav-links">
        <a href="farmer_dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="farmer_login.php">Logout</a>
      </div>
    </nav>

    <main>
      <div class="content-row">
        <!-- LEFT: Sensor Cards -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
          <?php foreach ($motors as $motor): ?>
          <section class="sensor-card" id="card_<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>" aria-labelledby="title_<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>">
            <div class="card-header">
              <div class="card-title" id="title_<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($motor['name'], ENT_QUOTES); ?> — Live Monitoring
              </div>

              <div class="card-actions" role="toolbar">
                <button class="back-btn" onclick="goBack()" title="Back to previous page" aria-label="Back">← Back</button>
                <div class="device-pill" title="Device"><?php echo htmlspecialchars($motor['topic_base'], ENT_QUOTES); ?></div>
              </div>
            </div>

            <div class="card-body">
              <div class="data-row">
                <div class="data-left">Temperature / Humidity</div>
                <div id="<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>_dht22" class="value">--</div>
              </div>

              <div class="data-row">
                <div class="data-left">Soil Moisture</div>
                <div id="<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>_soil" class="value">--</div>
              </div>

              <div class="data-row">
                <div class="data-left">Total Water Flow (Litres)</div>
                <div id="<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>_flow" class="value">--</div>
              </div>

              <div class="data-row">
                <div class="data-left">Motor Status</div>
                <div id="<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>_relay_status" class="status-off">--</div>
              </div>

              <div class="hint saveHintClass" id="saveHint_<?php echo htmlspecialchars($motor['id_safe'], ENT_QUOTES); ?>">Live readings are being saved every 30 seconds when new data arrives.</div>
            </div>
          </section>
          <?php endforeach; ?>
        </div>

        <!-- RIGHT: Article / Advisories -->
        <aside class="article-section" aria-labelledby="advisory-title">
          <div class="article-header">
            <div class="pill" aria-hidden="true">Tips & Best Practices</div>
            <div class="article-title" id="advisory-title">Advisories</div>
            <div class="article-meta">Concise guidance to reduce water waste & protect pumps</div>
          </div>

          <div class="article-content">
            <ul class="advice-list">
              <li><strong>Crop-wise irrigation:</strong> Use crop-specific schedules — excessive water damages root systems and wastes resources.</li>
              <li><strong>Optimal soil moisture:</strong> Keep moisture between field capacity and wilting point for best yields.</li>
              <li><strong>Leak prevention:</strong> Inspect joints/pipes regularly and check low continuous flow overnight.</li>
              <li><strong>Smart scheduling:</strong> Water early morning or late evening to reduce evaporation and improve absorption.</li>
              <li><strong>Water-saving systems:</strong> Use drip irrigation for row crops and localized sprinklers for orchards.</li>
              <li><strong>Motor care:</strong> Avoid frequent ON/OFF cycles; track motor-hour and schedule preventive maintenance.</li>
              <li><strong>Data-driven decisions:</strong> Combine soil sensors + weather forecasts to avoid unnecessary irrigation.</li>
            </ul>

            <div class="quick-checks">
              <strong>Quick Checks:</strong>
              <div class="quick-checks-list">
                <small>✓ Check valve tightness</small>
                <small>✓ Compare soil probes</small>
                <small>✓ Log motor starts</small>
              </div>
            </div>
          </div>
        </aside>
      </div>

      <!-- Set Water Usage Threshold form -->
      <section class="sensor-card threshold-section" aria-labelledby="water-threshold-title">
        <div class="card-header">
          <div class="card-title" id="water-threshold-title">Water Usage Threshold</div>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <input type="hidden" name="set_water_usage_threshold" value="1" />
            <div class="data-row">
              <div class="data-left">Threshold (liters)</div>
              <div class="value">
                <input
                  type="number"
                  name="water_usage_threshold_liters"
                  min="0"
                  step="0.01"
                  required
                  placeholder="0.00"
                  value="<?php echo isset($_SESSION['water_usage_threshold_liters']) ? htmlspecialchars((string)$_SESSION['water_usage_threshold_liters'], ENT_QUOTES) : ''; ?>"
                />
              </div>
            </div>
            <button type="submit">SET THRESHOLD</button>
          </form>
        </div>
      </section>

      <!-- Quick links to analytics & reports -->
      <section style="display: flex; gap: 16px; flex-wrap: wrap;" aria-label="Analytics and reports links" class="quick-links-section">
        <a href="analytics.php" class="back-btn">📊 Analytics</a>
        <a href="reports.php" class="back-btn">📄 Reports</a>
        <a href="chart.php" class="back-btn">📈 Charts</a>
      </section>
    </main>

    <footer>© 2026 FlowGuard — Smart Water Monitoring</footer>

    <!-- ========== MQTT + DB SAVE (THROTTLED) + CUMULATIVE FLOW WEBHOOK ========== -->
    <script>
      // CONFIG: webhook & threshold
      const WEBHOOK_URL = "http://localhost:5678/webhook/2bf143e1-3b6b-4192-b704-ecec647b5fa2";
      const THRESHOLD_LITERS = 10.0; // <- adjust this value as needed for demo
      const WEBHOOK_COOLDOWN_MS = 5 * 60 * 1000; // 5 minutes cooldown after trigger

      // FARMER ID from PHP session (safe int)
      const FARMER_ID = <?php echo intval($_SESSION['farmer_id']); ?>;

      // Device/topic names populated from server side
      const MOTORS = <?php echo json_encode($motors); ?>;
      const MQTT_TOPIC_BASE = "<?php echo addslashes($mqttTopicBase); ?>"; // default or first
      const DEVICE_ID_SAFE = "<?php echo addslashes($deviceIdSafe); ?>"; // default or first

      // sensor state - now indexed by device_id_safe
      let sensorStates = {};
      MOTORS.forEach(m => {
          sensorStates[m.id_safe] = { 
              temp: null, hum: null, soil: null, flow: null, motor: null, total_volume: null,
              lastSavedAt: 0, pendingSave: false, cumulativeFlow: 0.0, prevSaveTime: Date.now()
          };
      });

      // throttle saves to DB (ms)
      const SAVE_INTERVAL_MS = 30 * 1000; // 30 seconds
      let lastWebhookAt = 0;

      // Utility to format time for debug/hint
      function nowIso() { return new Date().toISOString(); }

      // ================= MQTT Configuration (HiveMQ Cloud) - Loaded from .env =================
      const MQTT_BROKER = "<?php echo addslashes(getEnv('MQTT_BROKER')); ?>";
      const MQTT_USERNAME = "<?php echo addslashes(getEnv('MQTT_USERNAME')); ?>";
      const MQTT_PASSWORD = "<?php echo addslashes(getEnv('MQTT_PASSWORD')); ?>";
      
      console.log("🔐 MQTT Configuration:");
      console.log("  Broker: " + MQTT_BROKER);
      console.log("  Username: " + MQTT_USERNAME);
      console.log("  Password length: " + MQTT_PASSWORD.length);
      console.log("  Topic Base: " + MQTT_TOPIC_BASE);
      
      let mqttClient = null;

      // Connect to MQTT broker
      function connectMQTT() {
        console.log("🔄 Connecting to HiveMQ Cloud...");
        
        const options = {
          username: MQTT_USERNAME,
          password: MQTT_PASSWORD,
          clientId: "flowguard_" + Math.random().toString(16).substr(2, 8),
          clean: true,
          reconnectPeriod: 1000
        };

        mqttClient = mqtt.connect(MQTT_BROKER, options);

        mqttClient.on("connect", () => {
          console.log("✅ Connected to HiveMQ Cloud");
          
          // Subscribe to topics for ALL motors
          MOTORS.forEach(motor => {
              const base = motor.topic_base;
              mqttClient.subscribe(base + "/dht22");
              mqttClient.subscribe(base + "/soil");
              mqttClient.subscribe(base + "/flow");
              mqttClient.subscribe(base + "/flow/rate");
              mqttClient.subscribe(base + "/flow/total");
              mqttClient.subscribe(base + "/relay/status");
              console.log("✅ Subscribed to topics for: " + base);
          });
        });

        mqttClient.on("error", (err) => {
          console.error("❌ MQTT Error:", err);
          document.getElementById("mqttStatus").innerText = "❌ Error: " + err.message;
          document.getElementById("mqttStatus").style.color = "#ef4444";
        });

        mqttClient.on("disconnect", () => {
          console.log("⚠️ Disconnected from MQTT broker");
          document.getElementById("mqttStatus").innerText = "⚠️ Disconnected";
          document.getElementById("mqttStatus").style.color = "#f59e0b";
        });

        mqttClient.on("message", (topic, message) => {
          try {
            let rawMsg = message.toString();
            console.log("📩 Topic: " + topic + " | Message: " + rawMsg);

            // 1. Identify which motor this belongs to
            let targetMotor = MOTORS.find(m => topic.startsWith(m.topic_base));
            if (!targetMotor) return; // ignore unknown devices

            let devIdSafe = targetMotor.id_safe;
            let sensor = sensorStates[devIdSafe];

            // 2. Attempt to parse JSON
            let msg = rawMsg;
            let jsonData = null;
            try {
                if (rawMsg.trim().startsWith('{')) {
                    jsonData = JSON.parse(rawMsg);
                }
            } catch (e) {}

            const relayEl = document.getElementById(devIdSafe + "_relay_status");
            const hint = document.getElementById("saveHint_" + devIdSafe);

            const getValue = (key, fallback) => {
                if (jsonData && jsonData[key] !== undefined) return jsonData[key];
                return fallback;
            };

            // 3. Update state and UI
            if (topic.endsWith("/dht22")) {
              let val = getValue('dht22', msg);
              const parts = String(val).split(",");
              const temp = parts[0] ? parts[0].trim() : "--";
              const humidity = parts[1] ? parts[1].trim() : "--";
              document.getElementById(devIdSafe + "_dht22").innerText = temp + "°C, " + humidity + "%";
              sensor.temp = parseFloat(parts[0]) || null;
              sensor.hum = parseFloat(parts[1]) || null;
            }
            else if (topic.endsWith("/soil")) {
              let val = getValue('soil', msg);
              document.getElementById(devIdSafe + "_soil").innerText = val + " %";
              sensor.soil = parseFloat(val) || null;
            }
            else if (topic.endsWith("/flow") || topic.endsWith("/flow/total")) {
              let val = getValue('flow', getValue('total', msg));
              document.getElementById(devIdSafe + "_flow").innerText = val + " L";
              sensor.total_volume = parseFloat(val) || null;
            }
            else if (topic.endsWith("/flow/rate")) {
              let val = getValue('rate', msg);
              document.getElementById(devIdSafe + "_flow").innerText = val + " L/min";
              sensor.flow = parseFloat(val) || 0;
            }
            else if (topic.endsWith("/relay/status")) {
              let val = getValue('status', msg);
              relayEl.innerText = val;
              relayEl.className = val === "ON" ? "status-on" : "status-off";
              sensor.motor = val;
            }

            hint.innerText = `Last update: ${nowIso()} — saving when new data arrives.`;
            scheduleSave(devIdSafe);

          } catch (err) {
            console.error("❌ Failed to handle MQTT message:", err);
          }
        });
      }

      // Initialize MQTT connection on page load
      window.addEventListener("DOMContentLoaded", connectMQTT);
      // Also try reconnecting if page is already loaded
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", connectMQTT);
      } else {
        connectMQTT();
      }


      // Debounced/throttled save logic
      function scheduleSave(devIdSafe) {
        let sensor = sensorStates[devIdSafe];
        const now = Date.now();
        if (now - sensor.lastSavedAt >= SAVE_INTERVAL_MS) {
          doSave(devIdSafe);
        } else {
          if (!sensor.pendingSave) {
            sensor.pendingSave = true;
            setTimeout(() => {
              sensor.pendingSave = false;
              doSave(devIdSafe);
            }, SAVE_INTERVAL_MS - (now - sensor.lastSavedAt));
          }
        }
      }

      function doSave(devIdSafe) {
        let sensor = sensorStates[devIdSafe];
        let targetMotor = MOTORS.find(m => m.id_safe === devIdSafe);
        const now = Date.now();

        const elapsedMs = sensor.prevSaveTime ? Math.max(1, now - sensor.prevSaveTime) : SAVE_INTERVAL_MS;
        sensor.prevSaveTime = now;
        sensor.lastSavedAt = now;

        const form = new FormData();
        form.append("save_sensor", "1");
        form.append("motor_id", targetMotor.id);
        form.append("device_id", targetMotor.topic_base);
        form.append("temperature", sensor.temp);
        form.append("humidity", sensor.hum);
        form.append("soil", sensor.soil);
        form.append("flow", sensor.flow);
        form.append("motor", sensor.motor ?? "OFF");

        const hint = document.getElementById("saveHint_" + devIdSafe);
        hint.innerText = "Saving latest reading...";
        hint.style.opacity = "0.9";

        fetch("", { method: "POST", body: form })
          .then(r => r.json())
          .then(j => {
            if (j && j.status === "saved") {
              const flowRate = (typeof sensor.flow === "number" && !isNaN(sensor.flow)) ? sensor.flow : 0;
              const minutes = elapsedMs / 60000;
              const litersAdded = flowRate * minutes;
              sensor.cumulativeFlow += litersAdded;

              hint.innerText = `Saved — cumulative: ${sensor.cumulativeFlow.toFixed(2)} L`;
              hint.style.opacity = "1";

              const nowCheck = Date.now();
              if (sensor.cumulativeFlow >= THRESHOLD_LITERS && (nowCheck - lastWebhookAt) > WEBHOOK_COOLDOWN_MS) {
                sendWebhook({
                  farmer_id: FARMER_ID,
                  device_id: targetMotor.topic_base,
                  cumulative_flow: Number(sensor.cumulativeFlow.toFixed(3)),
                  threshold: THRESHOLD_LITERS,
                  detected_at: new Date().toISOString()
                }).then(success => {
                  if (success) {
                    lastWebhookAt = Date.now();
                    sensor.cumulativeFlow = 0.0;
                    hint.innerText = "Alert sent — cumulative reset.";
                  } else {
                    hint.innerText = "Alert failed — retrying later.";
                  }
                });
              } else {
                setTimeout(() => {
                  hint.innerText = "Live readings are being saved every 30 seconds.";
                }, 3000);
              }

              if (typeof sensor.total_volume === "number" && !isNaN(sensor.total_volume)) {
                checkWaterUsageThreshold(sensor.total_volume);
              }
            } else {
              hint.innerText = "Save failed.";
              hint.style.opacity = "1";
            }
          })
          .catch(err => {
            hint.innerText = "Save failed (network).";
            hint.style.opacity = "1";
          });
      }

      // send webhook payload; returns Promise<boolean>
      function sendWebhook(payload) {
        // keep UI responsive
        const hint = document.getElementById("saveHint");
        hint.innerText = "Sending alert...";
        return fetch(WEBHOOK_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        })
        .then(r => {
          if (!r.ok) throw new Error("Webhook status " + r.status);
          return r.text();
        })
        .then(text => {
          console.log("Webhook success:", text);
          return true;
        })
        .catch(err => {
          console.error("Webhook error:", err);
          return false;
        });
      }

      // NEW CODE: compare total water usage with stored threshold and trigger one-time call
      function checkWaterUsageThreshold(totalLiters) {
        const form = new URLSearchParams();
        form.append("check_water_threshold", "1");
        form.append("total_water_liters", String(totalLiters));
        fetch("", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: form.toString()
        })
        .then(r => r.json())
        .then(j => {
          // Intentionally no UI changes; we only trigger server-side call when needed.
          if (!j || j.ok !== true) {
            console.warn("Water threshold check failed:", j);
          }
        })
        .catch(err => {
          console.warn("Water threshold check error:", err);
        });
      }

      // Cleanup MQTT connection on page unload
      window.addEventListener("beforeunload", () => {
        if (mqttClient) {
          console.log("🔌 Disconnecting MQTT...");
          mqttClient.end();
        }
      });

      // Back button behavior (green theme)
      function goBack() {
        if (window.history.length > 1) {
          window.history.back();
        } else {
          window.location.href = "farmer_dashboard.php";
        }
      }
    </script>
  </body>
  </html>
