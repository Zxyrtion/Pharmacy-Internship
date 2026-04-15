<?php
require_once '../../config.php';
if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

// Get customer's prescriptions with error handling
$prescriptions = [];
if ($s = $conn->prepare("SELECT p.*, o.total_amount
    FROM prescriptions p
    LEFT JOIN purchase_orders o ON o.prescription_id = p.id
    WHERE p.customer_id = ?
    ORDER BY p.created_at DESC")) {
    $s->bind_param('i', $_SESSION['user_id']);
    $s->execute();
    $prescriptions = $s->get_result()->fetch_all(MYSQLI_ASSOC);
}

$steps = ['Pending' => 1, 'Processing' => 2, 'Ready' => 3, 'Dispensed' => 4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Dispensing - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1rem; }
        .stepper { display: flex; justify-content: space-between; align-items: center; margin: 1.5rem 0; position: relative; }
        .stepper::before { content:''; position:absolute; top:20px; left:10%; right:10%; height:3px; background:#dee2e6; z-index:0; }
        .step { text-align: center; flex: 1; position: relative; z-index: 1; }
        .step-circle { width:40px; height:40px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; border:3px solid #dee2e6; background:white; color:#aaa; }
        .step.done .step-circle { background:#198754; border-color:#198754; color:white; }
        .step.active .step-circle { background:#0d6efd; border-color:#0d6efd; color:white; }
        .step-label { font-size:0.75rem; margin-top:0.4rem; color:#666; }
        .step.done .step-label, .step.active .step-label { color:#333; font-weight:600; }
        .badge-pending    { background:#ffc107; color:#000; }
        .badge-processing { background:#0dcaf0; color:#000; }
        .badge-ready      { background:#198754; }
        .badge-dispensed  { background:#6f42c1; }
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
        <h5 class="mb-0"><i class="bi bi-bag-check"></i> Track Dispensing</h5>
        <span></span>
    </div>

    <?php if (empty($prescriptions)): ?>
    <div class="page-card text-center text-muted py-5">
        <i class="bi bi-inbox" style="font-size:3rem;"></i>
        <p class="mt-2">No prescriptions to track. <a href="prescription_submit.php">Submit one first.</a></p>
    </div>
    <?php else: ?>
    <?php foreach ($prescriptions as $rx):
        $current_step = $steps[$rx['status']] ?? 1;
    ?>
    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <strong>#<?= $rx['id'] ?></strong> —
                <span style="color:#c0392b; font-style:italic;"><?= htmlspecialchars($rx['doctor_name']) ?></span>
            </div>
            <span class="badge badge-<?= strtolower($rx['status']) ?>"><?= $rx['status'] ?></span>
        </div>
        <div class="small text-muted mb-3">
            Patient: <?= htmlspecialchars($rx['patient_name']) ?> | Rx Date: <?= $rx['prescription_date'] ?>
            <?= $rx['total_amount'] ? ' | Total: ₱' . number_format($rx['total_amount'], 2) : '' ?>
        </div>

        <!-- Progress stepper -->
        <div class="stepper">
            <?php
            $step_defs = [
                1 => ['label' => 'Submitted',   'icon' => 'bi-file-earmark-medical'],
                2 => ['label' => 'Processing',   'icon' => 'bi-cart-check'],
                3 => ['label' => 'Ready',        'icon' => 'bi-bag-check'],
                4 => ['label' => 'Dispensed',    'icon' => 'bi-check-circle'],
            ];
            foreach ($step_defs as $num => $def):
                $cls = $num < $current_step ? 'done' : ($num === $current_step ? 'active' : '');
            ?>
            <div class="step <?= $cls ?>">
                <div class="step-circle">
                    <?php if ($num < $current_step): ?>
                        <i class="bi bi-check-lg"></i>
                    <?php else: ?>
                        <?= $num ?>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?= $def['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($rx['status'] === 'Ready'): ?>
        <div class="alert alert-success py-2 mt-2">
            <i class="bi bi-check-circle-fill"></i> Your medicines are ready for pickup!
            <a href="payment.php?rx_id=<?= $rx['id'] ?>" class="btn btn-sm btn-success ms-2">Pay Now</a>
        </div>
        <?php elseif ($rx['status'] === 'Processing'): ?>
        <div class="alert alert-info py-2 mt-2">
            <i class="bi bi-hourglass-split"></i> Pharmacist is preparing your medicines. Please wait.
        </div>
        <?php elseif ($rx['status'] === 'Pending'): ?>
        <div class="alert alert-warning py-2 mt-2">
            <i class="bi bi-clock"></i> Waiting for pharmacist to process your prescription.
        </div>
        <?php elseif ($rx['status'] === 'Dispensed'): ?>
        <div class="alert alert-secondary py-2 mt-2">
            <i class="bi bi-check-circle-fill"></i> Completed and paid.
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
