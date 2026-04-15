<?php
require_once 'config.php';

echo "<h2>Adding 'Ready' Status to Prescriptions Table</h2>";

// Add 'Ready' to the status enum
$alter_sql = "ALTER TABLE prescriptions 
              MODIFY COLUMN status ENUM('Pending','Processing','Ready','Dispensed','Cancelled') 
              DEFAULT 'Pending'";

if ($conn->query($alter_sql)) {
    echo "✓ Successfully added 'Ready' status to prescriptions table<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// Verify the change
$check = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'status'");
if ($check) {
    $row = $check->fetch_assoc();
    echo "<br>Current status column definition:<br>";
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<br><h3>Testing Status Values</h3>";
$test_statuses = ['Pending', 'Processing', 'Ready', 'Dispensed', 'Cancelled'];
foreach ($test_statuses as $status) {
    $count = $conn->query("SELECT COUNT(*) as cnt FROM prescriptions WHERE status='$status'")->fetch_assoc()['cnt'];
    echo "- $status: $count prescriptions<br>";
}

echo "<br><a href='index.php'>Back to Home</a>";
?>
