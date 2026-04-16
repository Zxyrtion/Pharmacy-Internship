<?php
require_once 'config.php';

echo "Fixing reports with empty reporter names...\n\n";

// Find reports with empty reporter
$result = $conn->query("SELECT DISTINCT intern_id FROM inventory_report WHERE reporter = '' OR reporter IS NULL");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $intern_id = $row['intern_id'];
        
        // Get intern's full name
        $user_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
        if ($user_stmt) {
            $user_stmt->bind_param("i", $intern_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            $full_name = $user['full_name'];
            $user_stmt->close();
            
            // Update reports
            $update_stmt = $conn->prepare("UPDATE inventory_report SET reporter = ? WHERE intern_id = ? AND (reporter = '' OR reporter IS NULL)");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $full_name, $intern_id);
                if ($update_stmt->execute()) {
                    $affected = $update_stmt->affected_rows;
                    echo "✓ Updated $affected report(s) for intern ID $intern_id to reporter: $full_name\n";
                }
                $update_stmt->close();
            }
        }
    }
} else {
    echo "No reports with empty reporter names found.\n";
}

echo "\nVerifying fix...\n";
$check = $conn->query("SELECT inventory_period, reporter, COUNT(*) as cnt FROM inventory_report GROUP BY inventory_period, reporter");
while ($row = $check->fetch_assoc()) {
    $reporter_display = empty($row['reporter']) ? '[EMPTY]' : $row['reporter'];
    echo "Period: {$row['inventory_period']} | Reporter: [$reporter_display] | Items: {$row['cnt']}\n";
}

$conn->close();
?>
