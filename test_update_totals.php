<?php
require_once 'config.php';

echo "<h1>Update PO Totals</h1>";

// Update all PO records with calculated grand totals
$sql = "UPDATE requisition_reports SET total = subtotal + tax + shipping + other_costs";
if ($conn->query($sql)) {
    echo "<p style='color: green;'>✓ Updated all PO totals</p>";
} else {
    echo "<p style='color: red;'>✗ Update failed: " . $conn->error . "</p>";
}

// Show updated records
echo "<h2>Current PO Records:</h2>";
$sql = "SELECT id, po_number, subtotal, tax, shipping, other_costs, total FROM requisition_reports ORDER BY id DESC LIMIT 5";
$res = $conn->query($sql);

if ($res) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>PO Number</th><th>Subtotal</th><th>Tax</th><th>Shipping</th><th>Other</th><th>Grand Total</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['po_number']) . "</td>";
        echo "<td>₱" . number_format($row['subtotal'], 2) . "</td>";
        echo "<td>₱" . number_format($row['tax'], 2) . "</td>";
        echo "<td>₱" . number_format($row['shipping'], 2) . "</td>";
        echo "<td>₱" . number_format($row['other_costs'], 2) . "</td>";
        echo "<td style='font-weight: bold; color: green;'>₱" . number_format($row['total'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='Users/pharmacist/view_requisitions.php'>View Manage Orders</a></p>";
?>
