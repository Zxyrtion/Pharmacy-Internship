<?php
require_once 'config.php';

echo "Checking Order Data for Prescription ID 5\n";
echo str_repeat("=", 60) . "\n\n";

$rx_id = 5;

// Check prescriptions table
echo "1. Prescriptions Table:\n";
$rx = $conn->query("SELECT * FROM prescriptions WHERE id=$rx_id")->fetch_assoc();
if ($rx) {
    echo "   Found prescription: " . $rx['prescription_id'] . "\n";
    echo "   Patient: " . $rx['patient_name'] . "\n";
    echo "   Medicine: " . $rx['medicine_name'] . "\n";
    echo "   Quantity: " . $rx['quantity'] . "\n";
    echo "   Status: " . $rx['status'] . "\n\n";
}

// Check purchase_orders table
echo "2. Purchase Orders Table:\n";
$check_po = $conn->query("SHOW TABLES LIKE 'purchase_orders'");
if ($check_po && $check_po->num_rows > 0) {
    echo "   Table exists\n";
    $po = $conn->query("SELECT * FROM purchase_orders WHERE prescription_id=$rx_id ORDER BY id DESC LIMIT 1");
    if ($po && $po->num_rows > 0) {
        $po_data = $po->fetch_assoc();
        echo "   Found order: ID " . $po_data['id'] . "\n";
        echo "   Total: ₱" . number_format($po_data['total_amount'] ?? 0, 2) . "\n";
        echo "   Status: " . $po_data['status'] . "\n\n";
        
        // Check items
        echo "3. Purchase Order Items:\n";
        $items = $conn->query("SELECT * FROM purchase_order_items WHERE order_id=" . $po_data['id']);
        if ($items && $items->num_rows > 0) {
            while ($item = $items->fetch_assoc()) {
                echo "   - " . $item['medicine_name'] . " x" . $item['quantity'] . " = ₱" . number_format($item['amount'], 2) . "\n";
            }
        } else {
            echo "   No items found\n";
        }
    } else {
        echo "   No purchase order found for prescription $rx_id\n";
    }
} else {
    echo "   Table does not exist\n";
}

echo "\n4. Alternative: Check prescription_orders table:\n";
$check_po2 = $conn->query("SHOW TABLES LIKE 'prescription_orders'");
if ($check_po2 && $check_po2->num_rows > 0) {
    echo "   Table exists\n";
    $po2 = $conn->query("SELECT * FROM prescription_orders WHERE prescription_id=$rx_id ORDER BY id DESC LIMIT 1");
    if ($po2 && $po2->num_rows > 0) {
        $po2_data = $po2->fetch_assoc();
        echo "   Found order: ID " . $po2_data['id'] . "\n";
        echo "   Total: ₱" . number_format($po2_data['total_amount'] ?? 0, 2) . "\n";
        echo "   Status: " . $po2_data['status'] . "\n\n";
        
        // Check items
        echo "5. Prescription Order Items:\n";
        $items2 = $conn->query("SELECT * FROM prescription_order_items WHERE order_id=" . $po2_data['id']);
        if ($items2 && $items2->num_rows > 0) {
            while ($item = $items2->fetch_assoc()) {
                echo "   - " . $item['medicine_name'] . " x" . $item['quantity'] . " = ₱" . number_format($item['amount'], 2) . "\n";
            }
        } else {
            echo "   No items found\n";
        }
    } else {
        echo "   No prescription order found for prescription $rx_id\n";
    }
} else {
    echo "   Table does not exist\n";
}

echo "\n6. Solution: Calculate from medicines table\n";
$med = $conn->query("SELECT * FROM medicines WHERE medicine_name LIKE '%" . $rx['medicine_name'] . "%' LIMIT 1");
if ($med && $med->num_rows > 0) {
    $med_data = $med->fetch_assoc();
    $calculated_total = $med_data['unit_price'] * $rx['quantity'];
    echo "   Medicine: " . $med_data['medicine_name'] . "\n";
    echo "   Unit Price: ₱" . number_format($med_data['unit_price'], 2) . "\n";
    echo "   Quantity: " . $rx['quantity'] . "\n";
    echo "   Calculated Total: ₱" . number_format($calculated_total, 2) . "\n";
} else {
    echo "   Medicine not found in medicines table\n";
    echo "   Using default price calculation\n";
}
?>
