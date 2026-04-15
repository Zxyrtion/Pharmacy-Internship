<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$rx_id = (int)($_GET['rx_id'] ?? 0);
$amount = (float)($_GET['amount'] ?? 0);

if (!$rx_id || !$amount) {
    header('Location: dashboard.php');
    exit();
}

$full_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock Payment - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .payment-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 3rem; max-width: 500px; margin: 0 auto; }
        .payment-header { text-align: center; margin-bottom: 2rem; }
        .payment-header h2 { color: #667eea; font-weight: 700; }
        .amount-display { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 2rem; text-align: center; margin: 2rem 0; }
        .amount-display .amount { font-size: 2.5rem; font-weight: 700; }
        .btn-pay { background: linear-gradient(135deg, #28a745, #20c997); border: none; border-radius: 10px; padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600; color: white; width: 100%; transition: all 0.3s; }
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(40,167,69,0.3); color: white; }
        .btn-cancel { background: #6c757d; border: none; border-radius: 10px; padding: 0.8rem 2rem; font-size: 1rem; color: white; width: 100%; margin-top: 1rem; }
        .mock-notice { background: #fff3cd; border: 2px dashed #ffc107; border-radius: 10px; padding: 1rem; margin-bottom: 2rem; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="payment-card">
        <div class="mock-notice">
            <i class="bi bi-info-circle text-warning fs-4"></i>
            <p class="mb-0 mt-2"><strong>Development Mode</strong></p>
            <small>This is a mock payment page for testing. No real payment will be processed.</small>
        </div>
        
        <div class="payment-header">
            <i class="bi bi-credit-card fs-1 text-primary"></i>
            <h2>Complete Payment</h2>
            <p class="text-muted">Prescription #<?= $rx_id ?></p>
        </div>

        <div class="amount-display">
            <div class="small mb-2">Total Amount</div>
            <div class="amount">₱<?= number_format($amount, 2) ?></div>
        </div>

        <div class="mb-4">
            <h5 class="mb-3">Select Payment Method</h5>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="payment_method" id="gcash" value="gcash" checked>
                <label class="form-check-label" for="gcash">
                    <i class="bi bi-phone text-primary"></i> GCash
                </label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="payment_method" id="paymaya" value="paymaya">
                <label class="form-check-label" for="paymaya">
                    <i class="bi bi-wallet2 text-success"></i> PayMaya
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="payment_method" id="card" value="card">
                <label class="form-check-label" for="card">
                    <i class="bi bi-credit-card text-info"></i> Credit/Debit Card
                </label>
            </div>
        </div>

        <form method="GET" action="payment_success.php">
            <input type="hidden" name="rx_id" value="<?= $rx_id ?>">
            <input type="hidden" name="mock" value="1">
            <button type="submit" class="btn-pay">
                <i class="bi bi-check-circle me-2"></i> Simulate Successful Payment
            </button>
        </form>

        <a href="payment.php?rx_id=<?= $rx_id ?>&cancelled=1" class="btn btn-cancel">
            <i class="bi bi-x-circle me-2"></i> Cancel Payment
        </a>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-check"></i> Secured Payment Gateway
            </small>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
