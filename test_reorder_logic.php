<?php
require_once 'config.php';

echo "<h1>Test Inventory Reorder Logic</h1>";

echo "<h2>Reorder Logic Rules:</h2>";
echo "<ul>";
echo "<li><strong>Reorder Point:</strong> 100 units</li>";
echo "<li><strong>Auto-Reorder Trigger:</strong> Stock &lt; 100</li>";
echo "<li><strong>Reorder Quantity:</strong> Enough to reach minimum 200 units</li>";
echo "<li><strong>Formula:</strong> Reorder Qty = max(0, 200 - Current Stock)</li>";
echo "</ul>";

echo "<h2>Test Scenarios:</h2>";
$testStocks = [50, 75, 99, 100, 120, 150, 199, 200, 250];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Current Stock</th><th>Stock &lt; 100?</th><th>Reorder Required</th><th>Reorder Point</th><th>Reorder Quantity</th><th>Stock After Reorder</th></tr>";

foreach ($testStocks as $stock) {
    $needsReorder = $stock < 100;
    $reorderRequired = $needsReorder ? 'Yes' : 'No';
    $reorderPoint = 100;
    $reorderQty = $needsReorder ? max(0, 200 - $stock) : 0;
    $stockAfterReorder = $stock + $reorderQty;
    
    echo "<tr>";
    echo "<td>" . $stock . "</td>";
    echo "<td style='text-align: center;'>" . ($needsReorder ? '✓' : '✗') . "</td>";
    echo "<td style='text-align: center; font-weight: bold; " . ($needsReorder ? 'color: green;' : 'color: red;') . "'>" . $reorderRequired . "</td>";
    echo "<td>" . $reorderPoint . "</td>";
    echo "<td>" . $reorderQty . "</td>";
    echo "<td style='font-weight: bold;'>" . $stockAfterReorder . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Current Inventory Data:</h2>";
$sql = "SELECT product_name, quantity, price FROM product_inventory ORDER BY quantity ASC";
$res = $conn->query($sql);

if ($res) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Product</th><th>Current Stock</th><th>Price</th><th>Needs Reorder?</th><th>Reorder Qty</th><th>After Reorder</th></tr>";
    
    while ($row = $res->fetch_assoc()) {
        $stock = $row['quantity'];
        $needsReorder = $stock < 100;
        $reorderQty = $needsReorder ? max(0, 200 - $stock) : 0;
        $stockAfterReorder = $stock + $reorderQty;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $stock . "</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td style='text-align: center; font-weight: bold; " . ($needsReorder ? 'color: green;' : 'color: red;') . "'>" . ($needsReorder ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $reorderQty . "</td>";
        echo "<td style='font-weight: bold;'>" . $stockAfterReorder . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='Users/intern/inventory_report.php'>Test Inventory Report Form</a></p>";
?>
