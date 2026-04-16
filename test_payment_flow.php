<?php
require_once 'config.php';

$rx_id_string = 'RX-20260416-5770';
$rx_id = 3;

echo "<h2>Payment Flow Test</h2>";

// Check prescription
$rx = $conn->query("SELECT * FROM prescriptions WHERE id=$rx_id")->fetch_assoc();
echo "<h3>1. Prescription Status</h3>";
echo "<p>ID: {$rx['prescription_id']}</p>";
echo "<p>Status: <strong style='color:" . ($rx['status'] === 'Ready' ? 'green' : 'red') . ";'>{$rx['status']}</strong></p>";
if ($rx['status'] !== 'Ready') {
    echo "<p style='color:red;'>⚠️ Status must be 'Ready' for payment!</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='fix_status'>Fix Status to Ready</button>";
    echo "</form>";
    
    if (isset($_POST['fix_status'])) {
        $conn->query("UPDATE prescriptions SET status='Ready' WHERE id=$rx_id");
        echo "<p style='color:green;'>✓ Fixed! Refresh page.</p>";
    }
}

// Check order
$order = $conn->query("SELECT * FROM prescription_orders WHERE prescription_id=$rx_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
echo "<h3>2. Order Status</h3>";
if ($order) {
    echo "<p>Order ID: {$order['id']}</p>";
    echo "<p>Total: ₱{$order['total_amount']}</p>";
    echo "<p>Status: <strong>{$order['status']}</strong></p>";
} else {
    echo "<p style='color:red;'>⚠️ No order found!</p>";
}

// Check order items
$items = $conn->query("SELECT * FROM prescription_order_items WHERE order_id={$order['id']}")->fetch_all(MYSQLI_ASSOC);
echo "<h3>3. Order Items</h3>";
if (empty($items)) {
    echo "<p style='color:red;'>⚠️ No order items! Run <a href='fix_order_items.php'>fix_order_items.php</a></p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Medicine</th><th>Qty</th><th>Price</th><th>Amount</th></tr>";
    foreach ($items as $item) {
        echo "<tr>";
        echo "<td>{$item['medicine_name']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>₱{$item['unit_price']}</td>";
        echo "<td>₱{$item['amount']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>4. Test Payment Flow</h3>";

if ($rx['status'] === 'Ready' && $order && !empty($items)) {
    echo "<p style='color:green;'>✓ Everything is ready!</p>";
    echo "<ol>";
    echo "<li><a href='Users/customer/payment.php?rx_id=$rx_id_string' target='_blank' style='font-size:18px; font-weight:bold; color:blue;'>CLICK HERE to go to Payment Page</a></li>";
    echo "<li>You should see the payment page with order details</li>";
    echo "<li>Click 'Pay Securely via PayMongo' button</li>";
    echo "<li>Should redirect to payment_mock.php</li>";
    echo "<li>Click 'Simulate Successful Payment'</li>";
    echo "<li>Should show success modal with countdown</li>";
    echo "<li>Auto-redirect to dashboard after 3 seconds</li>";
    echo "<li>Status should show 'Completed ✓'</li>";
    echo "</ol>";
} else {
    echo "<p style='color:red;'>⚠️ Not ready yet. Fix the issues above first.</p>";
}

// Show what createCheckoutSession will return
echo "<hr>";
echo "<h3>5. Mock Payment URL</h3>";
$mock_url = "http://{$_SERVER['HTTP_HOST']}/Pharmacy-Internship/Users/customer/payment_mock.php?rx_id=$rx_id&amount={$order['total_amount']}";
echo "<p>When you click 'Pay Securely', it should redirect to:</p>";
echo "<p><code>$mock_url</code></p>";
?>
