<?php
require_once "config.php";

echo "Adding missing columns to farmers table...\n\n";

try {
    // Add email column
    $conn->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE");
    echo "✅ Added email column\n";
    
    // Add full_name column
    $conn->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS full_name VARCHAR(255)");
    echo "✅ Added full_name column\n";
    
    // Add phone column
    $conn->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS phone VARCHAR(20)");
    echo "✅ Added phone column\n";
    
    // Add role column
    $conn->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'farmer'");
    echo "✅ Added role column\n";
    
    // Add status column
    $conn->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active'");
    echo "✅ Added status column\n";
    
    echo "\n=== Verifying farmers table structure ===\n";
    $stmt = $conn->query("
        SELECT column_name, data_type, column_default 
        FROM information_schema.columns 
        WHERE table_name = 'farmers' 
        ORDER BY ordinal_position
    ");
    
    while($row = $stmt->fetch()) {
        $default = $row['column_default'] ? " (default: " . $row['column_default'] . ")" : "";
        echo "  - " . $row['column_name'] . " (" . $row['data_type'] . ")" . $default . "\n";
    }
    
    echo "\n✅ Farmers table updated successfully!\n";
    echo "\nYou can now:\n";
    echo "  1. Register new farmers at: http://localhost/15K/client/farmer_register.php\n";
    echo "  2. Login as admin at: http://localhost/15K/client/admin_login.php\n";
    echo "     Username: admin@gmail.com\n";
    echo "     Password: admin1234\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
