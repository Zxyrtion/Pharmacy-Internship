<?php
require_once '../../config.php';
if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

// Load prescriptions grouped by prescription_id
$orders = [];
if ($s = $conn->prepare("SELECT 
    p.prescription_id,
    p.patient_id,
    p.patient_name,
    p.doctor_name,
    p.date_prescribed,
    p.status,
    MIN(p.id) as first_id,
    COUNT(*) as item_count
    FROM prescriptions p
    WHERE p.patient_id = ? AND p.status IN ('Processing','Dispensed')
    GROUP BY p.prescription_id
    ORDER BY p.created_at DESC")) {
    $s->bind_param('i', $_SESSION['user_id']);
    $s->execute();
    $orders = $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
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
        // Get all items for this prescription
        $items_stmt = $conn->prepare("SELECT * FROM prescriptions WHERE prescription_id=?");
        $items_stmt->bind_param('s', $ord['prescription_id']);
        $items_stmt->execute();
        $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate total (if prices are available)
        $total = 0;
    ?>
    <div class="page-card mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="rx-header flex-grow-1">
                <h6><?= htmlspecialchars($ord['doctor_name']) ?></h6>
            </div>
            <span class="badge badge-<?= strtolower($ord['status']) ?> ms-3"><?= $ord['status'] ?></span>
        </div>

        <div class="row mb-2 small">
            <div class="col-md-4"><strong>Prescription ID:</strong> <?= htmlspecialchars($ord['prescription_id']) ?></div>
            <div class="col-md-4"><strong>Date:</strong> <?= $ord['date_prescribed'] ?></div>
            <div class="col-md-4"><strong>Patient:</strong> <?= htmlspecialchars($ord['patient_name']) ?></div>
        </div>

        <div class="text-center fw-bold mb-2" style="letter-spacing:2px; font-size:0.9rem;">PRESCRIPTION ITEMS</div>

        <div class="table-responsive">
            <table class="table table-bordered med-table table-sm">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Dosage/Generic</th>
                        <th>Qty</th>
                        <th>Instructions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($item['dosage']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= htmlspecialchars($item['instructions']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>
        <?php if ($ord['status'] === 'Ready'): ?>
        <a href="payment.php?rx_id=<?= htmlspecialchars($ord['prescription_id']) ?>" class="btn btn-success btn-sm">
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
