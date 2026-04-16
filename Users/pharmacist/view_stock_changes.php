<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacist') {
    header('Location: /internship/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get stock changes
$sql = "SELECT sc.*, u.first_name, u.last_name 
        FROM stock_changes sc 
        LEFT JOIN users u ON sc.changed_by = u.id 
        ORDER BY sc.created_at DESC 
        LIMIT 100";
$result = $conn->query($sql);
$stock_changes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Changes History - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .card {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stock-increase {
            color: #28a745;
            font-weight: bold;
        }
        
        .stock-decrease {
            color: #dc3545;
            font-weight: bold;
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

    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h2><i class="bi bi-clock-history"></i> Stock Changes History</h2>
                <p class="text-muted">Track all inventory stock changes</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Medicine</th>
                                <th>Previous Stock</th>
                                <th>New Stock</th>
                                <th>Change</th>
                                <th>Reason</th>
                                <th>Changed By</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stock_changes)): ?>
                                <?php foreach ($stock_changes as $change): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($change['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($change['medicine_name']); ?></td>
                                        <td><?php echo $change['previous_stock']; ?></td>
                                        <td><?php echo $change['new_stock']; ?></td>
                                        <td class="<?php echo $change['change_amount'] > 0 ? 'stock-increase' : 'stock-decrease'; ?>">
                                            <?php echo ($change['change_amount'] > 0 ? '+' : '') . $change['change_amount']; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($change['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($change['first_name'] . ' ' . $change['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($change['reference_id'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No stock changes recorded yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-4">
                    <a href="manage_inventory.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
