<?php
require_once '../../config.php';
require_once '../../models/product_log.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if (!in_array($_SESSION['role_name'], ['Pharmacist', 'HR Personnel'])) { header('Location: /internship/index.php'); exit(); }

$product_log = new ProductLog($conn);
$filter_type = $_GET['type'] ?? 'all';

$logs = [];
$report_title = 'All Product Logs';
switch($filter_type) {
    case 'today':  $logs = $product_log->getLogsByDateRange(date('Y-m-d'), date('Y-m-d')); $report_title = "Today's Report"; break;
    case 'week':   $logs = $product_log->getLogsByDateRange(date('Y-m-d', strtotime('-7 days')), date('Y-m-d')); $report_title = 'Weekly Report'; break;
    case 'month':  $logs = $product_log->getLogsByDateRange(date('Y-m-01'), date('Y-m-d')); $report_title = 'Monthly Report'; break;
    case 'custom':
        $start = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end   = $_GET['end_date'] ?? date('Y-m-d');
        $logs  = $product_log->getLogsByDateRange($start, $end);
        $report_title = 'Custom Range Report'; break;
    default: $logs = $product_log->getAllProductLogs();
}

$sales_summary          = $product_log->getProductSalesSummary();
$pharmacist_performance = $product_log->getPharmacistPerformance();
$today_report           = $product_log->getDailyDispensingReport() ?? [];
$full_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Logs - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../style.css">
    <style>
        .logs-container { min-height:100vh; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); padding:2rem 0; }
        .logs-card { background:white; border-radius:20px; box-shadow:0 20px 40px rgba(0,0,0,.1); padding:2rem; margin:1rem 0; }
        .stats-box { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border-radius:15px; padding:1.5rem; text-align:center; margin-bottom:1rem; }
        .stats-number { font-size:2rem; font-weight:bold; }
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

<div class="logs-container">
    <div class="container">
        <div class="logs-card">
            <h2><i class="bi bi-file-earmark-text"></i> Product Logs & Dispensing Report</h2>

            <div class="row mb-4">
                <div class="col-md-3"><div class="stats-box"><div class="stats-number"><?= $today_report['total_prescriptions'] ?? 0 ?></div><div>Today's Prescriptions</div></div></div>
                <div class="col-md-3"><div class="stats-box"><div class="stats-number"><?= $today_report['total_items'] ?? 0 ?></div><div>Items Dispensed</div></div></div>
                <div class="col-md-3"><div class="stats-box"><div class="stats-number"><?= $today_report['unique_patients'] ?? 0 ?></div><div>Patients Today</div></div></div>
                <div class="col-md-3"><div class="stats-box"><div class="stats-number">₱<?= number_format($today_report['total_revenue'] ?? 0, 2) ?></div><div>Today's Revenue</div></div></div>
            </div>

            <div class="bg-light rounded p-3 mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" name="type" onchange="this.form.submit()">
                            <option value="all"    <?= $filter_type==='all'    ? 'selected':'' ?>>All Records</option>
                            <option value="today"  <?= $filter_type==='today'  ? 'selected':'' ?>>Today</option>
                            <option value="week"   <?= $filter_type==='week'   ? 'selected':'' ?>>Last 7 Days</option>
                            <option value="month"  <?= $filter_type==='month'  ? 'selected':'' ?>>This Month</option>
                            <option value="custom" <?= $filter_type==='custom' ? 'selected':'' ?>>Custom Date</option>
                        </select>
                    </div>
                    <?php if ($filter_type === 'custom'): ?>
                    <div class="col-md-3"><label class="form-label">Start</label><input type="date" class="form-control" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>"></div>
                    <div class="col-md-3"><label class="form-label">End</label><input type="date" class="form-control" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>"></div>
                    <div class="col-md-3"><label class="form-label">&nbsp;</label><button type="submit" class="btn btn-primary d-block w-100">Filter</button></div>
                    <?php endif; ?>
                </form>
            </div>

            <h5><?= htmlspecialchars($report_title) ?></h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Order</th><th>Prescription</th><th>Patient</th><th>Medicine</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>By</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('M j, Y H:i', strtotime($log['log_date']))) ?></td>
                                <td><?= htmlspecialchars($log['order_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['prescription_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['patient_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['medicine_name'] . ($log['generic_name'] ? ' (' . $log['generic_name'] . ')' : '')) ?></td>
                                <td><?= htmlspecialchars($log['quantity'] ?? $log['quantity_dispensed'] ?? '-') ?></td>
                                <td>₱<?= number_format($log['unit_price'], 2) ?></td>
                                <td><strong>₱<?= number_format($log['total_price'], 2) ?></strong></td>
                                <td><?= htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?></td>
                                <td><span class="badge bg-success">Dispensed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center">No records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sales Summary -->
        <div class="logs-card">
            <h3><i class="bi bi-bar-chart"></i> Product Sales Summary</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>Medicine</th><th>Count</th><th>Total Qty</th><th>Total Revenue</th><th>Avg Price</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sales_summary)): ?>
                            <?php foreach ($sales_summary as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['medicine_name'] . ($p['generic_name'] ? ' (' . $p['generic_name'] . ')' : '')) ?></td>
                                <td><?= $p['dispensed_count'] ?></td>
                                <td><?= $p['total_quantity'] ?></td>
                                <td><strong>₱<?= number_format($p['total_revenue'], 2) ?></strong></td>
                                <td>₱<?= number_format($p['avg_price'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mb-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
