<?php
require_once 'config.php';

echo "<h2>Purchase Orders Table Structure</h2>";

$result = $conn->query("DESCRIBE purchase_orders");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Check if there's a unique constraint on purchase_order_id
echo "<h3>Indexes on purchase_orders:</h3>";
$result = $conn->query("SHOW INDEXES FROM purchase_orders");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Key_name']}</td>";
        echo "<td>{$row['Column_name']}</td>";
        echo "<td>" . ($row['Non_unique'] == 0 ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}
?>
