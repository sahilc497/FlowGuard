<?php
include "config.php";

$username = "admin";
$password = getenv('DEFAULT_ADMIN_PASSWORD') ?: "12345"; // default password (override in .env)

// Hash the password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into DB (MySQL mysqli)
$stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hashedPassword);

if ($stmt->execute()) {
    echo "✅ Admin user created successfully (username: admin, password: 12345)";
} else {
    echo "⚠️ Error: " . $stmt->error;
}
?>
