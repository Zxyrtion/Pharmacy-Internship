<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "Creating test notification for technician...\n\n";

$tech_id = 28; // jhon paul Manulat
$message = "TEST: New inventory report for period Q1 2026 has been submitted by Linda Gwapa and requires your review.";

$result = createNotification($tech_id, $message, 'info', 'inventory_report', 0);

if ($result) {
    echo "✓ Notification created successfully!\n\n";
    
    // Verify it was created
    $check = $conn->query("SELECT * FROM notifications WHERE user_id = 28 ORDER BY created_at DESC LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        echo "Verified notification in database:\n";
        echo "ID: {$row['id']}\n";
        echo "Message: {$row['message']}\n";
        echo "Type: {$row['type']}\n";
        echo "Read: " . ($row['is_read'] ? 'Yes' : 'No') . "\n";
        echo "Created: {$row['created_at']}\n";
    }
} else {
    echo "✗ Failed to create notification\n";
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
