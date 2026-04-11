<?php
// farmer_dashboard.php
session_start();

// 🔒 Optional login check
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>FlowGuard Dashboard</title>
  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
  <style>
    body {
      font-family: Arial;
      background: #f4f4f4;
      padding: 20px;
    }

    .node {
      background: white;
      padding: 15px;
      margin: 10px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    h2 {
      margin-top: 0;
    }

    .status-on {
      color: green;
      font-weight: bold;
    }

    .status-off {
      color: red;
      font-weight: bold;
    }

    .warning {
      color: red;
      font-size: 18px;
      font-weight: bold;
    }

    /* 🔹 Invisible button */
    #hiddenBtn {
      background: none;
      border: none;
      color: transparent;
      cursor: pointer;
      position: absolute;
      top: 0;
      left: 0;
      width: 120px;   /* clickable width */
      height: 50px;   /* clickable height */
      z-index: 10;
    }
  </style>
</head>

<body>
  <h1>FlowGuard Dashboard</h1>

  <div class="node">
    <h2>Motor_2</h2>
    <p>Total Flow(L): <span id="esp32_2_flow_total">--</span></p>
    <p>Soil Moisture: <span id="esp32_2_soil">--</span></p>
    <p>Flow Rate: <span id="esp32_2_flow_rate">--</span></p>
    <p>Relay: <span id="esp32_2_relay_status" class="status-off">--</span></p>
    <p id="warning_msg"></p>
  </div>

  <!-- 🔹 Invisible button -->
  <button id="hiddenBtn"></button>
  <p id="secret_msg"></p>

  <br>
  <a href="farmer_dashboard.php"><button>⬅ Back</button></a>

  <script>
    // Invisible button action
    document.getElementById("hiddenBtn").addEventListener("click", function () {
      document.getElementById("secret_msg").innerText = "⚠️ WARNIING! Threshold reached";
    });

    // ✅ Connect MQTT
    const client = mqtt.connect('wss://f5bc2a85c9c747488d7ab174eaa1faaf.s1.eu.hivemq.cloud:8884/mqtt', {
      username: "hivemq.webclient.1758715810298",
      password: "<?php echo htmlspecialchars(getenv('MQTT_PASSWORD') ?: '', ENT_QUOTES); ?>"
    });

    client.on('connect', () => {
      console.log('✅ Connected to MQTT broker');
      client.subscribe('esp32_2/#');
    });

    client.on('message', (topic, message) => {
      const msg = message.toString();
      console.log("📩", topic, msg);

      const elId = topic.replace(/\//g, '_');
      const el = document.getElementById(elId);

      if (el) {
        el.innerText = msg;

        // ✅ Relay status color
        if (elId.endsWith('_relay_status')) {
          if (msg.toLowerCase().includes("on")) {
            el.classList.add('status-on');
            el.classList.remove('status-off');
          } else {
            el.classList.add('status-off');
            el.classList.remove('status-on');
          }
        }

        // ✅ Flow usage check
        if (elId === "esp32_2_flow") {
          const totalUsage = parseFloat(msg);
          if (!isNaN(totalUsage)) {
            if (totalUsage > 10) {
              document.getElementById("warning_msg").innerText =
                "⚠️ WARNING: Total usage exceeded 10L!";
              document.getElementById("warning_msg").classList.add("warning");
              alert("⚠️ WARNING: Total usage exceeded 10L!");
            } else {
              document.getElementById("warning_msg").innerText = "";
              document.getElementById("warning_msg").classList.remove("warning");
            }
          }
        }
      }
    });
  </script>
</body>

</html>
