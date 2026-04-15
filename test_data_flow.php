<?php
require_once 'config.php';

echo "<h2>Data Flow Test - Medicine Visibility Across Modules</h2>";

if (!isset($conn)) {
    die("<div style='color: red;'>Database connection failed</div>");
}

echo "<div style='color: green;'>Database connected</div>";

// Step 1: Check all relevant tables
echo "<h3>Step 1: Check Database Tables</h3>";

$tables = ['product_inventory', 'inventory_report', 'requisition_reports', 'requisition_report_items'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "<div style='color: green;'>✓ $table table exists</div>";
    } else {
        echo "<div style='color: red;'>✗ $table table MISSING</div>";
    }
}

// Step 2: Check data in product_inventory
echo "<h3>Step 2: Product Inventory Data</h3>";
$products = $conn->query("SELECT COUNT(*) as count FROM product_inventory");
if ($products) {
    $row = $products->fetch_assoc();
    echo "<div>Total products in product_inventory: " . $row['count'] . "</div>";
}

// Show sample products
$sample = $conn->query("SELECT * FROM product_inventory LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<h4>Sample Products:</h4>";
    echo "<table border='1'><tr><th>ID</th><th>Intern ID</th><th>Product Name</th><th>Description</th><th>Quantity</th><th>Price</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['intern_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange;'>No products found in product_inventory</div>";
}

// Step 3: Check data in inventory_report
echo "<h3>Step 3: Inventory Report Data</h3>";
$reports = $conn->query("SELECT COUNT(*) as count FROM inventory_report");
if ($reports) {
    $row = $reports->fetch_assoc();
    echo "<div>Total items in inventory_report: " . $row['count'] . "</div>";
}

// Show sample inventory reports
$sample = $conn->query("SELECT * FROM inventory_report LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<h4>Sample Inventory Reports:</h4>";
    echo "<table border='1'><tr><th>ID</th><th>Period</th><th>Reporter</th><th>Item</th><th>Reorder Required</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['inventory_period']) . "</td>";
        echo "<td>" . htmlspecialchars($row['reporter']) . "</td>";
        echo "<td>" . htmlspecialchars($row['item_number_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['reorder_required']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange;'>No data found in inventory_report</div>";
}

// Step 4: Check requisition reports
echo "<h3>Step 4: Requisition Reports (POs)</h3>";
$pos = $conn->query("SELECT COUNT(*) as count FROM requisition_reports");
if ($pos) {
    $row = $pos->fetch_assoc();
    echo "<div>Total POs: " . $row['count'] . "</div>";
}

// Step 5: Check requisition items
echo "<h3>Step 5: Requisition Report Items</h3>";
$items = $conn->query("SELECT COUNT(*) as count FROM requisition_report_items");
if ($items) {
    $row = $items->fetch_assoc();
    echo "<div>Total PO items: " . $row['count'] . "</div>";
}

// Step 6: Verify flow connectivity
echo "<h3>Step 6: Data Flow Verification</h3>";

// Check if there are products that would appear in inventory report
$flow_check = $conn->query("
    SELECT pi.*, u.first_name, u.last_name 
    FROM product_inventory pi 
    JOIN users u ON pi.intern_id = u.id 
    LIMIT 3
");

if ($flow_check && $flow_check->num_rows > 0) {
    echo "<div style='color: green;'>✓ Products are linked to users (interns)</div>";
    echo "<h4>Products with Intern Info:</h4>";
    echo "<table border='1'><tr><th>Product</th><th>Intern</th><th>Quantity</th></tr>";
    while ($row = $flow_check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange;'>⚠ No products with user links found</div>";
}

// Check if there are inventory reports that would appear in PO
$po_check = $conn->query("
    SELECT * FROM inventory_report 
    WHERE reorder_required = 'Yes' 
    LIMIT 3
");

if ($po_check && $po_check->num_rows > 0) {
    echo "<div style='color: green;'>✓ Items marked for reorder found (will appear in PO)</div>";
} else {
    echo "<div style='color: orange;'>⚠ No items marked for reorder yet</div>";
}

echo "<h3>Summary</h3>";
echo "<p>If all tables exist and have data, the flow should work:</p>";
echo "<ol>";
echo "<li>Add medicine in Product Inventory → Saves to <code>product_inventory</code></li>";
echo "<li>Submit Inventory Report → Copies to <code>inventory_report</code></li>";
echo "<li>Create PO → Pulls from <code>inventory_report</code> where reorder_required='Yes'</li>";
echo "<li>PO appears in Pharmacist view from <code>requisition_reports</code></li>";
echo "</ol>";

echo "<p><a href='execute_notifications_setup.php'>Run Setup to Create Missing Tables</a></p>";
?>
