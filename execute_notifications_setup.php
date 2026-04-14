<?php
require_once 'config.php';

echo "<h2>Executing Notifications Database Setup</h2>";

if (!isset($conn)) {
    echo "<div style='color: red;'>Database connection failed. Please check config.php</div>";
    exit;
}

echo "<div style='color: green;'>Database connected successfully</div>";

// Step 1: Create notifications table
echo "<h3>Step 1: Creating notifications table...</h3>";

$sql = "CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    `related_type` ENUM('inventory_report', 'purchase_order') NOT NULL,
    `related_id` INT NOT NULL DEFAULT 0,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_notifications` (`user_id`, `is_read`, `created_at`),
    INDEX `idx_related` (`related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "<div style='color: green;'>Notifications table created successfully</div>";
} else {
    echo "<div style='color: red;'>Error creating notifications table: " . $conn->error . "</div>";
}

// Step 2: Add status column to requisition_reports
echo "<h3>Step 2: Adding status column to requisition_reports...</h3>";

$sql = "ALTER TABLE `requisition_reports` 
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'";

if ($conn->query($sql)) {
    echo "<div style='color: green;'>Status column added successfully</div>";
} else {
    echo "<div style='color: orange;'>Status column already exists or error: " . $conn->error . "</div>";
}

// Step 3: Add rejection_reason column to requisition_reports
echo "<h3>Step 3: Adding rejection_reason column to requisition_reports...</h3>";

$sql = "ALTER TABLE `requisition_reports` 
ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT";

if ($conn->query($sql)) {
    echo "<div style='color: green;'>Rejection reason column added successfully</div>";
} else {
    echo "<div style='color: orange;'>Rejection reason column already exists or error: " . $conn->error . "</div>";
}

// Step 4: Add index for performance
echo "<h3>Step 4: Adding performance indexes...</h3>";

$sql = "CREATE INDEX IF NOT EXISTS `idx_requisition_reports_status` ON `requisition_reports` (`status`)";

if ($conn->query($sql)) {
    echo "<div style='color: green;'>Performance index added successfully</div>";
} else {
    echo "<div style='color: orange;'>Index already exists or error: " . $conn->error . "</div>";
}

// Step 5: Verify setup
echo "<h3>Step 5: Verifying setup...</h3>";

// Check notifications table
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<div style='color: green;'>Notifications table exists</div>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE notifications");
    echo "<h4>Notifications table structure:</h4>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: red;'>Notifications table NOT found</div>";
}

// Check requisition_reports columns
$columns = $conn->query("SHOW COLUMNS FROM requisition_reports LIKE 'status'");
if ($columns && $columns->num_rows > 0) {
    echo "<div style='color: green;'>Status column exists in requisition_reports</div>";
} else {
    echo "<div style='color: red;'>Status column NOT found in requisition_reports</div>";
}

$columns = $conn->query("SHOW COLUMNS FROM requisition_reports LIKE 'rejection_reason'");
if ($columns && $columns->num_rows > 0) {
    echo "<div style='color: green;'>Rejection reason column exists in requisition_reports</div>";
} else {
    echo "<div style='color: red;'>Rejection reason column NOT found in requisition_reports</div>";
}

// Step 6: Check users
echo "<h3>Step 6: Checking users...</h3>";

$users = $conn->query("SELECT role_name, COUNT(*) as count FROM users GROUP BY role_name ORDER BY role_name");
if ($users) {
    echo "<h4>Users by role:</h4>";
    echo "<table border='1'><tr><th>Role</th><th>Count</th></tr>";
    while ($row = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 7: Create test notification
echo "<h3>Step 7: Creating test notification...</h3>";

$tech_query = $conn->query("SELECT id FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1");
if ($tech_query && $tech_query->num_rows > 0) {
    $tech = $tech_query->fetch_assoc();
    $tech_id = $tech['id'];
    
    require_once 'notification_helper.php';
    $result = createNotification($tech_id, "Test notification after setup - " . date('H:i:s'), 'success', 'test', 0);
    
    if ($result) {
        echo "<div style='color: green;'>Test notification created successfully</div>";
        
        // Check unread count
        $count = getUnreadNotificationCount($tech_id);
        echo "<div>Unread notifications for technician: $count</div>";
    } else {
        echo "<div style='color: red;'>Failed to create test notification</div>";
    }
} else {
    echo "<div style='color: red;'>No technicians found to test with</div>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test the notification display by visiting the technician dashboard</li>";
echo "<li>Submit an inventory report as an intern</li>";
echo "<li>Check if notifications appear immediately</li>";
echo "</ol>";

echo "<p><a href='simple_notification_test.php'>Run Notification Test</a></p>";
?>
