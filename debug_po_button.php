<?php
require_once 'config.php';

// Test data - replace with actual values from your inventory report
$period = "2025-04";
$reporter = "John Doe";

echo "<h2>Debug PO Button Logic</h2>";

// Check if PO already exists for this inventory report
$existing_po = null;
if (isset($conn)) {
    echo "<h3>Step 1: Search Pattern</h3>";
    $search_pattern = "Inventory Report: $period by $reporter";
    echo "Search Pattern: " . htmlspecialchars($search_pattern) . "<br>";
    
    $search_with_wildcards = "%$search_pattern%";
    echo "With Wildcards: " . htmlspecialchars($search_with_wildcards) . "<br>";
    
    echo "<h3>Step 2: Database Query</h3>";
    $sql = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name FROM requisition_reports r LEFT JOIN users u ON r.technician_id = u.id WHERE r.comments LIKE ?";
    echo "SQL: " . htmlspecialchars($sql) . "<br>";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $search_with_wildcards);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing_po = $res->fetch_assoc();
        $stmt->close();
        
        echo "<h3>Step 3: Results</h3>";
        if ($existing_po) {
            echo "<div style='color: green;'>PO FOUND!</div>";
            echo "<pre>";
            print_r($existing_po);
            echo "</pre>";
        } else {
            echo "<div style='color: red;'>No PO found</div>";
        }
    } else {
        echo "<div style='color: red;'>Failed to prepare statement</div>";
    }
    
    echo "<h3>Step 4: All POs in Database</h3>";
    $all_pos = $conn->query("SELECT id, po_number, comments FROM requisition_reports ORDER BY id DESC LIMIT 5");
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
}
?>
