<?php
require_once '../../config.php';
require_once '../../models/purchase_order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacy Technician') {
    header('Location: /Pharmacy-Internship/index.php');
    exit();
}

$purchaseOrder = new PurchaseOrder($conn);
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Handle delete action
if (isset($_POST['delete_requisition'])) {
    $delete_id = $_POST['delete_id'];
    
    // Verify ownership and status
    $req_to_delete = $purchaseOrder->getRequisitionById($delete_id);
    if ($req_to_delete && $req_to_delete['pharmacist_id'] == $user_id && $req_to_delete['status'] === 'Draft') {
        if ($purchaseOrder->deleteRequisition($delete_id)) {
            $_SESSION['success_message'] = "Requisition deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete requisition.";
        }
    } else {
        $_SESSION['error_message'] = "Cannot delete this requisition.";
    }
    header('Location: my_requisitions.php');
    exit();
}

// Get success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Get filter
$status_filter = $_GET['status'] ?? '';

// Get user's requisitions
if (!empty($status_filter)) {
    $requisitions = array_filter(
        $purchaseOrder->getRequisitionsByUserId($user_id),
        function($req) use ($status_filter) {
            return $req['status'] === $status_filter;
        }
    );
} else {
    $requisitions = $purchaseOrder->getRequisitionsByUserId($user_id);
}

// Get statistics
$stats = $purchaseOrder->getUserRequisitionStats($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requisitions - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .requisitions-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .requisitions-card {
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
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            min-width: 150px;
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
        
        .status-submitted { background-color: #6c757d; color: white; }
        .status-approved { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-processed { background-color: #17a2b8; color: white; }
        .status-draft { background-color: #ffc107; color: black; }
        
        .urgency-normal { background-color: #28a745; color: white; }
        .urgency-urgent { background-color: #ffc107; color: black; }
        .urgency-critical { background-color: #dc3545; color: white; }
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

    <div class="requisitions-container">
        <div class="container">
            <div class="requisitions-card">
                <h2><i class="bi bi-file-text"></i> My Requisitions</h2>
                <p class="text-muted">View and track your submitted requisitions</p>
                
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
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['submitted']; ?></div>
                        <div class="stat-label">Submitted</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejected</div>
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
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="Draft" <?php echo $status_filter === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="Submitted" <?php echo $status_filter === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="Processed" <?php echo $status_filter === 'Processed' ? 'selected' : ''; ?>>Processed</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <a href="my_requisitions.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Clear Filter
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Requisitions Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Requisition ID</th>
                                <th>Department</th>
                                <th>Date Submitted</th>
                                <th>Date Required</th>
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
                                        <td><strong><?php echo htmlspecialchars($req['requisition_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($req['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($req['requisition_date']); ?></td>
                                        <td><?php echo htmlspecialchars($req['date_required'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge urgency-<?php echo strtolower($req['urgency']); ?>">
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
                                            <a href="view_requisition.php?id=<?php echo urlencode($req['id']); ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if (in_array($req['status'], ['Draft', 'Submitted'])): ?>
                                                <a href="edit_requisition.php?id=<?php echo urlencode($req['id']); ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($req['status'] === 'Draft'): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['requisition_id']); ?>')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No requisitions found</p>
                                        <a href="create_requisition.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Create New Requisition
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
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
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete requisition <strong id="deleteReqId"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="delete_requisition" value="1">
                        <input type="hidden" name="delete_id" id="deleteId">
                        <div class="text-center">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Yes, Delete
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(id, reqId) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteReqId').textContent = reqId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
