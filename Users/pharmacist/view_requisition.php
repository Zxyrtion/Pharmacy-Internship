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

// Get requisition ID from URL
$requisition_id = $_GET['id'] ?? '';

if (empty($requisition_id)) {
    header('Location: manage_requisitions.php');
    exit();
}

// Get requisition details
$requisition_details = $purchaseOrder->getRequisitionById($requisition_id);
$requisition_items = $purchaseOrder->getRequisitionItems($requisition_id);

if (!$requisition_details) {
    header('Location: manage_requisitions.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
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
        
        .view-card {
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
        
        .urgency-normal { background-color: #28a745; }
        .urgency-urgent { background-color: #ffc107; }
        .urgency-critical { background-color: #dc3545; }
        
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

    <div class="view-container">
        <div class="container">
            <div class="view-card">
                <div class="requisition-header">
                    <h3><i class="bi bi-file-text"></i> Requisition Details</h3>
                    <p class="mb-0">Requisition ID: <?php echo htmlspecialchars($requisition_details['requisition_id']); ?></p>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="bi bi-info-circle"></i> Basic Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Requisition ID:</strong></td>
                                <td><?php echo htmlspecialchars($requisition_details['requisition_id']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Requested By:</strong></td>
                                <td><?php echo htmlspecialchars($requisition_details['first_name'] . ' ' . $requisition_details['last_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Department:</strong></td>
                                <td><?php echo htmlspecialchars($requisition_details['department']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Requisition Date:</strong></td>
                                <td><?php echo htmlspecialchars($requisition_details['requisition_date']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Urgency:</strong></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        echo match($requisition_details['urgency']) {
                                            'Normal' => 'bg-success',
                                            'Urgent' => 'bg-warning',
                                            'Critical' => 'bg-danger',
                                            default => 'bg-secondary'
                                        }; 
                                        ?>">
                                        <?php echo htmlspecialchars($requisition_details['urgency']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge status-<?php echo strtolower($requisition_details['status']); ?>">
                                        <?php echo htmlspecialchars($requisition_details['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="bi bi-calculator"></i> Summary</h5>
                        <div class="total-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Total Amount:</strong></p>
                                    <h4 class="text-primary">PHP <?php echo number_format($requisition_details['total_amount'], 2); ?></h4>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Number of Items:</strong></p>
                                    <h4 class="text-info"><?php echo count($requisition_items); ?></h4>
                                </div>
                            </div>
                            
                            <?php if ($requisition_details['notes']): ?>
                                <div class="mt-3">
                                    <p><strong>Notes/Reason:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($requisition_details['notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="items-table">
                    <h5><i class="bi bi-list-ul"></i> Requisition Items</h5>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Dosage/Specifications</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Requested Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                    <th>Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requisition_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['dosage']); ?></td>
                                        <td><?php echo htmlspecialchars($item['current_stock']); ?></td>
                                        <td><?php echo htmlspecialchars($item['reorder_level']); ?></td>
                                        <td><?php echo htmlspecialchars($item['requested_quantity']); ?></td>
                                        <td>PHP <?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>PHP <?php echo number_format($item['total_price'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="manage_requisitions.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Requisitions
                    </a>
                    
                    <div>
                        <?php if ($requisition_details['status'] === 'Submitted'): ?>
                            <button type="button" class="btn btn-success me-2" 
                                    data-bs-toggle="modal" data-bs-target="#approveModal" 
                                    onclick="setRequisitionId(<?php echo $requisition_details['id']; ?>, 'approve')">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger" 
                                    data-bs-toggle="modal" data-bs-target="#rejectModal"
                                    onclick="setRequisitionId(<?php echo $requisition_details['id']; ?>, 'reject')">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                        <?php elseif ($requisition_details['status'] === 'Approved'): ?>
                            <a href="generate_purchase_order.php?requisition_id=<?php echo urlencode($requisition_details['id']); ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Generate Purchase Order
                            </a>
                        <?php endif; ?>
                    </div>
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
                    <form method="POST" action="manage_requisitions.php">
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
                    <form method="POST" action="manage_requisitions.php">
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
