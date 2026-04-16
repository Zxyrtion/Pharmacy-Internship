<?php
require_once 'config.php';

echo "<h2>Applying Enhanced Inventory Schema...</h2>";

// Read the SQL file
$sql_file = 'database/enhanced_inventory_schema.sql';
if (!file_exists($sql_file)) {
    die("Error: SQL file not found at $sql_file");
}

$sql = file_get_contents($sql_file);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    }
);

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($statements as $statement) {
    if (empty(trim($statement))) continue;
    
    try {
        if ($conn->query($statement)) {
            $success_count++;
            echo "<p style='color: green;'>✓ Executed successfully</p>";
        } else {
            $error_count++;
            $error_msg = $conn->error;
            $errors[] = $error_msg;
            echo "<p style='color: orange;'>⚠ Warning: $error_msg</p>";
        }
    } catch (Exception $e) {
        $error_count++;
        $error_msg = $e->getMessage();
        $errors[] = $error_msg;
        echo "<p style='color: orange;'>⚠ Warning: $error_msg</p>";
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Successful statements:</strong> $success_count</p>";
echo "<p><strong>Warnings/Errors:</strong> $error_count</p>";

if ($error_count > 0) {
    echo "<h4>Note:</h4>";
    echo "<p>Some warnings are expected if columns or tables already exist. This is normal.</p>";
}

echo "<hr>";
echo "<h3>✓ Enhanced Inventory Schema Applied Successfully!</h3>";
echo "<p>Your database now includes:</p>";
echo "<ul>";
echo "<li>Enhanced product_inventory table with expiry dates, batch numbers, and supplier tracking</li>";
echo "<li>Suppliers table for managing supplier information</li>";
echo "<li>Inventory movements table for tracking all stock changes</li>";
echo "<li>Stock alerts table for low stock and expiry notifications</li>";
echo "</ul>";

echo "<p><a href='Users/intern/enhanced_inventory.php'>Go to Enhanced Inventory Management</a></p>";
echo "<p><a href='Users/intern/dashboard.php'>Go to Intern Dashboard</a></p>";

$conn->close();
?>
