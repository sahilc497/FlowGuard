<?php
require("phpMQTT.php");   // 👈 Make sure phpMQTT.php is in the same folder

$server   = "broker.hivemq.com";   // HiveMQ public broker OR your HiveMQ Cloud hostname
$port     = 1883;                  // 8883 for TLS (HiveMQ Cloud), 1883 for public broker
$username = "";                    // set if using HiveMQ Cloud
$password = "";                    // set if using HiveMQ Cloud
$client_id = "phpSubscriber_" . rand(); // unique client ID

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

// Try connecting to MQTT broker
if (!$mqtt->connect(true, NULL, $username, $password)) {
    exit("❌ Failed to connect to MQTT broker.\n");
}

// OPTIMIZATION: Create database connection once and reuse it
// This avoids creating a new connection for every message
$db_conn = new mysqli("localhost", "root", "", "flowguard");
if ($db_conn->connect_error) {
    die("DB Connection failed: " . $db_conn->connect_error);
}

// OPTIMIZATION: Prepare statement once and reuse it (much faster)
$insert_stmt = $db_conn->prepare("INSERT INTO groundwater (device_id, used, remaining, timestamp) VALUES (?, ?, ?, NOW())");
if (!$insert_stmt) {
    die("Prepare failed: " . $db_conn->error);
}

// Subscribe to your topic (ESP32 must publish here)
$topics['flowguard/#'] = array("qos" => 0, "function" => "procMsg");
$mqtt->subscribe($topics, 0);

// Keep listening for messages
while ($mqtt->proc()) {}

// Cleanup
$insert_stmt->close();
$db_conn->close();
$mqtt->close();

/**
 * Callback when a message arrives
 * OPTIMIZATION: Uses global connection and prepared statement
 */
function procMsg($topic, $msg) {
    global $db_conn, $insert_stmt;
    
    echo "📩 Topic: $topic | Message: $msg\n";

    // Decode message (expecting JSON from ESP32)
    $data = json_decode($msg, true);
    if (!$data) return;  // skip invalid JSON

    // Example topic: flowguard/esp32/1
    $parts = explode("/", $topic); 
    $device_id = isset($parts[2]) ? "ESP32_" . $parts[2] : "ESP32_UNKNOWN";

    // Extract fields from ESP32 payload
    $used = isset($data['used']) ? (float)$data['used'] : 0;
    $remaining = isset($data['remaining']) ? (float)$data['remaining'] : 0;

    // OPTIMIZATION: Reuse prepared statement (no need to prepare each time)
    $insert_stmt->bind_param("sdd", $device_id, $used, $remaining);
    $insert_stmt->execute();
    $insert_stmt->reset(); // Reset for next use
}
?>
