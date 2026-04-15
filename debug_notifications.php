<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "<h2>Notification System Debug</h2>";

// Check if notifications table exists
if (isset($conn)) {
    echo "<h3>1. Database Structure Check</h3>";
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check && $table_check->num_rows > 0) {
        echo "<div style='color: green;'>Notifications table exists</div>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE notifications");
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
        echo "<div style='color: red;'>Notifications table does not exist</div>";
    }
    
    echo "<h3>2. Check Users Table</h3>";
    $users = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name, role_name FROM users ORDER BY role_name, full_name");
    if ($users) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Role</th></tr>";
        while ($row = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>3. Current Notifications</h3>";
    $notifications = $conn->query("SELECT n.*, u.first_name, u.last_name, u.role_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 10");
    if ($notifications && $notifications->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>User</th><th>Role</th><th>Message</th><th>Type</th><th>Created</th><th>Read</th></tr>";
        while ($row = $notifications->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['message']) . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: orange;'>No notifications found</div>";
    }
    
    echo "<h3>4. Test Notification Creation</h3>";
    // Get a test user (first technician)
    $test_user = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1");
    if ($test_user && $test_user->num_rows > 0) {
        $user = $test_user->fetch_assoc();
        echo "<div>Testing with user: " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")</div>";
        
        // Create test notification
        $result = createNotification($user['id'], "Test notification at " . date('Y-m-d H:i:s'), 'info', 'test', 0);
        if ($result) {
            echo "<div style='color: green;'>Test notification created successfully!</div>";
            
            // Check if it appears
            $count = getUnreadNotificationCount($user['id']);
            echo "<div>Unread count for this user: $count</div>";
            
            $unread = getUnreadNotifications($user['id'], 3);
            if (!empty($unread)) {
                echo "<div>Latest notifications:</div>";
                foreach ($unread as $notif) {
                    echo "<div>- " . htmlspecialchars($notif['message']) . " (" . $notif['created_at'] . ")</div>";
                }
            }
        } else {
            echo "<div style='color: red;'>Failed to create test notification</div>";
        }
    } else {
        echo "<div style='color: red;'>No technicians found to test with</div>";
    }
    
    echo "<h3>5. Test Manual Notification</h3>";
    ?>
    <form method="post">
        <div class="mb-3">
            <label for="user_id">Select User:</label>
            <select name="user_id" id="user_id" class="form-control">
                <?php
                $all_users = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name, ' - ', role_name) as display FROM users ORDER BY role_name, first_name");
                if ($all_users) {
                    while ($row = $all_users->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['display']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="message">Message:</label>
            <input type="text" name="message" id="message" class="form-control" value="Test manual notification">
        </div>
        <div class="mb-3">
            <label for="type">Type:</label>
            <select name="type" id="type" class="form-control">
                <option value="info">Info</option>
                <option value="success">Success</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
            </select>
        </div>
        <button type="submit" name="create_test" class="btn btn-primary">Create Test Notification</button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
        $user_id = (int)$_POST['user_id'];
        $message = sanitizeInput($_POST['message']);
        $type = sanitizeInput($_POST['type']);
        
        $result = createNotification($user_id, $message, $type, 'manual', 0);
        if ($result) {
            echo "<div style='color: green; margin-top: 10px;'>Manual notification created successfully!</div>";
        } else {
            echo "<div style='color: red; margin-top: 10px;'>Failed to create manual notification</div>";
        }
    }
    
} else {
    echo "<div style='color: red;'>Database connection failed</div>";
}
?>
