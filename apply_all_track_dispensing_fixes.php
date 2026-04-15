<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Track Dispensing - Apply All Fixes</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #28a745;}";
echo ".error{background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #dc3545;}";
echo ".info{background:#d1ecf1;padding:10px;margin:10px 0;border-radius:5px;border-left:4px solid #0dcaf0;}";
echo "h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px;}";
echo "table{border-collapse:collapse;width:100%;margin:10px 0;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#667eea;color:white;}";
echo "</style></head><body>";

echo "<h1>🔧 Track Dispensing - Complete Fix Application</h1>";

$errors = [];
$success = [];

// Fix 1: Add 'Ready' status to prescriptions table
echo "<h2>Fix 1: Update Prescriptions Table Status Enum</h2>";
$sql = "ALTER TABLE prescriptions 
        MODIFY COLUMN status ENUM('Pending','Processing','Ready','Dispensed','Cancelled') 
        DEFAULT 'Pending'";
if ($conn->query($sql)) {
    $success[] = "✓ Added 'Ready' status to prescriptions table";
    echo "<div class='success'>✓ Successfully updated prescriptions status enum</div>";
} else {
    if (strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'Ready') !== false) {
        $success[] = "✓ 'Ready' status already exists in prescriptions table";
        echo "<div class='info'>ℹ 'Ready' status already exists</div>";
    } else {
        $errors[] = "✗ Failed to update prescriptions status: " . $conn->error;
        echo "<div class='error'>✗ Error: " . htmlspecialchars($conn->error) . "</div>";
    }
}

// Fix 2: Add missing columns to product_logs
echo "<h2>Fix 2: Update Product Logs Table Structure</h2>";

$columns_to_add = [
    ['name' => 'doctor_name', 'definition' => 'VARCHAR(200) DEFAULT NULL', 'after' => 'patient_name'],
    ['name' => 'doctor_specialization', 'definition' => 'VARCHAR(200) DEFAULT NULL', 'after' => 'doctor_name'],
    ['name' => 'quantity', 'definition' => 'INT DEFAULT NULL', 'after' => 'quantity_dispensed']
];

foreach ($columns_to_add as $col) {
    $check = $conn->query("SHOW COLUMNS FROM product_logs LIKE '{$col['name']}'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE product_logs ADD COLUMN {$col['name']} {$col['definition']} AFTER {$col['after']}";
        if ($conn->query($sql)) {
            $success[] = "✓ Added '{$col['name']}' column to product_logs";
            echo "<div class='success'>✓ Added column: {$col['name']}</div>";
            
            // If it's the quantity column, copy data from quantity_dispensed
            if ($col['name'] === 'quantity') {
                $conn->query("UPDATE product_logs SET quantity = quantity_dispensed WHERE quantity IS NULL");
                echo "<div class='info'>ℹ Copied quantity_dispensed values to quantity column</div>";
            }
        } else {
            $errors[] = "✗ Failed to add '{$col['name']}': " . $conn->error;
            echo "<div class='error'>✗ Error adding {$col['name']}: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        $success[] = "✓ Column '{$col['name']}' already exists";
        echo "<div class='info'>ℹ Column '{$col['name']}' already exists</div>";
    }
}

// Fix 3: Update empty status values
echo "<h2>Fix 3: Clean Up Empty Status Values</h2>";
$sql = "UPDATE prescriptions SET status = 'Pending' WHERE status IS NULL OR status = ''";
if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        $success[] = "✓ Updated $affected prescriptions with empty status";
        echo "<div class='success'>✓ Fixed $affected prescriptions with empty status values</div>";
    } else {
        echo "<div class='info'>ℹ No prescriptions with empty status found</div>";
    }
} else {
    $errors[] = "✗ Failed to update empty status values: " . $conn->error;
    echo "<div class='error'>✗ Error: " . htmlspecialchars($conn->error) . "</div>";
}

// Verification
echo "<h2>Verification Results</h2>";

echo "<h3>Prescriptions Status Distribution</h3>";
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM prescriptions GROUP BY status ORDER BY status");
if ($result) {
    echo "<table><tr><th>Status</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?: '(empty)';
        echo "<tr><td>" . htmlspecialchars($status) . "</td><td>" . $row['cnt'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>Product Logs Table Structure</h3>";
$result = $conn->query("SHOW COLUMNS FROM product_logs");
if ($result) {
    echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Recent Prescriptions</h3>";
$result = $conn->query("SELECT id, prescription_id, patient_name, doctor_name, status, created_at 
                        FROM prescriptions ORDER BY created_at DESC LIMIT 5");
if ($result) {
    echo "<table><tr><th>ID</th><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['status']) . "</strong></td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Summary
echo "<h2>Summary</h2>";
if (empty($errors)) {
    echo "<div class='success'>";
    echo "<h3>✓ All Fixes Applied Successfully!</h3>";
    echo "<p>Total successful operations: " . count($success) . "</p>";
    echo "<ul>";
    foreach ($success as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠ Some Fixes Failed</h3>";
    echo "<p>Errors encountered:</p>";
    echo "<ul>";
    foreach ($errors as $err) {
        echo "<li>$err</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    if (!empty($success)) {
        echo "<div class='info'>";
        echo "<p>Successful operations:</p>";
        echo "<ul>";
        foreach ($success as $msg) {
            echo "<li>$msg</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li>Test the customer track dispensing page: <a href='Users/customer/track_dispensing.php'>Track Dispensing</a></li>";
echo "<li>Test the assistant dispense flow: <a href='Users/assistant/dispense_product.php'>Dispense Product</a></li>";
echo "<li>Verify product logs are being created: <a href='Users/pharmacist/product_logs.php'>Product Logs</a></li>";
echo "<li>Submit a test prescription and follow it through the complete workflow</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='test_track_dispensing_fix.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>Run Verification Test</a>";
echo "<a href='index.php' style='padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;'>Back to Home</a>";
echo "</div>";

echo "</body></html>";
?>
