<?php
require_once '../../config.php';
require_once '../../models/purchase_order.php';

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

$purchaseOrder = new PurchaseOrder($conn);
$success_message = '';
$error_message = '';

// Handle approval/rejection actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $requisition_id = $_POST['requisition_id'];
    $approver_id = $_SESSION['user_id'];
    
    switch($_POST['action']) {
        case 'approve':
            $result = $purchaseOrder->approveRequisition($requisition_id, $approver_id);
            if ($result) {
                $success_message = "Requisition approved successfully!";
            } else {
                $error_message = "Failed to approve requisition.";
            }
            break;
            
        case 'reject':
            $reason = $_POST['rejection_reason'] ?? '';
            $result = $purchaseOrder->rejectRequisition($requisition_id, $approver_id, $reason);
            if ($result) {
                $success_message = "Requisition rejected successfully!";
            } else {
                $error_message = "Failed to reject requisition.";
            }
            break;
    }
}

// Handle filter changes
$status_filter = $_GET['status'] ?? '';

// Get requisitions
$requisitions = $purchaseOrder->getAllRequisitionsWithFilter($status_filter);

// Get statistics
$stats = $purchaseOrder->getRequisitionStats();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requisitions - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .manage-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .manage-card {
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
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 5px;
        }
        
        .status-submitted { background-color: #6c757d; }
        .status-approved { background-color: #28a745; }
        .status-rejected { background-color: #dc3545; }
        .status-processed { background-color: #17a2b8; }
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

    <div class="manage-container">
        <div class="container">
            <div class="manage-card">
                <h2><i class="bi bi-clipboard-check"></i> Manage Requisitions</h2>
                <p class="text-muted">View, approve, and manage all purchase requisitions</p>
                
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
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Requisitions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['processed']; ?></div>
                        <div class="stat-label">Processed</div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <h4><i class="bi bi-funnel"></i> Filter Requisitions</h4>
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="Submitted" <?php echo $status_filter === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="Processed" <?php echo $status_filter === 'Processed' ? 'selected' : ''; ?>>Processed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                        </div>
                    </form>
                </div>
                
                <!-- Requisitions Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Requisition ID</th>
                                <th>Requested By</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Urgency</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($requisitions)): ?>
                                <?php foreach ($requisitions as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['requisition_id']); ?></td>
                                        <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['department']); ?></td>
                                        <td><?php echo htmlspecialchars($req['requisition_date']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                echo match($req['urgency']) {
                                                    'Normal' => 'bg-success',
                                                    'Urgent' => 'bg-warning',
                                                    'Critical' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                }; 
                                                ?>">
                                                <?php echo htmlspecialchars($req['urgency']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($req['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge status-<?php echo strtolower($req['status']); ?>">
                                                <?php echo htmlspecialchars($req['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($req['status'] === 'Submitted'): ?>
                                                <button type="button" class="btn btn-sm btn-success btn-action me-1" 
                                                        data-bs-toggle="modal" data-bs-target="#approveModal" 
                                                        onclick="setRequisitionId(<?php echo $req['id']; ?>, 'approve')">
                                                    <i class="bi bi-check-circle"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger btn-action" 
                                                        data-bs-toggle="modal" data-bs-target="#rejectModal"
                                                        onclick="setRequisitionId(<?php echo $req['id']; ?>, 'reject')">
                                                    <i class="bi bi-x-circle"></i> Reject
                                                </button>
                                            <?php elseif ($req['status'] === 'Approved'): ?>
                                                <a href="generate_purchase_order.php?requisition_id=<?php echo urlencode($req['id']); ?>" 
                                                   class="btn btn-sm btn-primary btn-action">
                                                    <i class="bi bi-cart-plus"></i> Generate PO
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="view_requisition.php?id=<?php echo urlencode($req['id']); ?>" 
                                               class="btn btn-sm btn-info btn-action">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No requisitions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="create_requisition.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create New Requisition
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this requisition?</p>
                    <form method="POST" action="">
                        <input type="hidden" name="requisition_id" id="approveRequisitionId">
                        <input type="hidden" name="action" value="approve">
                        <div class="text-center">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Yes, Approve
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
    
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="requisition_id" id="rejectRequisitionId">
                        <input type="hidden" name="action" value="reject">
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Rejection Reason</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" 
                                      placeholder="Please provide reason for rejection..." required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> Reject Requisition
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function setRequisitionId(reqId, action) {
            if (action === 'approve') {
                document.getElementById('approveRequisitionId').value = reqId;
            } else if (action === 'reject') {
                document.getElementById('rejectRequisitionId').value = reqId;
            }
        }
    </script>
</body>
</html>
