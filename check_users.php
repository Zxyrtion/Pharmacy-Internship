<?php
require_once 'config.php';

echo "<h2>User System Diagnostic</h2>";

if (!isset($conn)) {
    echo "<div style='color: red;'>Database connection failed</div>";
    exit;
}

echo "<div style='color: green;'>Database connected</div>";

// Check users table structure
echo "<h3>1. Users Table Structure</h3>";
$columns = $conn->query("SHOW COLUMNS FROM users");
if ($columns) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check all users
echo "<h3>2. All Users in System</h3>";
$users = $conn->query("SELECT id, first_name, last_name, email, role_name FROM users ORDER BY role_name, last_name");
if ($users && $users->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Role</th></tr>";
    while ($row = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='color: " . ($row['role_name'] === 'Pharmacy Technician' ? 'green' : 'black') . "; font-weight: bold;'>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: red;'>No users found in database!</div>";
}

// Count by role
echo "<h3>3. User Count by Role</h3>";
$roles = $conn->query("SELECT role_name, COUNT(*) as count FROM users GROUP BY role_name ORDER BY role_name");
if ($roles && $roles->num_rows > 0) {
    echo "<table border='1'><tr><th>Role</th><th>Count</th><th>Status</th></tr>";
    while ($row = $roles->fetch_assoc()) {
        $status = $row['count'] > 0 ? '<span style="color: green;">✓ OK</span>' : '<span style="color: red;">✗ Missing</span>';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check if notification can be created for existing user
echo "<h3>4. Test Notification Creation</h3>";
$any_user = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, role_name FROM users LIMIT 1");
if ($any_user && $any_user->num_rows > 0) {
    $user = $any_user->fetch_assoc();
    
    require_once 'notification_helper.php';
    
    echo "<div>Testing with user: " . htmlspecialchars($user['name']) . " (Role: " . htmlspecialchars($user['role_name']) . ", ID: " . $user['id'] . ")</div>";
    
    $result = createNotification($user['id'], "Test notification at " . date('H:i:s'), 'info', 'test', 0);
    
    if ($result) {
        echo "<div style='color: green;'>Notification created successfully!</div>";
        
        $count = getUnreadNotificationCount($user['id']);
        echo "<div>Unread notifications: $count</div>";
        
        // Show all notifications for this user
        $notifications = getUnreadNotifications($user['id'], 5);
        if (!empty($notifications)) {
            echo "<div>Latest notifications:</div>";
            foreach ($notifications as $notif) {
                echo "<div>- " . htmlspecialchars($notif['message']) . "</div>";
            }
        }
    } else {
        echo "<div style='color: red;'>Failed to create notification</div>";
    }
} else {
    echo "<div style='color: red;'>No users available for testing</div>";
}

echo "<h3>5. Required Users for Notification Flow</h3>";
echo "<p>For the notification system to work properly, you need at least:</p>";
echo "<ul>";
echo "<li>1 <strong>Intern</strong> - to submit inventory reports</li>";
echo "<li>1 <strong>Pharmacy Technician</strong> - to receive reports and approve them</li>";
echo "<li>1 <strong>Pharmacist</strong> - to receive PO notifications</li>";
echo "</ul>";

// Check notifications table
echo "<h3>6. Notifications Table Contents</h3>";
$notifs = $conn->query("SELECT n.*, u.first_name, u.last_name, u.role_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 10");
if ($notifs && $notifs->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>User</th><th>Role</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th></tr>";
    while ($row = $notifs->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange;'>No notifications in database</div>";
}
?>
