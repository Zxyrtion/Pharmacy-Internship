<?php
require_once 'config.php';

echo "<h2>Fixing Prescription #4</h2>";

// Get prescription data
$result = $conn->query("SELECT * FROM prescriptions WHERE id = 4");
if (!$result || $result->num_rows == 0) {
    die("<p style='color:red;'>Prescription #4 not found!</p>");
}

$rx = $result->fetch_assoc();

// Fix missing patient_name
if (empty($rx['patient_name']) || $rx['patient_name'] == '0') {
    echo "Fixing patient_name...<br>";
    $conn->query("UPDATE prescriptions SET patient_name = 'Unknown Patient' WHERE id = 4 AND (patient_name IS NULL OR patient_name = '' OR patient_name = '0')");
    echo "✓ Patient name updated<br>";
}

// Fix missing doctor_name
if (empty($rx['doctor_name'])) {
    echo "Fixing doctor_name...<br>";
    $conn->query("UPDATE prescriptions SET doctor_name = 'Dr. Unknown' WHERE id = 4 AND (doctor_name IS NULL OR doctor_name = '')");
    echo "✓ Doctor name updated<br>";
}

// Fix missing prescription_date
if (empty($rx['prescription_date']) || $rx['prescription_date'] == '0000-00-00') {
    echo "Fixing prescription_date...<br>";
    $conn->query("UPDATE prescriptions SET prescription_date = CURDATE() WHERE id = 4 AND (prescription_date IS NULL OR prescription_date = '0000-00-00' OR prescription_date = '')");
    echo "✓ Prescription date updated<br>";
}

// Check if purchase order exists
$po_result = $conn->query("SELECT * FROM purchase_orders WHERE prescription_id = 4");
if (!$po_result || $po_result->num_rows == 0) {
    echo "Creating purchase order...<br>";
    
    // Get customer_id from prescription
    $customer_id = $rx['customer_id'] ?? 0;
    
    // Create purchase order
    $conn->query("INSERT INTO purchase_orders (prescription_id, customer_id, total_amount, status, created_at) 
                  VALUES (4, $customer_id, 0.00, 'Pending', NOW())");
    $order_id = $conn->insert_id;
    echo "✓ Purchase order created (ID: $order_id)<br>";
    
    // Create a sample item
    echo "Creating sample order item...<br>";
    $conn->query("INSERT INTO purchase_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) 
                  VALUES ($order_id, 'Sample Medicine', 'Generic Name', 1, 0.00, 0.00, 'As directed')");
    echo "✓ Sample order item created<br>";
} else {
    echo "✓ Purchase order already exists<br>";
    
    // Check if items exist
    $po = $po_result->fetch_assoc();
    $items_result = $conn->query("SELECT * FROM purchase_order_items WHERE order_id = {$po['id']}");
    
    if (!$items_result || $items_result->num_rows == 0) {
        echo "Creating sample order item...<br>";
        $conn->query("INSERT INTO purchase_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) 
                      VALUES ({$po['id']}, 'Sample Medicine', 'Generic Name', 1, 0.00, 0.00, 'As directed')");
        echo "✓ Sample order item created<br>";
    } else {
        echo "✓ Order items already exist<br>";
    }
}

echo "<h3>✓ Prescription #4 has been fixed!</h3>";
echo "<p><a href='debug_prescription_4.php'>View updated data</a></p>";
echo "<p><a href='Users/assistant/dispense_product.php'>Go to Dispense Product</a></p>";
?>
