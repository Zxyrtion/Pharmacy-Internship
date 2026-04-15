<?php
require_once 'config.php';

if (!isLoggedIn()) {
    die("Please login first");
}

$rx_id = 4;

echo "<h2>Testing Dispense for Prescription #4</h2>";

// Step 1: Check prescription
echo "<h3>Step 1: Check Prescription</h3>";
$sv = $conn->prepare("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name
    FROM prescriptions p LEFT JOIN users u ON p.customer_id = u.id WHERE p.id=?");
$sv->bind_param('i', $rx_id);
$sv->execute();
$view_rx = $sv->get_result()->fetch_assoc();

if ($view_rx) {
    echo "✓ Prescription found<br>";
    echo "<pre>";
    print_r($view_rx);
    echo "</pre>";
} else {
    echo "<span style='color:red;'>✗ Prescription NOT found</span><br>";
}

// Step 2: Check purchase order
echo "<h3>Step 2: Check Purchase Order</h3>";
$so = $conn->prepare("SELECT * FROM purchase_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
$so->bind_param('i', $rx_id);
$so->execute();
$view_order = $so->get_result()->fetch_assoc();

if ($view_order) {
    echo "✓ Purchase order found (ID: {$view_order['id']})<br>";
    echo "<pre>";
    print_r($view_order);
    echo "</pre>";
} else {
    echo "<span style='color:red;'>✗ Purchase order NOT found</span><br>";
    echo "<p><strong>This is the problem!</strong> Creating purchase order now...</p>";
    
    // Create purchase order
    $customer_id = $view_rx['customer_id'] ?? 0;
    $stmt = $conn->prepare("INSERT INTO purchase_orders (prescription_id, customer_id, total_amount, status) VALUES (?, ?, 100.00, 'Pending')");
    $stmt->bind_param('ii', $rx_id, $customer_id);
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        echo "✓ Purchase order created (ID: $order_id)<br>";
        
        // Reload
        $so->execute();
        $view_order = $so->get_result()->fetch_assoc();
    }
}

// Step 3: Check purchase order items
echo "<h3>Step 3: Check Purchase Order Items</h3>";
if ($view_order) {
    $si = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id=?");
    $si->bind_param('i', $view_order['id']);
    $si->execute();
    $view_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($view_items)) {
        echo "✓ Found " . count($view_items) . " items<br>";
        echo "<pre>";
        print_r($view_items);
        echo "</pre>";
    } else {
        echo "<span style='color:red;'>✗ No items found</span><br>";
        echo "<p><strong>This is the problem!</strong> Creating sample item now...</p>";
        
        // Create sample item
        $stmt = $conn->prepare("INSERT INTO purchase_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) VALUES (?, 'Paracetamol 500mg', 'Acetaminophen', 10, 5.00, 50.00, 'Take 1 tablet every 6 hours')");
        $stmt->bind_param('i', $view_order['id']);
        if ($stmt->execute()) {
            echo "✓ Sample item created<br>";
            
            // Update total
            $conn->query("UPDATE purchase_orders SET total_amount = 50.00 WHERE id = {$view_order['id']}");
            
            // Reload
            $si->execute();
            $view_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
if ($view_rx && $view_order && !empty($view_items)) {
    echo "<p style='color:green; font-size:18px;'>✓ All data is ready! Prescription #4 should work now.</p>";
    echo "<p><a href='Users/assistant/dispense_product.php?rx_id=4' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>Test Dispense Button</a></p>";
} else {
    echo "<p style='color:red;'>✗ Some data is still missing. Please check the steps above.</p>";
}

echo "<p><a href='Users/assistant/dispense_product.php'>Back to Dispense Product</a></p>";
?>
