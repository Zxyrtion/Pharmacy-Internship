<?php
require_once 'config.php';

echo "<h2>Adding Medicines to Inventory</h2>";

$medicines = [
    ['name' => 'Alaxan FR', 'dosage' => '500mg', 'stock' => 0, 'reorder' => 100, 'price' => 11.00],
    ['name' => 'Paracetamol', 'dosage' => '500mg', 'stock' => 0, 'reorder' => 100, 'price' => 7.00],
];

foreach ($medicines as $med) {
    // Check if medicine already exists
    $check_sql = "SELECT id FROM inventory WHERE medicine_name = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $med['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ {$med['name']} already exists in inventory</p>";
    } else {
        // Insert new medicine
        $insert_sql = "INSERT INTO inventory (medicine_name, dosage, current_stock, reorder_level, unit_price, manufacturer, stock_status, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, 'N/A', 'Normal', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssiid", $med['name'], $med['dosage'], $med['stock'], $med['reorder'], $med['price']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Added {$med['name']} to inventory</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to add {$med['name']}: " . $conn->error . "</p>";
        }
    }
    $stmt->close();
}

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Go to Pharmacist dashboard</li>";
echo "<li>Generate a new PO with these medicines</li>";
echo "<li>Check 'View Stock Changes History' to see the automatic updates</li>";
echo "</ol>";

$conn->close();
?>
