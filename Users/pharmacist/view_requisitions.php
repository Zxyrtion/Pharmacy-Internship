<?php
require_once '../../config.php';
require_once '../../notification_helper.php';

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
$user_id = $_SESSION['user_id'];

// Handle status updates
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['po_id'])) {
    $po_id = (int)$_POST['po_id'];
    $new_status = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $remarks = isset($_POST['remarks']) ? sanitizeInput($_POST['remarks']) : '';
    
    global $conn;
    
    // Get PO details first for notification
    $po_stmt = $conn->prepare("SELECT po_number, technician_id FROM requisition_reports WHERE id = ?");
    $po_stmt->bind_param("i", $po_id);
    $po_stmt->execute();
    $po_result = $po_stmt->get_result();
    $po_data = $po_result->fetch_assoc();
    $po_stmt->close();
    
    if ($new_status === 'Rejected' && !empty($remarks)) {
        // Update with remarks for rejection
        $stmt = $conn->prepare("UPDATE requisition_reports SET status = ?, remarks = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $new_status, $remarks, $po_id);
            if ($stmt->execute()) {
                $success = "Purchase Order #$po_id has been rejected.";
            } else {
                $error = "Failed to update PO status.";
            }
            $stmt->close();
        }
    } else {
        // Update without remarks (for approval)
        $stmt = $conn->prepare("UPDATE requisition_reports SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $po_id);
            if ($stmt->execute()) {
                $success = "Purchase Order #$po_id has been approved.";
            } else {
                $error = "Failed to update PO status.";
            }
            $stmt->close();
        }
    }
    
    // Send notification to technician
    if ($po_data && $po_data['technician_id']) {
        $technician_id = $po_data['technician_id'];
        $po_number = $po_data['po_number'];
        
        if ($new_status === 'Approved') {
            $message = "Your Purchase Order $po_number has been approved by $full_name.";
            createNotification($technician_id, $message, 'success', 'purchase_order', $po_id);
        } elseif ($new_status === 'Rejected') {
            $message = "Your Purchase Order $po_number has been rejected by $full_name.";
            if (!empty($remarks)) {
                $message .= " Reason: $remarks";
            }
            createNotification($technician_id, $message, 'error', 'purchase_order', $po_id);
        }
    }
}

// Fetch POs
$pos = [];
if (isset($conn)) {
    $sql = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id ORDER BY r.po_date DESC, r.id DESC";
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
                        <th>Total Cost (₱)</th>
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
                            <td class="fw-bold text-success">₱<?= number_format($p['total'], 2) ?></td>
                            <td>
                                <?php if($p['status'] === 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($p['status'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                    <?php if(!empty($p['remarks'])): ?>
                                        <br><small class="text-muted" data-bs-toggle="tooltip" title="<?= htmlspecialchars($p['remarks']) ?>">
                                            <i class="bi bi-chat-left-text"></i> <?= substr(htmlspecialchars($p['remarks']), 0, 30) ?><?= strlen($p['remarks']) > 30 ? '...' : '' ?>
                                        </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    // Create safe modal ID
                                    $safe_modal_id = 'rejectPO_' . $p['id'];
                                ?>
                                <a href="view_po.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info text-white me-1" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if($p['status'] === 'Pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="po_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success me-1" title="Approve PO">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $safe_modal_id ?>" title="Reject PO">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                    
                                    <!-- Rejection Modal -->
                                    <div class="modal fade" id="rejectModal<?= $safe_modal_id ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Reject Purchase Order</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="po_id" value="<?= $p['id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        
                                                        <div class="alert alert-light border">
                                                            <strong>PO Number:</strong> <?= htmlspecialchars($p['po_number']) ?><br>
                                                            <strong>Vendor:</strong> <?= htmlspecialchars($p['vendor_company']) ?><br>
                                                            <strong>Total:</strong> ₱<?= number_format($p['total'], 2) ?>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="remarks<?= $safe_modal_id ?>" class="form-label fw-bold">Rejection Remarks <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" id="remarks<?= $safe_modal_id ?>" name="remarks" rows="4" placeholder="Enter reason for rejection..." required></textarea>
                                                            <div class="form-text">Please provide a clear reason why this PO is being rejected.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No Purchase Orders have been generated yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
