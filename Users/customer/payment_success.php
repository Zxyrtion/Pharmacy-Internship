<?php
require_once '../../config.php';
require_once '../../core/paymongo.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }

$full_name = $_SESSION['full_name'];
$rx_id = (int)($_GET['rx_id'] ?? 0);
if (!$rx_id) { header('Location: dashboard.php'); exit(); }

// Load pending payment record for this prescription
$sp = $conn->prepare("SELECT * FROM payments WHERE prescription_id=? AND customer_id=? AND status='Pending' ORDER BY id DESC LIMIT 1");
$sp->bind_param('ii', $rx_id, $_SESSION['user_id']);
$sp->execute();
$payment = $sp->get_result()->fetch_assoc();

$verified = false;
$payment_method_used = '';
$is_mock = isset($_GET['mock']) && $_GET['mock'] == '1';

// Handle mock payment (development mode)
if ($is_mock) {
    $verified = true;
    $payment_method_used = 'Mock Payment (Development)';
    
    // Create or update payment record
    if ($payment) {
        $mock_payment_id = 'mock_payment_' . uniqid();
        $upd = $conn->prepare("UPDATE payments SET status='Paid', paymongo_payment_id=?, paid_at=NOW() WHERE id=?");
        $upd->bind_param('si', $mock_payment_id, $payment['id']);
        $upd->execute();
    } else {
        // Create new payment record
        $sr_temp = $conn->prepare("SELECT o.total_amount FROM purchase_orders o WHERE o.prescription_id=? ORDER BY id DESC LIMIT 1");
        $sr_temp->bind_param('i', $rx_id);
        $sr_temp->execute();
        $order_temp = $sr_temp->get_result()->fetch_assoc();
        $amount = $order_temp['total_amount'] ?? 0;
        
        $mock_payment_id = 'mock_' . uniqid();
        $ins = $conn->prepare("INSERT INTO payments (prescription_id, customer_id, amount_due, payment_method, paymongo_payment_id, status, paid_at) VALUES (?,?,?,'mock',?,'Paid',NOW())");
        $ins->bind_param('iids', $rx_id, $_SESSION['user_id'], $amount, $mock_payment_id);
        $ins->execute();
    }
    
    // Mark prescription as Dispensed
    $upd2 = $conn->prepare("UPDATE prescriptions SET status='Dispensed' WHERE id=?");
    $upd2->bind_param('i', $rx_id);
    $upd2->execute();
    
    // Mark order as Paid
    $upd3 = $conn->prepare("UPDATE purchase_orders SET status='Paid' WHERE prescription_id=?");
    $upd3->bind_param('i', $rx_id);
    $upd3->execute();
    
} elseif ($payment && $payment['paymongo_session_id']) {
    // Verify with PayMongo (real payment)
    $session = getCheckoutSession($payment['paymongo_session_id']);
    $status  = $session['data']['attributes']['status'] ?? '';
    $payment_method_used = $session['data']['attributes']['payment_method_used'] ?? '';

    if ($status === 'active' || $status === 'completed' || $status === 'paid') {
        $verified = true;
        $payment_id = $session['data']['attributes']['payments'][0]['id'] ?? $payment['paymongo_session_id'];

        // Update payment record
        $upd = $conn->prepare("UPDATE payments SET status='Paid', paymongo_payment_id=?, paid_at=NOW() WHERE id=?");
        $upd->bind_param('si', $payment_id, $payment['id']);
        $upd->execute();

        // Mark prescription as Dispensed
        $upd2 = $conn->prepare("UPDATE prescriptions SET status='Dispensed' WHERE id=?");
        $upd2->bind_param('i', $rx_id);
        $upd2->execute();

        // Mark order as Paid
        $upd3 = $conn->prepare("UPDATE purchase_orders SET status='Paid' WHERE prescription_id=?");
        $upd3->bind_param('i', $rx_id);
        $upd3->execute();
    }
}

// Load prescription for receipt
$sr = $conn->prepare("SELECT p.*, o.total_amount FROM prescriptions p
    LEFT JOIN purchase_orders o ON o.prescription_id = p.id
    WHERE p.id=?");
$sr->bind_param('i', $rx_id);
$sr->execute();
$rx = $sr->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .success-icon { font-size: 5rem; color: #198754; }
        @media print { .no-print { display: none !important; } body { background: white; } .page-card { box-shadow: none; } }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm no-print">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="page-card text-center">
        <?php if ($verified): ?>
            <div class="success-icon mb-3"><i class="bi bi-check-circle-fill"></i></div>
            <h3 class="text-success fw-bold">Payment Successful!</h3>
            <p class="text-muted">Your payment has been confirmed via PayMongo.</p>

            <div class="card border-success mx-auto mt-4 mb-4" style="max-width:400px;">
                <div class="card-body">
                    <p class="mb-1"><strong>Prescription #:</strong> <?= $rx_id ?></p>
                    <p class="mb-1"><strong>Patient:</strong> <?= htmlspecialchars($rx['patient_name'] ?? '') ?></p>
                    <p class="mb-1"><strong>Doctor:</strong> <?= htmlspecialchars($rx['doctor_name'] ?? '') ?></p>
                    <p class="mb-1"><strong>Amount Paid:</strong> ₱<?= number_format($rx['total_amount'] ?? 0, 2) ?></p>
                    <?php if ($payment_method_used): ?>
                    <p class="mb-1"><strong>Method:</strong> <?= strtoupper($payment_method_used) ?></p>
                    <?php endif; ?>
                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Paid</span></p>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-center no-print">
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <a href="dashboard.php" class="btn btn-success">
                    <i class="bi bi-house"></i> Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div style="font-size:5rem; color:#ffc107;"><i class="bi bi-clock-history"></i></div>
            <h3 class="text-warning fw-bold">Payment Pending</h3>
            <p class="text-muted">Your payment is being processed. Please wait a moment.</p>
            <a href="my_prescriptions.php" class="btn btn-primary no-print">View My Prescriptions</a>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
