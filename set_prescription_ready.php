<?php
require_once 'config.php';

$rx_id = 'RX-20260416-5770';

// Update prescription to Ready status
$s = $conn->prepare("UPDATE prescriptions SET status='Ready' WHERE prescription_id=?");
$s->bind_param('s', $rx_id);

if ($s->execute()) {
    echo "<h2>Success!</h2>";
    echo "<p>Prescription <strong>$rx_id</strong> has been set to <span style='color:green;font-weight:bold;'>Ready</span> status.</p>";
    echo "<p>You can now test the payment flow:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='Users/customer/dashboard.php'>Customer Dashboard</a></li>";
    echo "<li>Click 'Pay Now' button for this prescription</li>";
    echo "<li>You should see the payment page</li>";
    echo "<li>Click 'Pay Securely via PayMongo'</li>";
    echo "<li>You'll be redirected to payment_mock.php</li>";
    echo "<li>Click 'Simulate Successful Payment'</li>";
    echo "<li>You'll see the success modal and auto-redirect to dashboard</li>";
    echo "</ol>";
    
    // Check current status
    $check = $conn->prepare("SELECT status FROM prescriptions WHERE prescription_id=?");
    $check->bind_param('s', $rx_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    echo "<p>Current status: <strong>" . $result['status'] . "</strong></p>";
} else {
    echo "<h2>Error!</h2>";
    echo "<p>Failed to update prescription status: " . $conn->error . "</p>";
}
?>
