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

// Get requisition ID
$requisition_id = $_GET['id'] ?? 0;

// Get requisition details
$requisition = $purchaseOrder->getRequisitionById($requisition_id);
$items = $purchaseOrder->getRequisitionItems($requisition_id);

// Check if requisition exists and belongs to this user
if (!$requisition || $requisition['pharmacist_id'] != $user_id) {
    header('Location: my_requisitions.php');
    exit();
}

// Get success/error messages from session
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Handle delete action
if (isset($_POST['delete_requisition'])) {
    if ($requisition['status'] === 'Draft') {
        if ($purchaseOrder->deleteRequisition($requisition_id)) {
            $_SESSION['success_message'] = "Requisition deleted successfully!";
            header('Location: my_requisitions.php');
            exit();
        } else {
            $error_message = "Failed to delete requisition.";
        }
    } else {
        $error_message = "Cannot delete this requisition.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Requisition - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .view-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .requisition-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .requisition-header {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .info-value {
            color: #495057;
        }
        
        .status-submitted { background-color: #6c757d; color: white; }
        .status-approved { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-processed { background-color: #17a2b8; color: white; }
        
        .urgency-normal { background-color: #28a745; color: white; }
        .urgency-urgent { background-color: #ffc107; color: black; }
        .urgency-critical { background-color: #dc3545; color: white; }
        
        .items-table {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .total-section {
            background: #e8f4f8;
            border: 2px solid #17a2b8;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            .view-container {
                background: white;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm no-print">
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

    <div class="view-container">
        <div class="container">
            <div class="requisition-card">
                <div class="requisition-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">PURCHASE REQUISITION</h2>
                            <p class="text-muted mb-0">Requisition ID: <?php echo htmlspecialchars($requisition['requisition_id']); ?></p>
                        </div>
                        <div>
                            <span class="badge status-<?php echo strtolower($requisition['status']); ?> fs-5">
                                <?php echo htmlspecialchars($requisition['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
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
                
                <!-- Requisition Information -->
                <div class="mb-4">
                    <h4><i class="bi bi-info-circle"></i> Requisition Information</h4>
                    <div class="info-row">
                        <span class="info-label">Requested By:</span>
                        <span class="info-value"><?php echo htmlspecialchars($requisition['pharmacist_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($requisition['department'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Requisition Date:</span>
                        <span class="info-value"><?php echo htmlspecialchars($requisition['requisition_date']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Required:</span>
                        <span class="info-value"><?php echo htmlspecialchars($requisition['date_required'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Urgency:</span>
                        <span class="info-value">
                            <span class="badge urgency-<?php echo strtolower($requisition['urgency']); ?>">
                                <?php echo htmlspecialchars($requisition['urgency']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created At:</span>
                        <span class="info-value"><?php echo date('M d, Y h:i A', strtotime($requisition['created_at'])); ?></span>
                    </div>
                    <?php if ($requisition['notes']): ?>
                    <div class="info-row">
                        <span class="info-label">Reason:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($requisition['notes'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Items Table -->
                <div class="mb-4">
                    <h4><i class="bi bi-list-ul"></i> Requested Items</h4>
                    <div class="table-responsive items-table">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine/Item</th>
                                    <th>Specifications</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                    <th>Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['dosage'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($item['requested_quantity']); ?></td>
                                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['total_price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No items found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Total Section -->
                <div class="total-section">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Total Items: <?php echo count($items); ?></h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4>Total Amount: <strong>₱<?php echo number_format($requisition['total_amount'], 2); ?></strong></h4>
                        </div>
                    </div>
                </div>
                
                <!-- Status Information -->
                <?php if ($requisition['status'] === 'Rejected'): ?>
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-x-circle"></i> Requisition Rejected</h5>
                        <p class="mb-0">This requisition has been rejected. Please check the notes for the reason.</p>
                    </div>
                <?php elseif ($requisition['status'] === 'Approved'): ?>
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle"></i> Requisition Approved</h5>
                        <p class="mb-0">This requisition has been approved and is awaiting purchase order generation.</p>
                    </div>
                <?php elseif ($requisition['status'] === 'Processed'): ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-cart-check"></i> Requisition Processed</h5>
                        <p class="mb-0">A purchase order has been generated for this requisition.</p>
                    </div>
                <?php elseif ($requisition['status'] === 'Submitted'): ?>
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-clock-history"></i> Pending Approval</h5>
                        <p class="mb-0">This requisition is awaiting approval from the pharmacist.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4 no-print">
                    <a href="my_requisitions.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to My Requisitions
                    </a>
                    <div>
                        <?php if (in_array($requisition['status'], ['Draft', 'Submitted'])): ?>
                            <a href="edit_requisition.php?id=<?php echo $requisition_id; ?>" class="btn btn-warning me-2">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        <?php endif; ?>
                        <?php if ($requisition['status'] === 'Draft'): ?>
                            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
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
                    <p>Are you sure you want to delete requisition <strong><?php echo htmlspecialchars($requisition['requisition_id']); ?></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="delete_requisition" value="1">
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
</body>
</html>
