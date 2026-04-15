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
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $technician_id = $_SESSION['user_id'];
    $technician_name = $_SESSION['full_name'];
    $department = $_POST['department'];
    $requisition_date = $_POST['requisition_date'];
    $date_required = $_POST['date_required'];
    $urgency = $_POST['urgency'];
    $reason = $_POST['reason'];
    
    // Process items from form
    $items = [];
    if (isset($_POST['medicine_name']) && is_array($_POST['medicine_name'])) {
        foreach ($_POST['medicine_name'] as $key => $value) {
            if (!empty($value)) {
                $items[] = [
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
    
    if (!empty($items)) {
        $result = $purchaseOrder->createRequisition($technician_id, $technician_name, $department, $requisition_date, $date_required, $urgency, $reason, $items);
        
        if ($result['success']) {
            $success_message = "Requisition created successfully! Requisition ID: " . $result['requisition_id'] . 
                               " Total Amount: ₱" . number_format($result['total_amount'], 2);
            // Clear form data
            $_POST = [];
        } else {
            $error_message = "Failed to create requisition: " . $result['error'];
        }
    } else {
        $error_message = "Please add at least one item to the requisition.";
    }
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
    <title>Create Requisition - MediCare Pharmacy</title>
    
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
        
        .signature-section {
            margin-top: 3rem;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .signature-line {
            border-bottom: 1px solid #6c757d;
            height: 40px;
            margin-bottom: 0.5rem;
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
                    <div class="requisition-title">PURCHASE REQUISITION</div>
                    <div class="text-muted">Process 13: Generate Requisition</div>
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
                
                <form method="POST" action="" id="requisitionForm">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="department" class="form-label">Requisition Department *</label>
                            <input type="text" class="form-control" id="department" name="department" required 
                                   value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : 'Pharmacy'; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="requisition_date" class="form-label">Requisition Date *</label>
                            <input type="date" class="form-control" id="requisition_date" name="requisition_date" required 
                                   value="<?php echo isset($_POST['requisition_date']) ? htmlspecialchars($_POST['requisition_date']) : date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="date_required" class="form-label">Date Required *</label>
                            <input type="date" class="form-control" id="date_required" name="date_required" required 
                                   value="<?php echo isset($_POST['date_required']) ? htmlspecialchars($_POST['date_required']) : ''; ?>">
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
                                    <tr>
                                        <td><input type="text" class="form-control" name="medicine_name[]" placeholder="Medicine/Item name" required></td>
                                        <td><input type="number" class="form-control" name="quantity[]" placeholder="Qty" min="1" required></td>
                                        <td><input type="text" class="form-control" name="dosage[]" placeholder="Dosage/Specs"></td>
                                        <td><input type="number" class="form-control" name="unit_price[]" placeholder="Price" step="0.01" min="0" required></td>
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
                                    </tr>
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
                                    <option value="Normal">Normal</option>
                                    <option value="Urgent">Urgent</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total: PHP (Upper Case)</label>
                                <div class="form-control" style="background: #e9ecef; font-weight: bold;" id="totalAmount">₱0.00</div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label for="reason" class="form-label">Reason for Application *</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required 
                                      placeholder="Please provide reason for this requisition..."></textarea>
                        </div>
                    </div>
                    
                    <div class="signature-section">
                        <h5><i class="bi bi-pencil-square"></i> Signatures</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Signature of Applicant</label>
                                <div class="signature-line"></div>
                                <small class="text-muted"><?php echo htmlspecialchars($full_name); ?></small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Signature of Department Head</label>
                                <div class="signature-line"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Approver's Signature</label>
                                <div class="signature-line"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit Seal</label>
                                <div class="signature-line"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-check-circle"></i> Submit Requisition
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let itemCount = 1;
        
        function addNewItem() {
            itemCount++;
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
        
        // Add event listeners to existing inputs
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
            const priceInputs = document.querySelectorAll('input[name="unit_price[]"]');
            
            quantityInputs.forEach(input => input.addEventListener('change', calculateTotal));
            priceInputs.forEach(input => input.addEventListener('change', calculateTotal));
            
            // Set minimum dates
            document.getElementById('requisition_date').min = new Date().toISOString().split('T')[0];
            document.getElementById('date_required').min = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>
