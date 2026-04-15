<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check role
if ($_SESSION['role_name'] !== 'Pharmacist') {
    header('Location: ../index.php');
    exit();
}

$full_name = $_SESSION['full_name'];

// Handle Quick Approvals
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['po_id'])) {
    $po_id = (int)$_POST['po_id'];
    $new_status = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    
    global $conn;
    $stmt = $conn->prepare("UPDATE requisition_reports SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $po_id);
        if ($stmt->execute()) {
            $success = "Purchase Order #$po_id status successfully updated to $new_status.";
        } else {
            $error = "Failed to update PO status.";
        }
        $stmt->close();
    }
}

// Fetch POs
$pos = [];
if (isset($conn)) {
    $sql = "SELECT r.*, u.full_name as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id ORDER BY r.po_date DESC, r.id DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $pos[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Pharmacist</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .page-header { background: #e74c3c; color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 10px 10px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Pharmacist Dashboard
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
            <h2><i class="bi bi-card-checklist"></i> View Purchase Orders</h2>
            <p class="mb-0">Review orders requested by the Technician</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>PO Number</th>
                        <th>Date</th>
                        <th>Requested By</th>
                        <th>Vendor / Supplier</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($pos)): ?>
                        <?php foreach($pos as $p): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($p['po_number']) ?></td>
                            <td><?= date('M d, Y', strtotime($p['po_date'])) ?></td>
                            <td><?= htmlspecialchars($p['technician_name'] ?? 'Unknown') ?></td>
                            <td>
                                <div><?= htmlspecialchars($p['vendor_company']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($p['vendor_phone']) ?></small>
                            </td>
                            <td class="fw-bold text-success">$<?= number_format($p['total'], 2) ?></td>
                            <td>
                                <?php if($p['status'] === 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($p['status'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_po.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info text-white me-1" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if($p['status'] === 'Pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="po_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve PO">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No Purchase Orders have been generated yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
