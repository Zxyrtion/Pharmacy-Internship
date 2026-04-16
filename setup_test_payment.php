<?php
require_once 'config.php';

$rx_id_string = 'RX-20260416-5770';

echo "<h2>Setting Up Test Payment Data</h2>";

// Get prescription
$s = $conn->prepare("SELECT * FROM prescriptions WHERE prescription_id=? LIMIT 1");
$s->bind_param('s', $rx_id_string);
$s->execute();
$rx = $s->get_result()->fetch_assoc();

if (!$rx) {
    die("<p style='color:red;'>Prescription $rx_id_string not found!</p>");
}

$rx_id = $rx['id'];
$patient_id = $rx['patient_id'];

echo "<p>✓ Found prescription: <strong>$rx_id_string</strong> (DB ID: $rx_id)</p>";
echo "<p>Patient ID: $patient_id, Patient Name: {$rx['patient_name']}</p>";

// Check if prescription_order exists
$check_order = $conn->prepare("SELECT * FROM prescription_orders WHERE prescription_id=?");
$check_order->bind_param('i', $rx_id);
$check_order->execute();
$existing_order = $check_order->get_result()->fetch_assoc();

if ($existing_order) {
    echo "<p style='color:orange;'>Order already exists (ID: {$existing_order['id']}, Total: ₱{$existing_order['total_amount']})</p>";
    $order_id = $existing_order['id'];
} else {
    // Create prescription_order
    $total = 100.00; // Sample total
    $pharmacist_id = 13; // Use existing pharmacist ID from your data
    
    $ins_order = $conn->prepare("INSERT INTO prescription_orders (prescription_id, pharmacist_id, order_date, total_amount, status, notes) VALUES (?,?,CURDATE(),?,'Ready','Test order for payment')");
    $ins_order->bind_param('iid', $rx_id, $pharmacist_id, $total);
    
    if ($ins_order->execute()) {
        $order_id = $conn->insert_id;
        echo "<p style='color:green;'>✓ Created prescription_order (ID: $order_id, Total: ₱$total)</p>";
    } else {
        die("<p style='color:red;'>Error creating order: " . $conn->error . "</p>");
    }
}

// Check if order items exist
$check_items = $conn->prepare("SELECT * FROM prescription_order_items WHERE order_id=?");
$check_items->bind_param('i', $order_id);
$check_items->execute();
$existing_items = $check_items->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($existing_items)) {
    echo "<p style='color:orange;'>Order items already exist (" . count($existing_items) . " items)</p>";
    foreach ($existing_items as $item) {
        echo "<li>{$item['medicine_name']} - Qty: {$item['quantity']} - ₱{$item['amount']}</li>";
    }
} else {
    // Create sample order items
    $items = [
        ['Paracetamol', 'Acetaminophen', 10, '1 tablet every 6 hours', 5.00],
        ['Amoxicillin', 'Amoxicillin', 20, '1 capsule 3x a day', 2.50]
    ];
    
    echo "<p style='color:green;'>✓ Creating order items:</p><ul>";
    foreach ($items as $item) {
        $amount = $item[2] * $item[4];
        $ins_item = $conn->prepare("INSERT INTO prescription_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) VALUES (?,?,?,?,?,?,?)");
        $ins_item->bind_param('issidds', $order_id, $item[0], $item[1], $item[2], $item[4], $amount, $item[3]);
        
        if ($ins_item->execute()) {
            echo "<li>{$item[0]} ({$item[1]}) - Qty: {$item[2]} × ₱{$item[4]} = ₱$amount</li>";
        }
    }
    echo "</ul>";
}

// Update prescription to Ready status
$upd = $conn->prepare("UPDATE prescriptions SET status='Ready' WHERE id=?");
$upd->bind_param('i', $rx_id);
$upd->execute();
echo "<p style='color:green;'>✓ Prescription status set to <strong>'Ready'</strong></p>";

// Update order to Ready status
$upd_order = $conn->prepare("UPDATE prescription_orders SET status='Ready' WHERE id=?");
$upd_order->bind_param('i', $order_id);
$upd_order->execute();
echo "<p style='color:green;'>✓ Order status set to <strong>'Ready'</strong></p>";

echo "<hr>";
echo "<h3 style='color:green;'>✓ Setup Complete! Now test the payment flow:</h3>";
echo "<ol>";
echo "<li><a href='Users/customer/dashboard.php' target='_blank'><strong>Go to Customer Dashboard</strong></a></li>";
echo "<li>Find prescription <strong>$rx_id_string</strong></li>";
echo "<li>Click the green <strong>'Pay Now'</strong> button</li>";
echo "<li>You should see the payment page with order details</li>";
echo "<li>Click <strong>'Pay Securely via PayMongo'</strong></li>";
echo "<li>You'll be redirected to <strong>payment_mock.php</strong></li>";
echo "<li>Click <strong>'Simulate Successful Payment'</strong></li>";
echo "<li>You'll see the <strong>success modal</strong> with countdown (3 seconds)</li>";
echo "<li>Auto-redirect to dashboard</li>";
echo "<li>Status will show <strong>'Completed ✓'</strong></li>";
echo "</ol>";

echo "<p style='background:#fff3cd; padding:1rem; border-radius:8px;'>";
echo "<strong>Note:</strong> If you're not logged in as the customer who owns this prescription, ";
echo "you won't see it in the dashboard. Make sure you're logged in as patient_id: <strong>$patient_id</strong>";
echo "</p>";
?>
