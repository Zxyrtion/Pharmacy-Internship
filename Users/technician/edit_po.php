<?php
require_once '../../config.php';

if (!isLoggedIn() || $_SESSION['role_name'] !== 'Pharmacy Technician') {
    header('Location: ../views/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$po_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$po_id) {
    header('Location: review_reports.php');
    exit();
}

// Fetch existing PO
$po = null;
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.id = ? AND r.technician_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $po_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $po = $res->fetch_assoc();
        $stmt->close();
    }
}

if (!$po) {
    echo "Purchase Order not found or you don't have permission to edit it.";
    exit();
}

// Fetch existing PO items
$items = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM requisition_report_items WHERE po_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
    }
}

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
        
        // Calculate grand total from components
        $total = $subtotal + $tax + $shipping + $other_costs;
        
        $comments = sanitizeInput($_POST['comments']);

        // Update PO header
        $stmt = $conn->prepare("UPDATE requisition_reports SET po_number = ?, po_date = ?, vendor_company = ?, vendor_contact = ?, vendor_address = ?, vendor_phone = ?, vendor_fax = ?, shipto_name = ?, shipto_company = ?, shipto_address = ?, shipto_phone = ?, requisitioner = ?, ship_via = ?, fob = ?, shipping_terms = ?, subtotal = ?, tax = ?, shipping = ?, other_costs = ?, total = ?, comments = ? WHERE id = ?");
        
        $stmt->bind_param("ssssssssssssssssdddsi", $po_number, $po_date, $vendor_company, $vendor_contact, $vendor_address, $vendor_phone, $vendor_fax, $shipto_name, $shipto_company, $shipto_address, $shipto_phone, $requisitioner, $ship_via, $fob, $shipping_terms, $subtotal, $tax, $shipping, $other_costs, $total, $comments, $po_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Delete existing items
            $stmt = $conn->prepare("DELETE FROM requisition_report_items WHERE po_id = ?");
            $stmt->bind_param("i", $po_id);
            $stmt->execute();
            $stmt->close();
            
            // Insert updated items
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
            $success = "Purchase Order #$po_number updated successfully!";
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to update PO: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Purchase Order #<?= htmlspecialchars($po['po_number']) ?> - MediCare Pharmacy</title>
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
                    <div class="po-title">EDIT PURCHASE ORDER</div>
                    <table class="table-borderless float-end" style="width: 200px; font-size: 0.85rem; border: 1px solid #ccc;">
                        <tr>
                            <td style="text-align: right; padding: 5px; font-weight: bold; background: #f0f0f0;">DATE</td>
                            <td style="padding: 0; border: 1px solid #ccc;"><input type="date" name="po_date" value="<?= htmlspecialchars($po['po_date']) ?>" class="box-input m-0 p-1 border-0 text-center w-100"></td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding: 5px; font-weight: bold; background: #f0f0f0;">PO #</td>
                            <td style="padding: 0; border: 1px solid #ccc;"><input type="text" name="po_number" value="<?= htmlspecialchars($po['po_number']) ?>" class="box-input m-0 p-1 border-0 text-center w-100"></td>
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
                            <option value="MediSupply Corp" data-contact="Sales Dept." data-address="123 Pharma Way, NY 10001" data-phone="(212) 555-0101" data-fax="(212) 555-0102" <?= $po['vendor_company'] === 'MediSupply Corp' ? 'selected' : '' ?>>MediSupply Corp</option>
                            <option value="Global Meds Wholesale" data-contact="John Doe" data-address="456 Health St, CA 90001" data-phone="(310) 555-0202" data-fax="(310) 555-0203" <?= $po['vendor_company'] === 'Global Meds Wholesale' ? 'selected' : '' ?>>Global Meds Wholesale</option>
                            <option value="Prime Care Distribution" data-contact="Jane Smith" data-address="789 Distribution Blvd, TX 75001" data-phone="(214) 555-0303" data-fax="(214) 555-0304" <?= $po['vendor_company'] === 'Prime Care Distribution' ? 'selected' : '' ?>>Prime Care Distribution</option>
                        </select>
                        <input type="text" name="vendor_contact" id="vendor_contact" class="box-input" placeholder="[Contact or Department]" value="<?= htmlspecialchars($po['vendor_contact']) ?>">
                        <input type="text" name="vendor_address" id="vendor_address" class="box-input" placeholder="[Street Address]" value="<?= htmlspecialchars($po['vendor_address']) ?>">
                        <input type="text" name="vendor_phone" id="vendor_phone" class="box-input" placeholder="Phone: (000) 000-0000" value="<?= htmlspecialchars($po['vendor_phone']) ?>">
                        <input type="text" name="vendor_fax" id="vendor_fax" class="box-input" placeholder="Fax: (000) 000-0000" value="<?= htmlspecialchars($po['vendor_fax']) ?>">
                    </div>
                </div>
                <div class="col-2"></div>
                <div class="col-5">
                    <div class="box-header">SHIP TO</div>
                    <div class="box-content">
                        <input type="text" name="shipto_name" class="box-input fw-bold" placeholder="[Name]" value="<?= htmlspecialchars($po['shipto_name']) ?>">
                        <input type="text" name="shipto_company" class="box-input" placeholder="[Company Name]" value="<?= htmlspecialchars($po['shipto_company']) ?>">
                        <input type="text" name="shipto_address" class="box-input" placeholder="[Street Address]" value="<?= htmlspecialchars($po['shipto_address']) ?>">
                        <input type="text" name="shipto_phone" class="box-input" placeholder="[Phone]" value="<?= htmlspecialchars($po['shipto_phone']) ?>">
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
                        <td><input type="text" name="requisitioner" value="<?= htmlspecialchars($po['requisitioner']) ?>"></td>
                        <td>
                            <select name="ship_via" class="box-input m-0 border-0 bg-transparent w-100 text-center">
                                <option value="JNT" <?= $po['ship_via'] === 'JNT' ? 'selected' : '' ?>>JNT</option>
                                <option value="LBC" <?= $po['ship_via'] === 'LBC' ? 'selected' : '' ?>>LBC</option>
                                <option value="DHL" <?= $po['ship_via'] === 'DHL' ? 'selected' : '' ?>>DHL</option>
                                <option value="FedEx" <?= $po['ship_via'] === 'FedEx' ? 'selected' : '' ?>>FedEx</option>
                            </select>
                        </td>
                        <td>
                            <select name="fob" class="box-input m-0 border-0 bg-transparent w-100 text-center">
                                <option value="FOB Origin" <?= $po['fob'] === 'FOB Origin' ? 'selected' : '' ?>>FOB Origin</option>
                                <option value="FOB Destination" <?= $po['fob'] === 'FOB Destination' ? 'selected' : '' ?>>FOB Destination</option>
                            </select>
                        </td>
                        <td>
                            <select name="shipping_terms" class="box-input m-0 border-0 bg-transparent w-100 text-center">
                                <option value="Prepaid" <?= $po['shipping_terms'] === 'Prepaid' ? 'selected' : '' ?>>Prepaid</option>
                                <option value="Collect" <?= $po['shipping_terms'] === 'Collect' ? 'selected' : '' ?>>Collect</option>
                                <option value="Prepaid & Add" <?= $po['shipping_terms'] === 'Prepaid & Add' ? 'selected' : '' ?>>Prepaid & Add</option>
                                <option value="Third-party billing" <?= $po['shipping_terms'] === 'Third-party billing' ? 'selected' : '' ?>>Third-party billing</option>
                                <option value="Freight included" <?= $po['shipping_terms'] === 'Freight included' ? 'selected' : '' ?>>Freight included</option>
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
                        <th width="15%">UNIT PRICE (PHP)</th>
                        <th width="15%">TOTAL</th>
                    </tr>
                </thead>
                <tbody class="striped">
                    <?php if (!empty($items)): ?>
                        <?php foreach($items as $item): ?>
                            <tr>
                                <td><input type="text" name="item_number[]" value="<?= htmlspecialchars($item['item_number']) ?>"></td>
                                <td><input type="text" name="description[]" value="<?= htmlspecialchars($item['description']) ?>"></td>
                                <td><input type="number" name="qty[]" class="qty-calc text-center" value="<?= $item['qty'] ?>"></td>
                                <td><input type="number" step="0.01" name="unit_price[]" class="price-calc text-end" value="<?= $item['unit_price'] ?>"></td>
                                <td><input type="text" name="line_total[]" class="line-total text-end" readonly value="<?= $item['total'] ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Add extra empty rows perfectly up to 10 for visuals -->
                    <?php 
                        $empty_rows = 12 - count($items);
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

            <!-- Totals and Comments -->
            <div class="clearfix mt-4">
                <div class="float-start" style="width: 60%;">
                    <div class="comments-box">
                        <div class="comments-header">COMMENTS / INSTRUCTIONS</div>
                        <div class="comments-content">
                            <textarea name="comments" placeholder="Enter any special instructions or comments..."><?= htmlspecialchars($po['comments'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <table class="totals-table">
                    <tr>
                        <th>SUBTOTAL:</th>
                        <td><input type="text" name="subtotal" id="subtotal" class="text-end" value="<?= htmlspecialchars($po['subtotal']) ?>"></td>
                    </tr>
                    <tr>
                        <th>TAX:</th>
                        <td><input type="text" name="tax" id="tax" class="text-end" value="<?= htmlspecialchars($po['tax']) ?>"></td>
                    </tr>
                    <tr>
                        <th>SHIPPING:</th>
                        <td><input type="text" name="shipping" id="shipping" class="text-end" value="<?= htmlspecialchars($po['shipping']) ?>"></td>
                    </tr>
                    <tr>
                        <th>OTHER COSTS:</th>
                        <td><input type="text" name="other_costs" id="other_costs" class="text-end" value="<?= htmlspecialchars($po['other_costs']) ?>"></td>
                    </tr>
                    <tr class="total-row fs-5">
                        <th>GRAND TOTAL:</th>
                        <td><input type="text" name="grand_total" id="grand_total" class="text-end text-success" readonly value="<?= htmlspecialchars($po['total']) ?>"></td>
                    </tr>
                </table>
            </div>

            <!-- Submit Button -->
            <div class="text-center mt-5">
                <button type="submit" class="btn btn-warning btn-lg px-5">
                    <i class="bi bi-pencil-square"></i> Update Purchase Order
                </button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#itemsTable tbody');
            const subtotalInput = document.getElementById('subtotal');
            const taxInput = document.getElementById('tax');
            const shippingInput = document.getElementById('shipping');
            const otherInput = document.getElementById('other_costs');
            const grandTotalInput = document.getElementById('grand_total');

            function calculateTotals() {
                const rows = tableBody.querySelectorAll('tr');
                let subtotal = 0;

                rows.forEach(row => {
                    const qtyInput = row.querySelector('.qty-calc');
                    const priceInput = row.querySelector('.price-calc');
                    const totalInput = row.querySelector('.line-total');

                    if (qtyInput && priceInput && totalInput) {
                        const qty = parseFloat(qtyInput.value) || 0;
                        const price = parseFloat(priceInput.value) || 0;
                        const total = qty * price;
                        totalInput.value = total.toFixed(2);
                        subtotal += total;
                    }
                });

                subtotalInput.value = subtotal.toFixed(2);

                // Auto-calculate tax (12% VAT) if tax is empty
                if (taxInput.value === '' || parseFloat(taxInput.value) === 0) {
                    const calculatedTax = subtotal * 0.12;
                    taxInput.value = calculatedTax.toFixed(2);
                }
                
                // Auto-calculate shipping (PHP 150 or 5% of subtotal, whichever is higher) if shipping is empty
                if (shippingInput.value === '' || parseFloat(shippingInput.value) === 0) {
                    const flatShipping = 150;
                    const percentShipping = subtotal * 0.05;
                    const calculatedShipping = Math.max(flatShipping, percentShipping);
                    shippingInput.value = calculatedShipping.toFixed(2);
                }
                
                // Auto-calculate other costs (PHP 50 flat fee) if other is empty
                if (otherInput.value === '' || parseFloat(otherInput.value) === 0) {
                    otherInput.value = '50.00';
                }

                // Recalculate with auto-filled values
                const finalTax = parseFloat(taxInput.value) || 0;
                const finalShipping = parseFloat(shippingInput.value) || 0;
                const finalOther = parseFloat(otherInput.value) || 0;

                const grandTotal = subtotal + finalTax + finalShipping + finalOther;
                grandTotalInput.value = grandTotal.toFixed(2);
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

            // Initial calculation
            calculateTotals();

            // Vendor info update function
            function updateVendorInfo() {
                const vendorSelect = document.getElementById('vendor_company');
                const selectedOption = vendorSelect.options[vendorSelect.selectedIndex];
                
                document.getElementById('vendor_contact').value = selectedOption.getAttribute('data-contact') || '';
                document.getElementById('vendor_address').value = selectedOption.getAttribute('data-address') || '';
                document.getElementById('vendor_phone').value = selectedOption.getAttribute('data-phone') || '';
                document.getElementById('vendor_fax').value = selectedOption.getAttribute('data-fax') || '';
            }

            // Make updateVendorInfo globally available
            window.updateVendorInfo = updateVendorInfo;
        });
    </script>
</body>
</html>
