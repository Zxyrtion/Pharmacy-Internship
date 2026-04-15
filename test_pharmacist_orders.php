<?php
require_once 'config.php';

echo "<h1>Pharmacist Orders Test</h1>";

// Test exact query used in view_requisitions.php
$sql = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id ORDER BY r.po_date DESC, r.id DESC";
$res = $conn->query($sql);

if ($res) {
    echo "<h2>Current Orders in requisition_reports:</h2>";
    if ($res->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>PO Number</th><th>Date</th><th>Technician</th><th>Vendor</th><th>Total (₱)</th><th>Status</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['po_number']) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['po_date'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['technician_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($row['vendor_company'] ?? 'Not specified') . "</td>";
            echo "<td style='text-align: right; font-weight: bold;'>₱" . number_format($row['total'], 2) . "</td>";
            echo "<td style='text-align: center;'>";
            if ($row['status'] === 'Pending') {
                echo "<span style='background: #ffc107; color: #000; padding: 2px 8px; border-radius: 4px;'>Pending</span>";
            } elseif ($row['status'] === 'Approved') {
                echo "<span style='background: #28a745; color: white; padding: 2px 8px; border-radius: 4px;'>Approved</span>";
            } else {
                echo "<span style='background: #dc3545; color: white; padding: 2px 8px; border-radius: 4px;'>Rejected</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✓ Found " . $res->num_rows . " orders</p>";
    } else {
        echo "<p style='color: red;'>✗ No orders found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Query failed</p>";
}

echo "<p><a href='Users/pharmacist/view_requisitions.php'>Go to Manage Orders</a></p>";
echo "<p><a href='Users/pharmacist/dashboard.php'>Go to Pharmacist Dashboard</a></p>";
?>
