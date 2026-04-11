<?php
require_once "config.php";

echo "<h2>PostgreSQL Database Connection Test (via config.php)</h2>";

// Test PostgreSQL connection using PDO from config.php
try {
    if (isset($pdo) || isset($conn)) {
        $connection = isset($pdo) ? $pdo : $conn;
        
        echo "<p style='color:green;font-weight:bold'>
            ✅ PostgreSQL Database connected successfully via config.php
        </p>";
        
        // Test query - List all tables
        $stmt = $connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<h3>Database Tables in 'flowguard':</h3><ul>";
            foreach ($tables as $table) {
                echo "<li><strong>$table</strong></li>";
            }
            echo "</ul>";
            
            // Show row counts for each table
            echo "<h3>Table Row Counts:</h3><ul>";
            foreach ($tables as $table) {
                $countStmt = $connection->query("SELECT COUNT(*) FROM \"$table\"");
                $count = $countStmt->fetchColumn();
                echo "<li><strong>$table:</strong> $count row(s)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange;font-weight:bold'>
                ⚠️ No tables found. Please run FlowGuard_pg.sql to create the database schema.
            </p>";
        }
        
        // Show database info
        $dbStmt = $connection->query("SELECT current_database(), current_user");
        $dbInfo = $dbStmt->fetch();
        echo "<h3>Connection Info:</h3>";
        echo "<ul>";
        echo "<li><strong>Database:</strong> " . $dbInfo['current_database'] . "</li>";
        echo "<li><strong>User:</strong> " . $dbInfo['current_user'] . "</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color:red;font-weight:bold'>
            ❌ Database connection object not found in config.php
        </p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;font-weight:bold'>
        ❌ PostgreSQL Connection Failed: " . $e->getMessage() . "
    </p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This project uses PostgreSQL database 'flowguard'.</p>";
echo "<p>Make sure PostgreSQL is running and the database is created.</p>";
?>
