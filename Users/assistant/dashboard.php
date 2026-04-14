<?php
require_once '../../config.php';
require_once '../../models/prescription.php';
require_once '../../models/inventory.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') {
    header('Location: ../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize models
$prescription = new Prescription($conn);
$inventory = new Inventory($conn);

// Get data for dashboard
$pending_prescriptions = $prescription->getPendingPrescriptions();
$prescription_stats = $prescription->getPrescriptionStats();
$inventory_stats = $inventory->getInventoryStats();
$low_stock_medicines = $inventory->getMedicinesByStockStatus('Low');
$critical_stock_medicines = $inventory->getMedicinesByStockStatus('Critical');
$recent_stock_logs = $inventory->getStockLogs(null, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Assistant Dashboard - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .welcome-header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #f39c12;
            margin-bottom: 1rem;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-person-plus"></i> Pharmacy Assistant Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Assist with customer service and inventory management.</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $prescription_stats['pending']; ?></h4>
                                    <p class="card-text">Pending Prescriptions</p>
                                </div>
                                <i class="bi bi-file-medical fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $inventory_stats['low_stock']; ?></h4>
                                    <p class="card-text">Low Stock Items</p>
                                </div>
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $inventory_stats['critical_stock']; ?></h4>
                                    <p class="card-text">Critical Stock</p>
                                </div>
                                <i class="bi bi-x-circle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">PHP <?php echo number_format($inventory_stats['total_value'], 2); ?></h4>
                                    <p class="card-text">Total Stock Value</p>
                                </div>
                                <i class="bi bi-currency-peso fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Feature Cards -->
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-people feature-icon"></i>
                        <h4>Customer Service</h4>
                        <p>Assist customers with inquiries</p>
                        <button class="btn btn-primary" onclick="showCustomerService()">Help Desk</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-box-seam feature-icon"></i>
                        <h4>Inventory Help</h4>
                        <p>Assist with stock management</p>
                        <a href="inventory.php" class="btn btn-primary">View Stock</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-receipt feature-icon"></i>
                        <h4>Order Processing</h4>
                        <p>Process customer orders</p>
                        <button class="btn btn-primary" onclick="showOrders()">View Orders</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-telephone feature-icon"></i>
                        <h4>Phone Support</h4>
                        <p>Handle customer calls</p>
                        <button class="btn btn-primary" onclick="showCallCenter()">Call Center</button>
                    </div>
                </div>
            </div>
            
            <!-- Process 16 & 17: Product Availability Check and Dispensing -->
            <div class="row mt-4 mb-4">
                <div class="col-md-12">
                    <div style="background: linear-gradient(135deg, #27ae60, #229954); border-radius: 15px; padding: 2rem; color: white;">
                        <h3><i class="bi bi-box-seam"></i> Check Availability & Dispense Products</h3>
                        <p>Check medicine availability in inventory and record product dispensing to customers</p>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <a href="dispense_product.php" class="btn btn-light btn-lg">
                                    <i class="bi bi-download"></i> Dispense Product
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="product_logs.php" class="btn btn-light btn-lg">
                                    <i class="bi bi-file-earmark-text"></i> View Product Logs & Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Prescriptions Section -->
            <div class="dashboard-card">
                <h3><i class="bi bi-file-medical"></i> Pending Prescriptions</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Prescription ID</th>
                                <th>Patient</th>
                                <th>Medicine</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pending_prescriptions)): ?>
                                <?php foreach (array_slice($pending_prescriptions, 0, 5) as $presc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($presc['prescription_id']); ?></td>
                                        <td><?php echo htmlspecialchars($presc['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($presc['medicine_name'] . ' ' . $presc['dosage']); ?></td>
                                        <td><?php echo htmlspecialchars($presc['date_prescribed']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewPrescription('<?php echo urlencode($presc['prescription_id']); ?>')">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No pending prescriptions</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Stock Alerts Section -->
            <div class="row">
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h3><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($low_stock_medicines)): ?>
                                        <?php foreach (array_slice($low_stock_medicines, 0, 3) as $medicine): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['stock_quantity']); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['reorder_level']); ?></td>
                                                <td>
                                                    <span class="badge bg-warning">Low</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No low stock items</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h3><i class="bi bi-x-circle"></i> Critical Stock</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($critical_stock_medicines)): ?>
                                        <?php foreach (array_slice($critical_stock_medicines, 0, 3) as $medicine): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['stock_quantity']); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['reorder_level']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger">Critical</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No critical stock items</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Stock Activities -->
            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history"></i> Recent Stock Activities</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>User</th>
                                <th>Previous Stock</th>
                                <th>New Stock</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_stock_logs)): ?>
                                <?php foreach ($recent_stock_logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['medicine_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['previous_stock']); ?></td>
                                        <td><?php echo htmlspecialchars($log['new_stock']); ?></td>
                                        <td><?php echo htmlspecialchars($log['reason'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($log['change_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No recent stock activities</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewPrescription(prescriptionId) {
            // Redirect to pharmacist's process order page with view mode
            window.location.href = '../pharmacist/process_order.php?prescription_id=' + prescriptionId + '&view=1';
        }
        
        function showCustomerService() {
            alert('Customer Service module would open here. This would include:\n- Customer inquiry tracking\n- Service ticket management\n- Customer communication log\n- FAQ and help resources');
        }
        
        function showOrders() {
            alert('Order Processing module would open here. This would include:\n- Customer order queue\n- Order status tracking\n- Payment processing\n- Order history');
        }
        
        function showCallCenter() {
            alert('Call Center module would open here. This would include:\n- Incoming call queue\n- Call history log\n- Customer phone directory\n- Call scripts and protocols');
        }
        
        // Auto-refresh dashboard every 30 seconds to get latest data
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Show notification if there are critical stock items
        document.addEventListener('DOMContentLoaded', function() {
            const criticalCount = <?php echo count($critical_stock_medicines); ?>;
            if (criticalCount > 0) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                alertDiv.style.zIndex = '9999';
                alertDiv.innerHTML = `
                    <strong>Critical Stock Alert!</strong> You have ${criticalCount} critical stock items that need immediate attention.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Auto-dismiss after 10 seconds
                setTimeout(function() {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 10000);
            }
        });
    </script>
</body>
</html>
