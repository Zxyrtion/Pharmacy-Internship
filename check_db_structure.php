<?php
require_once 'config.php';

echo "<h2>Requisition Reports Table Structure</h2>";
if (isset($conn)) {
    $result = $conn->query("DESCRIBE requisition_reports");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Sample Data</h2>";
    $result = $conn->query("SELECT id, technician_id, po_number, status FROM requisition_reports LIMIT 5");
    echo "<table border='1'><tr><th>ID</th><th>Technician ID</th><th>PO Number</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['technician_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['po_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
