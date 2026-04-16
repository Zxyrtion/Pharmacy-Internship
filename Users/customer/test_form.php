<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1 style='color:green;'>POST RECEIVED!</h1>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "<p><a href='payment.php?rx_id=RX-20260416-5770'>Back to payment</a></p>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Form</title>
</head>
<body>
    <h2>Test Form Submission</h2>
    <form method="POST">
        <button type="submit" name="pay_paymongo" value="1" style="padding:20px; font-size:20px; background:blue; color:white;">
            CLICK ME TO TEST
        </button>
    </form>
</body>
</html>
