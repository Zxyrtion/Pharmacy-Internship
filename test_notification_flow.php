<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "<h2>Test Notification Flow</h2>";

// Step 1: Check if database tables exist
if (isset($conn)) {
    echo "<h3>Step 1: Database Check</h3>";
    
    // Check notifications table
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check && $table_check->num_rows > 0) {
        echo "<div style='color: green;'>Notifications table exists</div>";
    } else {
        echo "<div style='color: red;'>Notifications table missing - creating...</div>";
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
    
    // Check users
    $users = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name, role_name FROM users ORDER BY role_name");
    echo "<h4>Users in system:</h4>";
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
    
    echo "<h3>Step 2: Test Direct Notification Creation</h3>";
    
    // Get a technician user
    $tech_query = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1");
    if ($tech_query && $tech_query->num_rows > 0) {
        $tech = $tech_query->fetch_assoc();
        echo "<div>Testing with technician: " . htmlspecialchars($tech['name']) . " (ID: " . $tech['id'] . ")</div>";
        
        // Test createNotification function directly
        $test_message = "Test notification at " . date('Y-m-d H:i:s');
        $result = createNotification($tech['id'], $test_message, 'info', 'test', 0);
        
        if ($result) {
            echo "<div style='color: green;'>Direct notification creation: SUCCESS</div>";
            
            // Check if it appears
            $count = getUnreadNotificationCount($tech['id']);
            echo "<div>Unread count: $count</div>";
            
            $unread = getUnreadNotifications($tech['id'], 3);
            if (!empty($unread)) {
                echo "<div>Latest notifications:</div>";
                foreach ($unread as $notif) {
                    echo "<div>- " . htmlspecialchars($notif['message']) . "</div>";
                }
            }
        } else {
            echo "<div style='color: red;'>Direct notification creation: FAILED</div>";
        }
    } else {
        echo "<div style='color: red;'>No technicians found</div>";
    }
    
    echo "<h3>Step 3: Simulate Intern Report Submission</h3>";
    
    // Get an intern user
    $intern_query = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_name = 'Intern' LIMIT 1");
    if ($intern_query && $intern_query->num_rows > 0) {
        $intern = $intern_query->fetch_assoc();
        echo "<div>Simulating submission by intern: " . htmlspecialchars($intern['name']) . "</div>";
        
        // Simulate the exact notification code from inventory_report.php
        $inventory_period = "2025-04";
        $reporter = $intern['name'];
        
        // Send notification to all technicians
        $technician_stmt = $conn->prepare("SELECT id FROM users WHERE role_name = 'Pharmacy Technician'");
        if ($technician_stmt) {
            $technician_stmt->execute();
            $technician_result = $technician_stmt->get_result();
            $notification_count = 0;
            while ($technician = $technician_result->fetch_assoc()) {
                $message = "New inventory report for period $inventory_period has been submitted by $reporter and requires your review.";
                $result = createNotification($technician['id'], $message, 'info', 'inventory_report', 0);
                if ($result) {
                    $notification_count++;
                }
            }
            $technician_stmt->close();
            
            echo "<div style='color: green;'>Sent $notification_count notifications to technicians</div>";
            
            // Check the results
            $tech_query = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1");
            if ($tech_query) {
                $tech = $tech_query->fetch_assoc();
                $count = getUnreadNotificationCount($tech['id']);
                echo "<div>Technician unread count after simulation: $count</div>";
                
                $unread = getUnreadNotifications($tech['id'], 5);
                if (!empty($unread)) {
                    echo "<div>Latest notifications for technician:</div>";
                    foreach ($unread as $notif) {
                        echo "<div>- " . htmlspecialchars($notif['message']) . " (" . $notif['created_at'] . ")</div>";
                    }
                }
            }
        } else {
            echo "<div style='color: red;'>Failed to prepare technician query</div>";
        }
    } else {
        echo "<div style='color: red;'>No interns found</div>";
    }
    
    echo "<h3>Step 4: Check All Current Notifications</h3>";
    $all_notifications = $conn->query("SELECT n.*, u.first_name, u.last_name, u.role_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 10");
    if ($all_notifications && $all_notifications->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>User</th><th>Role</th><th>Message</th><th>Type</th><th>Created</th></tr>";
        while ($row = $all_notifications->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['message']) . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: orange;'>No notifications found in database</div>";
    }
    
} else {
    echo "<div style='color: red;'>Database connection failed</div>";
}
?>
