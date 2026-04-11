<?php
header('Content-Type: application/json');
session_start();

$csvFile = __DIR__ . "/../DataSet/groundwater.csv";  // corrected path

if (!file_exists($csvFile)) {
    die(json_encode(["error" => "CSV file not found at $csvFile"]));
}

// OPTIMIZATION: Cache CSV data in session to avoid reading file on every request
$cacheKey = 'csv_data_cache';
$cacheTime = 300; // Cache for 5 minutes

if (!isset($_SESSION[$cacheKey]) || 
    !isset($_SESSION[$cacheKey . '_time']) || 
    (time() - $_SESSION[$cacheKey . '_time']) > $cacheTime) {
    
    // Read and cache CSV data
    $dataRows = array_map('str_getcsv', file($csvFile));
    if (!$dataRows) {
        die(json_encode(["error" => "CSV file is empty or could not be read."]));
    }
    
    $headers = array_shift($dataRows);
    $_SESSION[$cacheKey] = [
        'headers' => $headers,
        'rows' => $dataRows
    ];
    $_SESSION[$cacheKey . '_time'] = time();
} else {
    // Use cached data
    $headers = $_SESSION[$cacheKey]['headers'];
    $dataRows = $_SESSION[$cacheKey]['rows'];
}

// OPTIMIZATION: Use random index instead of array_rand (more efficient)
$randomIndex = mt_rand(0, count($dataRows) - 1);
$row = $dataRows[$randomIndex];
$rowData = array_combine($headers, $row);

$usage = floatval($rowData['usage_liters'] ?? 0);
$threshold = 200;
$anomaly_rule = $usage > $threshold ? 1 : 0;
$anomaly_ml = rand(0, 1);
$leak_flag = ($anomaly_ml && $anomaly_rule) ? 1 : 0;

if (!isset($_SESSION['groundwater'])) $_SESSION['groundwater'] = 10000;
$_SESSION['groundwater'] = max($_SESSION['groundwater'] - $usage, 0);

$result = [
    "timestamp" => date("Y-m-d H:i:s"),
    "usage_liters" => $usage,
    "anomaly_ml" => $anomaly_ml,
    "anomaly_rule" => $anomaly_rule,
    "leak_flag" => $leak_flag,
    "groundwater_level" => $_SESSION['groundwater']
];

echo json_encode($result);
?>
