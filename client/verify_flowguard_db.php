<?php
require_once "config.php";

echo "<!DOCTYPE html>
<html>
<head>
    <title>FlowGuard Database Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
        }
        h3 {
            color: #555;
            margin-top: 20px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-info {
            background: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 FlowGuard Database Verification Report</h1>";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>";

try {
    if (!isset($pdo) && !isset($conn)) {
        throw new Exception("Database connection not found in config.php");
    }
    
    $connection = isset($pdo) ? $pdo : $conn;
    
    // Connection Info
    echo "<div class='info-box'>";
    echo "<h2>✅ Database Connection Status</h2>";
    echo "<p class='success'>Successfully connected to PostgreSQL database 'flowguard'</p>";
    
    $dbStmt = $connection->query("SELECT current_database(), current_user, version()");
    $dbInfo = $dbStmt->fetch();
    
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td><strong>Database Name</strong></td><td>" . $dbInfo['current_database'] . "</td></tr>";
    echo "<tr><td><strong>Connected User</strong></td><td>" . $dbInfo['current_user'] . "</td></tr>";
    echo "<tr><td><strong>PostgreSQL Version</strong></td><td>" . $dbInfo['version'] . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Get all tables
    $stmt = $connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>📊 Database Tables Overview</h2>";
    echo "<p>Found <strong>" . count($tables) . "</strong> tables in the 'flowguard' database:</p>";
    
    if (count($tables) > 0) {
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Row Count</th><th>Columns</th><th>Status</th></tr>";
        
        foreach ($tables as $table) {
            // Get row count
            $countStmt = $connection->query("SELECT COUNT(*) FROM \"$table\"");
            $count = $countStmt->fetchColumn();
            
            // Get column count
            $colStmt = $connection->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = '$table' AND table_schema = 'public'");
            $colCount = $colStmt->fetchColumn();
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$count row(s)</td>";
            echo "<td>$colCount column(s)</td>";
            echo "<td><span class='badge badge-success'>Active</span></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Detailed table structure
        echo "<h2>🔧 Detailed Table Structures</h2>";
        
        foreach ($tables as $table) {
            echo "<h3>Table: <code>$table</code></h3>";
            
            // Get columns
            $colStmt = $connection->query("
                SELECT 
                    column_name, 
                    data_type, 
                    character_maximum_length,
                    is_nullable,
                    column_default
                FROM information_schema.columns 
                WHERE table_name = '$table' AND table_schema = 'public'
                ORDER BY ordinal_position
            ");
            $columns = $colStmt->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Column Name</th><th>Data Type</th><th>Nullable</th><th>Default</th></tr>";
            
            foreach ($columns as $col) {
                $dataType = $col['data_type'];
                if ($col['character_maximum_length']) {
                    $dataType .= "(" . $col['character_maximum_length'] . ")";
                }
                
                echo "<tr>";
                echo "<td><strong>" . $col['column_name'] . "</strong></td>";
                echo "<td>" . $dataType . "</td>";
                echo "<td>" . ($col['is_nullable'] == 'YES' ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($col['column_default'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show sample data if available
            $countStmt = $connection->query("SELECT COUNT(*) FROM \"$table\"");
            $rowCount = $countStmt->fetchColumn();
            
            if ($rowCount > 0) {
                echo "<p><span class='badge badge-info'>Sample Data (First 3 rows)</span></p>";
                $sampleStmt = $connection->query("SELECT * FROM \"$table\" LIMIT 3");
                $samples = $sampleStmt->fetchAll();
                
                if (count($samples) > 0) {
                    echo "<table>";
                    // Header
                    echo "<tr>";
                    foreach (array_keys($samples[0]) as $header) {
                        echo "<th>$header</th>";
                    }
                    echo "</tr>";
                    
                    // Data
                    foreach ($samples as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p class='warning'>⚠️ No data in this table yet.</p>";
            }
        }
        
    } else {
        echo "<p class='warning'>⚠️ No tables found. Please run FlowGuard_pg.sql to create the database schema.</p>";
    }
    
    // Summary
    echo "<div class='info-box'>";
    echo "<h2>📝 Summary</h2>";
    echo "<ul>";
    echo "<li>✅ PostgreSQL connection: <strong>Working</strong></li>";
    echo "<li>✅ Database 'flowguard': <strong>Exists</strong></li>";
    echo "<li>✅ Total tables: <strong>" . count($tables) . "</strong></li>";
    
    $totalRows = 0;
    foreach ($tables as $table) {
        $countStmt = $connection->query("SELECT COUNT(*) FROM \"$table\"");
        $totalRows += $countStmt->fetchColumn();
    }
    echo "<li>✅ Total records: <strong>$totalRows</strong></li>";
    echo "</ul>";
    echo "<p class='success'><strong>Your FlowGuard PostgreSQL database is properly configured and connected!</strong></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='info-box'>";
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='info-box'>";
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
