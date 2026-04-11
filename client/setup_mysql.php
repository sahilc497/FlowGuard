<?php
require_once "config.php";

echo "Testing MySQL Connection...\n\n";

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

echo "✅ Connected to MySQL successfully!\n\n";

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS flowguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "✅ Database 'flowguard' created/verified\n\n";

// Select database
$conn->select_db("flowguard");

// Read and execute SQL file
$sqlFile = file_get_contents(__DIR__ . "/FlowGuard_mysql.sql");
$queries = explode(";", $sqlFile);

$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query) || strpos($query, '--') === 0) continue;
    
    if ($conn->query($query)) {
        $successCount++;
    } else {
        // Ignore "table already exists" errors
        if (strpos($conn->error, "already exists") === false && !empty($conn->error)) {
            echo "⚠️ Error: " . $conn->error . "\n";
            $errorCount++;
        }
    }
}

echo "\n=== Database Setup Complete ===\n";
echo "Successful queries: $successCount\n";
echo "Errors (excluding 'already exists'): $errorCount\n\n";

// Verify tables
$result = $conn->query("SHOW TABLES");
echo "=== Tables in flowguard database ===\n";
while ($row = $result->fetch_array()) {
    echo "  - " . $row[0] . "\n";
}

echo "\n=== Checking admin user ===\n";
$result = $conn->query("SELECT username FROM admins");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ✅ Admin: " . $row['username'] . "\n";
    }
} else {
    echo "  ⚠️ No admin users found\n";
}

echo "\n✅ MySQL database setup complete!\n";
echo "\nYou can now:\n";
echo "  1. Login as admin: http://localhost/15K/client/admin_login.php\n";
echo "     Username: admin@gmail.com\n";
echo "     Password: admin1234\n";
echo "  2. Register farmers: http://localhost/15K/client/farmer_register.php\n";
?>
