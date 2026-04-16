<?php
require_once 'config.php';

echo "Checking users table structure...\n\n";

$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "Users table columns:\n";
    echo str_repeat("-", 80) . "\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

echo "\n\nChecking for technician users:\n";
echo str_repeat("-", 80) . "\n";

// Try with role_id
$result = $conn->query("SELECT u.id, u.first_name, u.last_name, u.role_id, r.role_name 
                        FROM users u 
                        LEFT JOIN roles r ON u.role_id = r.id 
                        WHERE r.role_name = 'Pharmacy Technician'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['first_name']} {$row['last_name']} (Role ID: {$row['role_id']}, Role: {$row['role_name']})\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
