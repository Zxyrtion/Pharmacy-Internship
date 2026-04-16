<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "Testing notification system...\n\n";

// Find all technicians
$technician_stmt = $conn->prepare("SELECT id, first_name, last_name, role_name FROM users WHERE role_name = 'Pharmacy Technician'");
if ($technician_stmt) {
    $technician_stmt->execute();
    $technician_result = $technician_stmt->get_result();
    
    echo "Found technicians:\n";
    echo str_repeat("-", 80) . "\n";
    
    $tech_count = 0;
    while ($technician = $technician_result->fetch_assoc()) {
        $tech_count++;
        echo "ID: " . $technician['id'] . " - " . $technician['first_name'] . " " . $technician['last_name'] . " (" . $technician['role_name'] . ")\n";
        
        // Try to create a test notification
        $message = "TEST: New inventory report for period Q1 2026 has been submitted by Test Intern and requires your review.";
        $result = createNotification($technician['id'], $message, 'info', 'inventory_report', 0);
        
        if ($result) {
            echo "  ✓ Test notification created successfully\n";
        } else {
            echo "  ✗ Failed to create test notification\n";
        }
    }
    $technician_stmt->close();
    
    echo "\nTotal technicians found: $tech_count\n";
} else {
    echo "Error: Failed to prepare statement - " . $conn->error . "\n";
}

// Check notifications table
echo "\n\nRecent notifications:\n";
echo str_repeat("-", 80) . "\n";
$result = $conn->query("SELECT n.id, n.user_id, u.first_name, u.last_name, n.message, n.type, n.is_read, n.created_at 
                        FROM notifications n 
                        LEFT JOIN users u ON n.user_id = u.id 
                        ORDER BY n.created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $read_status = $row['is_read'] ? 'Read' : 'Unread';
        echo "ID: {$row['id']} | User: {$row['first_name']} {$row['last_name']} | Type: {$row['type']} | Status: $read_status\n";
        echo "Message: " . substr($row['message'], 0, 80) . "...\n";
        echo "Created: {$row['created_at']}\n\n";
    }
}

$conn->close();
?>
