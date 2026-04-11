<?php
session_start();
include "config.php";  // ✅ contains DB + HiveMQ credentials

// Load MQTT library
require("phpMQTT.php");
use Bluerhinos\phpMQTT;

// Use HiveMQ creds from config.php
$server    = $MQTT_SERVER;
$port      = $MQTT_PORT;      // usually 8883 for TLS
$username  = $MQTT_USERNAME;
$password  = $MQTT_PASSWORD;
$cafile    = $MQTT_CAFILE;    // path to AmazonRootCA1.pem
$client_id = "phpClient_" . uniqid();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['farmer_id'])) {
        die("Unauthorized access");
    }

    $motor_id  = intval($_POST['motor_id']);
    $status    = ($_POST['status'] === 'ON') ? 'ON' : 'OFF';
    $farmer_id = intval($_SESSION['farmer_id']);

    // ✅ Update motor status in DB
    $updateQuery = "UPDATE motors SET status = '$status' WHERE id = $motor_id AND farmer_id = $farmer_id";
    if ($conn->query($updateQuery) === TRUE) {
        // ✅ Log history for admin
        $conn->query("INSERT INTO motor_updates (motor_id, farmer_id, status, updated_at)
                      VALUES ($motor_id, $farmer_id, '$status', NOW())");

        // ✅ Publish MQTT command so ESP32 reacts
        $mqtt = new phpMQTT($server, $port, $client_id, $cafile);

        if ($mqtt->connect(true, NULL, $username, $password)) {
            $topic = "esp32/$motor_id/motor";   // ESP32 must subscribe here
            $mqtt->publish($topic, $status, 0);
            $mqtt->close();
            echo "✅ Motor updated & MQTT command sent!";
        } else {
            echo "⚠️ DB updated, but MQTT publish failed!";
        }

    } else {
        echo "❌ Error updating motor: " . $conn->error;
    }
}

$conn->close();
?>
x