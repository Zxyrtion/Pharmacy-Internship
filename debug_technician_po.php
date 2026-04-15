<?php
require_once 'config.php';

// Simulate the exact scenario from technician's view_report.php
$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '2025-04';
$reporter = isset($_GET['reporter']) ? sanitizeInput($_GET['reporter']) : 'John Doe';

echo "<h2>Debug Technician PO Button</h2>";
echo "Period: " . htmlspecialchars($period) . "<br>";
echo "Reporter: " . htmlspecialchars($reporter) . "<br>";

// Fetch report data to get status
$status = 'Unknown';
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT status FROM inventory_report WHERE inventory_period = ? AND reporter = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $period, $reporter);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $status = $row['status'];
        }
        $stmt->close();
    }
}

echo "Report Status: " . htmlspecialchars($status) . "<br><br>";

// Check if PO already exists for this inventory report
$existing_po = null;
if (isset($conn) && $status === 'Approved') {
    echo "<h3>Searching for existing PO...</h3>";
    // Search for PO with inventory report reference in comments
    $search_pattern = "Inventory Report: $period by $reporter";
    echo "Search Pattern: " . htmlspecialchars($search_pattern) . "<br>";
    
    $stmt = $conn->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.comments LIKE ?");
    if ($stmt) {
        $search_with_wildcards = "%$search_pattern%";
        echo "SQL LIKE pattern: " . htmlspecialchars($search_with_wildcards) . "<br>";
        
        $stmt->bind_param("s", $search_with_wildcards);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing_po = $res->fetch_assoc();
        $stmt->close();
        
        echo "<h3>Result:</h3>";
        if ($existing_po) {
            echo "<div style='color: green;'>PO FOUND - Should show EDIT button</div>";
            echo "PO Number: " . htmlspecialchars($existing_po['po_number']) . "<br>";
            echo "PO ID: " . htmlspecialchars($existing_po['id']) . "<br>";
            echo "Edit Link: edit_po.php?id=" . htmlspecialchars($existing_po['id']) . "<br>";
        } else {
            echo "<div style='color: blue;'>NO PO FOUND - Should show CREATE button</div>";
        }
    } else {
        echo "<div style='color: red;'>Failed to prepare statement</div>";
    }
}

echo "<h3>Current POs in Database:</h3>";
$all_pos = $conn->query("SELECT id, po_number, comments FROM requisition_reports ORDER BY id DESC LIMIT 10");
if ($all_pos) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>PO Number</th><th>Comments</th></tr>";
    while ($row = $all_pos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['po_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['comments']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Test with actual parameters:</h3>";
echo "<a href='?period=2025-04&reporter=John%20Doe'>Test with 2025-04 by John Doe</a><br>";
echo "<a href='?period=2025-03&reporter=Jane%20Smith'>Test with 2025-03 by Jane Smith</a>";
?>
