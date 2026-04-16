<?php
require_once 'config.php';

$rx_id = 'RX-20260416-5770';

// Check prescription status
$s = $conn->prepare("SELECT * FROM prescriptions WHERE prescription_id=? LIMIT 1");
$s->bind_param('s', $rx_id);
$s->execute();
$rx = $s->get_result()->fetch_assoc();

echo "<h2>Prescription Status Check</h2>";
echo "<pre>";
if ($rx) {
    echo "Prescription ID: " . $rx['prescription_id'] . "\n";
    echo "Database ID: " . $rx['id'] . "\n";
    echo "Status: " . $rx['status'] . "\n";
    echo "Patient: " . $rx['patient_name'] . "\n";
    echo "Doctor: " . $rx['doctor_name'] . "\n";
    echo "Date: " . $rx['date_prescribed'] . "\n";
    echo "\n--- Full Data ---\n";
    print_r($rx);
} else {
    echo "Prescription not found!";
}

// Check if order exists
$so = $conn->prepare("SELECT * FROM prescription_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
$so->bind_param('i', $rx['id']);
$so->execute();
$order = $so->get_result()->fetch_assoc();

echo "\n\n--- Order Data ---\n";
if ($order) {
    print_r($order);
} else {
    echo "No order found for this prescription.";
}
echo "</pre>";
?>
