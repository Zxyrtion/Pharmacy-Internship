<?php
require_once 'config.php';

echo "<h2>Fixing Prescriptions with patient_name = '0'</h2>";

// Find all prescriptions with patient_name = '0'
$result = $conn->query("SELECT p.id, p.customer_id, p.patient_name, p.patient_id, 
                        CONCAT(u.first_name, ' ', u.last_name) AS customer_full_name
                        FROM prescriptions p
                        LEFT JOIN users u ON p.customer_id = u.id
                        WHERE p.patient_name = '0' OR p.patient_name = '' OR p.patient_name IS NULL");

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " prescriptions with patient_name = '0', empty, or NULL</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Prescription ID</th><th>Current Patient Name</th><th>Customer Name</th><th>Action</th></tr>";
    
    $fixed_count = 0;
    while ($row = $result->fetch_assoc()) {
        $prescription_id = $row['id'];
        $current_patient_name = $row['patient_name'];
        $customer_full_name = $row['customer_full_name'] ?? 'Unknown Customer';
        
        echo "<tr>";
        echo "<td>$prescription_id</td>";
        echo "<td>" . htmlspecialchars($current_patient_name) . "</td>";
        echo "<td>" . htmlspecialchars($customer_full_name) . "</td>";
        
        // Update patient_name to customer's full name
        $update = $conn->prepare("UPDATE prescriptions SET patient_name = ? WHERE id = ?");
        $update->bind_param('si', $customer_full_name, $prescription_id);
        
        if ($update->execute()) {
            echo "<td style='color:green;'>✓ Fixed - Set to: " . htmlspecialchars($customer_full_name) . "</td>";
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
} else {
    echo "<p style='color:green;'>✓ No prescriptions found with patient_name = '0', empty, or NULL. All prescriptions are properly configured!</p>";
}

echo "<hr>";
echo "<p><a href='Users/pharmacist/prescriptions.php'>View Prescriptions (Pharmacist)</a></p>";
echo "<p><a href='Users/assistant/dispense_product.php'>View Dispense Product (Assistant)</a></p>";
echo "<p><a href='check_prescription_data.php'>Check Prescription Data</a></p>";
?>
