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

// Fixed Medicine List with standard prices
$medicines = [
    "Paracetamol" => ["desc" => "fever and mild pain relief", "price" => 7.00],
    "Ibuprofen" => ["desc" => "pain and inflammation relief", "price" => 11.00],
    "Mefenamic Acid" => ["desc" => "strong pain and cramps", "price" => 8.00],
    "Lagundi" => ["desc" => "herbal cough relief syrup", "price" => 120.00],
    "Carbocisteine" => ["desc" => "loosens phlegm and mucus", "price" => 15.00],
    "Dextromethorphan" => ["desc" => "suppresses dry cough reflex", "price" => 6.00],
    "Cetirizine" => ["desc" => "allergy relief non drowsy", "price" => 5.00]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    $conn->begin_transaction();
    try {
        $product_ids = $_POST['product_id'] ?? [];
        $product_names = $_POST['product_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        
        $ins_stmt = $conn->prepare("INSERT INTO product_inventory (intern_id, product_name, description, quantity, price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
        $upd_stmt = $conn->prepare("UPDATE product_inventory SET quantity=?, total_price=? WHERE id=? AND intern_id=?");
        
        for ($i = 0; $i < count($product_names); $i++) {
            $p_name = $product_names[$i];
            
            // Validate against predefined array to prevent injection
            if (!isset($medicines[$p_name])) continue;
            
            $p_id = $product_ids[$i] ?? '';
            $qty = (int)($quantities[$i] ?? 0);
            
            // Only force insert if qty > 0 OR if it already exists (update)
            $desc = $medicines[$p_name]['desc'];
            $prc = (float)$medicines[$p_name]['price'];
            $tot = $qty * $prc;

            if (!empty($p_id)) {
                // Update existing
                if ($upd_stmt) {
                    $pid_int = (int)$p_id;
                    $upd_stmt->bind_param("idii", $qty, $tot, $pid_int, $user_id);
                    $upd_stmt->execute();
                }
            } else {
                // Insert new ONLY if quantity is > 0 to save database space
                if ($qty > 0 && $ins_stmt) {
                    $ins_stmt->bind_param("issidd", $user_id, $p_name, $desc, $qty, $prc, $tot);
                    $ins_stmt->execute();
                }
            }
        }
        
        if(isset($ins_stmt)) $ins_stmt->close();
        if(isset($upd_stmt)) $upd_stmt->close();
        
        $conn->commit();
        $_SESSION['success_msg'] = "Inventory saved successfully.";
        header("Location: product_inventory.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "System Error: " . $e->getMessage();
    }
}

// Fetch existing products into a mapped array
$existing_mapped = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM product_inventory WHERE intern_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $existing_mapped[$row['product_name']] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details Inventory List - MediCare Pharmacy</title>
    
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
            text-align: center;
            color: #bdc3c7;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 2rem;
            margin-top: 2rem;
        }
        .page-header h1 {
            font-size: 3rem;
            font-weight: 300;
        }
        .form-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }
        .table thead th {
            background-color: #e2e8f0;
            color: #2d3748;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }
        .table tbody td {
            vertical-align: middle;
        }
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
        }
        .quantity-input {
            font-weight: bold;
            color: #0d6efd;
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

    <div class="container mb-5">
        <div class="page-header">
            <h1>Product Details<br>Inventory List</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-card mb-4">
            <div class="alert alert-info py-2">
                <i class="bi bi-info-circle me-1"></i> Please enter the available quantity for each product below. Products with 0 quantity will remain unlisted.
            </div>
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th width="22%">Product Name</th>
                                <th width="42%">Description</th>
                                <th width="12%">Quantity</th>
                                <th width="12%">Price ($)</th>
                                <th width="12%">Total ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $m_name => $m_data): ?>
                                <?php 
                                    $has_existing = isset($existing_mapped[$m_name]);
                                    $p_id = $has_existing ? $existing_mapped[$m_name]['id'] : '';
                                    $qty = $has_existing ? $existing_mapped[$m_name]['quantity'] : 0;
                                    $total = $qty * $m_data['price'];
                                ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="product_id[]" value="<?= $p_id ?>">
                                        <input type="hidden" name="product_name[]" value="<?= htmlspecialchars($m_name) ?>">
                                        <strong><?= htmlspecialchars($m_name) ?></strong>
                                    </td>
                                    <td class="text-muted">
                                        <?= htmlspecialchars($m_data['desc']) ?>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control text-center quantity-input" name="quantity[]" min="0" value="<?= $qty ?>" required>
                                    </td>
                                    <td class="text-end">
                                        $<span class="price-val"><?= number_format($m_data['price'], 2, '.', '') ?></span>
                                    </td>
                                    <td class="text-end bg-light fw-bold text-success">
                                        $<span class="total-val"><?= number_format($total, 2, '.', '') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                        <i class="bi bi-save"></i> Save All Inventory
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#inventoryTable tbody');

            function calculateTotals() {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const qtyInput = row.querySelector('.quantity-input');
                    const priceSpan = row.querySelector('.price-val');
                    const totalSpan = row.querySelector('.total-val');
                    
                    if (qtyInput && priceSpan && totalSpan) {
                        const qty = parseInt(qtyInput.value) || 0;
                        const price = parseFloat(priceSpan.textContent) || 0;
                        totalSpan.textContent = (qty * price).toFixed(2);
                    }
                });
            }

            // Calculate when quantity inputs change
            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input')) {
                    // Prevent negative numbers entirely by clamping
                    if (e.target.value < 0) e.target.value = 0;
                    calculateTotals();
                }
            });
        });
    </script>
</body>
</html>
