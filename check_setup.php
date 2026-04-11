<?php
/**
 * FlowGuard Setup Verification Script
 * Run this to check if everything is configured correctly
 */

echo "<h1>FlowGuard Setup Verification</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

$errors = [];
$warnings = [];

// Check PHP version
echo "<h2>1. PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    $errors[] = "PHP 7.4+ required. Current: " . phpversion();
    echo "<span class='error'>✗ PHP version too old</span><br>";
} else {
    echo "<span class='ok'>✓ PHP version OK</span><br>";
}

// Check MySQL extension
if (extension_loaded('mysqli')) {
    echo "<span class='ok'>✓ MySQLi extension loaded</span><br>";
} else {
    $errors[] = "MySQLi extension not loaded";
    echo "<span class='error'>✗ MySQLi extension missing</span><br>";
}

// Check database connection
echo "<h2>2. Database Connection</h2>";
require_once __DIR__ . "/client/config.php";
if (isset($conn) && $conn->connect_error) {
    $errors[] = "Database connection failed: " . $conn->connect_error;
    echo "<span class='error'>✗ Database connection failed</span><br>";
} else {
    echo "<span class='ok'>✓ Database connected</span><br>";
    
    // Check tables
    $tables = ['admins', 'farmers', 'motors', 'groundwater', 'sensor_readings'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<span class='ok'>✓ Table '$table' exists</span><br>";
        } else {
            $errors[] = "Table '$table' missing";
            echo "<span class='error'>✗ Table '$table' missing</span><br>";
        }
    }
}

// Check Composer dependencies
echo "<h2>3. PHP Dependencies</h2>";
$vendorPath = __DIR__ . "/client/config/vendor/autoload.php";
if (file_exists($vendorPath)) {
    echo "<span class='ok'>✓ Composer dependencies installed</span><br>";
} else {
    $warnings[] = "Composer dependencies not installed. Run: cd client && composer install";
    echo "<span class='warning'>⚠ Composer dependencies missing</span><br>";
}

// Check Firebase config
echo "<h2>4. Firebase Configuration</h2>";
$firebaseJson = __DIR__ . "/client/config/firebase-service-account.json";
if (file_exists($firebaseJson)) {
    echo "<span class='ok'>✓ Firebase service account file exists</span><br>";
} else {
    $warnings[] = "Firebase config missing (optional)";
    echo "<span class='warning'>⚠ Firebase config missing (optional)</span><br>";
}

// Check Python files
echo "<h2>5. Python Files</h2>";
$pythonFiles = [
    'server/server.py',
    'client/app.py'
];
foreach ($pythonFiles as $file) {
    $path = __DIR__ . "/" . $file;
    if (file_exists($path)) {
        echo "<span class='ok'>✓ $file exists</span><br>";
    } else {
        $errors[] = "File missing: $file";
        echo "<span class='error'>✗ $file missing</span><br>";
    }
}

// Check ML models
echo "<h2>6. ML Model Files</h2>";
$modelFiles = [
    'server/leak_model.pkl',
    'server/scaler.pkl',
    'client/model/leak_model.pkl',
    'client/model/scaler.pkl'
];
foreach ($modelFiles as $file) {
    $path = __DIR__ . "/" . $file;
    if (file_exists($path)) {
        echo "<span class='ok'>✓ $file exists</span><br>";
    } else {
        $warnings[] = "Model file missing: $file";
        echo "<span class='warning'>⚠ $file missing</span><br>";
    }
}

// Check DataSet folder
echo "<h2>7. Data Files</h2>";
$csvPath = __DIR__ . "/DataSet/groundwater.csv";
if (file_exists($csvPath)) {
    echo "<span class='ok'>✓ groundwater.csv exists</span><br>";
} else {
    $warnings[] = "DataSet/groundwater.csv missing";
    echo "<span class='warning'>⚠ groundwater.csv missing</span><br>";
}

// Summary
echo "<h2>Summary</h2>";
if (empty($errors) && empty($warnings)) {
    echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
    echo "<span class='ok'><strong>✓ All checks passed! System is ready to run.</strong></span>";
    echo "</div>";
} else {
    if (!empty($errors)) {
        echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin-bottom:10px;'>";
        echo "<strong class='error'>Errors found:</strong><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul></div>";
    }
    
    if (!empty($warnings)) {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;'>";
        echo "<strong class='warning'>Warnings:</strong><ul>";
        foreach ($warnings as $warning) {
            echo "<li>$warning</li>";
        }
        echo "</ul></div>";
    }
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Fix any errors above</li>";
echo "<li>Install Python dependencies: <code>pip install -r requirements.txt</code></li>";
echo "<li>Start Flask servers: <code>python server/server.py</code> and <code>python client/app.py</code></li>";
echo "<li>Access web interface: <a href='client/index.php'>http://localhost/15K/client/</a></li>";
echo "</ol>";
?>
