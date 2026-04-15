<?php
require_once 'config.php';

echo "<h3>Checking Customer Prescriptions Data</h3>";

// Get customer guy's user_id
$user_query = $conn->query("SELECT id, username, full_name FROM users WHERE username='customer guy'");
if ($user_query && $user = $user_query->fetch_assoc()) {
    echo "<p>Customer: {$user['full_name']} (ID: {$user['id']})</p>";
    
    $customer_id = $user['id'];
    
    // Check prescriptions
    $rx_query = $conn->query("SELECT * FROM prescriptions WHERE customer_id = $customer_id ORDER BY created_at DESC LIMIT 5");
    
    if ($rx_query && $rx_query->num_rows > 0) {
        echo "<h4>Prescriptions found: {$rx_query->num_rows}</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Prescription ID</th><th>Patient Name</th><th>Doctor Name</th><th>Date</th><th>Status</th></tr>";
        
        while ($row = $rx_query->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['prescription_id']}</td>";
            echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
            echo "<td>{$row['date_prescribed']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No prescriptions found for this customer.</p>";
    }
} else {
    echo "<p>Customer 'customer guy' not found in users table.</p>";
}
?>
