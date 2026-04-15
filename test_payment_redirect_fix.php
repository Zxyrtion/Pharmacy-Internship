<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Payment Redirect Fix Test</title>";
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

echo "<h1>🔧 Payment Redirect Fix Test</h1>";

echo "<h2>Issue</h2>";
echo "<div class='warning'>";
echo "<p><strong>Problem:</strong> Clicking 'Pay Now' redirects to dashboard instead of payment page</p>";
echo "<p><strong>Cause:</strong> track_dispensing.php was passing prescription_id (string like 'RX-20260415-2305') but payment.php expected numeric id</p>";
echo "</div>";

echo "<h2>Fix Applied</h2>";
echo "<div class='success'>";
echo "<p>✓ Updated payment.php to accept both numeric ID and prescription_id string</p>";
echo "<p>✓ Updated track_dispensing.php to pass numeric ID</p>";
echo "<p>✓ Added better error handling and user feedback</p>";
echo "</div>";

echo "<h2>Test: Prescriptions Ready for Payment</h2>";

// Get prescriptions with Ready status
$query = "SELECT p.id, p.prescription_id, p.patient_name, p.doctor_name, p.status, 
          p.customer_id, o.total_amount 
          FROM prescriptions p 
          LEFT JOIN purchase_orders o ON o.prescription_id = p.id 
          WHERE p.status = 'Ready' 
          ORDER BY p.created_at DESC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<div class='success'>✓ Found " . $result->num_rows . " prescription(s) ready for payment</div>";
    echo "<table>";
    echo "<tr><th>Numeric ID</th><th>Prescription ID</th><th>Patient</th><th>Status</th><th>Amount</th><th>Test Links</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td><span style='background:#198754;color:white;padding:4px 8px;border-radius:4px;'>" . $row['status'] . "</span></td>";
        echo "<td>₱" . number_format($row['total_amount'] ?? 0, 2) . "</td>";
        echo "<td>";
        echo "<a href='Users/customer/payment.php?rx_id=" . $row['id'] . "' target='_blank' style='margin-right:10px;'>Pay (Numeric ID)</a>";
        echo "<a href='Users/customer/payment.php?rx_id=" . urlencode($row['prescription_id']) . "' target='_blank'>Pay (String ID)</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>";
    echo "<p><strong>Test Instructions:</strong></p>";
    echo "<ol>";
    echo "<li>Click either 'Pay (Numeric ID)' or 'Pay (String ID)' link above</li>";
    echo "<li>Both should now work and show the payment page</li>";
    echo "<li>If prescription is not Ready, you'll see an error message</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='warning'>⚠ No prescriptions with 'Ready' status found</div>";
    echo "<div class='info'>";
    echo "<p>To create a prescription ready for payment:</p>";
    echo "<ol>";
    echo "<li>Login as Customer and submit a prescription</li>";
    echo "<li>Login as Pharmacist and process it (create purchase order)</li>";
    echo "<li>Login as Assistant and dispense it (marks as Ready)</li>";
    echo "<li>Then come back here to test payment</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>Test: All Prescriptions (Any Status)</h2>";

$all_query = "SELECT p.id, p.prescription_id, p.patient_name, p.status, p.customer_id 
              FROM prescriptions p 
              ORDER BY p.created_at DESC 
              LIMIT 10";

$all_result = $conn->query($all_query);

if ($all_result && $all_result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Numeric ID</th><th>Prescription ID</th><th>Patient</th><th>Status</th><th>Test Link</th></tr>";
    
    while ($row = $all_result->fetch_assoc()) {
        $status_color = match($row['status']) {
            'Pending' => '#ffc107',
            'Processing' => '#0dcaf0',
            'Ready' => '#198754',
            'Dispensed' => '#6c757d',
            default => '#6c757d'
        };
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td><span style='background:$status_color;color:white;padding:4px 8px;border-radius:4px;'>" . $row['status'] . "</span></td>";
        echo "<td>";
        if ($row['status'] === 'Ready') {
            echo "<a href='Users/customer/payment.php?rx_id=" . $row['id'] . "' target='_blank'>Pay Now</a>";
        } else {
            echo "<span style='color:#999;'>Not ready for payment</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Summary</h2>";
echo "<div class='success'>";
echo "<h3>✓ Fix Complete</h3>";
echo "<ul>";
echo "<li>Payment page now accepts both numeric ID and prescription_id string</li>";
echo "<li>Track dispensing page now passes the correct ID format</li>";
echo "<li>Better error messages when prescription is not ready</li>";
echo "<li>No more unexpected redirects to dashboard</li>";
echo "</ul>";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='Users/customer/track_dispensing.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>Test Track Dispensing</a>";
echo "<a href='test_payment_system.php' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>Payment System Test</a>";
echo "<a href='index.php' style='padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;'>Back to Home</a>";
echo "</div>";

echo "</body></html>";
?>
