<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_quarter = sanitizeInput($_POST['inventory_quarter']);
    $inventory_year = sanitizeInput($_POST['inventory_year']);
    $inventory_period = $inventory_quarter . ' ' . $inventory_year;
    
    $reporter = $full_name; // Enforce intern's name from active session
    
    // Arrays from tabular form
    $reorders = $_POST['reorder_required'] ?? [];
    $item_numbers = $_POST['item_number_name'] ?? [];
    $manufacturers = $_POST['manufacturer'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    $costs = $_POST['cost_per_item'] ?? [];
    $stocks = $_POST['stock_quantity'] ?? [];
    $reorder_points = $_POST['reorder_point'] ?? [];
    $reorder_cycles = $_POST['reorder_cycle'] ?? [];
    $item_reorder_quantities = $_POST['item_reorder_quantity'] ?? [];
    $item_discontinueds = $_POST['item_discontinued'] ?? [];

    global $conn;
    $stmt = $conn->prepare("INSERT INTO inventory_report (intern_id, inventory_period, reporter, reorder_required, item_number_name, manufacturer, description, cost_per_item, stock_quantity, inventory_value, reorder_point, reorder_cycle, item_reorder_quantity, item_discontinued) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $conn->begin_transaction();
        
        // Wipe existing pending report items for this exact period so it overwrites instead of appending more duplicate rows
        $del_stmt = $conn->prepare("DELETE FROM inventory_report WHERE intern_id = ? AND inventory_period = ? AND status = 'Pending'");
        if ($del_stmt) {
            $del_stmt->bind_param("is", $user_id, $inventory_period);
            $del_stmt->execute();
            $del_stmt->close();
        }

        try {
            $inserted = 0;
            for ($i = 0; $i < count($item_numbers); $i++) {
                if (empty(trim($item_numbers[$i]))) continue; // skip blank rows

                $reorder_req = sanitizeInput($reorders[$i] ?? 'No');
                $item_num = sanitizeInput($item_numbers[$i]);
                $manuf = sanitizeInput($manufacturers[$i]);
                $desc = sanitizeInput($descriptions[$i]);
                $cost = (float)$costs[$i];
                $qty = (int)$stocks[$i];
                $inv_val = $cost * $qty;
                $r_point = (int)$reorder_points[$i];
                $r_cycle = sanitizeInput($reorder_cycles[$i] ?? 'Monthly');
                $item_r_qty = (int)$item_reorder_quantities[$i];
                $discont = sanitizeInput($item_discontinueds[$i] ?? 'No');
                
                $stmt->bind_param("issssssdidisis", $user_id, $inventory_period, $reporter, $reorder_req, $item_num, $manuf, $desc, $cost, $qty, $inv_val, $r_point, $r_cycle, $item_r_qty, $discont);
                if($stmt->execute()){
                    $inserted++;
                }
            }
            $conn->commit();
            if ($inserted > 0) {
                $success = "$inserted items submitted to HR successfully.";
            } else {
                $error = "No valid inventory items were submitted.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Database error: " . $e->getMessage();
        }
        $stmt->close();
    } else {
        $error = "Database error: Failed to prepare statement.";
    }
}

// Fetch existing products to pre-fill report
$existing_products = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM product_inventory WHERE intern_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $existing_products[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .report-header {
            text-align: center;
            margin-bottom: 2rem;
            margin-top: 1rem;
            border-bottom: 2px solid #ddd;
            padding-bottom: 1rem;
        }
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 3rem;
        }
        .table-responsive {
            margin-top: 1.5rem;
        }
        .table th {
            background-color: #e6e6fa; /* Light lavender mimicking the image */
            font-size: 0.85rem;
            text-transform: uppercase;
            vertical-align: middle;
            text-align: center;
        }
        .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }
        .form-control-sm, .form-select-sm {
            font-size: 0.85rem;
            border-radius: 4px;
        }
        .meta-info label {
            font-weight: 600;
            background-color: #ffe4b5;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        .meta-info {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        
        <div class="report-header">
            <h2>Inventory Report</h2>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-card mx-auto" style="max-width: 1400px;">
            <form method="POST" action="">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="meta-info">
                            <label for="inventory_quarter">Inventory Period:</label>
                            <select class="form-select form-select-sm w-auto mx-1" name="inventory_quarter" id="inventory_quarter" required>
                                <option value="Q1">Q1</option>
                                <option value="Q2">Q2</option>
                                <option value="Q3">Q3</option>
                                <option value="Q4">Q4</option>
                            </select>
                            <select class="form-select form-select-sm w-auto" name="inventory_year" required>
                                <?php 
                                   $current_year = date('Y');
                                   for($y = $current_year + 1; $y >= $current_year - 5; $y--) {
                                       $selected = ($y == $current_year) ? 'selected' : '';
                                       echo "<option value=\"$y\" $selected>$y</option>";
                                   }
                                ?>
                            </select>
                        </div>
                        <div class="meta-info">
                            <label for="reporter">Reporter:</label>
                            <input type="text" class="form-control form-control-sm w-auto" id="reporter" name="reporter" value="<?php echo htmlspecialchars($full_name); ?>" required readonly>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="reportTable">
                        <thead>
                            <tr>
                                <th style="min-width: 80px;">Reorder?</th>
                                <th style="min-width: 120px;">Item Number</th>
                                <th style="min-width: 150px;">Manufacturer</th>
                                <th style="min-width: 180px;">Description</th>
                                <th style="min-width: 100px;">Cost Per Item</th>
                                <th style="min-width: 80px;">Stock Qty</th>
                                <th style="min-width: 100px;">Inventory Value</th>
                                <th style="min-width: 80px;">Reorder Point</th>
                                <th style="min-width: 110px;">Reorder Cycle</th>
                                <th style="min-width: 80px;">Item Reorder Qty</th>
                                <th style="min-width: 100px;">Item Discontinued?</th>
                                <th style="min-width: 50px;">🗑️</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($existing_products)):
                                foreach ($existing_products as $prod): 
                            ?>
                            <tr>
                                <td>
                                    <select class="form-select form-select-sm" name="reorder_required[]">
                                        <option value="Yes">Yes</option>
                                        <option value="No" selected>No</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" name="item_number_name[]" value="<?= htmlspecialchars($prod['product_name']) ?>" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="manufacturer[]"></td>
                                <td><input type="text" class="form-control form-control-sm" name="description[]" value="<?= htmlspecialchars($prod['description'] ?? '') ?>"></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control cost-input" name="cost_per_item[]" value="<?= htmlspecialchars($prod['price']) ?>" required>
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm stock-input" name="stock_quantity[]" value="<?= htmlspecialchars($prod['quantity']) ?>" required></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control bg-light value-input" value="<?= number_format($prod['total_price'], 2, '.', '') ?>" readonly>
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm" name="reorder_point[]"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="reorder_cycle[]">
                                        <option value="Monthly">Monthly</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Yearly">Yearly</option>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control form-control-sm" name="item_reorder_quantity[]"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="item_discontinued[]">
                                        <option value="Yes">Yes</option>
                                        <option value="No" selected>No</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row border-0 py-0"><i class="bi bi-x-circle-fill"></i></button>
                                </td>
                            </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                            <tr>
                                <td>
                                    <select class="form-select form-select-sm" name="reorder_required[]">
                                        <option value="Yes">Yes</option>
                                        <option value="No" selected>No</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" name="item_number_name[]" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="manufacturer[]"></td>
                                <td><input type="text" class="form-control form-control-sm" name="description[]"></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control cost-input" name="cost_per_item[]" required>
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm stock-input" name="stock_quantity[]" required></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control bg-light value-input" readonly>
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm" name="reorder_point[]"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="reorder_cycle[]">
                                        <option value="Monthly">Monthly</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Yearly">Yearly</option>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control form-control-sm" name="item_reorder_quantity[]"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="item_discontinued[]">
                                        <option value="Yes">Yes</option>
                                        <option value="No" selected>No</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row border-0 py-0"><i class="bi bi-x-circle-fill"></i></button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-secondary" id="addRowBtn">
                        <i class="bi bi-plus-circle"></i> Add Row
                    </button>
                    <button type="submit" class="btn btn-success px-5">
                        <i class="bi bi-send"></i> Submit Report
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#reportTable tbody');
            const addRowBtn = document.getElementById('addRowBtn');

            function calculateTotals() {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
                    const stock = parseFloat(row.querySelector('.stock-input').value) || 0;
                    row.querySelector('.value-input').value = (cost * stock).toFixed(2);
                });
            }

            // Listen for input changes to calculate Value
            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('cost-input') || e.target.classList.contains('stock-input')) {
                    calculateTotals();
                }
            });

            // Remove Row
            tableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-row');
                if (btn) {
                    const row = btn.closest('tr');
                    if (tableBody.querySelectorAll('tr').length > 1) {
                        row.remove();
                        calculateTotals();
                    } else {
                        alert('You must have at least one reported item.');
                    }
                }
            });

            // Add Row
            addRowBtn.addEventListener('click', function() {
                const firstRow = tableBody.querySelector('tr');
                const newRow = firstRow.cloneNode(true);
                
                // Clear input values
                newRow.querySelectorAll('input').forEach(input => {
                    input.value = '';
                });
                
                // Reset selects to their default options
                newRow.querySelectorAll('select').forEach(select => {
                    // Set to No by default for booleans, Monthly for cycle
                    if(select.name.includes("reorder_required") || select.name.includes("item_discontinued")) {
                        select.value = "No";
                    } else if(select.name.includes("reorder_cycle")) {
                        select.value = "Monthly";
                    }
                });

                tableBody.appendChild(newRow);
            });
        });
    </script>
</body>
</html>
