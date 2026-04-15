<?php
require_once 'config.php';
require_once 'core/paymongo.php';

echo "<!DOCTYPE html><html><head><title>Payment System Test</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #28a745;}";
echo ".error{background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #dc3545;}";
echo ".info{background:#d1ecf1;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #0dcaf0;}";
echo ".warning{background:#fff3cd;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #ffc107;}";
echo "h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px;}";
echo "table{border-collapse:collapse;width:100%;margin:10px 0;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#667eea;color:white;}";
echo ".badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;}";
echo ".badge-success{background:#28a745;color:white;}";
echo ".badge-warning{background:#ffc107;color:#000;}";
echo ".badge-info{background:#0dcaf0;color:#000;}";
echo "</style></head><body>";

echo "<h1>💳 Payment System Test</h1>";

// Test 1: Check if paymongo.php exists and loads
echo "<h2>Test 1: PayMongo Integration File</h2>";
if (file_exists('core/paymongo.php')) {
    echo "<div class='success'>✓ core/paymongo.php exists</div>";
    
    if (defined('PAYMONGO_MOCK_MODE') && PAYMONGO_MOCK_MODE) {
        echo "<div class='info'>ℹ Running in MOCK MODE (Development)</div>";
        echo "<div class='info'>No real PayMongo API keys required</div>";
    } else {
        echo "<div class='warning'>⚠ Running in PRODUCTION MODE</div>";
        echo "<div class='info'>Using real PayMongo API keys</div>";
    }
} else {
    echo "<div class='error'>✗ core/paymongo.php is missing!</div>";
}

// Test 2: Check payments table
echo "<h2>Test 2: Database - Payments Table</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'payments'");
if ($check_table && $check_table->num_rows > 0) {
    echo "<div class='success'>✓ payments table exists</div>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE payments");
    if ($structure) {
        echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show payment records
    $payments = $conn->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 5");
    if ($payments && $payments->num_rows > 0) {
        echo "<h3>Recent Payments</h3>";
        echo "<table><tr><th>ID</th><th>Prescription</th><th>Amount</th><th>Method</th><th>Status</th><th>Created</th></tr>";
        while ($row = $payments->fetch_assoc()) {
            $status_class = $row['status'] === 'Paid' ? 'success' : ($row['status'] === 'Failed' ? 'error' : 'warning');
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>#" . $row['prescription_id'] . "</td>";
            echo "<td>₱" . number_format($row['amount_due'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
            echo "<td><span class='badge badge-" . $status_class . "'>" . $row['status'] . "</span></td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ No payment records yet</div>";
    }
} else {
    echo "<div class='error'>✗ payments table does not exist</div>";
    echo "<div class='info'>The table will be created automatically when you visit the payment page</div>";
}

// Test 3: Check prescriptions ready for payment
echo "<h2>Test 3: Prescriptions Ready for Payment</h2>";
$ready_rx = $conn->query("SELECT p.id, p.prescription_id, p.patient_name, p.doctor_name, p.status, o.total_amount 
                          FROM prescriptions p 
                          LEFT JOIN purchase_orders o ON o.prescription_id = p.id 
                          WHERE p.status = 'Ready' 
                          ORDER BY p.created_at DESC 
                          LIMIT 5");

if ($ready_rx && $ready_rx->num_rows > 0) {
    echo "<div class='success'>✓ Found " . $ready_rx->num_rows . " prescription(s) ready for payment</div>";
    echo "<table><tr><th>ID</th><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Amount</th><th>Action</th></tr>";
    while ($row = $ready_rx->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
        echo "<td>₱" . number_format($row['total_amount'] ?? 0, 2) . "</td>";
        echo "<td><a href='Users/customer/payment.php?rx_id=" . $row['id'] . "' target='_blank'>Pay Now</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠ No prescriptions ready for payment</div>";
    echo "<div class='info'>To test payment:</div>";
    echo "<ol>";
    echo "<li>Submit a prescription as Customer</li>";
    echo "<li>Process it as Pharmacist</li>";
    echo "<li>Dispense it as Assistant</li>";
    echo "<li>Then it will be ready for payment</li>";
    echo "</ol>";
}

// Test 4: Test mock payment function
echo "<h2>Test 4: Mock Payment Function Test</h2>";
if (function_exists('createCheckoutSession')) {
    echo "<div class='success'>✓ createCheckoutSession() function exists</div>";
    
    // Test creating a mock session
    $test_line_items = [
        [
            'currency' => 'PHP',
            'amount' => 10000, // 100.00 PHP in centavos
            'name' => 'Test Medicine',
            'quantity' => 1
        ]
    ];
    
    $result = createCheckoutSession(100.00, 'Test Payment', 999, $test_line_items);
    
    if ($result['success']) {
        echo "<div class='success'>✓ Mock checkout session created successfully</div>";
        echo "<div class='info'>Session ID: " . htmlspecialchars($result['session_id']) . "</div>";
        echo "<div class='info'>Checkout URL: " . htmlspecialchars($result['checkout_url']) . "</div>";
    } else {
        echo "<div class='error'>✗ Failed to create checkout session</div>";
        echo "<div class='error'>Error: " . htmlspecialchars($result['error']) . "</div>";
    }
} else {
    echo "<div class='error'>✗ createCheckoutSession() function not found</div>";
}

// Test 5: Check required files
echo "<h2>Test 5: Required Files Check</h2>";
$required_files = [
    'core/paymongo.php' => 'PayMongo integration',
    'Users/customer/payment.php' => 'Payment page',
    'Users/customer/payment_mock.php' => 'Mock payment page',
    'Users/customer/payment_success.php' => 'Payment success page'
];

$all_exist = true;
foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✓ $file - $description</div>";
    } else {
        echo "<div class='error'>✗ $file - $description (MISSING)</div>";
        $all_exist = false;
    }
}

// Summary
echo "<h2>Summary</h2>";
if ($all_exist) {
    echo "<div class='success'>";
    echo "<h3>✓ Payment System is Ready!</h3>";
    echo "<p>All required files are in place and the system is configured correctly.</p>";
    echo "<p><strong>Current Mode:</strong> " . (defined('PAYMONGO_MOCK_MODE') && PAYMONGO_MOCK_MODE ? 'Development (Mock Payments)' : 'Production (Real Payments)') . "</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠ Some Files Are Missing</h3>";
    echo "<p>Please ensure all required files are created.</p>";
    echo "</div>";
}

echo "<h2>Quick Links</h2>";
echo "<div class='info'>";
echo "<ul>";
echo "<li><a href='Users/customer/payment.php?rx_id=1' target='_blank'>Test Payment Page</a> (may error if prescription #1 doesn't exist)</li>";
echo "<li><a href='Users/customer/track_dispensing.php' target='_blank'>Track Dispensing</a></li>";
echo "<li><a href='Users/customer/dashboard.php' target='_blank'>Customer Dashboard</a></li>";
echo "<li><a href='PAYMENT_SETUP_GUIDE.md' target='_blank'>Payment Setup Guide</a></li>";
echo "</ul>";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='index.php' style='padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;'>Back to Home</a>";
echo "</div>";

echo "</body></html>";
?>
