<?php
require_once 'config.php';

echo "<h2>Debugging Prescription #4</h2>";

// Check prescription data
echo "<h3>Prescription Data:</h3>";
$result = $conn->query("SELECT * FROM prescriptions WHERE id = 4");
if ($result && $result->num_rows > 0) {
    $rx = $result->fetch_assoc();
    echo "<table border='1'>";
    foreach ($rx as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Prescription #4 not found!</p>";
}

// Check purchase order
echo "<h3>Purchase Order Data:</h3>";
$result = $conn->query("SELECT * FROM purchase_orders WHERE prescription_id = 4");
if ($result && $result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
        echo "<table border='1'>";
        foreach ($order as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table><br>";
    }
} else {
    echo "<p style='color:orange;'>No purchase order found for prescription #4</p>";
}

// Check purchase order items
echo "<h3>Purchase Order Items:</h3>";
$result = $conn->query("SELECT poi.* FROM purchase_order_items poi 
                        JOIN purchase_orders po ON poi.order_id = po.id 
                        WHERE po.prescription_id = 4");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Order ID</th><th>Medicine</th><th>Generic</th><th>Qty</th><th>Price</th><th>Amount</th></tr>";
    while ($item = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['order_id']}</td>";
        echo "<td>" . htmlspecialchars($item['medicine_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($item['generic_name'] ?? '') . "</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>{$item['unit_price']}</td>";
        echo "<td>{$item['amount']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>No purchase order items found</p>";
}

// Check customer data
echo "<h3>Customer Data:</h3>";
$result = $conn->query("SELECT p.customer_id, u.* FROM prescriptions p 
                        LEFT JOIN users u ON p.customer_id = u.id 
                        WHERE p.id = 4");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<table border='1'>";
    foreach ($user as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Fix Options:</h3>";
echo "<p><a href='fix_prescription_4.php'>Click here to fix prescription #4 data</a></p>";
echo "<p><a href='Users/assistant/dispense_product.php'>Back to Dispense Product</a></p>";
?>
