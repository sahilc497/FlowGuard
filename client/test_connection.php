<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowGuard PostgreSQL Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .test-section {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .test-section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .success {
            color: #28a745;
            font-weight: bold;
            padding: 10px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .error {
            color: #dc3545;
            font-weight: bold;
            padding: 10px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .warning {
            color: #ffc107;
            font-weight: bold;
            padding: 10px;
            background: rgba(255, 193, 7, 0.1);
            border-radius: 5px;
            margin: 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .info-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .info-card p {
            font-size: 20px;
            color: #333;
            font-weight: bold;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        
        .timestamp {
            text-align: right;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 FlowGuard PostgreSQL Connection Test</h1>
        <p class="subtitle">Comprehensive database connectivity and structure verification</p>
        
        <?php
        // Include config
        require_once "config.php";
        
        $allTestsPassed = true;
        
        // TEST 1: Check if connection exists
        echo '<div class="test-section">';
        echo '<h2>Test 1: Database Connection Object</h2>';
        
        if (isset($conn) || isset($pdo)) {
            $connection = isset($pdo) ? $pdo : $conn;
            echo '<div class="success">✅ Connection object found: ' . get_class($connection) . '</div>';
        } else {
            echo '<div class="error">❌ No connection object found in config.php</div>';
            $allTestsPassed = false;
        }
        echo '</div>';
        
        if (isset($connection)) {
            // TEST 2: Test connection
            echo '<div class="test-section">';
            echo '<h2>Test 2: PostgreSQL Server Connection</h2>';
            
            try {
                $stmt = $connection->query("SELECT version()");
                $version = $stmt->fetchColumn();
                echo '<div class="success">✅ Connected to PostgreSQL successfully</div>';
                echo '<p><strong>Version:</strong> ' . htmlspecialchars($version) . '</p>';
            } catch (PDOException $e) {
                echo '<div class="error">❌ Connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $allTestsPassed = false;
            }
            echo '</div>';
            
            // TEST 3: Database info
            echo '<div class="test-section">';
            echo '<h2>Test 3: Database Information</h2>';
            
            try {
                $stmt = $connection->query("SELECT current_database(), current_user, current_schema()");
                $info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo '<div class="info-grid">';
                echo '<div class="info-card"><h3>Database Name</h3><p>' . htmlspecialchars($info['current_database']) . '</p></div>';
                echo '<div class="info-card"><h3>Connected User</h3><p>' . htmlspecialchars($info['current_user']) . '</p></div>';
                echo '<div class="info-card"><h3>Schema</h3><p>' . htmlspecialchars($info['current_schema']) . '</p></div>';
                echo '</div>';
                
                echo '<div class="success">✅ Database information retrieved successfully</div>';
            } catch (PDOException $e) {
                echo '<div class="error">❌ Failed to get database info: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $allTestsPassed = false;
            }
            echo '</div>';
            
            // TEST 4: Tables
            echo '<div class="test-section">';
            echo '<h2>Test 4: Database Tables</h2>';
            
            try {
                $stmt = $connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) > 0) {
                    echo '<div class="success">✅ Found ' . count($tables) . ' table(s)</div>';
                    
                    echo '<table>';
                    echo '<tr><th>Table Name</th><th>Row Count</th><th>Columns</th><th>Status</th></tr>';
                    
                    $totalRows = 0;
                    foreach ($tables as $table) {
                        $countStmt = $connection->query("SELECT COUNT(*) FROM \"$table\"");
                        $count = $countStmt->fetchColumn();
                        $totalRows += $count;
                        
                        $colStmt = $connection->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = '$table' AND table_schema = 'public'");
                        $colCount = $colStmt->fetchColumn();
                        
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($table) . '</strong></td>';
                        echo '<td>' . number_format($count) . '</td>';
                        echo '<td>' . $colCount . '</td>';
                        echo '<td><span class="badge badge-success">Active</span></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    echo '<div class="info-grid">';
                    echo '<div class="info-card"><h3>Total Tables</h3><p>' . count($tables) . '</p></div>';
                    echo '<div class="info-card"><h3>Total Records</h3><p>' . number_format($totalRows) . '</p></div>';
                    echo '</div>';
                } else {
                    echo '<div class="warning">⚠️ No tables found. Run FlowGuard_pg.sql to create schema.</div>';
                    $allTestsPassed = false;
                }
            } catch (PDOException $e) {
                echo '<div class="error">❌ Failed to list tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $allTestsPassed = false;
            }
            echo '</div>';
            
            // TEST 5: Test CRUD operations
            echo '<div class="test-section">';
            echo '<h2>Test 5: CRUD Operations Test</h2>';
            
            try {
                // Test INSERT
                $testUsername = 'test_user_' . time();
                $testPassword = password_hash('test123', PASSWORD_DEFAULT);
                
                $stmt = $connection->prepare("INSERT INTO farmers (username, password, created_at) VALUES (?, ?, CURRENT_TIMESTAMP) RETURNING id");
                $stmt->execute([$testUsername, $testPassword]);
                $testId = $stmt->fetchColumn();
                
                echo '<div class="success">✅ INSERT test passed (ID: ' . $testId . ')</div>';
                
                // Test SELECT
                $stmt = $connection->prepare("SELECT * FROM farmers WHERE id = ?");
                $stmt->execute([$testId]);
                $row = $stmt->fetch();
                
                if ($row && $row['username'] === $testUsername) {
                    echo '<div class="success">✅ SELECT test passed</div>';
                } else {
                    echo '<div class="error">❌ SELECT test failed</div>';
                    $allTestsPassed = false;
                }
                
                // Test UPDATE
                $newUsername = 'updated_' . $testUsername;
                $stmt = $connection->prepare("UPDATE farmers SET username = ? WHERE id = ?");
                $stmt->execute([$newUsername, $testId]);
                
                $stmt = $connection->prepare("SELECT username FROM farmers WHERE id = ?");
                $stmt->execute([$testId]);
                $updatedUsername = $stmt->fetchColumn();
                
                if ($updatedUsername === $newUsername) {
                    echo '<div class="success">✅ UPDATE test passed</div>';
                } else {
                    echo '<div class="error">❌ UPDATE test failed</div>';
                    $allTestsPassed = false;
                }
                
                // Test DELETE
                $stmt = $connection->prepare("DELETE FROM farmers WHERE id = ?");
                $stmt->execute([$testId]);
                
                $stmt = $connection->prepare("SELECT COUNT(*) FROM farmers WHERE id = ?");
                $stmt->execute([$testId]);
                $count = $stmt->fetchColumn();
                
                if ($count == 0) {
                    echo '<div class="success">✅ DELETE test passed</div>';
                } else {
                    echo '<div class="error">❌ DELETE test failed</div>';
                    $allTestsPassed = false;
                }
                
                echo '<p style="margin-top: 15px; color: #666; font-size: 14px;">All CRUD operations (Create, Read, Update, Delete) are working correctly.</p>';
                
            } catch (PDOException $e) {
                echo '<div class="error">❌ CRUD test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $allTestsPassed = false;
            }
            echo '</div>';
            
            // TEST 6: Check critical tables
            echo '<div class="test-section">';
            echo '<h2>Test 6: Critical Tables Check</h2>';
            
            $requiredTables = ['admins', 'farmers', 'motors', 'alerts', 'motor_history', 'groundwater'];
            $missingTables = [];
            
            try {
                $stmt = $connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
                $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($requiredTables as $table) {
                    if (in_array($table, $existingTables)) {
                        echo '<div class="success">✅ Table <code>' . $table . '</code> exists</div>';
                    } else {
                        echo '<div class="error">❌ Table <code>' . $table . '</code> is missing</div>';
                        $missingTables[] = $table;
                        $allTestsPassed = false;
                    }
                }
                
                if (empty($missingTables)) {
                    echo '<p style="margin-top: 15px; color: #28a745; font-weight: bold;">All critical tables are present!</p>';
                } else {
                    echo '<div class="warning">⚠️ Missing tables: ' . implode(', ', $missingTables) . '</div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="error">❌ Failed to check tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $allTestsPassed = false;
            }
            echo '</div>';
            
            // FINAL SUMMARY
            echo '<div class="test-section" style="border-left-color: ' . ($allTestsPassed ? '#28a745' : '#dc3545') . ';">';
            echo '<h2>📊 Test Summary</h2>';
            
            if ($allTestsPassed) {
                echo '<div class="success" style="font-size: 18px; padding: 20px;">';
                echo '🎉 <strong>ALL TESTS PASSED!</strong><br>';
                echo 'Your PostgreSQL database "flowguard" is fully functional and ready to use.';
                echo '</div>';
            } else {
                echo '<div class="error" style="font-size: 18px; padding: 20px;">';
                echo '⚠️ <strong>SOME TESTS FAILED</strong><br>';
                echo 'Please review the errors above and fix the issues.';
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
        
        <div class="timestamp">
            Test completed at: <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>
</body>
</html>
