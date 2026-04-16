<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }

$rx_id = 3;
$amount = 64.00;

echo "<h2>Direct Payment Test</h2>";
echo "<p>This will take you directly to the mock payment page.</p>";

echo "<h3>Option 1: Direct Link</h3>";
echo "<a href='payment_mock.php?rx_id=$rx_id&amount=$amount' style='display:inline-block; padding:20px; background:blue; color:white; text-decoration:none; font-size:20px;'>
    GO TO MOCK PAYMENT
</a>";

echo "<hr>";

echo "<h3>Option 2: Auto Redirect (wait 3 seconds)</h3>";
echo "<p>Redirecting in <span id='countdown'>3</span> seconds...</p>";

echo "<script>
let count = 3;
setInterval(function() {
    count--;
    document.getElementById('countdown').textContent = count;
    if (count <= 0) {
        window.location.href = 'payment_mock.php?rx_id=$rx_id&amount=$amount';
    }
}, 1000);
</script>";
?>
