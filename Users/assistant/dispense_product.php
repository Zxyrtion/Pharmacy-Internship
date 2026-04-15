<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];
$success = '';
$error   = '';

// Ensure prescription_orders table exists (for customer prescriptions)
$conn->query("CREATE TABLE IF NOT EXISTS prescription_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Ensure prescription_order_items table exists
$conn->query("CREATE TABLE IF NOT EXISTS prescription_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    medicine_name VARCHAR(200) NOT NULL,
    generic_name VARCHAR(200) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) DEFAULT 0,
    amount DECIMAL(10,2) DEFAULT 0,
    sig VARCHAR(300) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES prescription_orders(id) ON DELETE CASCADE
)");

// Product logs table should already exist - don't recreate it
// The existing table has: order_id, prescription_id, medicine_id, medicine_name, dosage, 
// quantity_dispensed, unit_price, total_price, pharmacist_id, patient_id, patient_name, 
// action, notes, log_date, created_at

// Handle dispense confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_dispense'])) {
    $rx_id = (int)$_POST['prescription_id'];

    // Prevent duplicate dispensing
    $chk = $conn->prepare("SELECT status FROM prescriptions WHERE id=?");
    $chk->bind_param('i', $rx_id); $chk->execute();
    $chk_row = $chk->get_result()->fetch_assoc();

    if (!$chk_row || $chk_row['status'] !== 'Processing') {
        $error = 'This prescription has already been dispensed or is not ready.';
    } else {

    // Load order items and log them
    $so = $conn->prepare("SELECT * FROM prescription_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
    $so->bind_param('i', $rx_id); $so->execute();
    $order = $so->get_result()->fetch_assoc();

    $sr = $conn->prepare("SELECT * FROM prescriptions WHERE id=?");
    $sr->bind_param('i', $rx_id); $sr->execute();
    $rx = $sr->get_result()->fetch_assoc();

    if ($order && $rx) {
        $si = $conn->prepare("SELECT * FROM prescription_order_items WHERE order_id=?");
        $si->bind_param('i', $order['id']); $si->execute();
        $items = $si->get_result()->fetch_all(MYSQLI_ASSOC);

        // Create an entry in the orders table (required for product_logs foreign key)
        $order_id_str = 'ORD-' . date('Ymd') . '-' . str_pad($rx_id, 4, '0', STR_PAD_LEFT);
        $customer_id = $rx['customer_id'] ?? 0;
        $customer_name = $rx['patient_name'] ?? '';
        $total_amount = $order['total_amount'] ?? 0;
        $pharmacist_id = $_SESSION['user_id'];
        
        $insert_order = $conn->prepare("INSERT INTO orders (order_id, prescription_id, customer_id, customer_name, order_type, total_amount, status, pharmacist_id, order_date) VALUES (?,?,?,?,'Prescription',?,'Ready',?,NOW())");
        $insert_order->bind_param('siisdi', 
            $order_id_str, 
            $rx_id, 
            $customer_id, 
            $customer_name,
            $total_amount,
            $pharmacist_id
        );
        $insert_order->execute();
        $orders_table_id = $insert_order->insert_id;

        $stmt = $conn->prepare("INSERT INTO product_logs (prescription_id, order_id, medicine_name, dosage, quantity_dispensed, quantity, unit_price, total_price, pharmacist_id, patient_id, patient_name, doctor_name, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($stmt === false) {
            $error = 'Database error preparing product_logs insert: ' . $conn->error;
        } else {
            foreach ($items as $item) {
                // Assign to variables for bind_param (can't pass expressions by reference)
                $medicine_name = $item['medicine_name'] ?? '';
                $generic_name = $item['generic_name'] ?? '';
                $quantity = $item['quantity'] ?? 0;
                $unit_price = $item['unit_price'] ?? 0;
                $amount = $item['amount'] ?? 0;
                $patient_id = $rx['patient_id'] ?? 0;
                $patient_name = $rx['patient_name'] ?? '';
                $doctor_name = $rx['doctor_name'] ?? '';
                $notes = 'Sig: ' . ($item['sig'] ?? '');
                
                $stmt->bind_param('iissiiddiisss',
                    $rx_id, 
                    $orders_table_id,
                    $medicine_name, 
                    $generic_name,
                    $quantity,
                    $quantity,  // Both quantity_dispensed and quantity
                    $unit_price, 
                    $amount,
                    $pharmacist_id,
                    $patient_id,
                    $patient_name,
                    $doctor_name,
                    $notes
                );
                $stmt->execute();
            }
        }

        // Mark prescription as Ready (awaiting customer payment)
        $upd = $conn->prepare("UPDATE prescriptions SET status='Ready' WHERE id=?");
        $upd->bind_param('i', $rx_id); $upd->execute();

        $success = 'Prescription #' . $rx_id . ' dispensed successfully to ' . htmlspecialchars($rx['patient_name']) . '. Awaiting customer payment.';
    } else {
        $error = 'Order not found for this prescription.';
    }
    } // end duplicate check else
}

// Load prescriptions that are Processing only (not yet dispensed by assistant)
$res = $conn->query("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name, o.total_amount
    FROM prescriptions p
    LEFT JOIN users u ON p.customer_id = u.id
    LEFT JOIN prescription_orders o ON o.prescription_id = p.id
    WHERE p.status = 'Processing'
    ORDER BY p.created_at DESC");
$ready_prescriptions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// View single for dispensing
$view_rx = null; $view_items = []; $view_order = null;
if (isset($_GET['rx_id'])) {
    $vid = (int)$_GET['rx_id'];
    $sv = $conn->prepare("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name
        FROM prescriptions p LEFT JOIN users u ON p.customer_id = u.id WHERE p.id=?");
    if ($sv === false) {
        die("Error preparing prescription query: " . $conn->error);
    }
    $sv->bind_param('i', $vid); $sv->execute();
    $view_rx = $sv->get_result()->fetch_assoc();

    $so = $conn->prepare("SELECT * FROM prescription_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
    if ($so === false) {
        die("Error preparing prescription_orders query: " . $conn->error);
    }
    $so->bind_param('i', $vid); $so->execute();
    $view_order = $so->get_result()->fetch_assoc();

    if ($view_order) {
        $si = $conn->prepare("SELECT * FROM prescription_order_items WHERE order_id=?");
        if ($si === false) {
            die("Error preparing prescription_order_items query: " . $conn->error . "<br><br>The 'prescription_order_items' table may not exist. Please create it first.");
        }
        $si->bind_param('i', $view_order['id']); $si->execute();
        $view_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Recent logs
$logs_res = $conn->query("SELECT pl.*, u.first_name, u.last_name, 
    COALESCE(pl.doctor_name, p.doctor_name) as doctor_name
    FROM product_logs pl
    LEFT JOIN users u ON u.id = pl.pharmacist_id
    LEFT JOIN prescriptions p ON p.id = pl.prescription_id
    ORDER BY pl.log_date DESC LIMIT 20");
$recent_logs = $logs_res ? $logs_res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispense Product - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .rx-header { border-bottom: 2px solid #c0392b; padding-bottom: 1rem; margin-bottom: 1.5rem; text-align: center; }
        .rx-header h4 { color: #c0392b; font-weight: 700; font-style: italic; }
        .rx-header p { color: #2563b0; font-weight: 600; margin: 0; }
        .med-table thead { background: #2c3e50; color: white; }
        .validity-note { color: #c0392b; font-style: italic; font-size: 0.85rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="mt-3 mb-2 d-flex justify-content-between align-items-center">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <h5 class="mb-0"><i class="bi bi-bag-check"></i> Dispense Product — Process 17</h5>
        <span></span>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success mt-2"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-2"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($view_rx && $view_order): ?>
    <!-- DISPENSE DETAIL VIEW -->
    <div class="page-card">
        <div class="rx-header">
            <h4><?= htmlspecialchars($view_rx['doctor_name'] ?? '') ?></h4>
            <p><?= htmlspecialchars($view_rx['doctor_specialization'] ?? '') ?></p>
        </div>

        <div class="row mb-3">
            <div class="col-md-3"><strong>Date:</strong> <?= htmlspecialchars($view_rx['prescription_date'] ?? '') ?></div>
            <div class="col-md-3"><strong>Patient:</strong> <?= htmlspecialchars($view_rx['patient_name'] ?? '') ?></div>
            <div class="col-md-3"><strong>Customer:</strong> <?= htmlspecialchars($view_rx['customer_name'] ?? '-') ?></div>
            <div class="col-md-3"><strong>Total:</strong> ₱<?= number_format($view_order['total_amount'] ?? 0, 2) ?></div>
        </div>

        <div class="text-center fw-bold my-3" style="letter-spacing:2px;">PRESCRIPTION</div>

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
                        <th>Stock Check</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($view_items as $item):
                        // Check stock
                        $stock_stmt = $conn->prepare("SELECT stock_quantity FROM medicines WHERE medicine_name LIKE ? LIMIT 1");
                        $med_search = '%' . ($item['medicine_name'] ?? '') . '%';
                        $stock_stmt->bind_param('s', $med_search); $stock_stmt->execute();
                        $stock_row = $stock_stmt->get_result()->fetch_assoc();
                        $stock = $stock_row['stock_quantity'] ?? null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['medicine_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['generic_name'] ?? '') ?></td>
                        <td><?= $item['quantity'] ?? 0 ?></td>
                        <td><?= htmlspecialchars($item['sig'] ?? '') ?></td>
                        <td>₱<?= number_format($item['unit_price'] ?? 0, 2) ?></td>
                        <td>₱<?= number_format($item['amount'] ?? 0, 2) ?></td>
                        <td>
                            <?php if ($stock === null): ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php elseif ($stock >= ($item['quantity'] ?? 0)): ?>
                                <span class="badge bg-success"><i class="bi bi-check"></i> In Stock (<?= $stock ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x"></i> Low (<?= $stock ?>)</span>
                            <?php endif; ?>
                        </td>
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

        <div class="card mt-3 border-success">
            <div class="card-header bg-success text-white fw-semibold">
                <i class="bi bi-bag-check"></i> Confirm Dispensing
            </div>
            <div class="card-body">
                <p>Confirm all medicines have been prepared and handed to the customer.</p>
                <form method="POST" action="">
                    <input type="hidden" name="prescription_id" value="<?= $view_rx['id'] ?? 0 ?>">
                    <div class="d-flex gap-2">
                        <button type="submit" name="confirm_dispense" value="1" class="btn btn-success px-4">
                            <i class="bi bi-check-circle"></i> Confirm Dispensed to Customer
                        </button>
                        <a href="dispense_product.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- LIST VIEW -->
    <div class="page-card">
        <h5 class="mb-3">Prescriptions Ready for Dispensing</h5>

        <?php if (empty($ready_prescriptions)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size:3rem;"></i>
                <p class="mt-2">No prescriptions ready for dispensing yet.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Customer</th>
                        <th>Rx Date</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ready_prescriptions as $rx): ?>
                    <tr>
                        <td>#<?= $rx['id'] ?? 0 ?></td>
                        <td><?= htmlspecialchars($rx['patient_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($rx['doctor_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($rx['customer_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($rx['prescription_date'] ?? '') ?></td>
                        <td>₱<?= number_format($rx['total_amount'] ?? 0, 2) ?></td>
                        <td>
                            <a href="?rx_id=<?= $rx['id'] ?? 0 ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-bag-check"></i> Dispense
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Logs -->
    <div class="page-card mt-3">
        <h5 class="mb-3"><i class="bi bi-clock-history"></i> Recent Dispensing Log</h5>
        <?php if (empty($recent_logs)): ?>
            <p class="text-muted text-center">No dispensing records yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Medicine</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Dispensed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?= date('M j, Y H:i', strtotime($log['log_date'] ?? 'now')) ?></td>
                        <td><?= htmlspecialchars($log['patient_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['doctor_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['medicine_name'] ?? '') ?></td>
                        <td><?= $log['quantity'] ?? 0 ?></td>
                        <td>₱<?= number_format($log['total_price'] ?? 0, 2) ?></td>
                        <td><?= htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?></td>
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
</body>
</html>
