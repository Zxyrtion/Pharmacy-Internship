<?php
require_once 'config.php';

echo "<h2>Creating Sample Purchase Orders for Prescriptions #5 and #6</h2>";

// Use a default pharmacist ID (we'll use ID 1 or find any user)
$pharmacist_result = $conn->query("SELECT id FROM users LIMIT 1");
$pharmacist = $pharmacist_result ? $pharmacist_result->fetch_assoc() : null;

if (!$pharmacist) {
    // If no users exist, just use ID 1 as a placeholder
    $pharmacist_id = 1;
    echo "<p style='color:orange;'>Warning: No users found, using pharmacist_id = 1</p>";
} else {
    $pharmacist_id = $pharmacist['id'];
    echo "<p>Using User ID: $pharmacist_id</p>";
}

// Process prescriptions 5 and 6
$prescription_ids = [5, 6];

foreach ($prescription_ids as $rx_id) {
    echo "<h3>Processing Prescription #$rx_id</h3>";
    
    // Get prescription details
    $rx_result = $conn->query("SELECT * FROM prescriptions WHERE id = $rx_id");
    $rx = $rx_result ? $rx_result->fetch_assoc() : null;
    
    if (!$rx) {
        echo "<p style='color:red;'>Prescription #$rx_id not found!</p>";
        continue;
    }
    
    echo "<p>Prescription ID: " . htmlspecialchars($rx['prescription_id']) . "</p>";
    echo "<p>Patient: " . htmlspecialchars($rx['patient_name']) . "</p>";
    
    // Get all medicine items for this prescription
    $items_result = $conn->query("SELECT * FROM prescriptions WHERE prescription_id = '" . $rx['prescription_id'] . "'");
    $items = $items_result ? $items_result->fetch_all(MYSQLI_ASSOC) : [];
    
    if (empty($items)) {
        echo "<p style='color:red;'>No medicine items found!</p>";
        continue;
    }
    
    // Calculate total with sample prices
    $total = 0;
    $sample_prices = [
        'Alaxan' => 15.00,
        'Creatine' => 500.00,
        'default' => 50.00
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Medicine</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr>";
    
    foreach ($items as $item) {
        $medicine = $item['medicine_name'];
        $qty = (int)$item['quantity'];
        $unit_price = $sample_prices[$medicine] ?? $sample_prices['default'];
        $amount = $qty * $unit_price;
        $total += $amount;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($medicine) . "</td>";
        echo "<td>$qty</td>";
        echo "<td>₱" . number_format($unit_price, 2) . "</td>";
        echo "<td>₱" . number_format($amount, 2) . "</td>";
        echo "</tr>";
    }
    
    echo "<tr style='background:#f0f4ff; font-weight:bold;'>";
    echo "<td colspan='3'>TOTAL:</td>";
    echo "<td>₱" . number_format($total, 2) . "</td>";
    echo "</tr>";
    echo "</table>";
    
    // Create purchase order
    $stmt = $conn->prepare("INSERT INTO prescription_orders (prescription_id, pharmacist_id, order_date, total_amount, status, notes) VALUES (?,?,CURDATE(),?,'Pending','Auto-generated for testing')");
    $stmt->bind_param('iid', $rx_id, $pharmacist_id, $total);
    
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        echo "<p style='color:green;'>✓ Prescription order #$order_id created with total: ₱" . number_format($total, 2) . "</p>";
        
        // Create purchase order items
        $stmt2 = $conn->prepare("INSERT INTO prescription_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) VALUES (?,?,?,?,?,?,?)");
        
        foreach ($items as $item) {
            $medicine = $item['medicine_name'];
            $generic = $item['dosage'] ?? '';
            $qty = (int)$item['quantity'];
            $unit_price = $sample_prices[$medicine] ?? $sample_prices['default'];
            $amount = $qty * $unit_price;
            $sig = $item['instructions'] ?? '';
            
            $stmt2->bind_param('issidds', $order_id, $medicine, $generic, $qty, $unit_price, $amount, $sig);
            $stmt2->execute();
        }
        
        echo "<p style='color:green;'>✓ Prescription order items created</p>";
        
        // Update prescription status to Processing
        $update = $conn->prepare("UPDATE prescriptions SET status = 'Processing' WHERE id = ?");
        $update->bind_param('i', $rx_id);
        $update->execute();
        
        echo "<p style='color:green;'>✓ Prescription status updated to 'Processing'</p>";
    } else {
        echo "<p style='color:red;'>✗ Failed to create prescription order: " . $conn->error . "</p>";
    }
    
    echo "<hr>";
}

echo "<h3>Done!</h3>";
echo "<p>The prescriptions are now ready for dispensing by the assistant.</p>";
echo "<p><a href='Users/assistant/dispense_product.php'>Go to Assistant Dispense Page</a></p>";
echo "<p><a href='debug_dispense_issue.php'>Check Dispense Status</a></p>";
?>
