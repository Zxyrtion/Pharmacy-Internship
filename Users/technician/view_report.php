<?php
require_once '../../config.php';

if (!isLoggedIn() || $_SESSION['role_name'] !== 'Pharmacy Technician') {
    header('Location: ../index.php');
    exit();
}

$full_name = $_SESSION['full_name'];
$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '';
$reporter = isset($_GET['reporter']) ? sanitizeInput($_GET['reporter']) : '';

if (empty($period) || empty($reporter)) {
    header('Location: review_reports.php');
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $new_status = sanitizeInput($_POST['action']);
    if (in_array($new_status, ['Approved', 'Rejected'])) {
        $stmt = $conn->prepare("UPDATE inventory_report SET status = ? WHERE inventory_period = ? AND reporter = ? AND status = 'Pending'");
        if ($stmt) {
            $stmt->bind_param("sss", $new_status, $period, $reporter);
            $stmt->execute();
            $stmt->close();
            header('Location: review_reports.php');
            exit();
        }
    }
}

// Fetch report data
$status = 'Unknown';
$items = [];
$total_value = 0;

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM inventory_report WHERE inventory_period = ? AND reporter = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $period, $reporter);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if ($status === 'Unknown') $status = $row['status'];
            $items[] = $row;
            $total_value += $row['inventory_value'];
        }
        $stmt->close();
    }
}

// Check if PO already exists for this inventory report
$existing_po = null;
if (isset($conn) && $status === 'Approved') {
    // Search for PO with inventory report reference in comments
    $search_pattern = "Inventory Report: $period by $reporter";
    $stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.comments LIKE ?");
    if ($stmt) {
        $search_with_wildcards = "%$search_pattern%";
        $stmt->bind_param("s", $search_with_wildcards);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing_po = $res->fetch_assoc();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Details - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Technician Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Inventory Report Details</h2>
                <h5 class="text-muted">Period: <strong><?= htmlspecialchars($period) ?></strong> | Reporter: <strong><?= htmlspecialchars($reporter) ?></strong></h5>
            </div>
            <div>
                <span class="fs-5 badge <?= $status === 'Pending' ? 'bg-warning text-dark' : ($status === 'Approved' ? 'bg-success' : 'bg-danger') ?>">
                    Status: <?= htmlspecialchars($status) ?>
                </span>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Item Number/Name</th>
                                <th>Manufacturer</th>
                                <th>Cost Per Item</th>
                                <th>Stock Qty</th>
                                <th>Inventory Value</th>
                                <th>Reorder Point</th>
                                <th>Cycle</th>
                                <th>Req. Qty</th>
                                <th>Reorder?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['item_number_name']) ?>
                                        <?php if ($item['item_discontinued'] === 'Yes'): ?>
                                            <span class="badge bg-danger ms-1">Discontinued</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['manufacturer'] ?: '-') ?></td>
                                    <td>₱<?= number_format($item['cost_per_item'], 2) ?></td>
                                    <td><?= htmlspecialchars($item['stock_quantity']) ?></td>
                                    <td>₱<?= number_format($item['inventory_value'], 2) ?></td>
                                    <td><?= htmlspecialchars($item['reorder_point']) ?></td>
                                    <td><?= htmlspecialchars($item['reorder_cycle']) ?></td>
                                    <td><?= htmlspecialchars($item['item_reorder_quantity']) ?></td>
                                    <td>
                                        <?php if ($item['reorder_required'] === 'Yes'): ?>
                                            <span class="badge bg-primary">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center py-4">No items found for this report.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Total Inventory Value:</td>
                                <td class="text-primary">₱<?= number_format($total_value, 2) ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($status === 'Pending'): ?>
        <div class="d-flex gap-3 justify-content-end mb-5">
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="Rejected">
                <button type="submit" class="btn btn-danger btn-lg px-4" onclick="return confirm('Are you sure you want to REJECT this report?');">
                    <i class="bi bi-x-circle"></i> Reject Report
                </button>
            </form>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="Approved">
                <button type="submit" class="btn btn-success btn-lg px-4" onclick="return confirm('Are you sure you want to APPROVE this report?');">
                    <i class="bi bi-check-circle"></i> Approve Report
                </button>
            </form>
        </div>
        <?php elseif ($status === 'Approved'): ?>
        <div class="d-flex justify-content-end mb-5 gap-3">
            <?php if ($existing_po): ?>
                <a href="edit_po.php?id=<?= $existing_po['id'] ?>" class="btn btn-warning btn-lg px-4">
                    <i class="bi bi-pencil-square"></i> Edit PO #<?= htmlspecialchars($existing_po['po_number']) ?>
                </a>
            <?php endif; ?>
            <form method="POST" action="create_requisition.php" style="display: inline;">
                <input type="hidden" name="period" value="<?= htmlspecialchars($period) ?>">
                <input type="hidden" name="reporter" value="<?= htmlspecialchars($reporter) ?>">
                <button type="submit" class="btn btn-success btn-lg px-4">
                    <i class="bi bi-cart-plus"></i> Create Requisition
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
