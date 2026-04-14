<?php
require_once '../../config.php';
require_once '../../models/product_log.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Check if user has correct role - Allow Pharmacy Assistant and Pharmacist (for read-only)
if (!in_array($_SESSION['role_name'], ['Pharmacy Assistant', 'Pharmacist'])) {
    header('Location: ../../index.php');
    exit();
}

$product_log = new ProductLog($conn);

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_medicine = $_GET['medicine'] ?? '';
$filter_pharmacist = $_GET['pharmacist'] ?? '';

// Get data based on filter
$logs = [];
$report_title = 'All Product Logs';

switch($filter_type) {
    case 'today':
        $logs = $product_log->getLogsByDateRange(date('Y-m-d'), date('Y-m-d'));
        $report_title = 'Today\'s Dispensing Report';
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $logs = $product_log->getLogsByDateRange($start_date, date('Y-m-d'));
        $report_title = 'Weekly Dispensing Report';
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $logs = $product_log->getLogsByDateRange($start_date, date('Y-m-d'));
        $report_title = 'Monthly Dispensing Report';
        break;
    case 'custom':
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $logs = $product_log->getLogsByDateRange($start_date, $end_date);
        $report_title = 'Custom Date Range Report';
        break;
    default:
        $logs = $product_log->getAllProductLogs();
        $report_title = 'All Product Logs';
}

// Get statistics
$sales_summary = $product_log->getProductSalesSummary();
$pharmacist_performance = $product_log->getPharmacistPerformance();
$today_report = $product_log->getDailyDispensingReport();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Logs Report - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .logs-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .logs-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stats-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .table-scroll {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .btn-export {
            background: #4caf50;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            color: white;
            font-weight: 600;
        }
        
        .btn-export:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="logs-container">
        <div class="container">
            <div class="logs-card">
                <h2><i class="bi bi-file-earmark-text"></i> Product Logs & Dispensing Report</h2>
                <p class="text-muted">Track all dispensed products and generate analytics reports</p>
                
                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stats-number"><?php echo $today_report['total_prescriptions'] ?? 0; ?></div>
                            <div class="stats-label">Today's Prescriptions</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stats-number"><?php echo $today_report['total_items'] ?? 0; ?></div>
                            <div class="stats-label">Items Dispensed Today</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stats-number"><?php echo $today_report['unique_patients'] ?? 0; ?></div>
                            <div class="stats-label">Patients Served Today</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stats-number">₱<?php echo number_format($today_report['total_revenue'] ?? 0, 2); ?></div>
                            <div class="stats-label">Today's Revenue</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <h5><i class="bi bi-funnel"></i> Filter Report</h5>
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Report Type</label>
                            <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Records</option>
                                <option value="today" <?php echo $filter_type === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $filter_type === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $filter_type === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="custom" <?php echo $filter_type === 'custom' ? 'selected' : ''; ?>>Custom Date</option>
                            </select>
                        </div>
                        <?php if ($filter_type === 'custom'): ?>
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Product Logs Table -->
                <div class="table-scroll">
                    <h5><i class="bi bi-list-ul"></i> <?php echo htmlspecialchars($report_title); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Order ID</th>
                                    <th>Prescription</th>
                                    <th>Patient</th>
                                    <th>Medicine</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                    <th>Pharmacist</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($log['log_date']))); ?></td>
                                            <td><?php echo htmlspecialchars($log['order_id']); ?></td>
                                            <td><?php echo htmlspecialchars($log['prescription_id']); ?></td>
                                            <td><?php echo htmlspecialchars($log['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($log['medicine_name'] . ' ' . $log['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($log['quantity_dispensed']); ?></td>
                                            <td>₱<?php echo number_format($log['unit_price'], 2); ?></td>
                                            <td><strong>₱<?php echo number_format($log['total_price'], 2); ?></strong></td>
                                            <td><?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Sales Summary -->
            <div class="logs-card">
                <h3><i class="bi bi-bar-chart"></i> Product Sales Summary</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Medicine</th>
                                <th>Dispensed Count</th>
                                <th>Total Quantity</th>
                                <th>Total Revenue</th>
                                <th>Avg Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sales_summary)): ?>
                                <?php foreach ($sales_summary as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['medicine_name'] . ' ' . $product['dosage']); ?></td>
                                        <td><?php echo htmlspecialchars($product['dispensed_count']); ?></td>
                                        <td><?php echo htmlspecialchars($product['total_quantity']); ?> units</td>
                                        <td><strong>₱<?php echo number_format($product['total_revenue'], 2); ?></strong></td>
                                        <td>₱<?php echo number_format($product['avg_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No sales data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pharmacist Performance -->
            <div class="logs-card">
                <h3><i class="bi bi-people"></i> Pharmacist Performance (Last 30 Days)</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Pharmacist</th>
                                <th>Prescriptions Filled</th>
                                <th>Items Dispensed</th>
                                <th>Total Quantity</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pharmacist_performance)): ?>
                                <?php foreach ($pharmacist_performance as $perf): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(($perf['first_name'] ?? '') . ' ' . ($perf['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($perf['prescriptions_filled']); ?></td>
                                        <td><?php echo htmlspecialchars($perf['items_dispensed']); ?></td>
                                        <td><?php echo htmlspecialchars($perf['total_quantity']); ?> units</td>
                                        <td><strong>₱<?php echo number_format($perf['total_revenue'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No performance data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="text-center mb-4">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <button onclick="window.print()" class="btn btn-export">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
