<?php
require_once '../../config.php';

// Check logged in user
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check role
if ($_SESSION['role_name'] !== 'Pharmacy Technician') {
    header('Location: ../index.php');
    exit();
}

$full_name = $_SESSION['full_name'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $period = sanitizeInput($_POST['inventory_period']);
    $rep = sanitizeInput($_POST['reporter']);
    $new_status = sanitizeInput($_POST['status']);
    
    global $conn;
    $stmt = $conn->prepare("UPDATE inventory_report SET status = ? WHERE inventory_period = ? AND reporter = ? AND status = 'Pending'");
    if($stmt) {
        $stmt->bind_param("sss", $new_status, $period, $rep);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all reports grouped
$reports = [];
if (isset($conn)) {
    $sql = "SELECT inventory_period, reporter, status, MAX(created_at) as submitted_at, SUM(inventory_value) as total_value, COUNT(id) as item_count FROM inventory_report GROUP BY inventory_period, reporter, status ORDER BY submitted_at DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $reports[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Inventory Reports - Pharmacy Technician</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .page-header { background: #3498db; color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 10px 10px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Technician Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header text-center">
            <h2><i class="bi bi-clipboard-data"></i> Review Inventory Reports</h2>
            <p class="mb-0">Approve intern submissions and request stocks</p>
        </div>

        <div class="card p-4">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Inventory Period</th>
                        <th>Reporter (Intern)</th>
                        <th>Items Count</th>
                        <th>Total Value</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($reports)): ?>
                        <?php foreach($reports as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['inventory_period']) ?></td>
                            <td><?= htmlspecialchars($r['reporter']) ?></td>
                            <td><?= htmlspecialchars($r['item_count']) ?></td>
                            <td>$<?= number_format($r['total_value'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($r['submitted_at'])) ?></td>
                            <td>
                                <?php if($r['status'] === 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($r['status'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_report.php?period=<?= urlencode($r['inventory_period']) ?>&reporter=<?= urlencode($r['reporter']) ?>" class="btn btn-sm btn-info text-white me-1" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if($r['status'] === 'Pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="inventory_period" value="<?= htmlspecialchars($r['inventory_period']) ?>">
                                        <input type="hidden" name="reporter" value="<?= htmlspecialchars($r['reporter']) ?>">
                                        
                                        <input type="hidden" name="status" value="Approved">
                                        <button type="submit" class="btn btn-sm btn-success" title="Quick Approve"><i class="bi bi-check-circle"></i> Approve</button>
                                    </form>
                                <?php elseif($r['status'] === 'Approved'): ?>
                                    <a href="create_po.php?period=<?= urlencode($r['inventory_period']) ?>&reporter=<?= urlencode($r['reporter']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-file-earmark-text"></i> Create PO
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No inventory reports submitted yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
