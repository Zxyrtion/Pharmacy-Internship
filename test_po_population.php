<?php
require_once 'config.php';

echo "<h1>Test PO Population from Approved Report</h1>";

// Test the exact query used in create_po.php
$period = 'Q1 2026';
$reporter = 'Jasmine Duran';

echo "<h2>Testing Query from create_po.php:</h2>";
echo "<p>Period: $period</p>";
echo "<p>Reporter: $reporter</p>";

$sql = "SELECT item_number_name, description, item_reorder_quantity, cost_per_item FROM inventory_report WHERE inventory_period = ? AND reporter = ? AND reorder_required = 'Yes'";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $period, $reporter);
    $stmt->execute();
    $res = $stmt->get_result();
    
    echo "<h3>Results:</h3>";
    if ($res->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Item Number</th><th>Description</th><th>Reorder Qty</th><th>Cost</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['item_number_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . $row['item_reorder_quantity'] . "</td>";
            echo "<td>₱" . number_format($row['cost_per_item'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✓ Found " . $res->num_rows . " items for reordering</p>";
    } else {
        echo "<p style='color: red;'>✗ No items found for reordering</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>✗ Query preparation failed</p>";
}

// Show all items in the report for comparison
echo "<h2>All Items in Report:</h2>";
$sql_all = "SELECT item_number_name, description, item_reorder_quantity, cost_per_item, reorder_required FROM inventory_report WHERE inventory_period = ? AND reporter = ? ORDER BY item_number_name";
$stmt_all = $conn->prepare($sql_all);

if ($stmt_all) {
    $stmt_all->bind_param("ss", $period, $reporter);
    $stmt_all->execute();
    $res_all = $stmt_all->get_result();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Item Number</th><th>Description</th><th>Reorder Qty</th><th>Cost</th><th>Reorder Required</th></tr>";
    while ($row = $res_all->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['item_number_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . $row['item_reorder_quantity'] . "</td>";
        echo "<td>₱" . number_format($row['cost_per_item'], 2) . "</td>";
        echo "<td style='text-align: center; font-weight: bold; " . ($row['reorder_required'] === 'Yes' ? 'color: green;' : 'color: red;') . "'>" . $row['reorder_required'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $stmt_all->close();
}

echo "<p><a href='Users/technician/create_po.php?period=" . urlencode($period) . "&reporter=" . urlencode($reporter) . "'>Test PO Creation</a></p>";
?>
