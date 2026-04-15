<?php
require_once 'config.php';

echo "<h2>Fixing Prescriptions with patient_id = 0</h2>";

// Find all prescriptions with patient_id = 0
$result = $conn->query("SELECT id, customer_id, patient_name FROM prescriptions WHERE patient_id = 0 OR patient_id IS NULL");

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " prescriptions with patient_id = 0 or NULL</p>";
    
    $fixed_count = 0;
    while ($row = $result->fetch_assoc()) {
        $prescription_id = $row['id'];
        $customer_id = $row['customer_id'];
        $patient_name = $row['patient_name'];
        
        // Update patient_id to match customer_id
        $update = $conn->prepare("UPDATE prescriptions SET patient_id = ? WHERE id = ?");
        $update->bind_param('ii', $customer_id, $prescription_id);
        
        if ($update->execute()) {
            echo "<p style='color:green;'>✓ Fixed prescription #$prescription_id (Patient: $patient_name) - Set patient_id to $customer_id</p>";
            $fixed_count++;
        } else {
            echo "<p style='color:red;'>✗ Failed to fix prescription #$prescription_id: " . $conn->error . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<p><strong>Fixed $fixed_count prescriptions</strong></p>";
} else {
    echo "<p style='color:green;'>✓ No prescriptions found with patient_id = 0 or NULL. All prescriptions are properly configured!</p>";
}

echo "<hr>";
echo "<p><a href='Users/pharmacist/prescriptions.php'>View Prescriptions (Pharmacist)</a></p>";
echo "<p><a href='Users/assistant/dispense_product.php'>View Dispense Product (Assistant)</a></p>";
?>
