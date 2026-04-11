<?php
// PostgreSQL Connection Test for FlowGuard Database
$host = "localhost";
$port = "5432";
$user = "postgres";
$pass = "postgres";
$db   = "flowguard";

echo "<h2>PostgreSQL Connection Test for FlowGuard</h2>";
echo "<hr>";

// Test 1: Connection to PostgreSQL server
echo "<h3>Test 1: Connecting to PostgreSQL Server</h3>";
try {
    $dsn = "pgsql:host=$host;port=$port";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green;'>✅ Successfully connected to PostgreSQL server at $host:$port</p>";
    $pdo = null;
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Failed to connect to PostgreSQL server: " . $e->getMessage() . "</p>";
    die();
}

// Test 2: Connection to flowguard database
echo "<h3>Test 2: Connecting to 'flowguard' Database</h3>";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<p style='color:green;'>✅ Successfully connected to database '$db'</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Failed to connect to database '$db': " . $e->getMessage() . "</p>";
    echo "<p style='color:orange;'>💡 The database might not exist. Please create it first.</p>";
    die();
}

// Test 3: List all tables
echo "<h3>Test 3: Tables in 'flowguard' Database</h3>";
try {
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p style='color:green;'>✅ Found " . count($tables) . " table(s):</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><strong>$table</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>⚠️ No tables found in the database. You may need to run the SQL setup script.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error listing tables: " . $e->getMessage() . "</p>";
}

// Test 4: Check PostgreSQL version
echo "<h3>Test 4: PostgreSQL Version</h3>";
try {
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "<p style='color:green;'>✅ $version</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error getting version: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>All tests completed!</strong></p>";
?>
