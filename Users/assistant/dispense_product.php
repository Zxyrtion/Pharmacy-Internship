<?php
require_once '../../config.php';
require_once '../../models/prescription.php';
require_once '../../models/product_log.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') {
    header('Location: ../../index.php');
    exit();
}

$prescription = new Prescription($conn);
$product_log = new ProductLog($conn);
$success_message = '';
$error_message = '';

// Create product logs table if not exists
$product_log->createProductLogsTable();

// Handle form submission - Dispense product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'dispense') {
        $order_id = $_POST['order_id'];
        $prescription_id = $_POST['prescription_id'];
        $medicine_id = $_POST['medicine_id'];
        $medicine_name = $_POST['medicine_name'];
        $dosage = $_POST['dosage'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        $patient_id = $_POST['patient_id'];
        $patient_name = $_POST['patient_name'];
        $notes = $_POST['notes'] ?? '';
        $pharmacist_id = $_SESSION['user_id'];
        
        // Calculate total price
        $total_price = $quantity * $unit_price;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Log the dispensing
            $product_log->logProductDispense($order_id, $prescription_id, $medicine_id, $medicine_name, $dosage, $quantity, $unit_price, $total_price, $pharmacist_id, $patient_id, $patient_name);
            
            // Update order status
            $order_sql = "UPDATE orders SET status = 'Completed', completed_date = NOW() WHERE id = ?";
            $stmt = $conn->prepare($order_sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // Update prescription status to Dispensed
            $prescription->updateStatus($_POST['prescription_id'], 'Dispensed');
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Product dispensed successfully! " . htmlspecialchars($medicine_name) . " (" . htmlspecialchars($dosage) . ") x" . $quantity . " to " . htmlspecialchars($patient_name);
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to dispense product: " . $e->getMessage();
        }
    }
}

// Get order details if order_id is provided
$order_details = null;
$order_items = [];
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    $sql = "SELECT o.*, u.first_name, u.last_name, p.medicine_name, p.dosage, p.quantity, p.patient_id, p.patient_name FROM orders o 
            LEFT JOIN users u ON o.pharmacist_id = u.id
            LEFT JOIN prescriptions p ON o.prescription_id = p.id
            WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_details = $result->fetch_assoc();
    
    // Get order items
    $items_sql = "SELECT oi.*, m.stock_quantity FROM order_items oi 
                  LEFT JOIN medicines m ON m.medicine_name = oi.medicine_name AND m.dosage = oi.dosage
                  WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_items = $result->fetch_all(MYSQLI_ASSOC);
}

// Get dispensing logs for today
$today_logs = $product_log->getDailyDispensingReport();
$recent_dispensing = $product_log->getAllProductLogs();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispense Product - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .dispense-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .dispense-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .order-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dispense-form {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .btn-dispense {
            background: #4caf50;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-dispense:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .dispensing-log {
            max-height: 400px;
            overflow-y: auto;
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

    <div class="dispense-container">
        <div class="container">
            <div class="dispense-card">
                <h2><i class="bi bi-box-seam"></i> Dispense Product - Process 17</h2>
                <p class="text-muted">Check product availability and record dispensing of medications to customers</p>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Today's Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $today_logs['total_prescriptions'] ?? 0; ?></div>
                            <div class="stats-label">Prescriptions Filled Today</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $today_logs['total_items'] ?? 0; ?></div>
                            <div class="stats-label">Items Dispensed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $today_logs['unique_patients'] ?? 0; ?></div>
                            <div class="stats-label">Patients Served</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number">₱<?php echo number_format($today_logs['total_revenue'] ?? 0, 2); ?></div>
                            <div class="stats-label">Today's Revenue</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($order_details): ?>
                    <!-- Order Information -->
                    <div class="order-details">
                        <h4><i class="bi bi-file-earmark-text"></i> Order Details</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['order_id']); ?></p>
                                <p><strong>Prescription ID:</strong> <?php echo isset($order_details['prescription_id']) ? htmlspecialchars($order_details['prescription_id']) : 'N/A'; ?></p>
                                <p><strong>Patient:</strong> <?php echo htmlspecialchars($order_details['patient_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Order Status:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($order_details['status']); ?></span></p>
                                <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order_details['order_date']); ?></p>
                                <p><strong>Total Amount:</strong> ₱<?php echo number_format($order_details['total_amount'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dispense Form -->
                    <div class="dispense-form">
                        <h4><i class="bi bi-download"></i> Dispense Medication</h4>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Requested Qty</th>
                                        <th>Available Stock</th>
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td>
                                                <?php
                                                if ($item['stock_quantity'] >= $item['quantity']) {
                                                    echo '<span class="badge bg-success">' . htmlspecialchars($item['stock_quantity']) . '</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">Only ' . htmlspecialchars($item['stock_quantity']) . ' available</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($order_items): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="dispense">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_details['id']); ?>">
                                <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($order_details['prescription_id'] ?? ''); ?>">
                                <input type="hidden" name="medicine_id" value="<?php echo htmlspecialchars($order_items[0]['id'] ?? ''); ?>">
                                <input type="hidden" name="medicine_name" value="<?php echo htmlspecialchars($order_items[0]['medicine_name'] ?? ''); ?>">
                                <input type="hidden" name="dosage" value="<?php echo htmlspecialchars($order_items[0]['dosage'] ?? ''); ?>">
                                <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($order_items[0]['quantity'] ?? ''); ?>">
                                <input type="hidden" name="unit_price" value="<?php echo htmlspecialchars($order_items[0]['unit_price'] ?? ''); ?>">
                                <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($order_details['patient_id'] ?? ''); ?>">
                                <input type="hidden" name="patient_name" value="<?php echo htmlspecialchars($order_details['patient_name'] ?? ''); ?>">
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Dispensing Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="e.g., Instructions given to patient, side effects discussed..."></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                    <button type="submit" class="btn btn-dispense">
                                        <i class="bi bi-check-circle"></i> Confirm Dispensing
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No order selected. Please select an order from the dashboard to dispense products.
                    </div>
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Dispensing Log -->
            <div class="dispense-card">
                <h3><i class="bi bi-clock-history"></i> Recent Dispensing Log</h3>
                <div class="dispensing-log">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Order ID</th>
                                    <th>Patient</th>
                                    <th>Medicine</th>
                                    <th>Qty</th>
                                    <th>Total Price</th>
                                    <th>Pharmacist</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_dispensing)): ?>
                                    <?php foreach (array_slice($recent_dispensing, 0, 20) as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($log['log_date']))); ?></td>
                                            <td><?php echo htmlspecialchars($log['order_id']); ?></td>
                                            <td><?php echo htmlspecialchars($log['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($log['medicine_name'] . ' ' . $log['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($log['quantity_dispensed']); ?></td>
                                            <td>₱<?php echo number_format($log['total_price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No dispensing records yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh dispensing log every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
