<?php
require_once 'config.php';
require_once 'models/purchase_order.php';

echo "=== Testing Technician Requisitions Feature ===\n\n";

$purchaseOrder = new PurchaseOrder($conn);

// Test with user ID 3 (Jasmine Duran - Technician)
$technician_id = 3;

echo "1. Getting requisitions for technician (User ID: $technician_id):\n";
$requisitions = $purchaseOrder->getRequisitionsByUserId($technician_id);
echo "Found " . count($requisitions) . " requisitions\n\n";

if (!empty($requisitions)) {
    echo "Requisitions List:\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-12s | %-15s | %-12s | %-10s | %-12s | %-10s\n", 
           "Req ID", "Department", "Date", "Urgency", "Amount", "Status");
    echo str_repeat("-", 100) . "\n";
    
    foreach ($requisitions as $req) {
        printf("%-12s | %-15s | %-12s | %-10s | ₱%-11.2f | %-10s\n",
               $req['requisition_id'],
               $req['department'] ?? 'N/A',
               $req['requisition_date'],
               $req['urgency'],
               $req['total_amount'],
               $req['status']);
    }
    echo str_repeat("-", 100) . "\n";
}

// Get statistics
echo "\n2. Getting requisition statistics for technician:\n";
$stats = $purchaseOrder->getUserRequisitionStats($technician_id);
echo "Total: " . $stats['total'] . "\n";
echo "Submitted: " . $stats['submitted'] . "\n";
echo "Approved: " . $stats['approved'] . "\n";
echo "Rejected: " . $stats['rejected'] . "\n";
echo "Processed: " . $stats['processed'] . "\n";

// Test getting a specific requisition
if (!empty($requisitions)) {
    $first_req = $requisitions[0];
    echo "\n3. Getting details for requisition ID: " . $first_req['id'] . "\n";
    $req_details = $purchaseOrder->getRequisitionById($first_req['id']);
    $req_items = $purchaseOrder->getRequisitionItems($first_req['id']);
    
    echo "Requisition: " . $req_details['requisition_id'] . "\n";
    echo "Status: " . $req_details['status'] . "\n";
    echo "Items: " . count($req_items) . "\n";
    
    if (!empty($req_items)) {
        echo "\nItems:\n";
        foreach ($req_items as $item) {
            echo "  - " . $item['medicine_name'] . " (" . $item['dosage'] . ") x " . 
                 $item['requested_quantity'] . " = ₱" . number_format($item['total_price'], 2) . "\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
echo "Technician can now:\n";
echo "1. View all their requisitions at: Users/technician/my_requisitions.php\n";
echo "2. View specific requisition details at: Users/technician/view_requisition.php?id=X\n";
echo "3. See requisition stats on their dashboard\n";

$conn->close();
?>
