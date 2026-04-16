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

// Fetch inventory movements
$movements = [];
if (isset($conn)) {
    $stmt = $conn->prepare("
        SELECT m.*, p.product_name, p.unit 
        FROM inventory_movements m 
        JOIN product_inventory p ON m.product_id = p.id 
        WHERE p.intern_id = ? 
        ORDER BY m.created_at DESC 
        LIMIT 100
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $movements[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Movement History - MediCare Pharmacy</title>
    
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
        .movement-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        .table-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="enhanced_inventory.php">
                <i class="bi bi-arrow-left"></i> Back to Inventory
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
            <h1><i class="bi bi-clock-history"></i> Inventory Movement History</h1>
            <p class="mb-0">Track all stock changes and adjustments</p>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>Movement Type</th>
                            <th>Quantity</th>
                            <th>Previous Qty</th>
                            <th>New Qty</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($movement['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($movement['product_name']); ?></strong></td>
                                <td>
                                    <span class="badge movement-badge bg-<?php 
                                        echo $movement['movement_type'] === 'Stock In' ? 'success' : 
                                            ($movement['movement_type'] === 'Stock Out' ? 'danger' : 
                                            ($movement['movement_type'] === 'Adjustment' ? 'warning' : 'info')); 
                                    ?>">
                                        <?php echo $movement['movement_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo $movement['quantity']; ?> <?php echo $movement['unit']; ?></td>
                                <td><?php echo $movement['previous_quantity']; ?></td>
                                <td><?php echo $movement['new_quantity']; ?></td>
                                <td>₱<?php echo number_format($movement['unit_cost'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($movement['total_cost'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($movement['reference_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($movement['notes'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($movements)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox"></i> No movement history yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
