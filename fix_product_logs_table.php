<?php
require_once 'config.php';

echo "<h2>Fixing Product Logs Table Structure</h2>";

// Check current structure
echo "<h3>Current Structure:</h3>";
$result = $conn->query("DESCRIBE product_logs");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
}

// Add missing columns
echo "<h3>Adding Missing Columns...</h3>";

// Check and add quantity_dispensed (alias for quantity)
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'quantity_dispensed'");
if ($check->num_rows == 0) {
    // Check if 'quantity' exists
    $qty_check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'quantity'");
    if ($qty_check->num_rows > 0) {
        echo "Renaming 'quantity' to 'quantity_dispensed'...<br>";
        $conn->query("ALTER TABLE product_logs CHANGE COLUMN quantity quantity_dispensed INT NOT NULL");
    } else {
        echo "Adding quantity_dispensed column...<br>";
        $conn->query("ALTER TABLE product_logs ADD COLUMN quantity_dispensed INT NOT NULL DEFAULT 1");
    }
    echo "✓ quantity_dispensed column ready<br>";
} else {
    echo "✓ quantity_dispensed column already exists<br>";
}

// Add medicine_id column
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'medicine_id'");
if ($check->num_rows == 0) {
    echo "Adding medicine_id column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN medicine_id INT DEFAULT NULL AFTER order_id");
    echo "✓ medicine_id column added<br>";
} else {
    echo "✓ medicine_id column already exists<br>";
}

// Add patient_id column
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'patient_id'");
if ($check->num_rows == 0) {
    echo "Adding patient_id column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN patient_id INT DEFAULT NULL AFTER pharmacist_id");
    echo "✓ patient_id column added<br>";
} else {
    echo "✓ patient_id column already exists<br>";
}

// Add dosage column
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'dosage'");
if ($check->num_rows == 0) {
    echo "Adding dosage column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN dosage VARCHAR(50) DEFAULT NULL AFTER medicine_name");
    echo "✓ dosage column added<br>";
} else {
    echo "✓ dosage column already exists<br>";
}

// Add action column
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'action'");
if ($check->num_rows == 0) {
    echo "Adding action column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN action ENUM('Dispensed','Returned','Exchanged','Refunded') DEFAULT 'Dispensed' AFTER patient_name");
    echo "✓ action column added<br>";
} else {
    echo "✓ action column already exists<br>";
}

// Add notes column
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'notes'");
if ($check->num_rows == 0) {
    echo "Adding notes column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN notes TEXT DEFAULT NULL");
    echo "✓ notes column added<br>";
} else {
    echo "✓ notes column already exists<br>";
}

// Add created_at column
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'created_at'");
if ($check->num_rows == 0) {
    echo "Adding created_at column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "✓ created_at column added<br>";
} else {
    echo "✓ created_at column already exists<br>";
}

// Ensure sig column exists (for compatibility)
$check = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'sig'");
if ($check->num_rows == 0) {
    echo "Adding sig column...<br>";
    $conn->query("ALTER TABLE product_logs ADD COLUMN sig VARCHAR(300) DEFAULT NULL AFTER dosage");
    echo "✓ sig column added<br>";
} else {
    echo "✓ sig column already exists<br>";
}

echo "<h3>Final Structure:</h3>";
$result = $conn->query("DESCRIBE product_logs");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
}

echo "<h3>✓ Product logs table is now fixed!</h3>";
echo "<p><strong>Note:</strong> The table is ready but has no data yet. Data will be added when you dispense prescriptions.</p>";
echo "<p><a href='Users/assistant/product_logs.php'>Go to Product Logs & Reports</a></p>";
echo "<p><a href='Users/assistant/dispense_product.php'>Go to Dispense Product</a></p>";
?>
