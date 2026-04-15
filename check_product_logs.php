<?php
require_once 'config.php';

echo "<h2>Product Logs Table Diagnostic</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'product_logs'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color:green;'>✓ product_logs table exists</p>";
    
    // Show structure
    echo "<h3>Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE product_logs");
    if ($structure) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['Field']}</strong></td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the INSERT statement
    echo "<h3>Testing INSERT Statement:</h3>";
    $test_sql = "INSERT INTO product_logs (prescription_id, order_id, medicine_name, generic_name, quantity, unit_price, total_price, sig, pharmacist_id, patient_name, doctor_name) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($test_sql);
    
    if ($stmt === false) {
        echo "<p style='color:red;'>✗ Prepare failed: " . $conn->error . "</p>";
        echo "<p>SQL: " . htmlspecialchars($test_sql) . "</p>";
    } else {
        echo "<p style='color:green;'>✓ Prepare successful</p>";
        $stmt->close();
    }
    
    // Show sample data
    echo "<h3>Sample Data (last 5 records):</h3>";
    $data = $conn->query("SELECT * FROM product_logs ORDER BY id DESC LIMIT 5");
    if ($data && $data->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Prescription ID</th><th>Medicine</th><th>Quantity</th><th>Total Price</th><th>Patient</th><th>Date</th></tr>";
        while ($row = $data->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['prescription_id']}</td>";
            echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "<td>₱" . number_format($row['total_price'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['patient_name'] ?? '-') . "</td>";
            echo "<td>{$row['log_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in table yet</p>";
    }
    
} else {
    echo "<p style='color:red;'>✗ product_logs table does NOT exist</p>";
    echo "<p>The table needs to be created. Try accessing the dispense_product.php page to auto-create it.</p>";
}
?>
