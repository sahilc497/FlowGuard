<?php
include 'config.php';

$commands = [
    "ALTER TABLE farmers ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE",
    "ALTER TABLE farmers ADD COLUMN IF NOT EXISTS full_name VARCHAR(255)",
    "ALTER TABLE farmers ADD COLUMN IF NOT EXISTS phone VARCHAR(20)",
    "ALTER TABLE farmers ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'farmer'",
    "ALTER TABLE farmers ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active'"
];

echo "Applying schema updates to 'farmers' table...\n";

foreach ($commands as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Success: $sql\n";
    } else {
        echo "Error: " . $conn->error . " (Query: $sql)\n";
    }
}

echo "Schema updates completed.\n";

// Verify cols
$res = $conn->query("SHOW COLUMNS FROM farmers");
if ($res) {
    echo "Current columns:\n";
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
$conn->close();
?>
