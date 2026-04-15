<?php
require_once '../../config.php';
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

// Initialize inventory model
$inventory = new Inventory($conn);

// Get inventory data
$all_medicines = $inventory->getAllMedicinesWithStock();
$inventory_stats = $inventory->getInventoryStats();
$low_stock_medicines = $inventory->getMedicinesByStockStatus('Low');
$critical_stock_medicines = $inventory->getMedicinesByStockStatus('Critical');
$recent_stock_logs = $inventory->getStockLogs(null, 20);

// Handle search
$search_term = '';
if (isset($_GET['search'])) {
    $search_term = $_GET['search'];
    $all_medicines = $inventory->searchMedicines($search_term);
}

// Handle stock filter
$filter = 'all';
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    switch($filter) {
        case 'low':
            $all_medicines = $low_stock_medicines;
            break;
        case 'critical':
            $all_medicines = $critical_stock_medicines;
            break;
        case 'normal':
            $all_medicines = $inventory->getMedicinesByStockStatus('Normal');
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Pharmacy Assistant</title>
    
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
        
        .stock-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-box-seam"></i> Inventory Management</h1>
                <p class="mb-0">Monitor stock levels and assist with inventory tasks.</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $inventory_stats['total_medicines']; ?></h4>
                                    <p class="card-text">Total Medicines</p>
                                </div>
                                <i class="bi bi-capsule fs-1"></i>
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
                                    <p class="card-text">Low Stock</p>
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
                                    <p class="card-text">Total Value</p>
                                </div>
                                <i class="bi bi-currency-peso fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="search-box">
                <div class="row">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search medicines..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="?filter=normal" class="btn <?php echo $filter === 'normal' ? 'btn-success' : 'btn-outline-success'; ?>">Normal Stock</a>
                            <a href="?filter=low" class="btn <?php echo $filter === 'low' ? 'btn-warning' : 'btn-outline-warning'; ?>">Low Stock</a>
                            <a href="?filter=critical" class="btn <?php echo $filter === 'critical' ? 'btn-danger' : 'btn-outline-danger'; ?>">Critical</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Table -->
            <div class="dashboard-card">
                <h3><i class="bi bi-list-ul"></i> Medicine Inventory</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Dosage</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Unit Price</th>
                                <th>Total Value</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_medicines)): ?>
                                <?php foreach ($all_medicines as $medicine): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['dosage']); ?></td>
                                        <td>
                                            <span class="fw-bold"><?php echo htmlspecialchars($medicine['stock_quantity']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($medicine['reorder_level']); ?></td>
                                        <td>PHP <?php echo number_format($medicine['unit_price'], 2); ?></td>
                                        <td>PHP <?php echo number_format($medicine['stock_quantity'] * $medicine['unit_price'], 2); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            $status_text = $medicine['stock_status'];
                                            switch($medicine['stock_status']) {
                                                case 'Normal':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'Low':
                                                    $badge_class = 'bg-warning';
                                                    break;
                                                case 'Critical':
                                                    $badge_class = 'bg-danger';
                                                    break;
                                                default:
                                                    $badge_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> stock-badge"><?php echo htmlspecialchars($status_text); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($medicine['updated_at'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No medicines found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        // Auto-refresh inventory every 60 seconds
        setInterval(function() {
            location.reload();
        }, 60000);
        
        // Highlight critical items on page load
        document.addEventListener('DOMContentLoaded', function() {
            const criticalItems = document.querySelectorAll('.badge.bg-danger');
            if (criticalItems.length > 0) {
                console.log('Found ' + criticalItems.length + ' critical stock items');
            }
        });
    </script>
</body>
</html>
