<?php
require_once 'config.php';

echo "<h2>Fixing Prescription Status Issues</h2>";

// Find prescriptions with status 'Processing' but no purchase orders
$result = $conn->query("SELECT p.id, p.prescription_id, p.patient_name, p.doctor_name, p.status
                        FROM prescriptions p
                        LEFT JOIN purchase_orders po ON po.prescription_id = p.id
                        WHERE p.status = 'Processing' AND po.id IS NULL");

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " prescriptions with status 'Processing' but no purchase orders</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Prescription ID</th><th>Patient</th><th>Doctor</th><th>Current Status</th><th>Action</th></tr>";
    
    $fixed_count = 0;
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $prescription_id = $row['prescription_id'];
        $patient_name = $row['patient_name'];
        $doctor_name = $row['doctor_name'];
        
        echo "<tr>";
        echo "<td>$id</td>";
        echo "<td>" . htmlspecialchars($prescription_id) . "</td>";
        echo "<td>" . htmlspecialchars($patient_name) . "</td>";
        echo "<td>" . htmlspecialchars($doctor_name) . "</td>";
        echo "<td>Processing</td>";
        
        // Reset status to Pending so pharmacist can process it properly
        $update = $conn->prepare("UPDATE prescriptions SET status = 'Pending' WHERE id = ?");
        $update->bind_param('i', $id);
        
        if ($update->execute()) {
            echo "<td style='color:green;'>✓ Reset to 'Pending'</td>";
            $fixed_count++;
        } else {
            echo "<td style='color:red;'>✗ Failed: " . $conn->error . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<p><strong>Fixed $fixed_count prescriptions</strong></p>";
    echo "<p>These prescriptions have been reset to 'Pending' status. The pharmacist needs to:</p>";
    echo "<ol>";
    echo "<li>Go to the Pharmacist Prescriptions page</li>";
    echo "<li>Click 'Process' on each prescription</li>";
    echo "<li>Set unit prices for each medicine</li>";
    echo "<li>Click 'Confirm Purchase Order'</li>";
    echo "</ol>";
    echo "<p>After that, the assistant will be able to dispense the medicines.</p>";
} else {
    echo "<p style='color:green;'>✓ No prescriptions found with status issues!</p>";
}

echo "<hr>";
echo "<p><a href='Users/pharmacist/prescriptions.php'>Go to Pharmacist Prescriptions</a></p>";
echo "<p><a href='debug_dispense_issue.php'>Check Dispense Status Again</a></p>";
?>
