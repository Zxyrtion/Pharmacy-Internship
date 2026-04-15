<?php
require_once 'config.php';

echo "<h2>Creating Prescription Orders Table</h2>";

// Create a separate table for prescription orders (customer orders)
$sql = "CREATE TABLE IF NOT EXISTS prescription_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    pharmacist_id INT,
    order_date DATE,
    total_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('Pending','Processing','Ready','Dispensed','Paid','Cancelled') DEFAULT 'Pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(prescription_id),
    INDEX(status)
)";

if ($conn->query($sql)) {
    echo "<p style='color:green;'>✓ prescription_orders table created successfully!</p>";
} else {
    echo "<p style='color:red;'>✗ Error: " . $conn->error . "</p>";
}

// Create prescription order items table
$sql2 = "CREATE TABLE IF NOT EXISTS prescription_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    medicine_name VARCHAR(200),
    generic_name VARCHAR(200),
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) DEFAULT 0,
    amount DECIMAL(10,2) DEFAULT 0,
    sig VARCHAR(300),
    INDEX(order_id)
)";

if ($conn->query($sql2)) {
    echo "<p style='color:green;'>✓ prescription_order_items table created successfully!</p>";
} else {
    echo "<p style='color:red;'>✗ Error: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<p>Now we need to update the pharmacist and assistant code to use these new tables instead of purchase_orders.</p>";
echo "<p>The tables are:</p>";
echo "<ul>";
echo "<li><strong>prescription_orders</strong> - for customer prescription orders</li>";
echo "<li><strong>prescription_order_items</strong> - for the medicines in each order</li>";
echo "<li><strong>purchase_orders</strong> - for supplier/requisition orders (existing)</li>";
echo "</ul>";
?>
