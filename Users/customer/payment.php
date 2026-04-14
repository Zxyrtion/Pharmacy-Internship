<?php
require_once '../../config.php';
require_once '../../core/paymongo.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];
$error = '';

$conn->query("CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT,
    order_id INT,
    customer_id INT,
    amount_due DECIMAL(10,2),
    payment_method VARCHAR(50) DEFAULT 'paymongo',
    paymongo_session_id VARCHAR(200) NULL,
    paymongo_payment_id VARCHAR(200) NULL,
    status ENUM('Pending','Paid','Failed') DEFAULT 'Pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$rx_id = (int)($_GET['rx_id'] ?? 0);
if (!$rx_id) { header('Location: dashboard.php'); exit(); }

$s = $conn->prepare("SELECT p.* FROM prescriptions p WHERE p.id=? AND p.customer_id=?");
$s->bind_param('ii', $rx_id, $_SESSION['user_id']);
$s->execute();
$rx = $s->get_result()->fetch_assoc();

if (!$rx || $rx['status'] !== 'Ready') { header('Location: dashboard.php'); exit(); }

$so = $conn->prepare("SELECT * FROM purchase_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
$so->bind_param('i', $rx_id);
$so->execute();
$order = $so->get_result()->fetch_assoc();

$si = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id=?");
$si->bind_param('i', $order['id'] ?? 0);
$si->execute();
$order_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);

$amount_due = (float)($order['total_amount'] ?? 0);

// Create PayMongo checkout session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_paymongo'])) {
    $line_items = [];
    foreach ($order_items as $item) {
        $line_items[] = [
            'currency' => 'PHP',
            'amount'   => (int)round((float)$item['unit_price'] * 100),
            'name'     => $item['medicine_name'] . ($item['generic_name'] ? ' (' . $item['generic_name'] . ')' : ''),
            'quantity' => (int)$item['quantity'],
        ];
    }

    $description = 'Prescription #' . $rx_id . ' - ' . $rx['patient_name'];
    $result = createCheckoutSession($amount_due, $description, $rx_id, $line_items);

    if ($result['success']) {
        $stmt = $conn->prepare("INSERT INTO payments (prescription_id, order_id, customer_id, amount_due, payment_method, paymongo_session_id, status) VALUES (?,?,?,?,'paymongo',?,'Pending')");
        $stmt->bind_param('iiids', $rx_id, $order['id'], $_SESSION['user_id'], $amount_due, $result['session_id']);
        $stmt->execute();

        header('Location: ' . $result['checkout_url']);
        exit();
    } else {
        $error = 'PayMongo error: ' . $result['error'];
    }
}

$cancelled = isset($_GET['cancelled']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .rx-header { border-bottom: 2px solid #c0392b; padding-bottom: 1rem; margin-bottom: 1.5rem; text-align: center; }
        .rx-header h4 { color: #c0392b; font-weight: 700; font-style: italic; }
        .rx-header p { color: #2563b0; font-weight: 600; margin: 0; }
        .med-table thead { background: #2c3e50; color: white; }
        .med-table td, .med-table th { vertical-align: middle; }
        .validity-note { color: #c0392b; font-style: italic; font-size: 0.85rem; }
        .total-box { background: #f0f4ff; border-radius: 8px; padding: 1.2rem 1.5rem; font-size: 1.3rem; font-weight: 700; color: #1a3a6b; }
        .pay-btn { background: linear-gradient(135deg, #1565c0, #0d47a1); border: none; border-radius: 10px; padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600; color: white; width: 100%; transition: all 0.3s; }
        .pay-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13,71,161,0.3); color: white; }
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
    <div class="no-print mt-3 mb-2 d-flex justify-content-between align-items-center">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Paying the Product</h5>
        <span></span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger mt-2"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($cancelled): ?>
        <div class="alert alert-warning mt-2"><i class="bi bi-x-circle"></i> Payment was cancelled. You can try again below.</div>
    <?php endif; ?>

    <div class="page-card">
        <div class="rx-header">
            <h4><?= htmlspecialchars($rx['doctor_name']) ?></h4>
            <p><?= htmlspecialchars($rx['doctor_specialization']) ?></p>
        </div>

        <div class="row mb-3">
            <div class="col-md-4"><strong>Date:</strong> <?= htmlspecialchars($rx['prescription_date']) ?></div>
            <div class="col-md-4"><strong>Patient:</strong> <?= htmlspecialchars($rx['patient_name']) ?></div>
            <div class="col-md-4"><?= htmlspecialchars($rx['patient_age']) ?> / <?= htmlspecialchars($rx['patient_gender']) ?></div>
        </div>

        <div class="text-center fw-bold my-3" style="letter-spacing:2px;">PRESCRIPTION</div>

        <div class="table-responsive">
            <table class="table table-bordered med-table">
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
                    <?php foreach ($order_items as $item): ?>
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
                        <td colspan="5" class="text-end">TOTAL DUE:</td>
                        <td>₱<?= number_format($amount_due, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>

        <div class="row mt-3 mb-4">
            <div class="col-md-6">
                <p class="mb-1"><?= htmlspecialchars($rx['doctor_clinic'] ?? '') ?></p>
                <p class="mb-1"><?= htmlspecialchars($rx['doctor_contact'] ?? '') ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-1">PRC: <?= htmlspecialchars($rx['doctor_prc'] ?? '') ?></p>
                <p class="mb-1">PTR: <?= htmlspecialchars($rx['doctor_ptr'] ?? '') ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm no-print">
            <div class="card-body p-4">
                <div class="total-box mb-4 text-center">
                    Total Amount Due: ₱<?= number_format($amount_due, 2) ?>
                </div>
                <p class="text-center text-muted mb-3">Accepted payment methods:</p>
                <div class="text-center mb-4">
                    <span class="badge bg-light text-dark border me-2 px-3 py-2 fs-6"><i class="bi bi-phone"></i> GCash</span>
                    <span class="badge bg-light text-dark border me-2 px-3 py-2 fs-6"><i class="bi bi-phone"></i> Maya</span>
                    <span class="badge bg-light text-dark border me-2 px-3 py-2 fs-6"><i class="bi bi-credit-card"></i> Card</span>
                    <span class="badge bg-light text-dark border me-2 px-3 py-2 fs-6"><i class="bi bi-bag"></i> GrabPay</span>
                    <span class="badge bg-light text-dark border px-3 py-2 fs-6"><i class="bi bi-qr-code"></i> QRPh</span>
                </div>
                <form method="POST">
                    <button type="submit" name="pay_paymongo" value="1" class="pay-btn">
                        <i class="bi bi-lock-fill me-2"></i> Pay Securely via PayMongo
                    </button>
                </form>
                <p class="text-center text-muted mt-3" style="font-size:0.8rem;">
                    <i class="bi bi-shield-check"></i> Secured by PayMongo. You will be redirected to a safe checkout page.
                </p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
