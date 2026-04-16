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

// Fetch suppliers for dropdown
$suppliers = [];
if (isset($conn)) {
    $stmt = $conn->query("SELECT id, supplier_name FROM suppliers WHERE status = 'Active' ORDER BY supplier_name");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add/Update Product
        if ($action === 'save_product') {
            $product_id = $_POST['product_id'] ?? '';
            $product_name = sanitizeInput($_POST['product_name']);
            $barcode = sanitizeInput($_POST['barcode']);
            $description = sanitizeInput($_POST['description']);
            $category = sanitizeInput($_POST['category']);
            $manufacturer = sanitizeInput($_POST['manufacturer']);
            $quantity = (int)$_POST['quantity'];
            $unit = sanitizeInput($_POST['unit']);
            $price = (float)$_POST['price'];
            $expiry_date = $_POST['expiry_date'] ?: null;
            $batch_number = sanitizeInput($_POST['batch_number']);
            $supplier_id = $_POST['supplier_id'] ?: null;
            $reorder_level = (int)$_POST['reorder_level'];
            $max_stock_level = (int)$_POST['max_stock_level'];
            $location = sanitizeInput($_POST['location']);
            $total_price = $quantity * $price;
            
            if (empty($product_id)) {
                // Insert new product
                $stmt = $conn->prepare("INSERT INTO product_inventory (intern_id, product_name, barcode, description, category, manufacturer, quantity, unit, price, expiry_date, batch_number, supplier_id, total_price, reorder_level, max_stock_level, location, status, last_restocked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
                $stmt->bind_param("isssssisssidiii", $user_id, $product_name, $barcode, $description, $category, $manufacturer, $quantity, $unit, $price, $expiry_date, $batch_number, $supplier_id, $total_price, $reorder_level, $max_stock_level, $location);
            } else {
                // Update existing product
                $stmt = $conn->prepare("UPDATE product_inventory SET product_name=?, barcode=?, description=?, category=?, manufacturer=?, quantity=?, unit=?, price=?, expiry_date=?, batch_number=?, supplier_id=?, total_price=?, reorder_level=?, max_stock_level=?, location=?, last_restocked=NOW() WHERE id=? AND intern_id=?");
                $stmt->bind_param("sssssississiiiii", $product_name, $barcode, $description, $category, $manufacturer, $quantity, $unit, $price, $expiry_date, $batch_number, $supplier_id, $total_price, $reorder_level, $max_stock_level, $location, $product_id, $user_id);
            }
            
            if ($stmt->execute()) {
                $new_product_id = empty($product_id) ? $conn->insert_id : $product_id;
                
                // Log inventory movement
                $movement_type = empty($product_id) ? 'Stock In' : 'Adjustment';
                $prev_qty = 0;
                if (!empty($product_id)) {
                    $prev_stmt = $conn->prepare("SELECT quantity FROM product_inventory WHERE id=?");
                    $prev_stmt->bind_param("i", $product_id);
                    $prev_stmt->execute();
                    $prev_result = $prev_stmt->get_result();
                    if ($prev_row = $prev_result->fetch_assoc()) {
                        $prev_qty = $prev_row['quantity'];
                    }
                }
                
                $log_stmt = $conn->prepare("INSERT INTO inventory_movements (product_id, movement_type, quantity, previous_quantity, new_quantity, unit_cost, total_cost, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $notes = empty($product_id) ? "Initial stock entry" : "Stock adjustment";
                $log_stmt->bind_param("isiidddsi", $new_product_id, $movement_type, $quantity, $prev_qty, $quantity, $price, $total_price, $notes, $user_id);
                $log_stmt->execute();
                
                // Check for low stock alerts
                checkAndCreateAlerts($new_product_id);
                
                $_SESSION['success_msg'] = "Product saved successfully!";
            } else {
                $error = "Failed to save product: " . $conn->error;
            }
            $stmt->close();
            
            if (empty($error)) {
                header("Location: enhanced_inventory.php");
                exit();
            }
        }
        
        // Delete Product
        if ($action === 'delete_product') {
            $product_id = (int)$_POST['product_id'];
            $stmt = $conn->prepare("UPDATE product_inventory SET status='Discontinued' WHERE id=? AND intern_id=?");
            $stmt->bind_param("ii", $product_id, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Product discontinued successfully!";
            } else {
                $error = "Failed to discontinue product.";
            }
            $stmt->close();
            
            if (empty($error)) {
                header("Location: enhanced_inventory.php");
                exit();
            }
        }
    }
}

// Function to check and create alerts
function checkAndCreateAlerts($product_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM product_inventory WHERE id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if ($product) {
        // Check low stock
        if ($product['quantity'] <= $product['reorder_level'] && $product['quantity'] > 0) {
            $alert_stmt = $conn->prepare("INSERT INTO stock_alerts (product_id, alert_type, current_quantity, reorder_level) VALUES (?, 'Low Stock', ?, ?)");
            $alert_stmt->bind_param("iii", $product_id, $product['quantity'], $product['reorder_level']);
            $alert_stmt->execute();
            $alert_stmt->close();
        }
        
        // Check out of stock
        if ($product['quantity'] == 0) {
            $alert_stmt = $conn->prepare("INSERT INTO stock_alerts (product_id, alert_type, current_quantity, reorder_level) VALUES (?, 'Out of Stock', ?, ?)");
            $alert_stmt->bind_param("iii", $product_id, $product['quantity'], $product['reorder_level']);
            $alert_stmt->execute();
            $alert_stmt->close();
        }
        
        // Check expiring soon (within 30 days)
        if ($product['expiry_date']) {
            $expiry = new DateTime($product['expiry_date']);
            $today = new DateTime();
            $days_diff = $today->diff($expiry)->days;
            
            if ($days_diff <= 30 && $expiry > $today) {
                $alert_stmt = $conn->prepare("INSERT INTO stock_alerts (product_id, alert_type, current_quantity, reorder_level, expiry_date, days_to_expiry) VALUES (?, 'Expiring Soon', ?, ?, ?, ?)");
                $alert_stmt->bind_param("iiisi", $product_id, $product['quantity'], $product['reorder_level'], $product['expiry_date'], $days_diff);
                $alert_stmt->execute();
                $alert_stmt->close();
            }
            
            // Check expired
            if ($expiry <= $today) {
                $alert_stmt = $conn->prepare("INSERT INTO stock_alerts (product_id, alert_type, current_quantity, reorder_level, expiry_date, days_to_expiry) VALUES (?, 'Expired', ?, ?, ?, 0)");
                $alert_stmt->bind_param("iiis", $product_id, $product['quantity'], $product['reorder_level'], $product['expiry_date']);
                $alert_stmt->execute();
                $alert_stmt->close();
                
                // Update product status to Expired
                $upd_stmt = $conn->prepare("UPDATE product_inventory SET status='Expired' WHERE id=?");
                $upd_stmt->bind_param("i", $product_id);
                $upd_stmt->execute();
                $upd_stmt->close();
            }
        }
    }
}

// Fetch all products
$products = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT p.*, s.supplier_name FROM product_inventory p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.intern_id = ? ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}

// Fetch unacknowledged alerts
$alerts = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT a.*, p.product_name FROM stock_alerts a JOIN product_inventory p ON a.product_id = p.id WHERE p.intern_id = ? AND a.is_acknowledged = 0 ORDER BY a.created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Inventory Management - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .alert-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .alert-card.low-stock { border-left-color: #ffc107; }
        .alert-card.out-of-stock { border-left-color: #dc3545; }
        .alert-card.expiring-soon { border-left-color: #fd7e14; }
        .alert-card.expired { border-left-color: #6c757d; }
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .badge-status {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
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
            <h1><i class="bi bi-box-seam"></i> Enhanced Inventory Management</h1>
            <p class="mb-0">Complete inventory tracking with expiry dates, suppliers, and real-time alerts</p>
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

        <!-- Alerts Section -->
        <?php if (!empty($alerts)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Stock Alerts (<?php echo count($alerts); ?>)</h5>
            </div>
            <div class="card-body">
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert-card alert alert-<?php 
                        echo $alert['alert_type'] === 'Low Stock' ? 'warning' : 
                            ($alert['alert_type'] === 'Out of Stock' ? 'danger' : 
                            ($alert['alert_type'] === 'Expiring Soon' ? 'warning' : 'secondary')); 
                    ?> py-2">
                        <strong><?php echo htmlspecialchars($alert['product_name']); ?></strong> - 
                        <?php echo $alert['alert_type']; ?>
                        <?php if ($alert['alert_type'] === 'Expiring Soon'): ?>
                            (<?php echo $alert['days_to_expiry']; ?> days remaining)
                        <?php endif; ?>
                        <small class="text-muted">(Current: <?php echo $alert['current_quantity']; ?> units)</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add Product Button -->
        <div class="mb-3">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                <i class="bi bi-plus-circle"></i> Add New Product
            </button>
            <a href="inventory_movements.php" class="btn btn-info btn-lg">
                <i class="bi bi-clock-history"></i> View Movement History
            </a>
        </div>

        <!-- Products Grid -->
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                <span class="badge badge-status bg-<?php 
                                    echo $product['status'] === 'Active' ? 'success' : 
                                        ($product['status'] === 'Expired' ? 'danger' : 'secondary'); 
                                ?>"><?php echo $product['status']; ?></span>
                            </div>
                            
                            <?php if ($product['barcode']): ?>
                                <p class="text-muted small mb-1"><i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($product['barcode']); ?></p>
                            <?php endif; ?>
                            
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($product['description'] ?? 'No description'); ?></p>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Category:</small><br>
                                    <strong><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Manufacturer:</small><br>
                                    <strong><?php echo htmlspecialchars($product['manufacturer'] ?? 'N/A'); ?></strong>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Quantity:</small><br>
                                    <strong class="<?php echo $product['quantity'] <= $product['reorder_level'] ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $product['quantity']; ?> <?php echo $product['unit']; ?>
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Price:</small><br>
                                    <strong>₱<?php echo number_format($product['price'], 2); ?></strong>
                                </div>
                            </div>
                            
                            <?php if ($product['expiry_date']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Expiry Date:</small><br>
                                    <strong class="<?php 
                                        $expiry = new DateTime($product['expiry_date']);
                                        $today = new DateTime();
                                        $diff = $today->diff($expiry)->days;
                                        echo ($expiry <= $today) ? 'text-danger' : (($diff <= 30) ? 'text-warning' : 'text-success');
                                    ?>"><?php echo date('M d, Y', strtotime($product['expiry_date'])); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($product['supplier_name']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Supplier:</small><br>
                                    <strong><?php echo htmlspecialchars($product['supplier_name']); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> No products in inventory yet. Click "Add New Product" to get started!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_product">
                    <input type="hidden" name="product_id" id="product_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="product_name" id="product_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Barcode</label>
                                <input type="text" class="form-control" name="barcode" id="barcode">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" name="category" id="category" placeholder="e.g., Analgesic, Antibiotic">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" class="form-control" name="manufacturer" id="manufacturer">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Quantity *</label>
                                <input type="number" class="form-control" name="quantity" id="quantity" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit</label>
                                <select class="form-select" name="unit" id="unit">
                                    <option value="pcs">Pieces</option>
                                    <option value="box">Box</option>
                                    <option value="bottle">Bottle</option>
                                    <option value="pack">Pack</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" id="expiry_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Batch Number</label>
                                <input type="text" class="form-control" name="batch_number" id="batch_number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id" id="supplier_id">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" id="location" placeholder="e.g., Shelf A1">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" name="reorder_level" id="reorder_level" value="10" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Stock Level</label>
                                <input type="number" class="form-control" name="max_stock_level" id="max_stock_level" value="100" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('product_id').value = '';
            document.getElementById('product_name').value = '';
            document.getElementById('barcode').value = '';
            document.getElementById('description').value = '';
            document.getElementById('category').value = '';
            document.getElementById('manufacturer').value = '';
            document.getElementById('quantity').value = '';
            document.getElementById('unit').value = 'pcs';
            document.getElementById('price').value = '';
            document.getElementById('expiry_date').value = '';
            document.getElementById('batch_number').value = '';
            document.getElementById('supplier_id').value = '';
            document.getElementById('location').value = '';
            document.getElementById('reorder_level').value = '10';
            document.getElementById('max_stock_level').value = '100';
        }
        
        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = product.id;
            document.getElementById('product_name').value = product.product_name;
            document.getElementById('barcode').value = product.barcode || '';
            document.getElementById('description').value = product.description || '';
            document.getElementById('category').value = product.category || '';
            document.getElementById('manufacturer').value = product.manufacturer || '';
            document.getElementById('quantity').value = product.quantity;
            document.getElementById('unit').value = product.unit || 'pcs';
            document.getElementById('price').value = product.price;
            document.getElementById('expiry_date').value = product.expiry_date || '';
            document.getElementById('batch_number').value = product.batch_number || '';
            document.getElementById('supplier_id').value = product.supplier_id || '';
            document.getElementById('location').value = product.location || '';
            document.getElementById('reorder_level').value = product.reorder_level || 10;
            document.getElementById('max_stock_level').value = product.max_stock_level || 100;
            
            new bootstrap.Modal(document.getElementById('productModal')).show();
        }
        
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to discontinue this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
