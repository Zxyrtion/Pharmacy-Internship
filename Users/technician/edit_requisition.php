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

$success_message = '';
$error_message = '';

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

// Check if requisition can be edited (only Draft or Submitted status)
if (!in_array($requisition['status'], ['Draft', 'Submitted'])) {
    $_SESSION['error_message'] = "Cannot edit requisition with status: " . $requisition['status'];
    header('Location: view_requisition.php?id=' . $requisition_id);
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $department = $_POST['department'];
    $requisition_date = $_POST['requisition_date'];
    $date_required = $_POST['date_required'];
    $urgency = $_POST['urgency'];
    $reason = $_POST['reason'];
    
    // Process items from form
    $updated_items = [];
    if (isset($_POST['medicine_name']) && is_array($_POST['medicine_name'])) {
        foreach ($_POST['medicine_name'] as $key => $value) {
            if (!empty($value)) {
                $updated_items[] = [
                    'medicine_name' => $value,
                    'dosage' => $_POST['dosage'][$key] ?? '',
                    'current_stock' => $_POST['current_stock'][$key] ?? 0,
                    'reorder_level' => $_POST['reorder_level'][$key] ?? 0,
                    'quantity' => $_POST['quantity'][$key] ?? 0,
                    'unit_price' => $_POST['unit_price'][$key] ?? 0,
                    'supplier' => $_POST['supplier'][$key] ?? ''
                ];
            }
        }
    }
    
    if (!empty($updated_items)) {
        $result = $purchaseOrder->updateRequisitionWithItems($requisition_id, $department, $requisition_date, $date_required, $urgency, $reason, $updated_items);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Requisition updated successfully! Total Amount: ₱" . number_format($result['total_amount'], 2);
            header('Location: view_requisition.php?id=' . $requisition_id);
            exit();
        } else {
            $error_message = "Failed to update requisition: " . $result['error'];
        }
    } else {
        $error_message = "Please add at least one item to the requisition.";
    }
}

// Get suppliers for dropdown
$suppliers = $purchaseOrder->getSuppliers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Requisition - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .requisition-container {
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
            text-align: center;
        }
        
        .requisition-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .items-table {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .btn-add-item {
            background: #28a745;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-add-item:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-remove-item {
            background: #dc3545;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-submit {
            background: #007bff;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .total-section {
            background: #e8f4f8;
            border: 2px solid #17a2b8;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
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

    <div class="requisition-container">
        <div class="container">
            <div class="requisition-card">
                <div class="requisition-header">
                    <div class="requisition-title">EDIT PURCHASE REQUISITION</div>
                    <div class="text-muted">Requisition ID: <?php echo htmlspecialchars($requisition['requisition_id']); ?></div>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="requisitionForm">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="department" class="form-label">Requisition Department *</label>
                            <input type="text" class="form-control" id="department" name="department" required 
                                   value="<?php echo htmlspecialchars($requisition['department'] ?? 'Pharmacy'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="requisition_date" class="form-label">Requisition Date *</label>
                            <input type="date" class="form-control" id="requisition_date" name="requisition_date" required 
                                   value="<?php echo htmlspecialchars($requisition['requisition_date']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="date_required" class="form-label">Date Required *</label>
                            <input type="date" class="form-control" id="date_required" name="date_required" required 
                                   value="<?php echo htmlspecialchars($requisition['date_required'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5><i class="bi bi-list-ul"></i> Items to Purchase</h5>
                        <div class="table-responsive items-table">
                            <table class="table table-bordered mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Please Purchase Goods</th>
                                        <th width="10%">Quantity</th>
                                        <th width="20%">Specifications</th>
                                        <th width="15%">Unit Price</th>
                                        <th width="15%">The Amount</th>
                                        <th width="15%">Supplier</th>
                                        <th width="5%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><input type="text" class="form-control" name="medicine_name[]" placeholder="Medicine/Item name" required value="<?php echo htmlspecialchars($item['medicine_name']); ?>"></td>
                                        <td><input type="number" class="form-control" name="quantity[]" placeholder="Qty" min="1" required value="<?php echo htmlspecialchars($item['requested_quantity']); ?>" onchange="calculateTotal()"></td>
                                        <td><input type="text" class="form-control" name="dosage[]" placeholder="Dosage/Specs" value="<?php echo htmlspecialchars($item['dosage'] ?? ''); ?>"></td>
                                        <td><input type="number" class="form-control" name="unit_price[]" placeholder="Price" step="0.01" min="0" required value="<?php echo htmlspecialchars($item['unit_price']); ?>" onchange="calculateTotal()"></td>
                                        <td><input type="text" class="form-control" name="amount[]" readonly placeholder="0.00" value="<?php echo number_format($item['total_price'], 2); ?>"></td>
                                        <td>
                                            <select class="form-select" name="supplier[]">
                                                <option value="">Select Supplier</option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>" 
                                                            <?php echo ($item['supplier'] == $supplier['supplier_name']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-item" onclick="removeItem(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-add-item mt-2" onclick="addNewItem()">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="total-section">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="urgency" class="form-label">Urgency *</label>
                                <select class="form-select" id="urgency" name="urgency" required>
                                    <option value="">Select Urgency</option>
                                    <option value="Normal" <?php echo ($requisition['urgency'] == 'Normal') ? 'selected' : ''; ?>>Normal</option>
                                    <option value="Urgent" <?php echo ($requisition['urgency'] == 'Urgent') ? 'selected' : ''; ?>>Urgent</option>
                                    <option value="Critical" <?php echo ($requisition['urgency'] == 'Critical') ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total: PHP (Upper Case)</label>
                                <div class="form-control" style="background: #e9ecef; font-weight: bold;" id="totalAmount">₱<?php echo number_format($requisition['total_amount'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label for="reason" class="form-label">Reason for Application *</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required 
                                      placeholder="Please provide reason for this requisition..."><?php echo htmlspecialchars($requisition['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="view_requisition.php?id=<?php echo $requisition_id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-check-circle"></i> Update Requisition
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function addNewItem() {
            const tbody = document.getElementById('itemsTableBody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td><input type="text" class="form-control" name="medicine_name[]" placeholder="Medicine/Item name" required></td>
                <td><input type="number" class="form-control" name="quantity[]" placeholder="Qty" min="1" required onchange="calculateTotal()"></td>
                <td><input type="text" class="form-control" name="dosage[]" placeholder="Dosage/Specs"></td>
                <td><input type="number" class="form-control" name="unit_price[]" placeholder="Price" step="0.01" min="0" required onchange="calculateTotal()"></td>
                <td><input type="text" class="form-control" name="amount[]" readonly placeholder="0.00"></td>
                <td>
                    <select class="form-select" name="supplier[]">
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="button" class="btn btn-sm btn-danger btn-remove-item" onclick="removeItem(this)"><i class="bi bi-trash"></i></button></td>
            `;
            
            tbody.appendChild(newRow);
        }
        
        function removeItem(button) {
            const row = button.closest('tr');
            const tbody = document.getElementById('itemsTableBody');
            
            if (tbody.children.length > 1) {
                row.remove();
                calculateTotal();
            } else {
                alert('At least one item is required.');
            }
        }
        
        function calculateTotal() {
            let total = 0;
            const rows = document.getElementById('itemsTableBody').getElementsByTagName('tr');
            
            for (let row of rows) {
                const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                const unitPrice = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
                const amount = quantity * unitPrice;
                
                row.querySelector('input[name="amount[]"]').value = amount.toFixed(2);
                total += amount;
            }
            
            document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
        }
        
        // Calculate total on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
            
            // Set minimum dates
            document.getElementById('requisition_date').min = new Date().toISOString().split('T')[0];
            document.getElementById('date_required').min = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>
