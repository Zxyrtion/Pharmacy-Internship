<?php
require_once 'config.php';

echo "<h2>Adding Remarks Column to requisition_reports</h2>";

if (!isset($conn)) {
    die("<div style='color: red;'>Database connection failed</div>");
}

// Check if remarks column exists
$check = $conn->query("SHOW COLUMNS FROM requisition_reports LIKE 'remarks'");
if ($check && $check->num_rows > 0) {
    echo "<div style='color: green;'>✓ 'remarks' column already exists</div>";
} else {
    // Add remarks column
    $sql = "ALTER TABLE requisition_reports ADD COLUMN remarks TEXT DEFAULT NULL AFTER status";
    if ($conn->query($sql)) {
        echo "<div style='color: green;'>✓ 'remarks' column added successfully</div>";
    } else {
        echo "<div style='color: red;'>✗ Error adding column: " . $conn->error . "</div>";
    }
}

echo "<p><a href='Users/pharmacist/view_requisitions.php'>Go to View Requisitions</a></p>";
?>
