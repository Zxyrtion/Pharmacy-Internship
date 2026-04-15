<?php
require_once 'config.php';

echo "<h2>Testing Product Logs INSERT</h2>";

// Test the new INSERT statement
$test_sql = "INSERT INTO product_logs (prescription_id, order_id, medicine_name, dosage, quantity_dispensed, unit_price, total_price, pharmacist_id, patient_id, patient_name, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)";

echo "<p>SQL: " . htmlspecialchars($test_sql) . "</p>";

$stmt = $conn->prepare($test_sql);

if ($stmt === false) {
    echo "<p style='color:red;'>✗ Prepare failed: " . $conn->error . "</p>";
} else {
    echo "<p style='color:green;'>✓ Prepare successful!</p>";
    
    // Test with sample data
    $prescription_id = 5;
    $order_id = 1;
    $medicine_name = "Test Medicine";
    $dosage = "Test Dosage";
    $quantity_dispensed = 1;
    $unit_price = 100.00;
    $total_price = 100.00;
    $pharmacist_id = 13;
    $patient_id = 20;
    $patient_name = "Test Patient";
    $notes = "Test notes";
    
    $stmt->bind_param('iissiddisss',
        $prescription_id, $order_id,
        $medicine_name, $dosage,
        $quantity_dispensed, $unit_price, $total_price,
        $pharmacist_id, $patient_id, $patient_name, $notes
    );
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Test insert successful! Insert ID: " . $stmt->insert_id . "</p>";
        
        // Delete the test record
        $conn->query("DELETE FROM product_logs WHERE id = " . $stmt->insert_id);
        echo "<p>Test record deleted</p>";
    } else {
        echo "<p style='color:red;'>✗ Execute failed: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

echo "<p><a href='Users/assistant/dispense_product.php'>Go to Dispense Product Page</a></p>";
?>
