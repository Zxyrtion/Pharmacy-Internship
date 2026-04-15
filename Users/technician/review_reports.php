<?php
require_once '../../config.php';
require_once '../../notification_helper.php';

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
    $remarks = isset($_POST['remarks']) ? sanitizeInput($_POST['remarks']) : '';
    
    global $conn;
    
    if ($new_status === 'Rejected' && !empty($remarks)) {
        // Update with remarks for rejection
        $stmt = $conn->prepare("UPDATE inventory_report SET status = ?, remarks = ? WHERE inventory_period = ? AND reporter = ? AND status = 'Pending'");
        if($stmt) {
            $stmt->bind_param("ssss", $new_status, $remarks, $period, $rep);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Update without remarks (for approval)
        $stmt = $conn->prepare("UPDATE inventory_report SET status = ? WHERE inventory_period = ? AND reporter = ? AND status = 'Pending'");
        if($stmt) {
            $stmt->bind_param("sss", $new_status, $period, $rep);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Send notification to intern
    $internId = getUserIdByName($rep);
    if ($internId) {
        if ($new_status === 'Approved') {
            $message = "Your inventory report for period $period has been approved by $full_name. You can now proceed with creating a purchase order.";
            createNotification($internId, $message, 'success', 'inventory_report', 0);
        } elseif ($new_status === 'Rejected') {
            $message = "Your inventory report for period $period has been rejected by $full_name.";
            if (!empty($remarks)) {
                $message .= " Reason: $remarks";
            }
            createNotification($internId, $message, 'error', 'inventory_report', 0);
        }
    }
}

// Fetch all reports grouped
$reports = [];
if (isset($conn)) {
    $sql = "SELECT inventory_period, reporter, status, remarks, MAX(created_at) as submitted_at, SUM(inventory_value) as total_value, COUNT(id) as item_count FROM inventory_report GROUP BY inventory_period, reporter, status, remarks ORDER BY submitted_at DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Check if PO exists for this approved report
            if ($row['status'] === 'Approved') {
                $search_pattern = "Inventory Report: " . $row['inventory_period'] . " by " . $row['reporter'];
                $po_stmt = $conn->prepare("SELECT id, po_number FROM requisition_reports WHERE comments LIKE ?");
                if ($po_stmt) {
                    $search_with_wildcards = "%$search_pattern%";
                    $po_stmt->bind_param("s", $search_with_wildcards);
                    $po_stmt->execute();
                    $po_result = $po_stmt->get_result();
                    $existing_po = $po_result->fetch_assoc();
                    $po_stmt->close();
                    
                    // Add PO info to the report data
                    $row['has_po'] = !empty($existing_po);
                    $row['po_id'] = $existing_po['id'] ?? null;
                    $row['po_number'] = $existing_po['po_number'] ?? null;
                } else {
                    $row['has_po'] = false;
                    $row['po_id'] = null;
                    $row['po_number'] = null;
                }
            } else {
                $row['has_po'] = false;
                $row['po_id'] = null;
                $row['po_number'] = null;
            }
            
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
                        <th>Total Value (₱)</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($reports)): ?>
                        <?php foreach($reports as $r): ?>
                        <?php 
                            // Create safe modal ID by sanitizing inventory period
                            $safe_modal_id = preg_replace('/[^a-zA-Z0-9]/', '_', $r['inventory_period'] . '_' . $r['reporter']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r['inventory_period']) ?></td>
                            <td><?= htmlspecialchars($r['reporter']) ?></td>
                            <td><?= htmlspecialchars($r['item_count']) ?></td>
                            <td>₱<?= number_format($r['total_value'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($r['submitted_at'])) ?></td>
                            <td>
                                <?php if($r['status'] === 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($r['status'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                    <?php if(!empty($r['remarks'])): ?>
                                        <br><small class="text-muted" data-bs-toggle="tooltip" title="<?= htmlspecialchars($r['remarks']) ?>">
                                            <i class="bi bi-chat-left-text"></i> <?= substr(htmlspecialchars($r['remarks']), 0, 30) ?><?= strlen($r['remarks']) > 30 ? '...' : '' ?>
                                        </small>
                                    <?php endif; ?>
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
                                        <button type="submit" class="btn btn-sm btn-success me-1" title="Quick Approve"><i class="bi bi-check-circle"></i> Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $safe_modal_id ?>" title="Reject with Remarks">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                    
                                    <!-- Rejection Modal -->
                                    <div class="modal fade" id="rejectModal<?= $safe_modal_id ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Reject Inventory Report</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="inventory_period" value="<?= htmlspecialchars($r['inventory_period']) ?>">
                                                        <input type="hidden" name="reporter" value="<?= htmlspecialchars($r['reporter']) ?>">
                                                        <input type="hidden" name="status" value="Rejected">
                                                        
                                                        <div class="mb-3">
                                                            <label for="remarks<?= $safe_modal_id ?>" class="form-label fw-bold">Rejection Remarks <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" id="remarks<?= $safe_modal_id ?>" name="remarks" rows="4" placeholder="Enter reason for rejection..." required></textarea>
                                                            <div class="form-text">Please provide a clear reason why this report is being rejected.</div>
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
                                <?php elseif($r['status'] === 'Approved'): ?>
                                    <?php if($r['has_po']): ?>
                                        <a href="edit_po.php?id=<?= $r['po_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil-square"></i> Edit PO
                                        </a>
                                    <?php else: ?>
                                        <a href="create_po.php?period=<?= urlencode($r['inventory_period']) ?>&reporter=<?= urlencode($r['reporter']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-file-earmark-text"></i> Create PO
                                        </a>
                                    <?php endif; ?>
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
