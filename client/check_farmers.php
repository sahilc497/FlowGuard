<?php
include 'config.php';

$res = $conn->query("SHOW COLUMNS FROM farmers");
if ($res) {
    echo "Columns in 'farmers' table:\n";
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
