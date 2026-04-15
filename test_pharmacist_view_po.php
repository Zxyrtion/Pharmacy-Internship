<?php
require_once 'config.php';

echo "<h1>Test Pharmacist View PO</h1>";

$po_id = 1; // Test with existing PO

echo "<h2>Testing PO Details (ID: $po_id):</h2>";

// Test the exact query used in view_po.php
$stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.id = ?");
if ($stmt) {
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $po = $res->fetch_assoc();
    $stmt->close();
    
    if ($po) {
        echo "<h3>PO Header Information:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>PO Number</th><td>" . htmlspecialchars($po['po_number']) . "</td></tr>";
        echo "<tr><th>Date</th><td>" . date('M d, Y', strtotime($po['po_date'])) . "</td></tr>";
        echo "<tr><th>Technician</th><td>" . htmlspecialchars($po['technician_name']) . "</td></tr>";
        echo "<tr><th>Vendor</th><td>" . htmlspecialchars($po['vendor_company'] ?? 'Not specified') . "</td></tr>";
        echo "<tr><th>Status</th><td><span style='background: #ffc107; color: #000; padding: 2px 8px; border-radius: 4px;'>" . htmlspecialchars($po['status']) . "</span></td></tr>";
        echo "<tr><th>Subtotal</th><td>PHP " . number_format($po['subtotal'], 2) . "</td></tr>";
        echo "<tr><th>Tax</th><td>PHP " . number_format($po['tax'], 2) . "</td></tr>";
        echo "<tr><th>Shipping</th><td>PHP " . number_format($po['shipping'], 2) . "</td></tr>";
        echo "<tr><th>Other Costs</th><td>PHP " . number_format($po['other_costs'], 2) . "</td></tr>";
        echo "<tr><th>Total</th><td style='font-weight: bold; color: green;'>PHP " . number_format($po['total'], 2) . "</td></tr>";
        echo "</table>";
        
        // Test items query
        echo "<h3>PO Items:</h3>";
        $stmt_items = $conn->prepare("SELECT * FROM requisition_report_items WHERE po_id = ?");
        if ($stmt_items) {
            $stmt_items->bind_param("i", $po_id);
            $stmt_items->execute();
            $res_items = $stmt_items->get_result();
            
            if ($res_items->num_rows > 0) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Item #</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>";
                while($item = $res_items->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['item_number']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['description']) . "</td>";
                    echo "<td style='text-align: center;'>" . $item['qty'] . "</td>";
                    echo "<td style='text-align: right;'>PHP " . number_format($item['unit_price'], 2) . "</td>";
                    echo "<td style='text-align: right; font-weight: bold;'>PHP " . number_format($item['total'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p style='color: green;'>Found " . $res_items->num_rows . " items</p>";
            } else {
                echo "<p style='color: red;'>No items found for this PO</p>";
            }
            $stmt_items->close();
        }
        
        echo "<p style='color: green;'>PO data loaded successfully!</p>";
    } else {
        echo "<p style='color: red;'>PO not found</p>";
    }
} else {
    echo "<p style='color: red;'>Query preparation failed</p>";
}

echo "<p><a href='Users/pharmacist/view_po.php?id=$po_id'>View PO in System</a></p>";
echo "<p><a href='Users/pharmacist/view_requisitions.php'>Back to Manage Orders</a></p>";
?>
