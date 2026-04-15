<?php
require_once 'config.php';

echo "<h2>Checking and Fixing Purchase Order Tables</h2>";

// Check purchase_orders table structure
echo "<h3>Purchase Orders Table:</h3>";
$result = $conn->query("DESCRIBE purchase_orders");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Table doesn't exist or error: " . $conn->error;
}

// Check purchase_order_items table structure
echo "<h3>Purchase Order Items Table:</h3>";
$result = $conn->query("DESCRIBE purchase_order_items");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Table doesn't exist or error: " . $conn->error;
}

// Fix the purchase_order_items table - add missing columns if needed
echo "<h3>Fixing purchase_order_items table...</h3>";

// Check if order_id column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'order_id'");
if ($check->num_rows == 0) {
    echo "Adding order_id column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN order_id INT NOT NULL AFTER id");
    echo "✓ order_id column added<br>";
} else {
    echo "✓ order_id column already exists<br>";
}

// Check if medicine_name column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'medicine_name'");
if ($check->num_rows == 0) {
    echo "Adding medicine_name column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN medicine_name VARCHAR(200) NOT NULL");
    echo "✓ medicine_name column added<br>";
} else {
    echo "✓ medicine_name column already exists<br>";
}

// Check if generic_name column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'generic_name'");
if ($check->num_rows == 0) {
    echo "Adding generic_name column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN generic_name VARCHAR(200) DEFAULT NULL");
    echo "✓ generic_name column added<br>";
} else {
    echo "✓ generic_name column already exists<br>";
}

// Check if quantity column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'quantity'");
if ($check->num_rows == 0) {
    echo "Adding quantity column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN quantity INT NOT NULL DEFAULT 1");
    echo "✓ quantity column added<br>";
} else {
    echo "✓ quantity column already exists<br>";
}

// Check if unit_price column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'unit_price'");
if ($check->num_rows == 0) {
    echo "Adding unit_price column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN unit_price DECIMAL(10,2) DEFAULT 0");
    echo "✓ unit_price column added<br>";
} else {
    echo "✓ unit_price column already exists<br>";
}

// Check if amount column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'amount'");
if ($check->num_rows == 0) {
    echo "Adding amount column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN amount DECIMAL(10,2) DEFAULT 0");
    echo "✓ amount column added<br>";
} else {
    echo "✓ amount column already exists<br>";
}

// Check if sig column exists
$check = $conn->query("SHOW COLUMNS FROM purchase_order_items LIKE 'sig'");
if ($check->num_rows == 0) {
    echo "Adding sig column...<br>";
    $conn->query("ALTER TABLE purchase_order_items ADD COLUMN sig VARCHAR(300) DEFAULT NULL");
    echo "✓ sig column added<br>";
} else {
    echo "✓ sig column already exists<br>";
}

echo "<h3>Final Structure:</h3>";
$result = $conn->query("DESCRIBE purchase_order_items");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
}

echo "<h3>✓ All done! Tables are now fixed.</h3>";
echo "<p><a href='Users/assistant/dispense_product.php'>Go back to Dispense Product</a></p>";
?>
