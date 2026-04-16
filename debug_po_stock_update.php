<?php
require_once 'config.php';

echo "<h2>Debug: PO Stock Update</h2>";

// Get the latest PO
$po_sql = "SELECT * FROM purchase_orders ORDER BY id DESC LIMIT 1";
$po_result = $conn->query($po_sql);

if ($po_result && $po_result->num_rows > 0) {
    $po = $po_result->fetch_assoc();
    echo "<h3>Latest PO: " . htmlspecialchars($po['purchase_order_id']) . "</h3>";
    echo "<p>Requisition ID: " . $po['requisition_id'] . "</p>";
    
    // Get items from this PO
    $items_sql = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param("i", $po['id']);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    echo "<h4>PO Items:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Medicine</th><th>Quantity</th><th>Exists in medicines table?</th><th>Current Stock</th></tr>";
    
    while ($item = $items_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['medicine_name']) . "</td>";
        echo "<td>" . $item['quantity_ordered'] . "</td>";
        
        // Check if medicine exists
        $check_sql = "SELECT id, stock_quantity FROM medicines WHERE medicine_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if ($check_stmt) {
            $check_stmt->bind_param("s", $item['medicine_name']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $med = $check_result->fetch_assoc();
                echo "<td style='background: lightgreen;'>YES (ID: " . $med['id'] . ")</td>";
                echo "<td>" . $med['stock_quantity'] . "</td>";
            } else {
                echo "<td style='background: pink;'>NO - Medicine not found!</td>";
                echo "<td>N/A</td>";
            }
            $check_stmt->close();
        } else {
            echo "<td style='background: orange;'>ERROR: " . $conn->error . "</td>";
            echo "<td>N/A</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    // Check stock_changes table
    echo "<h4>Stock Changes for this PO:</h4>";
    $changes_sql = "SELECT * FROM stock_changes WHERE reference_id = ?";
    $stmt = $conn->prepare($changes_sql);
    $stmt->bind_param("s", $po['purchase_order_id']);
    $stmt->execute();
    $changes_result = $stmt->get_result();
    
    if ($changes_result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Medicine</th><th>Previous</th><th>New</th><th>Change</th><th>Date</th></tr>";
        while ($change = $changes_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($change['medicine_name']) . "</td>";
            echo "<td>" . $change['previous_stock'] . "</td>";
            echo "<td>" . $change['new_stock'] . "</td>";
            echo "<td style='color: green;'>+" . $change['change_amount'] . "</td>";
            echo "<td>" . $change['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No stock changes recorded for this PO!</p>";
        echo "<p><strong>This means the medicines don't exist in the 'medicines' table.</strong></p>";
    }
    
} else {
    echo "<p>No POs found</p>";
}

$conn->close();
?>
