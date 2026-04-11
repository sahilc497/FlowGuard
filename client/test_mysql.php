<?php
// Simple MySQL test
$conn = new mysqli("localhost", "root", "", "flowguard");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to MySQL!\n";
echo "Database: flowguard\n\n";

// Show tables
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "Tables:\n";
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
    }
} else {
    echo "No tables found or database doesn't exist.\n";
    echo "Run: c:\\xampp\\mysql\\bin\\mysql.exe -u root < client\\FlowGuard_mysql.sql\n";
}

$conn->close();
?>
