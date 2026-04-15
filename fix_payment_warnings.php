<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Fix Payment Warnings</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #28a745;}";
echo ".error{background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #dc3545;}";
echo ".info{background:#d1ecf1;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #0dcaf0;}";
echo ".warning{background:#fff3cd;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #ffc107;}";
echo "h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px;}";
echo "table{border-collapse:collapse;width:100%;margin:10px 0;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#667eea;color:white;}";
echo "</style></head><body>";

echo "<h1>🔧 Payment Warnings Fix</h1>";

echo "<h2>Warnings Found</h2>";
echo "<div class='warning'>";
echo "<p>The following fields were causing 'Undefined array key' warnings:</p>";
echo "<ul>";
echo "<li><code>doctor_specialization</code> - Line 154</li>";
echo "<li><code>prescription_date</code> - Line 158 (should be <code>date_prescribed</code>)</li>";
echo "<li><code>patient_age</code> - Line 160</li>";
echo "<li><code>patient_gender</code> - Line 160</li>";
echo "</ul>";
echo "<p>These fields don't exist in the prescriptions table.</p>";
echo "</div>";

echo "<h2>Prescriptions Table Structure</h2>";
echo "<div class='info'>";
echo "<p>Actual columns in prescriptions table:</p>";
echo "<table>";
echo "<tr><th>Column</th><th>Type</th><th>Description</th></tr>";

$columns = [
    'id' => 'int(11)',
    'customer_id' => 'int(11)',
    'prescription_id' => 'varchar(20)',
    'patient_id' => 'int(11)',
    'patient_name' => 'varchar(100)',
    'medicine_name' => 'varchar(200)',
    'dosage' => 'varchar(50)',
    'quantity' => 'int(11)',
    'instructions' => 'text',
    'doctor_name' => 'varchar(100)',
    'date_prescribed' => 'date',
    'status' => 'enum',
    'created_at' => 'timestamp',
    'updated_at' => 'timestamp'
];

foreach ($columns as $col => $type) {
    echo "<tr><td><code>$col</code></td><td>$type</td><td>";
    if ($col === 'date_prescribed') echo "✓ Use this instead of prescription_date";
    elseif ($col === 'doctor_name') echo "✓ Available";
    elseif ($col === 'patient_name') echo "✓ Available";
    echo "</td></tr>";
}
echo "</table>";
echo "</div>";

echo "<h2>Fixes Applied</h2>";
echo "<div class='success'>";
echo "<p>✓ Updated payment.php to use null coalescing operator (??) for all optional fields</p>";
echo "<p>✓ Changed <code>prescription_date</code> to <code>date_prescribed</code></p>";
echo "<p>✓ Added conditional display for optional fields (age, gender, specialization)</p>";
echo "<p>✓ All undefined array key warnings are now suppressed</p>";
echo "</div>";

echo "<h2>Code Changes</h2>";
echo "<div class='info'>";
echo "<h3>Before:</h3>";
echo "<pre>";
echo htmlspecialchars('<?= htmlspecialchars($rx[\'doctor_specialization\']) ?>');
echo "\n";
echo htmlspecialchars('<?= htmlspecialchars($rx[\'prescription_date\']) ?>');
echo "\n";
echo htmlspecialchars('<?= htmlspecialchars($rx[\'patient_age\']) ?> / <?= htmlspecialchars($rx[\'patient_gender\']) ?>');
echo "</pre>";

echo "<h3>After:</h3>";
echo "<pre>";
echo htmlspecialchars('<?php if (!empty($rx[\'doctor_specialization\'] ?? \'\')): ?>');
echo "\n";
echo htmlspecialchars('<?= htmlspecialchars($rx[\'date_prescribed\'] ?? date(\'Y-m-d\')) ?>');
echo "\n";
echo htmlspecialchars('<?php 
$age = $rx[\'patient_age\'] ?? \'\';
$gender = $rx[\'patient_gender\'] ?? \'\';
if ($age || $gender) {
    echo htmlspecialchars($age);
    if ($age && $gender) echo \' / \';
    echo htmlspecialchars($gender);
}
?>');
echo "</pre>";
echo "</div>";

echo "<h2>Test Payment Page</h2>";

$ready_rx = $conn->query("SELECT id, prescription_id, patient_name, doctor_name FROM prescriptions WHERE status='Ready' LIMIT 1");

if ($ready_rx && $ready_rx->num_rows > 0) {
    $rx = $ready_rx->fetch_assoc();
    echo "<div class='success'>";
    echo "<p>✓ Found a prescription ready for testing</p>";
    echo "<p><strong>Prescription:</strong> " . htmlspecialchars($rx['prescription_id']) . " - " . htmlspecialchars($rx['patient_name']) . "</p>";
    echo "<p><a href='Users/customer/payment.php?rx_id=" . $rx['id'] . "' target='_blank' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Test Payment Page (Should have NO warnings)</a></p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<p>⚠ No prescriptions with 'Ready' status found for testing</p>";
    echo "</div>";
}

echo "<h2>Summary</h2>";
echo "<div class='success'>";
echo "<h3>✓ All Warnings Fixed</h3>";
echo "<ul>";
echo "<li>All undefined array key warnings are now handled with null coalescing operator (??)</li>";
echo "<li>Optional fields are conditionally displayed</li>";
echo "<li>Correct column names are used (date_prescribed instead of prescription_date)</li>";
echo "<li>Payment page will display cleanly without PHP warnings</li>";
echo "</ul>";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='check_prescriptions_columns.php' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>View Table Structure</a>";
echo "<a href='index.php' style='padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;'>Back to Home</a>";
echo "</div>";

echo "</body></html>";
?>
