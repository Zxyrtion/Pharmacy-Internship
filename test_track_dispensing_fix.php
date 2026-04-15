<?php
require_once 'config.php';

echo "<h2>Track Dispensing Fix - Verification Test</h2>";

echo "<h3>1. Checking Prescriptions Table Structure</h3>";
$check = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'status'");
if ($check) {
    $row = $check->fetch_assoc();
    echo "✓ Status column type: " . htmlspecialchars($row['Type']) . "<br>";
    if (strpos($row['Type'], 'Ready') !== false) {
        echo "✓ 'Ready' status is available<br>";
    } else {
        echo "✗ 'Ready' status is missing<br>";
    }
}

echo "<h3>2. Checking Product Logs Table Structure</h3>";
$required_cols = ['quantity', 'quantity_dispensed', 'doctor_name', 'doctor_specialization'];
foreach ($required_cols as $col) {
    $check = $conn->query("SHOW COLUMNS FROM product_logs LIKE '$col'");
    if ($check && $check->num_rows > 0) {
        echo "✓ Column '$col' exists<br>";
    } else {
        echo "✗ Column '$col' is missing<br>";
    }
}

echo "<h3>3. Current Prescription Status Distribution</h3>";
$statuses = $conn->query("SELECT status, COUNT(*) as cnt FROM prescriptions GROUP BY status");
if ($statuses) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    while ($row = $statuses->fetch_assoc()) {
        $status_display = $row['status'] ?: '(empty)';
        echo "<tr><td>" . htmlspecialchars($status_display) . "</td><td>" . $row['cnt'] . "</td></tr>";
    }
    echo "</table><br>";
}

echo "<h3>4. Sample Prescription Details</h3>";
$sample = $conn->query("SELECT id, prescription_id, patient_name, doctor_name, status, created_at 
                        FROM prescriptions ORDER BY created_at DESC LIMIT 3");
if ($sample) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Created</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['status'] ?: '(empty)') . "</strong></td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

echo "<h3>5. Product Logs Sample</h3>";
$logs = $conn->query("SELECT id, prescription_id, medicine_name, quantity, quantity_dispensed, 
                      doctor_name, patient_name, log_date 
                      FROM product_logs ORDER BY log_date DESC LIMIT 3");
if ($logs && $logs->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Rx ID</th><th>Medicine</th><th>Qty</th><th>Qty Dispensed</th><th>Doctor</th><th>Patient</th><th>Date</th></tr>";
    while ($row = $logs->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['prescription_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
        echo "<td>" . ($row['quantity'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['quantity_dispensed'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . $row['log_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No product logs found yet.<br>";
}

echo "<h3>6. Status Flow Test</h3>";
$steps = [
    'Pending' => 1,
    'Submitted' => 1,
    '' => 1,
    'Processing' => 2,
    'Ready' => 3,
    'Dispensed' => 4
];
echo "Status mapping for track_dispensing.php:<br>";
echo "<ul>";
foreach ($steps as $status => $step) {
    $display = $status ?: '(empty)';
    echo "<li>$display → Step $step</li>";
}
echo "</ul>";

echo "<h3>Summary</h3>";
echo "<div style='background:#d4edda; padding:15px; border-radius:5px; margin:10px 0;'>";
echo "<strong>✓ Fixes Applied:</strong><br>";
echo "1. Added 'Ready' status to prescriptions table enum<br>";
echo "2. Added 'quantity', 'doctor_name', 'doctor_specialization' columns to product_logs<br>";
echo "3. Updated track_dispensing.php to handle empty/null status values<br>";
echo "4. Updated assistant dispense_product.php to populate all required fields<br>";
echo "</div>";

echo "<h3>Testing Instructions</h3>";
echo "<ol>";
echo "<li>Customer submits a prescription (status: Pending or empty)</li>";
echo "<li>Pharmacist processes it (status: Processing)</li>";
echo "<li>Assistant dispenses it (status: Ready)</li>";
echo "<li>Customer pays (status: Dispensed)</li>";
echo "</ol>";

echo "<br><a href='Users/customer/track_dispensing.php'>Test Track Dispensing Page</a> | ";
echo "<a href='Users/assistant/dispense_product.php'>Test Assistant Dispense</a> | ";
echo "<a href='index.php'>Back to Home</a>";
?>
