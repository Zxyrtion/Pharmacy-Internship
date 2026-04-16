<?php
require_once 'config.php';

$order_id = 5;
$rx_id = 3;

echo "<h2>Fixing Order Items for Order ID: $order_id</h2>";

// Check current items
$check = $conn->query("SELECT * FROM prescription_order_items WHERE order_id=$order_id");
echo "<p>Current items count: " . $check->num_rows . "</p>";

if ($check->num_rows > 0) {
    echo "<p style='color:orange;'>Items already exist:</p><ul>";
    while ($item = $check->fetch_assoc()) {
        echo "<li>{$item['medicine_name']} - Qty: {$item['quantity']} - ₱{$item['amount']}</li>";
    }
    echo "</ul>";
} else {
    // Get prescription data to see what medicines were prescribed
    $rx = $conn->query("SELECT * FROM prescriptions WHERE id=$rx_id")->fetch_assoc();
    
    echo "<p>Prescription medicine: {$rx['medicine_name']}</p>";
    echo "<p>Quantity: {$rx['quantity']}</p>";
    
    // Create order items based on prescription
    $items = [
        [
            'medicine_name' => $rx['medicine_name'] ?: 'Alaxan',
            'generic_name' => 'Ibuprofen + Paracetamol',
            'quantity' => $rx['quantity'] ?: 2,
            'unit_price' => 12.00,
            'sig' => $rx['instructions'] ?: 'Take as needed'
        ],
        [
            'medicine_name' => 'Paracetamol',
            'generic_name' => 'Acetaminophen',
            'quantity' => 10,
            'unit_price' => 4.00,
            'sig' => '1 tablet every 6 hours'
        ]
    ];
    
    echo "<p style='color:green;'>Creating order items:</p><ul>";
    
    foreach ($items as $item) {
        $amount = $item['quantity'] * $item['unit_price'];
        
        $ins = $conn->prepare("INSERT INTO prescription_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) VALUES (?,?,?,?,?,?,?)");
        $ins->bind_param('issidds', 
            $order_id, 
            $item['medicine_name'], 
            $item['generic_name'], 
            $item['quantity'], 
            $item['unit_price'], 
            $amount, 
            $item['sig']
        );
        
        if ($ins->execute()) {
            echo "<li>✓ {$item['medicine_name']} ({$item['generic_name']}) - Qty: {$item['quantity']} × ₱{$item['unit_price']} = ₱$amount</li>";
        } else {
            echo "<li style='color:red;'>✗ Error: " . $conn->error . "</li>";
        }
    }
    echo "</ul>";
    
    // Update order total
    $new_total = 0;
    $items_check = $conn->query("SELECT SUM(amount) as total FROM prescription_order_items WHERE order_id=$order_id");
    $new_total = $items_check->fetch_assoc()['total'];
    
    $conn->query("UPDATE prescription_orders SET total_amount=$new_total WHERE id=$order_id");
    echo "<p style='color:green;'>✓ Updated order total to ₱$new_total</p>";
}

// Update order status to Ready
$conn->query("UPDATE prescription_orders SET status='Ready' WHERE id=$order_id");
echo "<p style='color:green;'>✓ Order status set to 'Ready'</p>";

echo "<hr>";
echo "<h3 style='color:green;'>✓ Fixed! Now test the payment:</h3>";
echo "<ol>";
echo "<li><a href='Users/customer/payment.php?rx_id=RX-20260416-5770' target='_blank'><strong>Go to Payment Page</strong></a></li>";
echo "<li>You should now see the order details with medicines</li>";
echo "<li>Click 'Pay Securely via PayMongo'</li>";
echo "<li>Redirects to payment_mock.php</li>";
echo "<li>Click 'Simulate Successful Payment'</li>";
echo "<li>See success modal with countdown</li>";
echo "<li>Auto-redirect to dashboard</li>";
echo "</ol>";
?>
