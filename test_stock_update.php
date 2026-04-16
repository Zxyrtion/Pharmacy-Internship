<?php
require_once 'config.php';
require_once 'models/purchase_order.php';

echo "<h2>Testing Stock Update</h2>";

// Check if medicines table has data
$result = $conn->query("SELECT id, medicine_name, current_stock FROM medicines LIMIT 5");
echo "<h3>Available Medicines:</h3>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Medicine Name</th><th>Current Stock</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
        echo "<td>" . $row['current_stock'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test updating stock for the first medicine
    $result->data_seek(0);
    $first_med = $result->fetch_assoc();
    
    echo "<hr><h3>Testing Stock Update for: " . htmlspecialchars($first_med['medicine_name']) . "</h3>";
    
    $po = new PurchaseOrder($conn);
    $success = $po->updateInventoryStock($first_med['medicine_name'], 10, 1, 'Test PO Generation', 'TEST-PO-001');
    
    if ($success) {
        echo "<p style='color: green;'>✓ Stock update successful!</p>";
        echo "<p>Go to <a href='Users/pharmacist/view_stock_changes.php'>View Stock Changes</a> to see the update</p>";
    } else {
        echo "<p style='color: red;'>✗ Stock update failed</p>";
    }
    
} else {
    echo "<p style='color: red;'>No medicines found in database!</p>";
    echo "<p>You need to add medicines first before generating POs.</p>";
}

$conn->close();
?>
