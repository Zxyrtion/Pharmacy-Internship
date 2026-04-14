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
    header('Location: /Pharmacy-Internship/index.php');
    exit();
}

$purchaseOrder = new PurchaseOrder($conn);
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['requisition_id'])) {
    $requisition_id = $_POST['requisition_id'];
    $supplier_name = $_POST['supplier_name'];
    $expected_delivery_date = $_POST['expected_delivery_date'];
    $payment_terms = $_POST['payment_terms'];
    $created_by = $_SESSION['user_id'];
    $notes = $_POST['notes'] ?? '';
    
    // Generate purchase order
    $result = $purchaseOrder->generatePurchaseOrder($requisition_id, $supplier_name, $expected_delivery_date, $payment_terms, $created_by, $notes);
    
    if ($result['success']) {
        $success_message = "Purchase Order generated successfully! PO ID: " . $result['purchase_order_id'] . 
                           " Total Amount: ₱" . number_format($result['total_amount'], 2);
    } else {
        $error_message = "Failed to generate Purchase Order: " . $result['error'];
    }
}

// Get requisition details if requisition_id is provided
$requisition_details = null;
$requisition_items = [];
if (isset($_GET['requisition_id'])) {
    $requisition_details = $purchaseOrder->getRequisitionById($_GET['requisition_id']);
    $requisition_items = $purchaseOrder->getRequisitionItems($_GET['requisition_id']);
}

// Get suppliers for dropdown
$suppliers = $purchaseOrder->getSuppliers();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Purchase Order - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .purchase-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .purchase-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .requisition-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .order-summary {
            background: #e8f4f8;
            border: 2px solid #17a2b8;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-generate {
            background: #17a2b8;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-generate:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            color: #17a2b8;
        }
        
        .urgency-badge {
            font-size: 0.875rem;
        }
        
        .urgency-normal { background-color: #28a745; }
        .urgency-urgent { background-color: #ffc107; }
        .urgency-critical { background-color: #dc3545; }
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

    <div class="purchase-container">
        <div class="container">
            <div class="purchase-card">
                <h2><i class="bi bi-cart-plus"></i> Generate Purchase Order</h2>
                <p class="text-muted">Process 14: Generating Purchase Order from Requisition Report Details</p>
                
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
                
                <?php if ($requisition_details): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="requisition_id" value="<?php echo htmlspecialchars($requisition_details['id']); ?>">
                        
                        <div class="requisition-details">
                            <h4><i class="bi bi-file-text"></i> Requisition Report Details</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Requisition ID:</strong> <?php echo htmlspecialchars($requisition_details['requisition_id']); ?></p>
                                    <p><strong>Requested by:</strong> <?php echo htmlspecialchars($requisition_details['first_name'] . ' ' . $requisition_details['last_name']); ?></p>
                                    <p><strong>Requisition Date:</strong> <?php echo htmlspecialchars($requisition_details['requisition_date']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Urgency:</strong> 
                                        <span class="badge urgency-badge urgency-<?php echo strtolower($requisition_details['urgency']); ?>">
                                            <?php echo htmlspecialchars($requisition_details['urgency']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-info"><?php echo htmlspecialchars($requisition_details['status']); ?></span>
                                    </p>
                                    <p><strong>Total Amount:</strong> ₱<?php echo number_format($requisition_details['total_amount'], 2); ?></p>
                                </div>
                            </div>
                            <?php if ($requisition_details['notes']): ?>
                                <div class="mt-3">
                                    <p><strong>Notes:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($requisition_details['notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <h5><i class="bi bi-list-ul"></i> Requisition Items</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Medicine Name</th>
                                        <th>Dosage</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Requested Qty</th>
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
                                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['total_price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="order-summary">
                            <h4><i class="bi bi-receipt"></i> Purchase Order Details</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="supplier_name" class="form-label">Supplier Name *</label>
                                        <select class="form-select" id="supplier_name" name="supplier_name" required>
                                            <option value="">Select Supplier</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>">
                                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="expected_delivery_date" class="form-label">Expected Delivery Date *</label>
                                        <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_terms" class="form-label">Payment Terms *</label>
                                        <input type="text" class="form-control" id="payment_terms" name="payment_terms" 
                                               placeholder="e.g., Net 30, COD" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Additional Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Any additional notes for the supplier..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-row">
                                <span>Order Total:</span>
                                <span>₱<?php echo number_format($requisition_details['total_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-generate">
                                <i class="bi bi-cart-plus"></i> Generate Purchase Order
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No requisition found. Please select a requisition from the dashboard.
                    </div>
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Set minimum date to today for expected delivery
        document.getElementById('expected_delivery_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
