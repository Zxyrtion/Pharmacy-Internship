<?php
require_once 'config.php';

echo "<h2>Checking Database Tables</h2>";

// Check for prescription_orders table
$result = $conn->query("SHOW TABLES LIKE 'prescription_orders'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✓ Table 'prescription_orders' exists</p>";
    
    // Show structure
    $structure = $conn->query("DESCRIBE prescription_orders");
    echo "<h3>prescription_orders structure:</h3><pre>";
    while ($row = $structure->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red;'>✗ Table 'prescription_orders' NOT found</p>";
}

// Check for prescription_order_items table
$result2 = $conn->query("SHOW TABLES LIKE 'prescription_order_items'");
if ($result2->num_rows > 0) {
    echo "<p style='color:green;'>✓ Table 'prescription_order_items' exists</p>";
    
    // Show structure
    $structure2 = $conn->query("DESCRIBE prescription_order_items");
    echo "<h3>prescription_order_items structure:</h3><pre>";
    while ($row = $structure2->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red;'>✗ Table 'prescription_order_items' NOT found</p>";
    
    // Check for alternative names
    echo "<h3>Checking for similar table names:</h3>";
    $all_tables = $conn->query("SHOW TABLES");
    echo "<ul>";
    while ($table = $all_tables->fetch_array()) {
        if (stripos($table[0], 'order') !== false || stripos($table[0], 'item') !== false) {
            echo "<li>" . $table[0] . "</li>";
        }
    }
    echo "</ul>";
}

// Check data in prescription_orders
echo "<hr><h3>Sample data from prescription_orders:</h3>";
$data = $conn->query("SELECT * FROM prescription_orders LIMIT 3");
if ($data && $data->num_rows > 0) {
    echo "<pre>";
    while ($row = $data->fetch_assoc()) {
        print_r($row);
        echo "\n---\n";
    }
    echo "</pre>";
} else {
    echo "<p>No data found in prescription_orders table</p>";
}
?>
