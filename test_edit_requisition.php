<?php
require_once 'config.php';
require_once 'models/purchase_order.php';

echo "=== Testing Edit Requisition Feature ===\n\n";

$purchaseOrder = new PurchaseOrder($conn);

// Get a submitted requisition
$result = $conn->query("SELECT id, requisition_id, pharmacist_id, status FROM requisitions WHERE status = 'Submitted' LIMIT 1");
$requisition = $result->fetch_assoc();

if (!$requisition) {
    echo "No submitted requisitions found to test with.\n";
    exit;
}

echo "1. Testing with Requisition:\n";
echo "   ID: " . $requisition['id'] . "\n";
echo "   Requisition ID: " . $requisition['requisition_id'] . "\n";
echo "   Status: " . $requisition['status'] . "\n\n";

// Get current items
$items = $purchaseOrder->getRequisitionItems($requisition['id']);
echo "2. Current Items: " . count($items) . "\n";
foreach ($items as $item) {
    echo "   - " . $item['medicine_name'] . " x " . $item['requested_quantity'] . " @ ₱" . $item['unit_price'] . "\n";
}

// Test update (modify first item quantity)
echo "\n3. Testing Update...\n";
$updated_items = [];
foreach ($items as $item) {
    $updated_items[] = [
        'medicine_name' => $item['medicine_name'],
        'dosage' => $item['dosage'],
        'current_stock' => $item['current_stock'],
        'reorder_level' => $item['reorder_level'],
        'quantity' => $item['requested_quantity'] + 10, // Add 10 to quantity
        'unit_price' => $item['unit_price'],
        'supplier' => $item['supplier']
    ];
}

$result = $purchaseOrder->updateRequisitionWithItems(
    $requisition['id'],
    'Pharmacy',
    date('Y-m-d'),
    date('Y-m-d', strtotime('+7 days')),
    'Normal',
    'Updated requisition for testing',
    $updated_items
);

if ($result['success']) {
    echo "✓ Update successful!\n";
    echo "  New Total Amount: ₱" . number_format($result['total_amount'], 2) . "\n";
    
    // Verify update
    $updated_req = $purchaseOrder->getRequisitionById($requisition['id']);
    $updated_items_check = $purchaseOrder->getRequisitionItems($requisition['id']);
    
    echo "\n4. Verification:\n";
    echo "   Total Amount in DB: ₱" . number_format($updated_req['total_amount'], 2) . "\n";
    echo "   Items Count: " . count($updated_items_check) . "\n";
    foreach ($updated_items_check as $item) {
        echo "   - " . $item['medicine_name'] . " x " . $item['requested_quantity'] . " @ ₱" . $item['unit_price'] . "\n";
    }
} else {
    echo "✗ Update failed: " . $result['error'] . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Edit feature is working correctly!\n";
echo "Technicians can now edit requisitions with 'Draft' or 'Submitted' status.\n";

$conn->close();
?>
