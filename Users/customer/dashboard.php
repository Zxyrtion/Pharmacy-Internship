<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Customer') {
    header('Location: /Pharmacy-Internship/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Fetch real recent prescriptions/orders
$conn->query("CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT, doctor_name VARCHAR(200), doctor_specialization VARCHAR(200),
    doctor_prc VARCHAR(50), doctor_ptr VARCHAR(50), doctor_clinic VARCHAR(300),
    doctor_contact VARCHAR(100), patient_name VARCHAR(200), patient_age VARCHAR(10),
    patient_gender VARCHAR(10), patient_dob DATE NULL, prescription_date DATE,
    next_appointment DATE NULL, notes TEXT NULL, validity_months INT DEFAULT 3,
    status ENUM('Pending','Processing','Ready','Dispensed','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY, prescription_id INT, pharmacist_id INT,
    order_date DATE, total_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('Pending','Dispensed','Paid','Cancelled') DEFAULT 'Pending',
    notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $conn->prepare(
    "SELECT p.id, p.prescription_date, p.patient_name, p.doctor_name, p.status, o.total_amount
     FROM prescriptions p
     LEFT JOIN purchase_orders o ON o.prescription_id = p.id
     WHERE p.customer_id = ?
     ORDER BY p.created_at DESC LIMIT 5"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count ready for payment
$sp = $conn->prepare("SELECT COUNT(*) as cnt FROM prescriptions WHERE customer_id=? AND status='Ready'");
$sp->bind_param('i', $user_id);
$sp->execute();
$ready_count = $sp->get_result()->fetch_assoc()['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .welcome-header {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/Pharmacy-Internship/index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-person"></i> Customer Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Manage your prescriptions and orders.</p>
            </div>
            
            <div class="row">
                <!-- Process 15: Present Doctor's Prescription -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-medical feature-icon"></i>
                        <h4>Present Prescription</h4>
                        <p>Submit your doctor's prescription</p>
                        <a href="prescription_submit.php" class="btn btn-primary">Submit Now</a>
                    </div>
                </div>

                <!-- Process 16: Purchase Order (view order details) -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-cart-check feature-icon"></i>
                        <h4>Purchase Order</h4>
                        <p>View your prescription orders</p>
                        <a href="purchase_order_view.php" class="btn btn-primary">View Orders</a>
                    </div>
                </div>

                <!-- Process 17: Track dispensing status -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-bag-check feature-icon"></i>
                        <h4>Track Dispensing</h4>
                        <p>Check if your medicines are ready</p>
                        <a href="track_dispensing.php" class="btn btn-primary">Track</a>
                    </div>
                </div>

                <!-- Process 18: Pay -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card position-relative">
                        <i class="bi bi-cash-coin feature-icon"></i>
                        <h4>Pay for Medicine</h4>
                        <p>Pay for your dispensed medicines</p>
                        <?php if ($ready_count > 0): ?>
                            <span class="position-absolute top-0 end-0 mt-2 me-2 badge bg-danger"><?= $ready_count ?></span>
                        <?php endif; ?>
                        <a href="my_prescriptions.php" class="btn btn-<?= $ready_count > 0 ? 'success' : 'primary' ?>">
                            <?= $ready_count > 0 ? 'Pay Now' : 'View' ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history"></i> Recent Prescription Orders</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No orders yet. <a href="prescription_submit.php">Submit a prescription</a> to get started.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $ord):
                                $badge = match($ord['status']) {
                                    'Pending'    => 'warning text-dark',
                                    'Processing' => 'info text-dark',
                                    'Ready'      => 'success',
                                    'Dispensed'  => 'secondary',
                                    default      => 'secondary'
                                };
                            ?>
                            <tr>
                                <td>#<?= $ord['id'] ?></td>
                                <td><?= htmlspecialchars($ord['prescription_date']) ?></td>
                                <td><?= htmlspecialchars($ord['patient_name']) ?></td>
                                <td><?= htmlspecialchars($ord['doctor_name']) ?></td>
                                <td><?= $ord['total_amount'] ? '₱' . number_format($ord['total_amount'], 2) : '-' ?></td>
                                <td><span class="badge bg-<?= $badge ?>"><?= $ord['status'] ?></span></td>
                                <td>
                                    <?php if ($ord['status'] === 'Ready'): ?>
                                        <a href="payment.php?rx_id=<?= $ord['id'] ?>" class="btn btn-sm btn-success">Pay</a>
                                    <?php else: ?>
                                        <a href="my_prescriptions.php" class="btn btn-sm btn-info">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
