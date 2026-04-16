<?php
require_once 'config.php';

echo "Checking inventory reports...\n\n";

$result = $conn->query("SELECT inventory_period, reporter, COUNT(*) as cnt FROM inventory_report GROUP BY inventory_period, reporter");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reporter_display = empty($row['reporter']) ? '[EMPTY]' : $row['reporter'];
        echo "Period: {$row['inventory_period']} | Reporter: [$reporter_display] | Items: {$row['cnt']}\n";
    }
}

$conn->close();
?>
