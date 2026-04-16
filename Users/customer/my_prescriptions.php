<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

// Load prescriptions grouped by prescription_id (same as dashboard)
$prescriptions = [];
if ($s = $conn->prepare("SELECT p.id, p.prescription_id, p.date_prescribed as prescription_date, 
    p.patient_name, p.doctor_name, p.status, p.created_at
    FROM prescriptions p
    WHERE p.patient_id = ?
    GROUP BY p.prescription_id
    ORDER BY p.created_at DESC")) {
    $s->bind_param('i', $_SESSION['user_id']);
    $s->execute();
    $prescriptions = $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .badge-pending    { background:#ffc107; color:#000; }
        .badge-processing { background:#0dcaf0; color:#000; }
        .badge-ready      { background:#198754; color:#fff; }
        .badge-dispensed  { background:#6c757d; color:#fff; }
        .badge-cancelled  { background:#dc3545; color:#fff; }
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
        <h5 class="mb-0"><i class="bi bi-file-medical"></i> My Prescriptions</h5>
        <a href="prescription_submit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New</a>
    </div>

    <div class="page-card">
        <?php if (empty($prescriptions)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size:3rem;"></i>
                <p class="mt-2">No prescriptions submitted yet.</p>
                <a href="prescription_submit.php" class="btn btn-primary">Submit Prescription</a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Doctor</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $rx): 
                        $badge = match($rx['status']) {
                            'Pending'    => 'warning text-dark',
                            'Processing' => 'info text-dark',
                            'Ready'      => 'success',
                            'Completed'  => 'secondary',
                            'Cancelled'  => 'danger',
                            default      => 'secondary'
                        };
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($rx['prescription_id']) ?></td>
                        <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($rx['patient_name']) ?></td>
                        <td><?= htmlspecialchars($rx['prescription_date']) ?></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= $rx['status'] ?></span></td>
                        <td>
                            <?php if ($rx['status'] === 'Ready'): ?>
                                <a href="payment.php?rx_id=<?= htmlspecialchars($rx['prescription_id']) ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-cash"></i> Pay Now
                                </a>
                            <?php elseif ($rx['status'] === 'Completed'): ?>
                                <span class="text-success"><i class="bi bi-check-circle-fill"></i> Completed</span>
                            <?php else: ?>
                                <a href="track_dispensing.php" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Track Status
                                </a>
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
