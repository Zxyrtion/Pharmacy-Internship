<?php
require_once '../../config.php';
if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

// Load orders with items
$s = $conn->prepare("SELECT p.*, o.id as order_id, o.total_amount, o.order_date
    FROM prescriptions p
    LEFT JOIN purchase_orders o ON o.prescription_id = p.id
    WHERE p.customer_id = ? AND p.status IN ('Processing','Ready','Dispensed')
    ORDER BY p.created_at DESC");
$s->bind_param('i', $_SESSION['user_id']);
$s->execute();
$orders = $s->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .rx-header { border-bottom: 2px solid #c0392b; padding-bottom: 0.5rem; margin-bottom: 1rem; }
        .rx-header h6 { color: #c0392b; font-weight: 700; font-style: italic; margin: 0; }
        .rx-header small { color: #2563b0; font-weight: 600; }
        .med-table thead { background: #2c3e50; color: white; }
        .badge-processing { background:#0dcaf0; color:#000; }
        .badge-ready      { background:#198754; }
        .badge-dispensed  { background:#6f42c1; }
        .validity-note { color:#c0392b; font-style:italic; font-size:0.8rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="mt-3 mb-2 d-flex justify-content-between align-items-center">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <h5 class="mb-0"><i class="bi bi-cart-check"></i> My Purchase Orders</h5>
        <span></span>
    </div>

    <?php if (empty($orders)): ?>
    <div class="page-card text-center text-muted py-5">
        <i class="bi bi-inbox" style="font-size:3rem;"></i>
        <p class="mt-2">No purchase orders yet. <a href="prescription_submit.php">Submit a prescription</a> first.</p>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $ord):
        $items_stmt = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id=?");
        $items_stmt->bind_param('i', $ord['order_id']);
        $items_stmt->execute();
        $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>
    <div class="page-card mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="rx-header flex-grow-1">
                <h6><?= htmlspecialchars($ord['doctor_name']) ?></h6>
                <small><?= htmlspecialchars($ord['doctor_specialization']) ?></small>
            </div>
            <span class="badge badge-<?= strtolower($ord['status']) ?> ms-3"><?= $ord['status'] ?></span>
        </div>

        <div class="row mb-2 small">
            <div class="col-md-3"><strong>Order #:</strong> <?= $ord['order_id'] ?></div>
            <div class="col-md-3"><strong>Rx Date:</strong> <?= $ord['prescription_date'] ?></div>
            <div class="col-md-3"><strong>Patient:</strong> <?= htmlspecialchars($ord['patient_name']) ?></div>
            <div class="col-md-3"><strong>Order Date:</strong> <?= $ord['order_date'] ?? '-' ?></div>
        </div>

        <div class="text-center fw-bold mb-2" style="letter-spacing:2px; font-size:0.9rem;">PRESCRIPTION</div>

        <div class="table-responsive">
            <table class="table table-bordered med-table table-sm">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Generic Name</th>
                        <th>Qty</th>
                        <th>Sig.</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($item['generic_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= htmlspecialchars($item['sig']) ?></td>
                        <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                        <td>₱<?= number_format($item['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f0f4ff; font-weight:700;">
                        <td colspan="5" class="text-end">TOTAL:</td>
                        <td>₱<?= number_format($ord['total_amount'] ?? 0, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>
        <?php if ($ord['status'] === 'Ready'): ?>
        <a href="payment.php?rx_id=<?= $ord['id'] ?>" class="btn btn-success btn-sm">
            <i class="bi bi-cash"></i> Pay Now
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
