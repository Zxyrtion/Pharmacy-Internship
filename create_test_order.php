<?php
require_once 'config.php';

$rx_id_string = 'RX-20260416-5770';

// Get prescription
$s = $conn->prepare("SELECT * FROM prescriptions WHERE prescription_id=? LIMIT 1");
$s->bind_param('s', $rx_id_string);
$s->execute();
$rx = $s->get_result()->fetch_assoc();

if (!$rx) {
    die("Prescription not found!");
}

$rx_id = $rx['id'];
$customer_id = $rx['patient_id'];

echo "<h2>Creating Test Order Data</h2>";
echo "<p>Prescription ID: $rx_id_string (DB ID: $rx_id)</p>";

// Check if order already exists
$check = $conn->prepare("SELECT * FROM orders WHERE prescription_id=?");
$check->bind_param('i', $rx_id);
$check->execute();
$existing_order = $check->get_result()->fetch_assoc();

if ($existing_order) {
    echo "<p style='color:orange;'>Order already exists (ID: {$existing_order['id']})</p>";
    $order_id = $existing_order['id'];
} else {
    // Create order
    $order_id_string = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $total = 150.00; // Sample total
    
    $ins_order = $conn->prepare("INSERT INTO orders (order_id, prescription_id, customer_id, customer_name, order_type, total_amount, status, order_date) VALUES (?,?,?,?,'Prescription',?,'Ready',NOW())");
    $ins_order->bind_param('siiss', $order_id_string, $rx_id, $customer_id, $rx['patient_name'], $total);
    
    if ($ins_order->execute()) {
        $order_id = $conn->insert_id;
        echo "<p style='color:green;'>✓ Order created (ID: $order_id)</p>";
    } else {
        die("Error creating order: " . $conn->error);
    }
}

// Check if order items exist
$check_items = $conn->prepare("SELECT * FROM order_items WHERE order_id=?");
$check_items->bind_param('i', $order_id);
$check_items->execute();
$existing_items = $check_items->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($existing_items)) {
    echo "<p style='color:orange;'>Order items already exist (" . count($existing_items) . " items)</p>";
} else {
    // Create sample order items
    $items = [
        ['Paracetamol', 'Acetaminophen', 10, '1 tab every 6 hours', 5.00],
        ['Amoxicillin', 'Amoxicillin', 20, '1 cap 3x a day', 7.00]
    ];
    
    foreach ($items as $item) {
        $amount = $item[2] * $item[4];
        $ins_item = $conn->prepare("INSERT INTO order_items (order_id, medicine_name, dosage, quantity, unit_price, total_price) VALUES (?,?,?,?,?,?)");
        $ins_item->bind_param('issids', $order_id, $item[0], $item[3], $item[2], $item[4], $amount);
        $ins_item->execute();
    }
    echo "<p style='color:green;'>✓ Order items created (2 items)</p>";
}

// Update prescription to Ready status
$upd = $conn->prepare("UPDATE prescriptions SET status='Ready' WHERE id=?");
$upd->bind_param('i', $rx_id);
$upd->execute();
echo "<p style='color:green;'>✓ Prescription status set to 'Ready'</p>";

echo "<hr>";
echo "<h3>Test the Payment Flow:</h3>";
echo "<ol>";
echo "<li><a href='Users/customer/dashboard.php' target='_blank'>Go to Customer Dashboard</a></li>";
echo "<li>Find prescription <strong>$rx_id_string</strong></li>";
echo "<li>Click 'Pay Now' button</li>";
echo "<li>You should see the payment page with order details</li>";
echo "<li>Click 'Pay Securely via PayMongo'</li>";
echo "<li>You'll be redirected to payment_mock.php</li>";
echo "<li>Click 'Simulate Successful Payment'</li>";
echo "<li>You'll see the success modal with countdown</li>";
echo "<li>After 3 seconds, auto-redirect to dashboard</li>";
echo "<li>Status will show 'Completed ✓'</li>";
echo "</ol>";
?>
