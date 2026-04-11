<?php
/**
 * ESP32 Smart Farm Trial Dashboard
 * Uses environment variables for MQTT credentials
 */
require_once __DIR__ . '/env-loader.php';

// Get MQTT config from environment
$mqtt_broker = getEnv('MQTT_BROKER');
$mqtt_username = getEnv('MQTT_USERNAME');
$mqtt_password = getEnv('MQTT_PASSWORD');
$mqtt_topic_base = getEnv('MQTT_TOPIC_BASE', 'esp32_1');
?>
<!DOCTYPE html>
<html>
<head>
  <title>ESP32 Smart Farm Dashboard</title>
  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
  <style>
    body {
      font-family: Arial;
      background: #0f172a;
      color: white;
      text-align: center;
    }
    h1 {
      margin-top: 20px;
    }
    .card {
      display: inline-block;
      background: #1e293b;
      padding: 20px;
      margin: 15px;
      border-radius: 15px;
      width: 250px;
      box-shadow: 0 0 10px #000;
    }
    button {
      padding: 10px 20px;
      margin: 10px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 16px;
    }
    .on { background: green; color: white; }
    .off { background: red; color: white; }
    .auto { background: orange; color: white; }
    
    .status {
      font-size: 12px;
      padding: 10px;
      margin-top: 20px;
      background: #0f172a;
      border-radius: 5px;
    }
    .connected {
      color: #4ade80;
    }
    .disconnected {
      color: #ef4444;
    }
  </style>
</head>

<body>

<h1>🌱 ESP32 Smart Farm Dashboard</h1>

<div class="card">
  <h2>🌡 Temperature & Humidity</h2>
  <p id="dht">Waiting...</p>
</div>

<div class="card">
  <h2>🌿 Soil Moisture</h2>
  <p id="soil">Waiting...</p>
</div>

<div class="card">
  <h2>💧 Water Flow</h2>
  <p id="flow">Waiting...</p>
</div>

<div class="card">
  <h2>⚡ Motor Control</h2>
  <p id="relayStatus">OFF</p>
  <button class="on" onclick="sendCommand('ON')">ON</button>
  <button class="off" onclick="sendCommand('OFF')">OFF</button>
  <button class="auto" onclick="sendCommand('AUTO')">AUTO</button>
</div>

<div class="status">
  <span id="connectionStatus" class="disconnected">● Disconnected</span><br>
  <small id="debugInfo">Loading...</small>
</div>

<script>
  // ===== MQTT CONFIG ===== (Loaded from .env via PHP)
  const broker = "<?php echo htmlspecialchars($mqtt_broker, ENT_QUOTES); ?>";
  const topicBase = "<?php echo htmlspecialchars($mqtt_topic_base, ENT_QUOTES); ?>";

  const options = {
    username: "<?php echo htmlspecialchars($mqtt_username, ENT_QUOTES); ?>",
    password: "<?php echo htmlspecialchars($mqtt_password, ENT_QUOTES); ?>",
    reconnectPeriod: 1000,
    clientId: "dashboard_" + Math.random().toString(16).substr(2, 8)
  };

  const client = mqtt.connect(broker, options);

  client.on("connect", () => {
    console.log("✅ Connected to MQTT");
    document.getElementById("connectionStatus").textContent = "● Connected";
    document.getElementById("connectionStatus").className = "connected";

    // Subscribe to sensor topics
    client.subscribe(topicBase + "/dht22");
    client.subscribe(topicBase + "/soil");
    client.subscribe(topicBase + "/flow");
    client.subscribe(topicBase + "/relay/status");
    
    console.log("✅ Subscribed to topics");
  });

  client.on("disconnect", () => {
    console.log("⚠️ Disconnected from MQTT");
    document.getElementById("connectionStatus").textContent = "● Disconnected";
    document.getElementById("connectionStatus").className = "disconnected";
  });

  client.on("error", (err) => {
    console.error("❌ MQTT Error:", err);
    document.getElementById("debugInfo").innerText = "Error: " + err.message;
  });

  client.on("message", (topic, message) => {
    let msg = message.toString();
    console.log(topic + ": " + msg);

    if (topic === topicBase + "/dht22") {
      document.getElementById("dht").innerText = msg;
    }

    if (topic === topicBase + "/soil") {
      document.getElementById("soil").innerText = msg;
    }

    if (topic === topicBase + "/flow") {
      document.getElementById("flow").innerText = msg;
    }

    if (topic === topicBase + "/relay/status") {
      document.getElementById("relayStatus").innerText = msg;
    }

    document.getElementById("debugInfo").innerText = "Last update: " + new Date().toLocaleTimeString();
  });

  function sendCommand(cmd) {
    console.log("📤 Sending command: " + cmd);
    client.publish(topicBase + "/relay", cmd);
  }

  // Log initial info
  document.getElementById("debugInfo").innerText = "Connecting to broker...";
</script>

</body>
</html>
