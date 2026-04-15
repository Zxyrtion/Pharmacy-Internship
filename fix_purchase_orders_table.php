<?php
require_once 'config.php';

echo "<h3>Fixing purchase_orders table</h3>";

// Check current structure
$result = $conn->query("DESCRIBE purchase_orders");
if ($result) {
    echo "<h4>Current columns:</h4><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['Field']} ({$row['Type']})</li>";
    }
    echo "</ul>";
}

// Add missing columns
$columns_to_add = [
    'prescription_id' => "ALTER TABLE purchase_orders ADD COLUMN prescription_id INT AFTER id",
    'pharmacist_id' => "ALTER TABLE purchase_orders ADD COLUMN pharmacist_id INT AFTER prescription_id",
    'order_date' => "ALTER TABLE purchase_orders ADD COLUMN order_date DATE AFTER pharmacist_id",
    'total_amount' => "ALTER TABLE purchase_orders ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0 AFTER order_date",
    'status' => "ALTER TABLE purchase_orders ADD COLUMN status ENUM('Pending','Dispensed','Paid','Cancelled') DEFAULT 'Pending' AFTER total_amount",
    'notes' => "ALTER TABLE purchase_orders ADD COLUMN notes TEXT NULL AFTER status",
    'created_at' => "ALTER TABLE purchase_orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER notes"
];

foreach ($columns_to_add as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM purchase_orders LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        echo "<p>Adding column: $col...</p>";
        if ($conn->query($sql)) {
            echo "<p>✅ $col added successfully!</p>";
        } else {
            echo "<p>❌ Error adding $col: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ $col already exists</p>";
    }
}

echo "<h4>Updated structure:</h4>";
$result2 = $conn->query("DESCRIBE purchase_orders");
if ($result2) {
    echo "<ul>";
    while ($row = $result2->fetch_assoc()) {
        echo "<li>{$row['Field']} ({$row['Type']})</li>";
    }
    echo "</ul>";
}

echo "<p><a href='Users/pharmacist/prescriptions.php'>Back to Prescriptions</a></p>";
?>
