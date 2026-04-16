<?php
require_once 'config.php';

echo "<h2>Checking Inventory Table Structure</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'inventory'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ 'inventory' table exists</p>";
    
    // Show table structure
    $result = $conn->query("DESCRIBE inventory");
    if ($result) {
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show sample data
    $result = $conn->query("SELECT * FROM inventory LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Sample Data:</h3>";
        echo "<table border='1' cellpadding='5'>";
        $first = true;
        while ($row = $result->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $col) {
                    echo "<th>$col</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in inventory table</p>";
    }
} else {
    echo "<p style='color: red;'>✗ 'inventory' table does NOT exist</p>";
    
    // Check for alternative table names
    echo "<h3>Looking for similar tables:</h3>";
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $table = $row[0];
        if (stripos($table, 'invent') !== false || stripos($table, 'stock') !== false || stripos($table, 'medicine') !== false) {
            echo "<p>Found: <strong>$table</strong></p>";
        }
    }
}

$conn->close();
?>
