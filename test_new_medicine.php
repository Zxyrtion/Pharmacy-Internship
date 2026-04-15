<?php
// Test script to verify new medicine saving
require_once 'config.php';

echo "<h2>New Medicine Save Test</h2>";

if (!isset($conn)) {
    die("Database connection failed");
}

// Check current product count
$before = $conn->query("SELECT COUNT(*) as count FROM product_inventory");
$before_count = $before->fetch_assoc()['count'];
echo "<div>Current products in database: $before_count</div>";

// Show last 5 products
echo "<h3>Last 5 Products:</h3>";
$products = $conn->query("SELECT * FROM product_inventory ORDER BY id DESC LIMIT 5");
if ($products && $products->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Description</th><th>Qty</th><th>Price</th></tr>";
    while ($row = $products->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div>No products found</div>";
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Go to <a href='Users/intern/product_inventory.php'>Product Inventory</a></li>";
echo "<li>Click 'Add New Medicine' button</li>";
echo "<li>Fill in medicine name, description, quantity (>0), and price</li>";
echo "<li>Click 'Save All Inventory'</li>";
echo "<li>Refresh this page to see if the new medicine appears</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> New medicines require quantity > 0 to be saved.</p>";
?>
