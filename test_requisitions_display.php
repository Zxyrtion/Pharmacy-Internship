<?php
require_once 'config.php';
require_once 'models/purchase_order.php';

echo "=== Testing Requisitions Display ===\n\n";

$purchaseOrder = new PurchaseOrder($conn);

// Get all requisitions
echo "1. Getting all requisitions:\n";
$requisitions = $purchaseOrder->getAllRequisitionsWithFilter('');
echo "Found " . count($requisitions) . " requisitions\n\n";

if (!empty($requisitions)) {
    echo "Requisitions List:\n";
    echo str_repeat("-", 120) . "\n";
    printf("%-12s | %-20s | %-15s | %-12s | %-10s | %-12s | %-10s\n", 
           "Req ID", "Requested By", "Department", "Date", "Urgency", "Amount", "Status");
    echo str_repeat("-", 120) . "\n";
    
    foreach ($requisitions as $req) {
        printf("%-12s | %-20s | %-15s | %-12s | %-10s | ₱%-11.2f | %-10s\n",
               $req['requisition_id'],
               ($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? ''),
               $req['department'] ?? 'N/A',
               $req['requisition_date'],
               $req['urgency'],
               $req['total_amount'],
               $req['status']);
    }
    echo str_repeat("-", 120) . "\n";
}

// Get statistics
echo "\n2. Getting requisition statistics:\n";
$stats = $purchaseOrder->getRequisitionStats();
echo "Total: " . $stats['total'] . "\n";
echo "Pending (Submitted): " . $stats['pending'] . "\n";
echo "Approved: " . $stats['approved'] . "\n";
echo "Processed: " . $stats['processed'] . "\n";

// Get only submitted requisitions
echo "\n3. Getting only SUBMITTED requisitions:\n";
$submitted = $purchaseOrder->getAllRequisitionsWithFilter('Submitted');
echo "Found " . count($submitted) . " submitted requisitions\n";

if (!empty($submitted)) {
    foreach ($submitted as $req) {
        echo "  - " . $req['requisition_id'] . " by " . $req['pharmacist_name'] . 
             " (₱" . number_format($req['total_amount'], 2) . ")\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "If you see requisitions above, they should appear in the Pharmacist's Manage Requisitions page.\n";

$conn->close();
?>
