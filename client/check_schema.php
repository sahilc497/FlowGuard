<?php
require_once "config.php";

// Check if farmers table has the required columns
echo "Checking farmers table structure...\n\n";

$stmt = $conn->query("
    SELECT column_name, data_type, is_nullable, column_default 
    FROM information_schema.columns 
    WHERE table_name = 'farmers' 
    ORDER BY ordinal_position
");

$columns = $stmt->fetchAll();
$hasEmail = false;
$hasFullName = false;
$hasPhone = false;
$hasRole = false;
$hasStatus = false;

echo "Farmers table columns:\n";
foreach($columns as $col) {
    echo "  - " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
    
    if ($col['column_name'] === 'email') $hasEmail = true;
    if ($col['column_name'] === 'full_name') $hasFullName = true;
    if ($col['column_name'] === 'phone') $hasPhone = true;
    if ($col['column_name'] === 'role') $hasRole = true;
    if ($col['column_name'] === 'status') $hasStatus = true;
}

echo "\n";
echo "Has email column: " . ($hasEmail ? "YES" : "NO") . "\n";
echo "Has full_name column: " . ($hasFullName ? "YES" : "NO") . "\n";
echo "Has phone column: " . ($hasPhone ? "YES" : "NO") . "\n";
echo "Has role column: " . ($hasRole ? "YES" : "NO") . "\n";
echo "Has status column: " . ($hasStatus ? "YES" : "NO") . "\n";

if (!$hasEmail || !$hasFullName || !$hasPhone || !$hasRole || !$hasStatus) {
    echo "\n⚠️ WARNING: farmers table is missing columns required by farmer_register.php!\n";
    echo "\nThe registration form expects these columns:\n";
    echo "  - username\n";
    echo "  - password\n";
    echo "  - email (MISSING!)\n" ;
    echo "  - full_name (MISSING!)\n";
    echo "  - phone (MISSING!)\n";
    echo "  - role (MISSING!)\n";
    echo "  - status (MISSING!)\n";
    echo "  - created_at\n";
    echo "\nYou need to update the FlowGuard_pg.sql schema!\n";
}
?>
