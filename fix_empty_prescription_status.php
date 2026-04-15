<?php
require_once 'config.php';

echo "<h2>Fixing Empty Prescription Status Values</h2>";

// Update empty status values to 'Pending'
$update_sql = "UPDATE prescriptions SET status = 'Pending' WHERE status IS NULL OR status = ''";
if ($conn->query($update_sql)) {
    $affected = $conn->affected_rows;
    echo "✓ Updated $affected prescriptions with empty status to 'Pending'<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// Verify the fix
echo "<br><h3>Current Status Distribution</h3>";
$check = $conn->query("SELECT status, COUNT(*) as cnt FROM prescriptions GROUP BY status");
if ($check) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    while ($row = $check->fetch_assoc()) {
        $status_display = $row['status'] ?: '(empty)';
        echo "<tr><td>" . htmlspecialchars($status_display) . "</td><td>" . $row['cnt'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<br><a href='test_track_dispensing_fix.php'>Run Verification Test</a> | ";
echo "<a href='Users/customer/track_dispensing.php'>View Track Dispensing</a> | ";
echo "<a href='index.php'>Back to Home</a>";
?>
