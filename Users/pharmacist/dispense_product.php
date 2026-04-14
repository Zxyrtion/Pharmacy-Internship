<?php
require_once '../../config.php';
require_once '../../models/prescription.php';
require_once '../../models/product_log.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacist') { header('Location: /Pharmacy-Internship/index.php'); exit(); }

$prescription = new Prescription($conn);
$product_log = new ProductLog($conn);
$success_message = '';
$error_message = '';

$product_log->createProductLogsTable();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'dispense') {
        $order_id      = $_POST['order_id'];
        $prescription_id = $_POST['prescription_id'];
        $medicine_id   = $_POST['medicine_id'];
        $medicine_name = $_POST['medicine_name'];
        $dosage        = $_POST['dosage'] ?? '';
        $quantity      = $_POST['quantity'];
        $unit_price    = $_POST['unit_price'];
        $patient_id    = $_POST['patient_id'];
        $patient_name  = $_POST['patient_name'];
        $notes         = $_POST['notes'] ?? '';
        $pharmacist_id = $_SESSION['user_id'];
        $total_price   = $quantity * $unit_price;

        $conn->begin_transaction();
        try {
            $product_log->logProductDispense($order_id, $prescription_id, $medicine_id, $medicine_name, $dosage, $quantity, $unit_price, $total_price, $pharmacist_id, $patient_id, $patient_name);
            $stmt = $conn->prepare("UPDATE orders SET status = 'Completed', completed_date = NOW() WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $prescription->updateStatus($_POST['prescription_id'], 'Dispensed');
            $conn->commit();
            $success_message = "Product dispensed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to dispense product: " . $e->getMessage();
        }
    }
}

$order_details = null;
$order_items = [];
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    $sql = "SELECT o.*, u.first_name, u.last_name FROM orders o
            LEFT JOIN users u ON o.pharmacist_id = u.id
            WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_details = $stmt->get_result()->fetch_assoc();

    $items_sql = "SELECT oi.*, COALESCE(m.stock_quantity, 0) as stock_quantity
                  FROM order_items oi
                  LEFT JOIN medicines m ON m.medicine_name = oi.medicine_name
                  WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$today_logs   = $product_log->getDailyDispensingReport() ?? [];
$recent_dispensing = $product_log->getAllProductLogs();

$full_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispense Product - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../style.css">
    <style>
        .dispense-container { min-height:100vh; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); padding:2rem 0; }
        .dispense-card { background:white; border-radius:20px; box-shadow:0 20px 40px rgba(0,0,0,.1); padding:2rem; margin:1rem 0; }
        .stats-card { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border-radius:15px; padding:1.5rem; text-align:center; margin-bottom:1rem; }
        .stats-number { font-size:1.8rem; font-weight:bold; }
        .btn-dispense { background:#4caf50; border:none; border-radius:25px; padding:12px 30px; font-weight:600; }
        .btn-dispense:hover { background:#45a049; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="dispense-container">
    <div class="container">
        <div class="dispense-card">
            <h2><i class="bi bi-box-seam"></i> Dispense Product - Process 17</h2>
            <p class="text-muted">Check product availability and record dispensing of medications to customers</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?= $today_logs['total_prescriptions'] ?? 0 ?></div><div>Prescriptions Today</div></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?= $today_logs['total_items'] ?? 0 ?></div><div>Items Dispensed</div></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?= $today_logs['unique_patients'] ?? 0 ?></div><div>Patients Served</div></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number">₱<?= number_format($today_logs['total_revenue'] ?? 0, 2) ?></div><div>Today's Revenue</div></div></div>
            </div>

            <?php if ($order_details): ?>
            <div class="bg-light rounded p-3 mb-3">
                <h4>Order Details</h4>
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order_details['order_id'] ?? '') ?></p>
                <p><strong>Status:</strong> <span class="badge bg-info"><?= htmlspecialchars($order_details['status'] ?? '') ?></span></p>
                <p><strong>Total:</strong> ₱<?= number_format($order_details['total_amount'] ?? 0, 2) ?></p>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr><th>Medicine</th><th>Qty</th><th>Available Stock</th><th>Unit Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td>
                                <?php if ($item['stock_quantity'] >= $item['quantity']): ?>
                                    <span class="badge bg-success"><?= $item['stock_quantity'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Only <?= $item['stock_quantity'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                            <td>₱<?= number_format($item['total_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($order_items): ?>
            <form method="POST">
                <input type="hidden" name="action" value="dispense">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_details['id'] ?? '') ?>">
                <input type="hidden" name="prescription_id" value="<?= htmlspecialchars($order_details['prescription_id'] ?? '') ?>">
                <input type="hidden" name="medicine_id" value="<?= htmlspecialchars($order_items[0]['id'] ?? '') ?>">
                <input type="hidden" name="medicine_name" value="<?= htmlspecialchars($order_items[0]['medicine_name'] ?? '') ?>">
                <input type="hidden" name="dosage" value="">
                <input type="hidden" name="quantity" value="<?= htmlspecialchars($order_items[0]['quantity'] ?? '') ?>">
                <input type="hidden" name="unit_price" value="<?= htmlspecialchars($order_items[0]['unit_price'] ?? '') ?>">
                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($order_details['customer_id'] ?? '') ?>">
                <input type="hidden" name="patient_name" value="<?= htmlspecialchars($order_details['customer_name'] ?? '') ?>">
                <div class="mb-3">
                    <label class="form-label">Dispensing Notes</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <button type="submit" class="btn btn-dispense text-white"><i class="bi bi-check-circle"></i> Confirm Dispensing</button>
                </div>
            </form>
            <?php endif; ?>

            <?php else: ?>
            <div class="alert alert-info"><i class="bi bi-info-circle"></i> No order selected. Please select an order from the dashboard.</div>
            <a href="dashboard.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            <?php endif; ?>
        </div>

        <!-- Recent Dispensing Log -->
        <div class="dispense-card">
            <h3><i class="bi bi-clock-history"></i> Recent Dispensing Log</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Patient</th><th>Medicine</th><th>Qty</th><th>Total</th><th>By</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_dispensing)): ?>
                            <?php foreach (array_slice($recent_dispensing, 0, 20) as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('M j, Y H:i', strtotime($log['log_date']))) ?></td>
                                <td><?= htmlspecialchars($log['patient_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['medicine_name'] . ($log['generic_name'] ? ' (' . $log['generic_name'] . ')' : '')) ?></td>
                                <td><?= htmlspecialchars($log['quantity'] ?? $log['quantity_dispensed'] ?? '-') ?></td>
                                <td>₱<?= number_format($log['total_price'], 2) ?></td>
                                <td><?= htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?></td>
                                <td><span class="badge bg-success">Dispensed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No dispensing records yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
