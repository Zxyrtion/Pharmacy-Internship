<?php
require_once '../../config.php';
require_once '../../models/inventory.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacist') {
    header('Location: ../index.php');
    exit();
}

$inventory = new Inventory($conn);

// Create stock logs table if not exists
$inventory->createStockLogsTable();

// Handle stock update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_stock') {
        $medicine_id = $_POST['medicine_id'];
        $new_stock = $_POST['new_stock'];
        $reason = $_POST['reason'] ?? 'Stock adjustment';
        
        if ($inventory->updateStockLevel($medicine_id, $new_stock, $reason)) {
            $success_message = "Stock level updated successfully!";
        } else {
            $error_message = "Failed to update stock level.";
        }
    }
}

// Initialize message variables
$success_message = '';
$error_message = '';

// Handle filtering
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Get medicines
if (!empty($search_term)) {
    $medicines = $inventory->searchMedicines($search_term);
} elseif (!empty($status_filter)) {
    $medicines = $inventory->getMedicinesByStockStatus($status_filter);
} else {
    $medicines = $inventory->getAllMedicinesWithStock();
}

// Get statistics
$stats = $inventory->getInventoryStats();

// Get stock logs
$stock_logs = $inventory->getStockLogs(null, 20);

// Get medicines needing reorder
$reorder_needed = $inventory->getMedicinesNeedingReorder();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .inventory-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .inventory-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stock-normal { background-color: #28a745; }
        .stock-low { background-color: #ffc107; }
        .stock-critical { background-color: #dc3545; }
        
        .reorder-alert {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .btn-update-stock {
            background: #17a2b8;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-update-stock:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        .stock-change-log {
            max-height: 300px;
            overflow-y: auto;
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

    <div class="inventory-container">
        <div class="container">
            <div class="inventory-card">
                <h2><i class="bi bi-capsule"></i> Manage Inventory</h2>
                <p class="text-muted">Check stock levels, monitor orders, and manage medicine inventory</p>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Section -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_medicines']; ?></div>
                        <div class="stat-label">Total Medicines</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['low_stock']; ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['critical_stock']; ?></div>
                        <div class="stat-label">Critical Stock</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">PHP <?php echo number_format($stats['total_value'], 2); ?></div>
                        <div class="stat-label">Total Value</div>
                    </div>
                </div>
                
                <!-- Reorder Alert Section -->
                <?php if (!empty($reorder_needed)): ?>
                    <div class="reorder-alert">
                        <h4><i class="bi bi-exclamation-triangle"></i> Items Needing Reorder</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reorder_needed as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                            <td>
                                                <span class="badge stock-critical">
                                                    <?php echo htmlspecialchars($item['stock_quantity']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['reorder_level']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" data-bs-target="#stockModal<?php echo $item['id']; ?>">
                                                    <i class="bi bi-arrow-up-circle"></i> Update Stock
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <h4><i class="bi bi-funnel"></i> Filter & Search</h4>
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Stock Status</label>
                            <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="Normal" <?php echo $status_filter === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="Low" <?php echo $status_filter === 'Low' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="Critical" <?php echo $status_filter === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Medicines</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search_term); ?>"
                                   placeholder="Search by name, dosage, or supplier...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Inventory Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Dosage</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Unit Price</th>
                                <th>Manufacturer</th>
                                <th>Stock Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medicines)): ?>
                                <?php foreach ($medicines as $medicine): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['dosage']); ?></td>
                                        <td>
                                            <span class="badge stock-<?php echo strtolower($medicine['stock_status']); ?>">
                                                <?php echo htmlspecialchars($medicine['stock_quantity']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($medicine['reorder_level']); ?></td>
                                        <td>PHP <?php echo number_format($medicine['unit_price'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['manufacturer'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge stock-<?php echo strtolower($medicine['stock_status']); ?>">
                                                <?php echo htmlspecialchars($medicine['stock_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info btn-action" 
                                                    data-bs-toggle="modal" data-bs-target="#stockModal<?php echo $medicine['id']; ?>">
                                                <i class="bi bi-arrow-up-circle"></i> Update Stock
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary btn-action" 
                                                    data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $medicine['id']; ?>">
                                                <i class="bi bi-eye"></i> Details
                                            </button>
                                        </td>
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
                
                <!-- Stock Change Log -->
                <div class="mt-4">
                    <h4><i class="bi bi-clock-history"></i> Recent Stock Changes</h4>
                    <div class="stock-change-log">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Previous Stock</th>
                                        <th>New Stock</th>
                                        <th>Change</th>
                                        <th>Reason</th>
                                        <th>User</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($stock_logs)): ?>
                                        <?php foreach ($stock_logs as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['medicine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($log['previous_stock']); ?></td>
                                                <td><?php echo htmlspecialchars($log['new_stock']); ?></td>
                                                <td>
                                                    <?php
                                                    $change = $log['new_stock'] - $log['previous_stock'];
                                                    $change_class = $change >= 0 ? 'text-success' : 'text-danger';
                                                    $change_icon = $change >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
                                                    ?>
                                                    <span class="<?php echo $change_class; ?>">
                                                        <i class="bi <?php echo $change_icon; ?>"></i>
                                                        <?php echo abs($change); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['reason'] ?? 'No reason provided'); ?></td>
                                                <td><?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($log['change_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No stock changes recorded</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="add_medicine.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Medicine
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stock Update Modals -->
    <?php foreach ($medicines as $medicine): ?>
        <div class="modal fade" id="stockModal<?php echo $medicine['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Stock Level</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Medicine</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($medicine['medicine_name'] . ' ' . $medicine['dosage']); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_stock<?php echo $medicine['id']; ?>" class="form-label">Current Stock</label>
                                <input type="number" class="form-control" id="new_stock<?php echo $medicine['id']; ?>" 
                                       name="new_stock" value="<?php echo $medicine['stock_quantity']; ?>" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason<?php echo $medicine['id']; ?>" class="form-label">Reason for Change</label>
                                <textarea class="form-control" id="reason<?php echo $medicine['id']; ?>" name="reason" rows="2" 
                                          placeholder="e.g., Stock received, Stock adjustment, Dispensed..."></textarea>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-update-stock">
                                    <i class="bi bi-check-circle"></i> Update Stock
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
