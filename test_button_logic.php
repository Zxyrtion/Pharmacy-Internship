<?php
require_once 'config.php';

// Simulate the exact scenario from view_report.php
$period = "2025-04";
$reporter = "John Doe";
$status = 'Approved';

echo "<h2>Testing Button Logic</h2>";
echo "Period: " . htmlspecialchars($period) . "<br>";
echo "Reporter: " . htmlspecialchars($reporter) . "<br>";
echo "Status: " . htmlspecialchars($status) . "<br><br>";

// Check if PO already exists for this inventory report
$existing_po = null;
if (isset($conn) && $status === 'Approved') {
    // Search for PO with inventory report reference in comments
    $search_pattern = "Inventory Report: $period by $reporter";
    echo "Looking for: " . htmlspecialchars($search_pattern) . "<br>";
    
    $stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.comments LIKE ?");
    if ($stmt) {
        $search_with_wildcards = "%$search_pattern%";
        echo "SQL LIKE pattern: " . htmlspecialchars($search_with_wildcards) . "<br>";
        
        $stmt->bind_param("s", $search_with_wildcards);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing_po = $res->fetch_assoc();
        $stmt->close();
        
        echo "<h3>Button Logic Result:</h3>";
        if ($existing_po) {
            echo "<div style='color: green; font-size: 18px; font-weight: bold;'>SHOW EDIT PO BUTTON</div>";
            echo "PO Number: " . htmlspecialchars($existing_po['po_number']) . "<br>";
            echo "PO ID: " . htmlspecialchars($existing_po['id']) . "<br>";
        } else {
            echo "<div style='color: blue; font-size: 18px; font-weight: bold;'>SHOW CREATE PO BUTTON</div>";
        }
    }
}

echo "<h3>Manual Test - Create Test PO:</h3>";
?>
<form method="post">
    <input type="hidden" name="create_test" value="1">
    <button type="submit">Create Test PO with Reference</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    // Create a test PO with the reference
    $user_id = 1; // Assuming user ID 1 exists
    $po_number = 'TEST-' . date('YmdHis');
    $po_date = date('Y-m-d');
    $test_comments = "Test comment | Inventory Report: $period by $reporter";
    
    $stmt = $conn->prepare("INSERT INTO requisition_reports (technician_id, po_number, po_date, comments, subtotal, tax, shipping, other_costs, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $subtotal = 100;
    $tax = 12;
    $shipping = 150;
    $other_costs = 50;
    $total = 312;
    $stmt->bind_param("issssddddd", $user_id, $po_number, $po_date, $test_comments, $subtotal, $tax, $shipping, $other_costs, $total);
    
    if ($stmt->execute()) {
        echo "<div style='color: green;'>Test PO created successfully! Refresh page to see button change.</div>";
    } else {
        echo "<div style='color: red;'>Failed to create test PO: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
}
?>
