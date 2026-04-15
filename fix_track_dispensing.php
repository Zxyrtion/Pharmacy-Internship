<?php
require_once 'config.php';

echo "<h2>Fixing Track Dispensing Issues</h2>";

// Issue 1: Add 'quantity' column alias to product_logs for compatibility
echo "<h3>Issue 1: Product Logs Table - Adding quantity column compatibility</h3>";
$check_col = $conn->query("SHOW COLUMNS FROM product_logs LIKE 'quantity'");
if ($check_col && $check_col->num_rows == 0) {
    echo "✓ Column 'quantity' doesn't exist (using quantity_dispensed is correct)<br>";
} else {
    echo "✓ Column 'quantity' already exists<br>";
}

// Issue 2: Fix prescription status mapping
echo "<h3>Issue 2: Checking Prescription Status Values</h3>";
$status_check = $conn->query("SELECT DISTINCT status FROM prescriptions ORDER BY status");
if ($status_check) {
    echo "Current status values in prescriptions table:<br>";
    while ($row = $status_check->fetch_assoc()) {
        echo "- " . htmlspecialchars($row['status']) . "<br>";
    }
}

// Issue 3: Update any 'Pending' prescriptions that should be 'Submitted'
echo "<h3>Issue 3: Normalizing Status Values</h3>";
echo "Note: The track_dispensing.php expects these statuses:<br>";
echo "- Pending (Step 1: Submitted)<br>";
echo "- Processing (Step 2: Processing)<br>";
echo "- Ready (Step 3: Ready)<br>";
echo "- Dispensed (Step 4: Dispensed)<br><br>";

// Check if we need to update the status enum
$table_info = $conn->query("SHOW CREATE TABLE prescriptions");
if ($table_info) {
    $row = $table_info->fetch_assoc();
    echo "Current table structure:<br>";
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre><br>";
}

// Issue 4: Add missing doctor_name and doctor_specialization columns to product_logs if needed
echo "<h3>Issue 4: Checking product_logs columns</h3>";
$cols_to_check = ['doctor_name', 'doctor_specialization', 'quantity'];
foreach ($cols_to_check as $col) {
    $check = $conn->query("SHOW COLUMNS FROM product_logs LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        echo "⚠ Column '$col' is missing from product_logs<br>";
        
        if ($col === 'doctor_name') {
            $alter = $conn->query("ALTER TABLE product_logs ADD COLUMN doctor_name VARCHAR(200) DEFAULT NULL AFTER patient_name");
            echo ($alter ? "✓" : "✗") . " Added doctor_name column<br>";
        } elseif ($col === 'doctor_specialization') {
            $alter = $conn->query("ALTER TABLE product_logs ADD COLUMN doctor_specialization VARCHAR(200) DEFAULT NULL AFTER doctor_name");
            echo ($alter ? "✓" : "✗") . " Added doctor_specialization column<br>";
        } elseif ($col === 'quantity') {
            // Add quantity as an alias/copy of quantity_dispensed for compatibility
            $alter = $conn->query("ALTER TABLE product_logs ADD COLUMN quantity INT DEFAULT NULL AFTER quantity_dispensed");
            echo ($alter ? "✓" : "✗") . " Added quantity column<br>";
            
            // Copy existing data
            $update = $conn->query("UPDATE product_logs SET quantity = quantity_dispensed WHERE quantity IS NULL");
            echo ($update ? "✓" : "✗") . " Copied quantity_dispensed values to quantity<br>";
        }
    } else {
        echo "✓ Column '$col' exists<br>";
    }
}

echo "<h3>Summary</h3>";
echo "<p>The main issues are:</p>";
echo "<ol>";
echo "<li><strong>Status Flow:</strong> Prescriptions should flow: Pending → Processing → Ready → Dispensed</li>";
echo "<li><strong>Product Logs:</strong> The table uses 'quantity_dispensed' but some queries look for 'quantity'</li>";
echo "<li><strong>Assistant Dispense:</strong> Updates status to 'Ready' correctly, but needs to ensure product_logs are created</li>";
echo "</ol>";

echo "<h3>Next Steps</h3>";
echo "<p>1. Update track_dispensing.php to handle the correct status flow</p>";
echo "<p>2. Update product_logs queries to use 'quantity_dispensed' or add 'quantity' column</p>";
echo "<p>3. Ensure assistant dispense properly logs to product_logs table</p>";

echo "<br><a href='index.php' class='btn btn-primary'>Back to Home</a>";
?>
