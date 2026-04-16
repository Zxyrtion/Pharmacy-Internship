<?php
require_once 'config.php';
require_once 'core/paymongo.php';

echo "<h2>Testing Payment POST</h2>";

$rx_id = 3;
$amount = 64.00;

echo "<p>Testing createCheckoutSession function...</p>";

$result = createCheckoutSession($amount, "Test Prescription #$rx_id", $rx_id, []);

echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "<p style='color:green;'>✓ Function works!</p>";
    echo "<p>Checkout URL: <a href='{$result['checkout_url']}'>{$result['checkout_url']}</a></p>";
} else {
    echo "<p style='color:red;'>✗ Function failed!</p>";
}

echo "<hr>";
echo "<h3>Test Form Submission</h3>";
echo "<form method='POST' action='Users/customer/payment.php?rx_id=RX-20260416-5770'>";
echo "<button type='submit' name='pay_paymongo' value='1' style='padding:1rem 2rem; font-size:18px; background:blue; color:white; border:none; cursor:pointer;'>Test Submit</button>";
echo "</form>";
?>
