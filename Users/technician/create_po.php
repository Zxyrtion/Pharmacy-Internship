<?php
require_once '../../config.php';

if (!isLoggedIn() || $_SESSION['role_name'] !== 'Pharmacy Technician') {
    header('Location: ../views/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '';
$reporter = isset($_GET['reporter']) ? sanitizeInput($_GET['reporter']) : '';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    $conn->begin_transaction();
    try {
        $po_number = sanitizeInput($_POST['po_number']);
        $po_date = sanitizeInput($_POST['po_date']);
        $vendor_company = sanitizeInput($_POST['vendor_company']);
        $vendor_contact = sanitizeInput($_POST['vendor_contact']);
        $vendor_address = sanitizeInput($_POST['vendor_address']);
        $vendor_phone = sanitizeInput($_POST['vendor_phone']);
        $vendor_fax = sanitizeInput($_POST['vendor_fax']);
        
        $shipto_name = sanitizeInput($_POST['shipto_name']);
        $shipto_company = sanitizeInput($_POST['shipto_company']);
        $shipto_address = sanitizeInput($_POST['shipto_address']);
        $shipto_phone = sanitizeInput($_POST['shipto_phone']);
        
        $requisitioner = sanitizeInput($_POST['requisitioner']);
        $ship_via = sanitizeInput($_POST['ship_via']);
        $fob = sanitizeInput($_POST['fob']);
        $shipping_terms = sanitizeInput($_POST['shipping_terms']);
        
        $subtotal = (float)$_POST['subtotal'];
        $tax = (float)$_POST['tax'];
        $shipping = (float)$_POST['shipping'];
        $other_costs = (float)$_POST['other_costs'];
        $total = (float)$_POST['total'];
        $comments = sanitizeInput($_POST['comments']);

        $stmt = $conn->prepare("INSERT INTO requisition_reports (technician_id, po_number, po_date, vendor_company, vendor_contact, vendor_address, vendor_phone, vendor_fax, shipto_name, shipto_company, shipto_address, shipto_phone, requisitioner, ship_via, fob, shipping_terms, subtotal, tax, shipping, other_costs, total, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("issssssssssssssssdddds", $user_id, $po_number, $po_date, $vendor_company, $vendor_contact, $vendor_address, $vendor_phone, $vendor_fax, $shipto_name, $shipto_company, $shipto_address, $shipto_phone, $requisitioner, $ship_via, $fob, $shipping_terms, $subtotal, $tax, $shipping, $other_costs, $total, $comments);
        
        if ($stmt->execute()) {
            $po_id = $stmt->insert_id;
            $stmt->close();
            
            $item_numbers = $_POST['item_number'] ?? [];
            $descriptions = $_POST['description'] ?? [];
            $qtys = $_POST['qty'] ?? [];
            $unit_prices = $_POST['unit_price'] ?? [];
            $line_totals = $_POST['line_total'] ?? [];
            
            if (!empty($item_numbers)) {
                $item_stmt = $conn->prepare("INSERT INTO requisition_report_items (po_id, item_number, description, qty, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                for ($i=0; $i < count($item_numbers); $i++) {
                    if (empty(trim($item_numbers[$i]))) continue;
                    
                    $i_num = sanitizeInput($item_numbers[$i]);
                    $desc = sanitizeInput($descriptions[$i]);
                    $qty = (int)$qtys[$i];
                    $u_price = (float)$unit_prices[$i];
                    $l_total = (float)$line_totals[$i];
                    
                    $item_stmt->bind_param("issidd", $po_id, $i_num, $desc, $qty, $u_price, $l_total);
                    $item_stmt->execute();
                }
                $item_stmt->close();
            }
            $conn->commit();
            $success = "Purchase Order #$po_number created and sent to Pharmacist successfully!";
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to create PO: " . $e->getMessage();
    }
}

// Fetch items that require reordering
$items_to_order = [];
if (isset($conn) && $period && $reporter) {
    $sql = "SELECT item_number_name, description, item_reorder_quantity, cost_per_item FROM inventory_report WHERE inventory_period = ? AND reporter = ? AND reorder_required = 'Yes'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $period, $reporter);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $items_to_order[] = $row;
        }
        $stmt->close();
    }
}

$auto_po = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Arial', sans-serif; }
        .po-container { 
            max-width: 1000px; 
            margin: 2rem auto; 
            background: white; 
            padding: 3rem; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        .po-title { color: #5b9bd5; font-size: 2.5rem; text-align: right; text-transform: uppercase; letter-spacing: 2px; }
        .company-name { font-size: 1.5rem; font-weight: bold; margin-bottom: 0px; }
        .info-panel { font-size: 0.85rem; line-height: 1.4; color: #333; }
        .box-header { background-color: #3b5998; color: white; padding: 5px 10px; font-weight: bold; font-size: 0.85rem; }
        .box-content { padding: 10px; border: 1px solid #dee2e6; border-top: none; min-height: 120px; font-size: 0.85rem; }
        .box-input { width: 100%; border: none; border-bottom: 1px dashed #ccc; outline: none; margin-bottom: 3px; font-size: 0.85rem; background: transparent; }
        
        .req-table { width: 100%; margin-top: 1.5rem; border: 1px solid #3b5998; }
        .req-table th { background-color: #3b5998; color: white; font-weight: bold; font-size: 0.85rem; padding: 5px; text-align: center; border-right: 1px solid white; }
        .req-table th:last-child { border-right: none; }
        .req-table td { border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; padding: 5px; }
        .req-table td:last-child { border-right: none; }
        .req-table input { width: 100%; border: none; background: transparent; outline: none; font-size: 0.85rem; }
        
        .items-table { width: 100%; margin-top: 1rem; border-collapse: collapse; border-bottom: 1px solid #dee2e6; }
        .items-table th { background-color: #3b5998; color: white; font-size: 0.85rem; padding: 8px; text-align: center; border-right: 1px solid white; }
        .items-table td { border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; padding: 2px; }
        .items-table input { width: 100%; border: none; outline: none; padding: 5px; font-size: 0.85rem; }
        .items-table .striped tr:nth-child(even) { background-color: #f9f9f9; }
        
        .totals-table { width: 250px; float: right; margin-top: 1rem; font-size: 0.85rem; }
        .totals-table th { text-align: right; padding: 5px 10px; font-weight: bold; }
        .totals-table td { border: 1px solid #dee2e6; }
        .totals-table input { width: 100%; border: none; outline: none; padding: 5px; background: transparent; text-align: right; }
        .total-row th, .total-row td { background-color: #a4b4da; font-weight: bold; border: 1px solid #3b5998; }
        
        .comments-box { margin-top: 1rem; width: 60%; float: left; }
        .comments-header { background-color: #d9d9d9; font-weight: bold; font-size: 0.85rem; padding: 5px 10px; border: 1px solid #aaa; }
        .comments-content { border: 1px solid #aaa; border-top: none; height: 100px; }
        .comments-content textarea { width: 100%; height: 100%; border: none; outline: none; resize: none; padding: 10px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="review_reports.php">
                <i class="bi bi-arrow-left"></i> Back to Reports
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?> (Technician)
                </span>
            </div>
        </div>
    </nav>

    <div class="container my-3">
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </div>

    <form method="POST" action="">
        <div class="po-container">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-6 info-panel">
                    <div class="company-name">[MediCare Pharmacy]</div>
                    <div>[123 Health Ave]</div>
                    <div>[Medical City, MC 12345]</div>
                    <div>Phone: (555) 123-4567</div>
                    <div>Fax: (555) 123-4568</div>
                    <div>Website: medicarepharm.com</div>
                </div>
                <div class="col-6">
                    <div class="po-title">PURCHASE ORDER</div>
                    <table class="table-borderless float-end" style="width: 200px; font-size: 0.85rem; border: 1px solid #ccc;">
                        <tr>
                            <td style="text-align: right; padding: 5px; font-weight: bold; background: #f0f0f0;">DATE</td>
                            <td style="padding: 0; border: 1px solid #ccc;"><input type="date" name="po_date" value="<?= date('Y-m-d') ?>" class="box-input m-0 p-1 border-0 text-center w-100"></td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding: 5px; font-weight: bold; background: #f0f0f0;">PO #</td>
                            <td style="padding: 0; border: 1px solid #ccc;"><input type="text" name="po_number" value="<?= $auto_po ?>" class="box-input m-0 p-1 border-0 text-center w-100" readonly></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Addresses Section -->
            <div class="row mb-3">
                <div class="col-5">
                    <div class="box-header">VENDOR</div>
                    <div class="box-content">
                        <select name="vendor_company" id="vendor_company" class="box-input fw-bold" style="cursor:pointer;" onchange="updateVendorInfo()">
                            <option value="">[Select a Vendor / Supplier]</option>
                            <option value="MediSupply Corp" data-contact="Sales Dept." data-address="123 Pharma Way, NY 10001" data-phone="(212) 555-0101" data-fax="(212) 555-0102">MediSupply Corp</option>
                            <option value="Global Meds Wholesale" data-contact="John Doe" data-address="456 Health St, CA 90001" data-phone="(310) 555-0202" data-fax="(310) 555-0203">Global Meds Wholesale</option>
                            <option value="Prime Care Distribution" data-contact="Jane Smith" data-address="789 Distribution Blvd, TX 75001" data-phone="(214) 555-0303" data-fax="(214) 555-0304">Prime Care Distribution</option>
                        </select>
                        <input type="text" name="vendor_contact" id="vendor_contact" class="box-input" placeholder="[Contact or Department]">
                        <input type="text" name="vendor_address" id="vendor_address" class="box-input" placeholder="[Street Address]">
                        <input type="text" name="vendor_phone" id="vendor_phone" class="box-input" placeholder="Phone: (000) 000-0000">
                        <input type="text" name="vendor_fax" id="vendor_fax" class="box-input" placeholder="Fax: (000) 000-0000">
                    </div>
                </div>
                <div class="col-2"></div>
                <div class="col-5">
                    <div class="box-header">SHIP TO</div>
                    <div class="box-content">
                        <input type="text" name="shipto_name" class="box-input fw-bold" placeholder="[Name]" value="<?= htmlspecialchars($full_name) ?>">
                        <input type="text" name="shipto_company" class="box-input" placeholder="[Company Name]" value="MediCare Pharmacy Tech">
                        <input type="text" name="shipto_address" class="box-input" placeholder="[Street Address]">
                        <input type="text" name="shipto_phone" class="box-input" placeholder="[Phone]">
                    </div>
                </div>
            </div>

            <!-- Requisitioner Section -->
            <table class="req-table">
                <thead>
                    <tr>
                        <th width="25%">REQUISITIONER</th>
                        <th width="25%">SHIP VIA</th>
                        <th width="25%">F.O.B.</th>
                        <th width="25%">SHIPPING TERMS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="requisitioner" value="<?= htmlspecialchars($full_name) ?>"></td>
                        <td>
                            <select name="ship_via" class="box-input m-0 border-0 bg-transparent w-100 text-center">
                                <option value="JNT">JNT</option>
                                <option value="LBC">LBC</option>
                                <option value="DHL">DHL</option>
                                <option value="FedEx">FedEx</option>
                            </select>
                        </td>
                        <td>
                            <select name="fob" class="box-input m-0 border-0 bg-transparent w-100 text-center">
                                <option value="FOB Origin">FOB Origin</option>
                                <option value="FOB Destination">FOB Destination</option>
                            </select>
                        </td>
                        <td>
                            <select name="shipping_terms" class="box-input m-0 border-0 bg-transparent w-100 text-center">
                                <option value="Prepaid">Prepaid</option>
                                <option value="Collect">Collect</option>
                                <option value="Prepaid & Add">Prepaid & Add</option>
                                <option value="Third-party billing">Third-party billing</option>
                                <option value="Freight included">Freight included</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Items Section -->
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th width="15%">ITEM #</th>
                        <th width="45%">DESCRIPTION</th>
                        <th width="10%">QTY</th>
                        <th width="15%">UNIT PRICE</th>
                        <th width="15%">TOTAL</th>
                    </tr>
                </thead>
                <tbody class="striped">
                    <?php if (!empty($items_to_order)): ?>
                        <?php foreach($items_to_order as $item): ?>
                            <tr>
                                <td><input type="text" name="item_number[]" value="<?= htmlspecialchars($item['item_number_name']) ?>"></td>
                                <td><input type="text" name="description[]" value="<?= htmlspecialchars($item['description']) ?>"></td>
                                <td><input type="number" name="qty[]" class="qty-calc text-center" value="<?= $item['item_reorder_quantity'] ?>"></td>
                                <td><input type="number" step="0.01" name="unit_price[]" class="price-calc text-end" value="<?= $item['cost_per_item'] ?>"></td>
                                <td><input type="text" name="line_total[]" class="line-total text-end" readonly></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Add extra empty rows perfectly up to 10 for visuals -->
                    <?php 
                        $empty_rows = 12 - count($items_to_order);
                        for($i=0; $i<$empty_rows; $i++):
                    ?>
                    <tr>
                        <td><input type="text" name="item_number[]"></td>
                        <td><input type="text" name="description[]"></td>
                        <td><input type="number" name="qty[]" class="qty-calc text-center"></td>
                        <td><input type="number" step="0.01" name="unit_price[]" class="price-calc text-end"></td>
                        <td><input type="text" name="line_total[]" class="line-total text-end" readonly></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <!-- Bottom Section -->
            <div class="clearfix mt-2">
                <div class="comments-box">
                    <div class="comments-header">Comments or Special Instructions</div>
                    <div class="comments-content">
                        <textarea name="comments" placeholder="Add comments here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4 px-4 w-100">
                        <i class="bi bi-send-fill"></i> Send Purchase Order to Pharmacist
                    </button>
                    <a href="review_reports.php" class="btn btn-outline-secondary mt-2 w-100">Cancel</a>
                </div>
                
                <table class="totals-table">
                    <tr>
                        <th>SUBTOTAL</th>
                        <td><input type="text" name="subtotal" id="subtotal" readonly></td>
                    </tr>
                    <tr>
                        <th>TAX</th>
                        <td><input type="number" step="0.01" name="tax" id="tax" class="totals-calc"></td>
                    </tr>
                    <tr>
                        <th>SHIPPING</th>
                        <td><input type="number" step="0.01" name="shipping" id="shipping" class="totals-calc"></td>
                    </tr>
                    <tr>
                        <th>OTHER</th>
                        <td><input type="number" step="0.01" name="other_costs" id="other_costs" class="totals-calc"></td>
                    </tr>
                    <tr class="total-row">
                        <th>TOTAL</th>
                        <td><input type="text" name="total" id="grand_total" readonly></td>
                    </tr>
                </table>
            </div>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#itemsTable tbody');
            const subtotalInput = document.getElementById('subtotal');
            const taxInput = document.getElementById('tax');
            const shippingInput = document.getElementById('shipping');
            const otherInput = document.getElementById('other_costs');
            const grandTotalInput = document.getElementById('grand_total');

            function calculateTotals() {
                let subtotal = 0;
                
                // Lines
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const qty = parseFloat(row.querySelector('.qty-calc').value) || 0;
                    const price = parseFloat(row.querySelector('.price-calc').value) || 0;
                    const total = qty * price;
                    
                    const totalField = row.querySelector('.line-total');
                    if(qty > 0 || price > 0) {
                        totalField.value = total.toFixed(2);
                        subtotal += total;
                    } else {
                        totalField.value = '';
                    }
                });

                subtotalInput.value = subtotal.toFixed(2);

                // Summary
                const tax = parseFloat(taxInput.value) || 0;
                const shipping = parseFloat(shippingInput.value) || 0;
                const other = parseFloat(otherInput.value) || 0;

                const grandTotal = subtotal + tax + shipping + other;
                grandTotalInput.value = '$   ' + grandTotal.toFixed(2);
            }

            // Listeners
            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('qty-calc') || e.target.classList.contains('price-calc')) {
                    calculateTotals();
                }
            });

            [taxInput, shippingInput, otherInput].forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Initial calc
            calculateTotals();
        });

        function updateVendorInfo() {
            const select = document.getElementById('vendor_company');
            const option = select.options[select.selectedIndex];
            
            document.getElementById('vendor_contact').value = option.dataset.contact || '';
            document.getElementById('vendor_address').value = option.dataset.address || '';
            document.getElementById('vendor_phone').value = option.dataset.phone || '';
            document.getElementById('vendor_fax').value = option.dataset.fax || '';
        }
    </script>
</body>
</html>
