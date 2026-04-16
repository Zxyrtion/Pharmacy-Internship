<?php
// DEBUG: Log every page load
file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - Page accessed. Method: " . $_SERVER['REQUEST_METHOD'] . ", rx_id: " . ($_GET['rx_id'] ?? 'NONE') . "\n", FILE_APPEND);

// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';
require_once '../../core/paymongo.php';

if (!isLoggedIn()) { 
    file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - Not logged in, redirecting\n", FILE_APPEND);
    header('Location: ../../views/auth/login.php'); 
    exit(); 
}
if ($_SESSION['role_name'] !== 'Customer') { 
    file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - Not customer, redirecting\n", FILE_APPEND);
    header('Location: ../../index.php'); 
    exit(); 
}

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

$rx_id = $_GET['rx_id'] ?? '';
if (!$rx_id) { 
    file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - No rx_id, redirecting to dashboard\n", FILE_APPEND);
    header('Location: dashboard.php'); 
    exit(); 
}

// Check if rx_id is numeric (database id) or string (prescription_id)
if (is_numeric($rx_id)) {
    // It's a database ID
    $s = $conn->prepare("SELECT p.* FROM prescriptions p WHERE p.id=? AND p.patient_id=?");
    $s->bind_param('ii', $rx_id, $_SESSION['user_id']);
} else {
    // It's a prescription_id string (like RX-20260415-2305)
    $s = $conn->prepare("SELECT p.* FROM prescriptions p WHERE p.prescription_id=? AND p.patient_id=? LIMIT 1");
    $s->bind_param('si', $rx_id, $_SESSION['user_id']);
}
$s->execute();
$rx = $s->get_result()->fetch_assoc();

if (!$rx) { 
    file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - Prescription not found, redirecting to dashboard\n", FILE_APPEND);
    header('Location: dashboard.php'); 
    exit(); 
}

file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - Prescription found. Status: " . $rx['status'] . "\n", FILE_APPEND);

// Get the numeric ID for later use
$rx_numeric_id = (int)$rx['id'];

// Check if prescription is ready for payment
if ($rx['status'] !== 'Ready') {
    file_put_contents('payment_access.log', date('Y-m-d H:i:s') . " - Status not Ready: " . $rx['status'] . "\n", FILE_APPEND);
    // If already completed, redirect to dashboard
    if ($rx['status'] === 'Completed') {
        header('Location: dashboard.php?msg=already_paid');
        exit();
    }
    // Otherwise redirect to track dispensing with a message
    header('Location: track_dispensing.php?error=not_ready&status=' . urlencode($rx['status'])); 
    exit(); 
}

// Get order from prescription_orders table
$order = null;
$so = $conn->prepare("SELECT * FROM prescription_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
if ($so) {
    $so->bind_param('i', $rx_numeric_id);
    $so->execute();
    $order = $so->get_result()->fetch_assoc();
}

// Get order items
$order_items = [];
$order_id_val = (int)($order['id'] ?? -1);

if ($order_id_val >= 0) {  // Allow order_id = 0
    $si = $conn->prepare("SELECT * FROM prescription_order_items WHERE order_id=?");
    if ($si) {
        $si->bind_param('i', $order_id_val);
        $si->execute();
        $order_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// If no order or items found, show error
if (!$order || empty($order_items)) {
    $error = 'Order details not found. The prescription may not have been processed yet. Please contact the pharmacy.';
    // Don't allow payment if no order data
    $amount_due = 0;
} else {
    $amount_due = (float)($order['total_amount'] ?? 0);
}

// Create PayMongo checkout session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_paymongo'])) {
    // DEBUG: Log that we're in POST handler
    file_put_contents('payment_debug.log', date('Y-m-d H:i:s') . " - POST received\n", FILE_APPEND);
    
    // Check if we have order items
    if (empty($order_items)) {
        $error = 'Cannot process payment: No order items found.';
        file_put_contents('payment_debug.log', date('Y-m-d H:i:s') . " - No order items\n", FILE_APPEND);
    } else {
        file_put_contents('payment_debug.log', date('Y-m-d H:i:s') . " - Order items found: " . count($order_items) . "\n", FILE_APPEND);
        
        $line_items = [];
        foreach ($order_items as $item) {
            $line_items[] = [
                'currency' => 'PHP',
                'amount'   => (int)round((float)$item['unit_price'] * 100),
                'name'     => $item['medicine_name'] . ($item['generic_name'] ? ' (' . $item['generic_name'] . ')' : ''),
                'quantity' => (int)$item['quantity'],
            ];
        }

        $description = 'Prescription #' . $rx_numeric_id . ' - ' . $rx['patient_name'];
        $result = createCheckoutSession($amount_due, $description, $rx_numeric_id, $line_items);
        
        file_put_contents('payment_debug.log', date('Y-m-d H:i:s') . " - createCheckoutSession result: " . json_encode($result) . "\n", FILE_APPEND);

        if ($result['success']) {
            $stmt = $conn->prepare("INSERT INTO payments (prescription_id, order_id, customer_id, amount_due, payment_method, paymongo_session_id, status) VALUES (?,?,?,?,'paymongo',?,'Pending')");
            $stmt->bind_param('iiids', $rx_numeric_id, $order['id'], $_SESSION['user_id'], $amount_due, $result['session_id']);
            $stmt->execute();

            file_put_contents('payment_debug.log', date('Y-m-d H:i:s') . " - Redirecting to: " . $result['checkout_url'] . "\n", FILE_APPEND);
            
            header('Location: ' . $result['checkout_url']);
            exit();
        } else {
            $error = 'PayMongo error: ' . $result['error'];
            file_put_contents('payment_debug.log', date('Y-m-d H:i:s') . " - Error: " . $result['error'] . "\n", FILE_APPEND);
        }
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

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-warning mt-2">
            <strong>DEBUG:</strong> POST request received! pay_paymongo = <?= isset($_POST['pay_paymongo']) ? 'YES' : 'NO' ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger mt-2"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($cancelled): ?>
        <div class="alert alert-warning mt-2"><i class="bi bi-x-circle"></i> Payment was cancelled. You can try again below.</div>
    <?php endif; ?>
    <?php if (defined('PAYMONGO_MOCK_MODE') && PAYMONGO_MOCK_MODE): ?>
        <div class="alert alert-info mt-2">
            <i class="bi bi-info-circle"></i> <strong>Development Mode:</strong> This system is using mock payment for testing. No real payment will be processed.
        </div>
    <?php endif; ?>

    <div class="page-card">
        <div class="rx-header">
            <h4><?= htmlspecialchars($rx['doctor_name'] ?? 'N/A') ?></h4>
            <?php if (!empty($rx['doctor_specialization'] ?? '')): ?>
            <p><?= htmlspecialchars($rx['doctor_specialization']) ?></p>
            <?php endif; ?>
        </div>

        <div class="row mb-3">
            <div class="col-md-4"><strong>Date:</strong> <?= htmlspecialchars($rx['date_prescribed'] ?? date('Y-m-d')) ?></div>
            <div class="col-md-4"><strong>Patient:</strong> <?= htmlspecialchars($rx['patient_name'] ?? 'N/A') ?></div>
            <div class="col-md-4">
                <?php 
                $age = $rx['patient_age'] ?? '';
                $gender = $rx['patient_gender'] ?? '';
                if ($age || $gender) {
                    echo htmlspecialchars($age);
                    if ($age && $gender) echo ' / ';
                    echo htmlspecialchars($gender);
                }
                ?>
            </div>
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
                <p class="text-center text-muted mb-3">Payment method:</p>
                <div class="text-center mb-4">
                    <span class="badge bg-success px-4 py-3 fs-5">
                        <i class="bi bi-phone"></i> GCash
                    </span>
                </div>
                <form method="POST" action="" onsubmit="console.log('Form submitting...'); return true;">
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
