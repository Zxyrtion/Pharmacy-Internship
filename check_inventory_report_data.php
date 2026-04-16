<?php
require_once 'config.php';

echo "Checking inventory_report table data...\n\n";

// Check total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM inventory_report");
if ($count_result) {
    $count = $count_result->fetch_assoc()['total'];
    echo "Total records in inventory_report: $count\n\n";
}

// Show all records
$result = $conn->query("SELECT id, intern_id, inventory_period, reporter, item_number_name, stock_quantity, inventory_value, status, created_at FROM inventory_report ORDER BY created_at DESC LIMIT 10");

if ($result && $result->num_rows > 0) {
    echo "Recent inventory report entries:\n";
    echo str_repeat("-", 120) . "\n";
    printf("%-5s %-10s %-15s %-20s %-25s %-10s %-12s %-10s\n", 
        "ID", "Intern", "Period", "Reporter", "Item", "Qty", "Value", "Status");
    echo str_repeat("-", 120) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-5s %-10s %-15s %-20s %-25s %-10s %-12s %-10s\n", 
            $row['id'],
            $row['intern_id'],
            $row['inventory_period'],
            substr($row['reporter'], 0, 20),
            substr($row['item_number_name'], 0, 25),
            $row['stock_quantity'],
            number_format($row['inventory_value'], 2),
            $row['status']
        );
    }
} else {
    echo "No records found in inventory_report table.\n";
}

// Check grouped data (same as technician query)
echo "\n\nGrouped data (as shown to technician):\n";
echo str_repeat("-", 100) . "\n";
$sql = "SELECT inventory_period, reporter, status, MAX(created_at) as submitted_at, SUM(inventory_value) as total_value, COUNT(id) as item_count FROM inventory_report GROUP BY inventory_period, reporter, status ORDER BY submitted_at DESC";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    printf("%-15s %-20s %-10s %-12s %-15s\n", "Period", "Reporter", "Status", "Total Value", "Item Count");
    echo str_repeat("-", 100) . "\n";
    while ($row = $res->fetch_assoc()) {
        printf("%-15s %-20s %-10s ₱%-11s %-15s\n", 
            $row['inventory_period'],
            substr($row['reporter'], 0, 20),
            $row['status'],
            number_format($row['total_value'], 2),
            $row['item_count']
        );
    }
} else {
    echo "No grouped data found.\n";
}

$conn->close();
?>
