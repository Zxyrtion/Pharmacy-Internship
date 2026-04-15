<?php
require_once 'config.php';

echo "<h2>Checking Prescription Medicines for #5 and #6</h2>";

// Check if there are medicine items for these prescriptions
$result = $conn->query("SELECT * FROM prescriptions WHERE prescription_id IN (
    SELECT prescription_id FROM prescriptions WHERE id IN (5, 6)
) ORDER BY prescription_id, id");

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " medicine items</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Prescription ID</th><th>Medicine Name</th><th>Dosage/Generic</th><th>Quantity</th><th>Instructions</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['dosage'] ?? '-') . "</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>" . htmlspecialchars($row['instructions'] ?? '-') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>⚠ No medicine items found for these prescriptions!</p>";
    echo "<p>This means the prescriptions were submitted without any medicines, which is why the pharmacist can't process them.</p>";
}

echo "<hr>";
echo "<p><a href='Users/pharmacist/prescriptions.php'>Go to Pharmacist Prescriptions</a></p>";
?>
