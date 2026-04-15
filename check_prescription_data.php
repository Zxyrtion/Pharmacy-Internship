<?php
require_once 'config.php';

echo "<h2>Checking All Prescriptions Data</h2>";

// Get all prescriptions with customer info
$result = $conn->query("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name 
                        FROM prescriptions p 
                        LEFT JOIN users u ON p.customer_id = u.id 
                        ORDER BY p.id DESC 
                        LIMIT 10");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>Prescription ID</th>
            <th>Customer ID</th>
            <th>Customer Name</th>
            <th>Patient ID</th>
            <th>Patient Name</th>
            <th>Doctor</th>
            <th>Status</th>
          </tr>";
    
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['patient_id'] == 0 || $row['patient_id'] === null) ? "style='background-color: #ffcccc;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id'] ?? '-') . "</td>";
        echo "<td>{$row['customer_id']}</td>";
        echo "<td>" . htmlspecialchars($row['customer_name'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['patient_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name'] ?? '-') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color:red;'>Red rows indicate patient_id = 0 or NULL</p>";
} else {
    echo "<p>No prescriptions found</p>";
}

echo "<hr>";
echo "<h3>Check Users Table:</h3>";
$users = $conn->query("SELECT id, username, first_name, last_name, role_name FROM users WHERE role_name = 'Customer' LIMIT 5");
if ($users && $users->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>First Name</th><th>Last Name</th><th>Role</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
        echo "<td>{$user['role_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
