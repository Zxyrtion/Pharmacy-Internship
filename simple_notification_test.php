<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "<h2>Simple Notification Test</h2>";

// Test 1: Check database connection
if (!isset($conn)) {
    echo "<div style='color: red;'>Database connection failed</div>";
    exit;
}
echo "<div style='color: green;'>Database connection: OK</div>";

// Test 2: Check notifications table
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<div style='color: green;'>Notifications table: EXISTS</div>";
} else {
    echo "<div style='color: red;'>Notifications table: MISSING</div>";
    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        related_type ENUM('inventory_report', 'purchase_order') NOT NULL,
        related_id INT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql)) {
        echo "<div style='color: green;'>Notifications table created</div>";
    }
}

// Test 3: Check users
$users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_name = 'Pharmacy Technician'");
if ($users) {
    $row = $users->fetch_assoc();
    $tech_count = $row['count'];
    echo "<div style='color: " . ($tech_count > 0 ? 'green' : 'red') . ";'>Technicians found: $tech_count</div>";
}

$users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_name = 'Intern'");
if ($users) {
    $row = $users->fetch_assoc();
    $intern_count = $row['count'];
    echo "<div style='color: " . ($intern_count > 0 ? 'green' : 'red') . ";'>Interns found: $intern_count</div>";
}

// Test 4: Create test notification
$tech_query = $conn->query("SELECT id FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1");
if ($tech_query && $tech_query->num_rows > 0) {
    $tech = $tech_query->fetch_assoc();
    $tech_id = $tech['id'];
    
    $result = createNotification($tech_id, "Test notification at " . date('H:i:s'), 'info', 'test', 0);
    if ($result) {
        echo "<div style='color: green;'>Test notification created: SUCCESS</div>";
        
        // Test 5: Check notification count
        $count = getUnreadNotificationCount($tech_id);
        echo "<div style='color: green;'>Unread count: $count</div>";
        
        // Test 6: Check notification retrieval
        $notifications = getUnreadNotifications($tech_id, 3);
        echo "<div style='color: green;'>Notifications retrieved: " . count($notifications) . "</div>";
        
        if (!empty($notifications)) {
            echo "<div>Latest notification: " . htmlspecialchars($notifications[0]['message']) . "</div>";
        }
    } else {
        echo "<div style='color: red;'>Test notification created: FAILED</div>";
    }
} else {
    echo "<div style='color: red;'>No technicians found for testing</div>";
}

// Test 7: Check all notifications in database
$all_notifications = $conn->query("SELECT COUNT(*) as count FROM notifications");
if ($all_notifications) {
    $row = $all_notifications->fetch_assoc();
    echo "<div style='color: green;'>Total notifications in database: " . $row['count'] . "</div>";
}

echo "<h3>Test Complete</h3>";
echo "<p>If all tests show green, the notification system should work.</p>";
echo "<p>Next step: Test the actual inventory report submission.</p>";
?>
