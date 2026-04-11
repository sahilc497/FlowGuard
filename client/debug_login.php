<?php
require_once "config.php";

echo "=== CHECKING FARMERS TABLE ===\n";
$stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'farmers' ORDER BY ordinal_position");
echo "Columns in farmers table:\n";
while($row = $stmt->fetch()) {
    echo "  - " . $row['column_name'] . " (" . $row['data_type'] . ")\n";
}

echo "\n=== FARMERS DATA ===\n";
$stmt = $conn->query("SELECT id, username FROM farmers LIMIT 5");
$farmers = $stmt->fetchAll();
if (count($farmers) > 0) {
    echo "Found " . count($farmers) . " farmer(s):\n";
    foreach($farmers as $f) {
        echo "  - ID: " . $f['id'] . ", Username: " . $f['username'] . "\n";
    }
} else {
    echo "No farmers found in database.\n";
}

echo "\n=== ADMINS DATA ===\n";
$stmt = $conn->query("SELECT id, username FROM admins");
$admins = $stmt->fetchAll();
if (count($admins) > 0) {
    echo "Found " . count($admins) . " admin(s):\n";
    foreach($admins as $a) {
        echo "  - ID: " . $a['id'] . ", Username: " . $a['username'] . "\n";
    }
} else {
    echo "No admins found in database.\n";
}

echo "\n=== TESTING ADMIN LOGIN ===\n";
$testUsername = "admin@gmail.com";
$testPassword = getenv("TEST_PASSWORD") ?: "admin1234"; // for testing only, use .env

$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
$stmt->execute([$testUsername]);
$admin = $stmt->fetch();

if ($admin) {
    echo "✅ Admin found: " . $admin['username'] . "\n";
    echo "Password hash in DB: " . substr($admin['password'], 0, 20) . "...\n";
    
    if (password_verify($testPassword, $admin['password'])) {
        echo "✅ Password verification: SUCCESS\n";
    } else {
        echo "❌ Password verification: FAILED\n";
        echo "The password 'admin1234' does not match the hash in database.\n";
    }
} else {
    echo "❌ Admin user 'admin@gmail.com' not found in database.\n";
}

echo "\n=== TESTING FARMER REGISTRATION ===\n";
$testUser = "test_farmer_" . time();
$testPass = password_hash("test123", PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO farmers (username, password, created_at) VALUES (?, ?, CURRENT_TIMESTAMP) RETURNING id");
    $stmt->execute([$testUser, $testPass]);
    $newId = $stmt->fetchColumn();
    echo "✅ Created test farmer with ID: $newId\n";
    
    // Clean up
    $stmt = $conn->prepare("DELETE FROM farmers WHERE id = ?");
    $stmt->execute([$newId]);
    echo "✅ Cleaned up test farmer\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
?>
