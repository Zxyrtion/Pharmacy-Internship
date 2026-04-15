<?php
require_once 'config.php';

echo "Checking prescriptions table structure:\n\n";

$result = $conn->query('DESCRIBE prescriptions');

if ($result) {
    echo "Current columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error or table doesn't exist: " . $conn->error . "\n";
}

// Check if customer_id column exists
$check = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'customer_id'");
if ($check && $check->num_rows == 0) {
    echo "\n❌ customer_id column is MISSING!\n";
    echo "Adding customer_id column...\n";
    
    $alter = $conn->query("ALTER TABLE prescriptions ADD COLUMN customer_id INT AFTER id");
    if ($alter) {
        echo "✓ customer_id column added successfully!\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "\n✓ customer_id column exists!\n";
}
?>
