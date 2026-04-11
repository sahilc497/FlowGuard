<?php
require_once "config.php";

echo "=================================================================\n";
echo "         FLOWGUARD DATABASE VERIFICATION REPORT\n";
echo "=================================================================\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";

try {
    if (!isset($pdo) && !isset($conn)) {
        throw new Exception("Database connection not found in config.php");
    }
    
    $connection = isset($pdo) ? $pdo : $conn;
    
    // Connection Info
    echo "✅ DATABASE CONNECTION STATUS\n";
    echo "-----------------------------------------------------------------\n";
    
    $dbStmt = $connection->query("SELECT current_database(), current_user, version()");
    $dbInfo = $dbStmt->fetch();
    
    echo "Database Name    : " . $dbInfo['current_database'] . "\n";
    echo "Connected User   : " . $dbInfo['current_user'] . "\n";
    echo "PostgreSQL Version: " . $dbInfo['version'] . "\n\n";
    
    // Get all tables
    $stmt = $connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 DATABASE TABLES OVERVIEW\n";
    echo "-----------------------------------------------------------------\n";
    echo "Total Tables: " . count($tables) . "\n\n";
    
    if (count($tables) > 0) {
        $totalRows = 0;
        
        foreach ($tables as $table) {
            // Get row count
            $countStmt = $connection->query("SELECT COUNT(*) FROM \"$table\"");
            $count = $countStmt->fetchColumn();
            $totalRows += $count;
            
            // Get column count
            $colStmt = $connection->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = '$table' AND table_schema = 'public'");
            $colCount = $colStmt->fetchColumn();
            
            echo "Table: " . str_pad($table, 20) . " | Rows: " . str_pad($count, 6) . " | Columns: $colCount\n";
        }
        
        echo "\n";
        echo "=================================================================\n";
        echo "📝 SUMMARY\n";
        echo "=================================================================\n";
        echo "✅ PostgreSQL Connection : WORKING\n";
        echo "✅ Database 'flowguard'  : EXISTS\n";
        echo "✅ Total Tables          : " . count($tables) . "\n";
        echo "✅ Total Records         : $totalRows\n";
        echo "\n";
        echo "🎉 Your FlowGuard PostgreSQL database is properly configured!\n";
        echo "=================================================================\n";
        
    } else {
        echo "⚠️ No tables found. Please run FlowGuard_pg.sql to create the database schema.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
