<?php
require_once '../../config.php';
if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

// Load all prescriptions with order info
$filter = $_GET['status'] ?? 'Processing';
$allowed = ['Pending','Processing','Ready','Dispensed','All'];
if (!in_array($filter, $allowed)) $filter = 'Processing';

if ($filter === 'All') {
    $res = $conn->query("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name, o.total_amount
        FROM prescriptions p
        LEFT JOIN users u ON p.customer_id = u.id
        LEFT JOIN purchase_orders o ON o.prescription_id = p.id
        ORDER BY p.created_at DESC");
} else {
    $s = $conn->prepare("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name, o.total_amount
        FROM prescriptions p
        LEFT JOIN users u ON p.customer_id = u.id
        LEFT JOIN purchase_orders o ON o.prescription_id = p.id
        WHERE p.status=? ORDER BY p.created_at DESC");
    $s->bind_param('s', $filter); $s->execute();
    $res = $s->get_result();
}
$orders = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .badge-pending    { background:#ffc107; color:#000; }
        .badge-processing { background:#0dcaf0; color:#000; }
        .badge-ready      { background:#198754; color:#fff; }
        .badge-dispensed  { background:#6c757d; color:#fff; }
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
        <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Processing</h5>
        <span></span>
    </div>

    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Customer Prescription Orders</h5>
            <div class="btn-group">
                <?php foreach (['Pending','Processing','Ready','Dispensed','All'] as $s): ?>
                <a href="?status=<?= $s ?>" class="btn btn-sm btn-outline-primary <?= $filter === $s ? 'active fw-bold' : '' ?>"><?= $s ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-2">No orders found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Customer</th><th>Rx Date</th><th>Total</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                    <tr>
                        <td>#<?= $ord['id'] ?? 0 ?></td>
                        <td><?= htmlspecialchars($ord['patient_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ord['doctor_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ord['customer_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($ord['prescription_date'] ?? '') ?></td>
                        <td><?= ($ord['total_amount'] ?? 0) ? '₱'.number_format($ord['total_amount'],2) : '-' ?></td>
                        <td><span class="badge badge-<?= strtolower($ord['status'] ?? 'pending') ?>"><?= $ord['status'] ?? 'Pending' ?></span></td>
                        <td>
                            <?php if (($ord['status'] ?? '') === 'Processing'): ?>
                                <a href="dispense_product.php?rx_id=<?= $ord['id'] ?? 0 ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-bag-check"></i> Dispense
                                </a>
                            <?php elseif (($ord['status'] ?? '') === 'Ready'): ?>
                                <span class="text-success"><i class="bi bi-check-circle-fill"></i> Dispensed — Awaiting Payment</span>
                            <?php elseif (($ord['status'] ?? '') === 'Dispensed'): ?>
                                <span class="text-secondary"><i class="bi bi-check-circle-fill"></i> Completed</span>
                            <?php else: ?>
                                <span class="text-muted small"><?= $ord['status'] ?? 'Unknown' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
