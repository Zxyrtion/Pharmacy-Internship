<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$success = '';
$error = '';

// Extract success msg
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add/Update Supplier
        if ($action === 'save_supplier') {
            $supplier_id = $_POST['supplier_id'] ?? '';
            $supplier_name = sanitizeInput($_POST['supplier_name']);
            $contact_person = sanitizeInput($_POST['contact_person']);
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);
            $address = sanitizeInput($_POST['address']);
            $status = sanitizeInput($_POST['status']);
            
            if (empty($supplier_id)) {
                // Insert new supplier
                $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $supplier_name, $contact_person, $phone, $email, $address, $status);
            } else {
                // Update existing supplier
                $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, contact_person=?, phone=?, email=?, address=?, status=? WHERE id=?");
                $stmt->bind_param("ssssssi", $supplier_name, $contact_person, $phone, $email, $address, $status, $supplier_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Supplier saved successfully!";
            } else {
                $error = "Failed to save supplier: " . $conn->error;
            }
            $stmt->close();
            
            if (empty($error)) {
                header("Location: supplier_management.php");
                exit();
            }
        }
        
        // Delete Supplier
        if ($action === 'delete_supplier') {
            $supplier_id = (int)$_POST['supplier_id'];
            $stmt = $conn->prepare("UPDATE suppliers SET status='Inactive' WHERE id=?");
            $stmt->bind_param("i", $supplier_id);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Supplier deactivated successfully!";
            } else {
                $error = "Failed to deactivate supplier.";
            }
            $stmt->close();
            
            if (empty($error)) {
                header("Location: supplier_management.php");
                exit();
            }
        }
    }
}

// Fetch all suppliers
$suppliers = [];
if (isset($conn)) {
    $stmt = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name ASC");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f0f4f8;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .supplier-card {
            transition: transform 0.2s;
        }
        .supplier-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="page-header">
            <h1><i class="bi bi-truck"></i> Supplier Management</h1>
            <p class="mb-0">Manage your pharmacy suppliers and contacts</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Supplier Button -->
        <div class="mb-3">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="resetForm()">
                <i class="bi bi-plus-circle"></i> Add New Supplier
            </button>
        </div>

        <!-- Suppliers Grid -->
        <div class="row">
            <?php foreach ($suppliers as $supplier): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card supplier-card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($supplier['supplier_name']); ?></h5>
                                <span class="badge bg-<?php echo $supplier['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $supplier['status']; ?>
                                </span>
                            </div>
                            
                            <?php if ($supplier['contact_person']): ?>
                                <p class="mb-1"><i class="bi bi-person"></i> <?php echo htmlspecialchars($supplier['contact_person']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($supplier['phone']): ?>
                                <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($supplier['phone']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($supplier['email']): ?>
                                <p class="mb-1"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($supplier['address']): ?>
                                <p class="mb-1 text-muted small"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($supplier['address']); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(<?php echo $supplier['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($suppliers)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> No suppliers yet. Click "Add New Supplier" to get started!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Supplier Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_supplier">
                    <input type="hidden" name="supplier_id" id="supplier_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Supplier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Supplier Name *</label>
                            <input type="text" class="form-control" name="supplier_name" id="supplier_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" name="contact_person" id="contact_person">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Supplier';
            document.getElementById('supplier_id').value = '';
            document.getElementById('supplier_name').value = '';
            document.getElementById('contact_person').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('email').value = '';
            document.getElementById('address').value = '';
            document.getElementById('status').value = 'Active';
        }
        
        function editSupplier(supplier) {
            document.getElementById('modalTitle').textContent = 'Edit Supplier';
            document.getElementById('supplier_id').value = supplier.id;
            document.getElementById('supplier_name').value = supplier.supplier_name;
            document.getElementById('contact_person').value = supplier.contact_person || '';
            document.getElementById('phone').value = supplier.phone || '';
            document.getElementById('email').value = supplier.email || '';
            document.getElementById('address').value = supplier.address || '';
            document.getElementById('status').value = supplier.status;
            
            new bootstrap.Modal(document.getElementById('supplierModal')).show();
        }
        
        function deleteSupplier(supplierId) {
            if (confirm('Are you sure you want to deactivate this supplier?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="supplier_id" value="${supplierId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
