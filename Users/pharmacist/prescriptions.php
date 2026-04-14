<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacist') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];
$success = '';
$error   = '';

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    pharmacist_id INT,
    order_date DATE,
    total_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('Pending','Dispensed','Paid','Cancelled') DEFAULT 'Pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    medicine_name VARCHAR(200),
    generic_name VARCHAR(200),
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) DEFAULT 0,
    amount DECIMAL(10,2) DEFAULT 0,
    sig VARCHAR(300)
)");

// Handle create purchase order (set prices)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $rx_id  = (int)$_POST['prescription_id'];
    $notes  = trim($_POST['order_notes'] ?? '');
    $items  = $_POST['order_items'] ?? [];

    $filtered = array_filter($items, fn($i) => !empty($i['medicine_name']) && (float)($i['unit_price'] ?? 0) > 0);

    if (empty($filtered)) {
        $error = 'Please set unit prices for at least one item.';
    } else {
        $total = 0;
        foreach ($filtered as $item) {
            $total += (int)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? 0);
        }

        $stmt = $conn->prepare("INSERT INTO purchase_orders (prescription_id, pharmacist_id, order_date, total_amount, notes) VALUES (?,?,CURDATE(),?,?)");
        $stmt->bind_param('iids', $rx_id, $_SESSION['user_id'], $total, $notes);
        $stmt->execute();
        $order_id = $stmt->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO purchase_order_items (order_id, medicine_name, generic_name, quantity, unit_price, amount, sig) VALUES (?,?,?,?,?,?,?)");
        foreach ($filtered as $item) {
            $med = $item['medicine_name']; $gen = $item['generic_name'] ?? '';
            $qty = (int)($item['quantity'] ?? 1); $price = (float)($item['unit_price'] ?? 0);
            $amt = $qty * $price; $sig = $item['sig'] ?? '';
            $stmt2->bind_param('issidds', $order_id, $med, $gen, $qty, $price, $amt, $sig);
            $stmt2->execute();
        }

        $upd = $conn->prepare("UPDATE prescriptions SET status='Processing' WHERE id=?");
        $upd->bind_param('i', $rx_id); $upd->execute();

        $success = 'Purchase order created. Total: ₱' . number_format($total, 2);
    }
}

// Handle dispense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispense'])) {
    $rx_id = (int)$_POST['prescription_id'];
    $conn->prepare("UPDATE prescriptions SET status='Ready' WHERE id=?")->bind_param('i', $rx_id);
    $upd = $conn->prepare("UPDATE prescriptions SET status='Ready' WHERE id=?");
    $upd->bind_param('i', $rx_id); $upd->execute();
    $upd2 = $conn->prepare("UPDATE purchase_orders SET status='Dispensed' WHERE prescription_id=?");
    $upd2->bind_param('i', $rx_id); $upd2->execute();
    $success = 'Prescription #' . $rx_id . ' marked as Ready for customer payment.';
}

// Filter
$filter = $_GET['status'] ?? 'Pending';
if (!in_array($filter, ['Pending','Processing','Ready','Dispensed','All'])) $filter = 'Pending';

if ($filter === 'All') {
    $res = $conn->query("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name
        FROM prescriptions p LEFT JOIN users u ON p.customer_id = u.id
        ORDER BY p.created_at DESC");
} else {
    $s = $conn->prepare("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name
        FROM prescriptions p LEFT JOIN users u ON p.customer_id = u.id
        WHERE p.status=? ORDER BY p.created_at DESC");
    $s->bind_param('s', $filter); $s->execute();
    $res = $s->get_result();
}
$prescriptions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// View single
$view_rx = null; $view_items = []; $view_order = null; $view_order_items = [];
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $sv = $conn->prepare("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name
        FROM prescriptions p LEFT JOIN users u ON p.customer_id = u.id WHERE p.id=?");
    $sv->bind_param('i', $vid); $sv->execute();
    $view_rx = $sv->get_result()->fetch_assoc();

    $si = $conn->prepare("SELECT * FROM prescription_items WHERE prescription_id=?");
    $si->bind_param('i', $vid); $si->execute();
    $view_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);

    $so = $conn->prepare("SELECT * FROM purchase_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
    $so->bind_param('i', $vid); $so->execute();
    $view_order = $so->get_result()->fetch_assoc();

    if ($view_order) {
        $soi = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id=?");
        $soi->bind_param('i', $view_order['id']); $soi->execute();
        $view_order_items = $soi->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .rx-header { border-bottom: 2px solid #c0392b; padding-bottom: 1rem; margin-bottom: 1.5rem; text-align: center; }
        .rx-header h4 { color: #c0392b; font-weight: 700; font-style: italic; }
        .rx-header p { color: #2563b0; font-weight: 600; margin: 0; }
        .med-table thead { background: #2c3e50; color: white; }
        .med-table td, .med-table th { vertical-align: middle; }
        .badge-pending    { background:#ffc107; color:#000; }
        .badge-processing { background:#0dcaf0; color:#000; }
        .badge-ready      { background:#198754; }
        .badge-dispensed  { background:#6f42c1; }
        .badge-cancelled  { background:#dc3545; }
        .validity-note { color:#c0392b; font-style:italic; font-size:0.85rem; }
        @media print { .no-print { display:none !important; } body { background:white; } .page-card { box-shadow:none; } }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm no-print">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="no-print mt-3 mb-2 d-flex justify-content-between align-items-center">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <h5 class="mb-0"><i class="bi bi-file-medical"></i> Prescriptions</h5>
        <span></span>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success no-print mt-2"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger no-print mt-2"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($view_rx): ?>
    <!-- DETAIL VIEW -->
    <div class="page-card">
        <div class="rx-header">
            <h4><?= htmlspecialchars($view_rx['doctor_name']) ?></h4>
            <p><?= htmlspecialchars($view_rx['doctor_specialization']) ?></p>
        </div>

        <div class="row mb-3">
            <div class="col-md-3"><strong>Date:</strong> <?= htmlspecialchars($view_rx['prescription_date']) ?></div>
            <div class="col-md-3"><strong>Patient:</strong> <?= htmlspecialchars($view_rx['patient_name']) ?></div>
            <div class="col-md-3"><strong>Age/Gender:</strong> <?= htmlspecialchars($view_rx['patient_age']) ?> / <?= htmlspecialchars($view_rx['patient_gender']) ?></div>
            <div class="col-md-3"><strong>Submitted by:</strong> <?= htmlspecialchars($view_rx['customer_name'] ?? '-') ?></div>
        </div>
        <div class="mb-3">
            <strong>Status:</strong>
            <span class="badge badge-<?= strtolower($view_rx['status']) ?>"><?= $view_rx['status'] ?></span>
        </div>

        <div class="text-center fw-bold my-3" style="letter-spacing:2px;">PRESCRIPTION</div>

        <?php if ($view_rx['status'] === 'Pending'): ?>
        <!-- Set prices form -->
        <form method="POST">
            <input type="hidden" name="prescription_id" value="<?= $view_rx['id'] ?>">
            <div class="table-responsive">
                <table class="table table-bordered med-table">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Generic Name</th>
                            <th>Qty</th>
                            <th>Sig.</th>
                            <th>Unit Price (₱)</th>
                            <th>Amount (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($view_items as $i => $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['medicine_name']) ?>
                                <input type="hidden" name="order_items[<?= $i ?>][medicine_name]" value="<?= htmlspecialchars($item['medicine_name']) ?>">
                            </td>
                            <td><?= htmlspecialchars($item['generic_name']) ?>
                                <input type="hidden" name="order_items[<?= $i ?>][generic_name]" value="<?= htmlspecialchars($item['generic_name']) ?>">
                                <input type="hidden" name="order_items[<?= $i ?>][sig]" value="<?= htmlspecialchars($item['sig']) ?>">
                            </td>
                            <td>
                                <input type="number" name="order_items[<?= $i ?>][quantity]" class="form-control form-control-sm qty"
                                       value="<?= htmlspecialchars($item['quantity']) ?>" min="1" style="width:70px;">
                            </td>
                            <td><?= htmlspecialchars($item['sig']) ?></td>
                            <td>
                                <input type="number" name="order_items[<?= $i ?>][unit_price]" class="form-control form-control-sm price"
                                       step="0.01" min="0" placeholder="0.00" style="width:110px;" required>
                            </td>
                            <td class="fw-semibold text-end amount-cell">-</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f0f4ff; font-weight:700;">
                            <td colspan="5" class="text-end">TOTAL:</td>
                            <td class="text-end" id="grandTotal">₱0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if ($view_rx['notes']): ?>
            <div class="mb-3"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($view_rx['notes'])) ?></div>
            <?php endif; ?>
            <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>

            <div class="card mt-3 border-primary no-print">
                <div class="card-header bg-primary text-white fw-semibold">
                    <i class="bi bi-cart-plus"></i> Confirm Purchase Order
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <textarea name="order_notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" name="create_order" value="1" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle"></i> Confirm Purchase Order
                    </button>
                </div>
            </div>
        </form>

        <?php elseif ($view_rx['status'] === 'Processing' && $view_order): ?>
        <!-- Dispense view -->
        <div class="table-responsive">
            <table class="table table-bordered med-table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Generic Name</th>
                        <th>Qty</th>
                        <th>Sig.</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                        <th>Dispensed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($view_order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($item['generic_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= htmlspecialchars($item['sig']) ?></td>
                        <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                        <td>₱<?= number_format($item['amount'], 2) ?></td>
                        <td class="text-center"><i class="bi bi-clock text-warning"></i></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f0f4ff; font-weight:700;">
                        <td colspan="5" class="text-end">TOTAL:</td>
                        <td colspan="2">₱<?= number_format($view_order['total_amount'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>

        <div class="card mt-3 border-success no-print">
            <div class="card-header bg-success text-white fw-semibold">
                <i class="bi bi-bag-check"></i> Dispense Medicines
            </div>
            <div class="card-body">
                <p>Confirm all medicines are prepared and ready for the customer.</p>
                <form method="POST">
                    <input type="hidden" name="prescription_id" value="<?= $view_rx['id'] ?>">
                    <button type="submit" name="dispense" value="1" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Mark as Dispensed / Ready for Pickup
                    </button>
                </form>
            </div>
        </div>

        <?php elseif (in_array($view_rx['status'], ['Ready','Dispensed'])): ?>
        <!-- Already dispensed -->
        <div class="table-responsive">
            <table class="table table-bordered med-table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Generic Name</th>
                        <th>Qty</th>
                        <th>Sig.</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($view_order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($item['generic_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= htmlspecialchars($item['sig']) ?></td>
                        <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                        <td>₱<?= number_format($item['amount'], 2) ?></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f0f4ff; font-weight:700;">
                        <td colspan="5" class="text-end">TOTAL:</td>
                        <td colspan="2">₱<?= number_format($view_order['total_amount'] ?? 0, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>
        <div class="alert alert-success mt-3">
            <i class="bi bi-check-circle-fill"></i>
            <?= $view_rx['status'] === 'Ready' ? 'Medicines dispensed. Awaiting customer payment.' : 'Completed and paid.' ?>
        </div>
        <?php endif; ?>

        <div class="row mt-3">
            <div class="col-md-6">
                <p class="mb-1"><?= htmlspecialchars($view_rx['doctor_clinic'] ?? '') ?></p>
                <p class="mb-1"><?= htmlspecialchars($view_rx['doctor_contact'] ?? '') ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-1">PRC: <?= htmlspecialchars($view_rx['doctor_prc'] ?? '') ?></p>
                <p class="mb-1">PTR: <?= htmlspecialchars($view_rx['doctor_ptr'] ?? '') ?></p>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3 no-print">
            <a href="prescriptions.php?status=<?= $filter ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    <?php else: ?>
    <!-- LIST VIEW -->
    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Customer Prescriptions</h5>
            <div class="btn-group no-print">
                <?php foreach (['Pending','Processing','Ready','Dispensed','All'] as $s): ?>
                <a href="?status=<?= $s ?>" class="btn btn-sm btn-outline-primary <?= $filter === $s ? 'active fw-bold' : '' ?>">
                    <?= $s ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($prescriptions)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size:3rem;"></i>
                <p class="mt-2">No <?= strtolower($filter) ?> prescriptions found.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Submitted By</th>
                        <th>Rx Date</th>
                        <th>Status</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $rx): ?>
                    <tr>
                        <td>#<?= $rx['id'] ?></td>
                        <td><?= htmlspecialchars($rx['patient_name']) ?></td>
                        <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($rx['customer_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($rx['prescription_date']) ?></td>
                        <td><span class="badge badge-<?= strtolower($rx['status']) ?>"><?= $rx['status'] ?></span></td>
                        <td class="no-print">
                            <a href="?view=<?= $rx['id'] ?>&status=<?= $filter ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i>
                                <?= $rx['status'] === 'Pending' ? 'Process' : 'View' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function calcTotals() {
        let grand = 0;
        document.querySelectorAll('tbody tr').forEach(row => {
            const qty   = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.price')?.value) || 0;
            const amt   = qty * price;
            const cell  = row.querySelector('.amount-cell');
            if (cell) cell.textContent = amt > 0 ? '₱' + amt.toLocaleString('en-PH', {minimumFractionDigits:2}) : '-';
            grand += amt;
        });
        const gt = document.getElementById('grandTotal');
        if (gt) gt.textContent = '₱' + grand.toLocaleString('en-PH', {minimumFractionDigits:2});
    }
    document.querySelectorAll('.qty, .price').forEach(el => el.addEventListener('input', calcTotals));
</script>
</body>
</html>
