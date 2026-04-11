<?php
include 'client/config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if not exists with correct location column as specified
$sql = "CREATE TABLE IF NOT EXISTS rainfall (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL DEFAULT 'Farm A', 
    date DATE NOT NULL,
    amount_mm FLOAT NOT NULL,
    UNIQUE KEY unique_date_loc (date, location)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'rainfall' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Seed data for the last 30 days
echo "Seeding data...<br>";

$locations = ['Farm A'];
$today = new DateTime();

// Prepare insert statement
$stmt = $conn->prepare("INSERT IGNORE INTO rainfall (location, date, amount_mm) VALUES (?, ?, ?)");

if ($stmt) {
    for ($i = 0; $i < 30; $i++) {
        $date = clone $today;
        $date->modify("-$i days");
        $dateStr = $date->format('Y-m-d');
        
        foreach ($locations as $loc) {
            // Random rainfall between 0 and 50mm, with some dry days
            $amount = (rand(0, 10) > 7) ? rand(5, 50) : 0; 
            
            $stmt->bind_param("ssd", $loc, $dateStr, $amount);
            $stmt->execute();
        }
    }
    echo "Data seeded successfully.<br>";
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();
?>
