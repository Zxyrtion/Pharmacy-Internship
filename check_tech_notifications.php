<?php
require_once 'config.php';

echo "Checking notifications for technician (ID: 28)...\n\n";

$result = $conn->query("SELECT * FROM notifications WHERE user_id = 28 ORDER BY created_at DESC LIMIT 10");

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " notification(s):\n";
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        $read_status = $row['is_read'] ? 'Read' : 'UNREAD';
        echo "ID: {$row['id']} | Type: {$row['type']} | Status: $read_status\n";
        echo "Message: {$row['message']}\n";
        echo "Created: {$row['created_at']}\n\n";
    }
} else {
    echo "No notifications found for technician.\n";
}

$conn->close();
?>
