<?php
require_once '../../config.php';
require_once '../../notification_helper.php';

// Check logged in & role
if (!isLoggedIn() || $_SESSION['role_name'] !== 'Pharmacist') {
    header('Location: ../index.php');
    exit();
}

$full_name = $_SESSION['full_name'];
$po_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$po_id) {
    header('Location: view_requisitions.php');
    exit();
}

// Fetch PO
$po = null;
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $po = $res->fetch_assoc();
        $stmt->close();
    }
}

if (!$po) {
    echo "Purchase Order not found.";
    exit();
}

// Handle PO approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput($_POST['action']);
    
    if ($action === 'approve') {
        // Update PO status to approved
        $stmt = $conn->prepare("UPDATE requisition_reports SET status = 'approved' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $po_id);
            $stmt->execute();
            $stmt->close();
            
            // Send notification to technician
            $message = "Your Purchase Order #{$po['po_number']} has been approved by Pharmacist $full_name.";
            createNotification($po['technician_id'], $message, 'success', 'purchase_order', $po_id);
            
            header("Location: view_po.php?id=$po_id&approved=1");
            exit();
        }
    } elseif ($action === 'reject') {
        $rejection_reason = sanitizeInput($_POST['rejection_reason'] ?? '');
        
        if (empty($rejection_reason)) {
            $error = "Please provide a reason for rejection.";
        } else {
            // Update PO status to rejected with reason
            $stmt = $conn->prepare("UPDATE requisition_reports SET status = 'rejected', rejection_reason = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $rejection_reason, $po_id);
                $stmt->execute();
                $stmt->close();
                
                // Send notification to technician
                $message = "Your Purchase Order #{$po['po_number']} has been rejected by Pharmacist $full_name. Reason: $rejection_reason";
                createNotification($po['technician_id'], $message, 'error', 'purchase_order', $po_id);
                
                header("Location: view_po.php?id=$po_id&rejected=1");
                exit();
            }
        }
    }
}

// Fetch items
$items = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM requisition_report_items WHERE po_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
    }
}

// Fetch original inventory report details
$inventory_report_items = [];
if (isset($conn) && !empty($po['technician_id'])) {
    $stmt = $conn->prepare("SELECT ir.* FROM inventory_report ir WHERE ir.reporter = (SELECT CONCAT(u.first_name, ' ', u.last_name) FROM users u WHERE u.id = ?) AND ir.inventory_period = (SELECT inventory_period FROM requisition_reports WHERE id = ?) AND ir.reorder_required = 'Yes'");
    if ($stmt) {
        $stmt->bind_param("ii", $po['technician_id'], $po_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $inventory_report_items[] = $row;
        }
        $stmt->close();
    }
}

// Handle rapid form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $new_status = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("UPDATE requisition_reports SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $po_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: view_requisitions.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PO #<?= htmlspecialchars($po['po_number']) ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Arial', sans-serif; }
        .po-container { 
            max-width: 1000px; 
            margin: 2rem auto; 
            background: white; 
            padding: 3rem; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        .po-title { color: #5b9bd5; font-size: 2.5rem; text-align: right; text-transform: uppercase; letter-spacing: 2px; }
        .company-name { font-size: 1.5rem; font-weight: bold; margin-bottom: 0px; }
        .info-panel { font-size: 0.85rem; line-height: 1.4; color: #333; }
        
        .box-header { background-color: #e74c3c; color: white; padding: 5px 10px; font-weight: bold; font-size: 0.85rem; margin-top: 1rem; }
        .box-content { padding: 10px; border: 1px solid #dee2e6; border-top: none; font-size: 0.85rem; }
        
        .req-table, .items-table { width: 100%; margin-top: 1.5rem; border: 1px solid #dee2e6; }
        .req-table th, .items-table th { background-color: #3b5998; color: white; padding: 8px; font-size: 0.85rem; }
        .req-table td, .items-table td { border-bottom: 1px solid #dee2e6; padding: 8px; font-size: 0.85rem; }
        
        .totals-table { width: 300px; float: right; margin-top: 1rem; }
        .totals-table th { text-align: right; padding: 5px; font-size: 0.85rem; }
        .totals-table td { padding: 5px; text-align: right; font-weight: bold; }
        .total-row { background-color: #e2e8f0; border-top: 2px solid #3b5998; }
        
        .action-banner {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="view_requisitions.php">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?> (Pharmacist)
                </span>
            </div>
        </div>
    </nav>

    <div class="po-container">
        
        <?php if ($po['status'] === 'Pending'): ?>
        <div class="action-banner">
            <div>
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-exclamation-circle-fill text-warning me-2"></i> Action Required</h5>
                <small class="text-muted">This purchase order requires pharmacist authorization before fulfillment.</small>
            </div>
            <form method="POST" class="m-0">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-outline-danger me-2"><i class="bi bi-x-circle"></i> Reject Request</button>
            </form>
            <form method="POST" class="m-0">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle-fill"></i> Authorize & Approve Order</button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert <?= $po['status'] === 'Approved' ? 'alert-success' : 'alert-danger' ?> mb-4">
            <h5 class="mb-0"><i class="bi <?= $po['status'] === 'Approved' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> Status: <?= htmlspecialchars($po['status']) ?></h5>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row">
            <div class="col-6 info-panel">
                <div class="company-name">MediCare Pharmacy</div>
                <div>PO Generated By: <?= htmlspecialchars($po['technician_name']) ?> (Tech)</div>
            </div>
            <div class="col-6 text-end">
                <div class="po-title">PURCHASE ORDER</div>
                <div class="fs-5 fw-bold text-secondary">#<?= htmlspecialchars($po['po_number']) ?></div>
                <div class="text-muted">Date: <?= date('M d, Y', strtotime($po['po_date'])) ?></div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-6">
                <div class="box-header">VENDOR details</div>
                <div class="box-content">
                    <div class="fw-bold"><?= htmlspecialchars($po['vendor_company']) ?></div>
                    <div><?= htmlspecialchars($po['vendor_contact']) ?></div>
                    <div><?= htmlspecialchars($po['vendor_address']) ?></div>
                    <div>Phone: <?= htmlspecialchars($po['vendor_phone']) ?></div>
                    <div>Fax: <?= htmlspecialchars($po['vendor_fax']) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="box-header">SHIP TO</div>
                <div class="box-content">
                    <div class="fw-bold"><?= htmlspecialchars($po['shipto_name']) ?></div>
                    <div><?= htmlspecialchars($po['shipto_company']) ?></div>
                    <div><?= htmlspecialchars($po['shipto_address']) ?></div>
                    <div>Phone: <?= htmlspecialchars($po['shipto_phone']) ?></div>
                </div>
            </div>
        </div>
        
        <table class="req-table">
            <thead>
                <tr>
                    <th>SHIP VIA</th>
                    <th>F.O.B.</th>
                    <th>TERMS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($po['ship_via']) ?></td>
                    <td><?= htmlspecialchars($po['fob']) ?></td>
                    <td><?= htmlspecialchars($po['shipping_terms']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Original Inventory Report Details -->
        <?php if (!empty($inventory_report_items)): ?>
        <div class="box-header" style="background-color: #6c757d; margin-top: 2rem;">ORIGINAL INVENTORY REPORT DETAILS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="15%">ITEM #</th>
                    <th width="20%">MANUFACTURER</th>
                    <th width="30%">DESCRIPTION</th>
                    <th width="10%" class="text-center">STOCK QTY</th>
                    <th width="10%" class="text-center">REORDER POINT</th>
                    <th width="10%" class="text-center">REORDER QTY</th>
                    <th width="5%" class="text-center">REORDER?</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inventory_report_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_number_name']) ?></td>
                    <td><?= htmlspecialchars($item['manufacturer'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($item['description'] ?: '-') ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['stock_quantity']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['reorder_point']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['item_reorder_quantity']) ?></td>
                    <td class="text-center">
                        <?php if ($item['reorder_required'] === 'Yes'): ?>
                            <span class="badge bg-primary">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Purchase Order Items -->
        <div class="box-header" style="background-color: #3b5998; margin-top: 2rem;">PURCHASE ORDER DETAILS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="15%">ITEM #</th>
                    <th width="45%">DESCRIPTION</th>
                    <th width="10%" class="text-center">QTY</th>
                    <th width="15%" class="text-end">UNIT PRICE</th>
                    <th width="15%" class="text-end">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['item_number']) ?></td>
                    <td><?= htmlspecialchars($i['description']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['qty']) ?></td>
                    <td class="text-end">₱<?= number_format($i['unit_price'], 2) ?></td>
                    <td class="text-end fw-bold">₱<?= number_format($i['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="clearfix mt-4">
            <div class="float-start" style="width: 50%;">
                <div class="box-header" style="background:#555;">COMMENTS / INSTRUCTIONS</div>
                <div class="box-content" style="min-height: 80px; background:#f9f9f9;">
                    <?= nl2br(htmlspecialchars($po['comments'] ?? 'No comments provided.')) ?>
                </div>
            </div>
            
            <table class="totals-table">
                <tr>
                    <th>SUBTOTAL:</th>
                    <td>₱<?= number_format($po['subtotal'], 2) ?></td>
                </tr>
                <tr>
                    <th>TAX:</th>
                    <td>₱<?= number_format($po['tax'], 2) ?></td>
                </tr>
                <tr>
                    <th>SHIPPING:</th>
                    <td>₱<?= number_format($po['shipping'], 2) ?></td>
                </tr>
                <tr>
                    <th>OTHER COSTS:</th>
                    <td>₱<?= number_format($po['other_costs'], 2) ?></td>
                </tr>
                <tr class="total-row fs-5">
                    <th>GRAND TOTAL:</th>
                    <td class="text-success">₱<?= number_format($po['total'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Status Display Only -->
        <?php if (($po['status'] ?? 'Pending') === 'Approved'): ?>
        <div class="alert alert-success mt-4">
            <i class="bi bi-check-circle"></i> This Purchase Order has been <strong>Approved</strong>
        </div>
        <?php elseif (($po['status'] ?? 'Pending') === 'Rejected'): ?>
        <div class="alert alert-danger mt-4">
            <i class="bi bi-x-circle"></i> This Purchase Order has been <strong>Rejected</strong>
            <?php if (!empty($po['remarks'])): ?>
                <br><strong>Reason:</strong> <?= htmlspecialchars($po['remarks']) ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-warning mt-4">
            <i class="bi bi-clock"></i> This Purchase Order is <strong>Pending</strong> - awaiting pharmacist review
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
