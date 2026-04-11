<?php
// =====================================================
// 🔹 MYSQL DATABASE (FlowGuard) - XAMPP
// =====================================================
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP MySQL password is empty
$db   = "flowguard";

// Create MySQL connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// =====================================================
// 🔹 MQTT CONFIG (FROM ENVIRONMENT VARIABLES)
// =====================================================
$MQTT_SERVER   = getenv("MQTT_BROKER") ?: "f5bc2a85c9c747488d7ab174eaa1faaf.s1.eu.hivemq.cloud";
$MQTT_PORT     = getenv("MQTT_PORT") ?: 8883;
$MQTT_USERNAME = getenv("MQTT_USERNAME") ?: "";
$MQTT_PASSWORD = getenv("MQTT_PASSWORD") ?: "";
$MQTT_CAFILE   = __DIR__ . "/AmazonRootCA1.pem";

// Firebase removed - not needed for core functionality
?>
