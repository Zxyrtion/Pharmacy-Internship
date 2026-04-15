<?php
require_once 'config.php';

echo "<h2>Checking Orders Table</h2>";

$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color:green;'>✓ orders table exists</p>";
    
    $structure = $conn->query("DESCRIBE orders");
    if ($structure) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color:red;'>✗ orders table does NOT exist</p>";
}

// Check foreign key constraints on product_logs
echo "<h3>Foreign Key Constraints on product_logs:</h3>";
$fk = $conn->query("SELECT 
    CONSTRAINT_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'product_logs' 
AND TABLE_SCHEMA = 'internship_system'
AND REFERENCED_TABLE_NAME IS NOT NULL");

if ($fk && $fk->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Constraint</th><th>Column</th><th>References</th></tr>";
    while ($row = $fk->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['CONSTRAINT_NAME']}</td>";
        echo "<td>{$row['COLUMN_NAME']}</td>";
        echo "<td>{$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No foreign key constraints found</p>";
}
?>
