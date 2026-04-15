<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "<h2>Debug Inventory Report Submission</h2>";

if (isset($conn)) {
    echo "<h3>Step 1: Check Users</h3>";
    
    // Get an intern user
    $intern_query = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_name = 'Intern' LIMIT 1");
    if ($intern_query && $intern_query->num_rows > 0) {
        $intern = $intern_query->fetch_assoc();
        echo "<div>Found intern: " . htmlspecialchars($intern['name']) . " (ID: " . $intern['id'] . ")</div>";
        
        // Simulate the exact submission process
        echo "<h3>Step 2: Simulate Report Submission</h3>";
        
        $user_id = $intern['id'];
        $inventory_period = "2025-04";
        $reporter = $intern['name'];
        $inserted = 1; // Simulate successful insertion
        
        echo "<div>Simulating successful submission...</div>";
        
        if ($inserted > 0) {
            echo "<div>Submission successful - creating notifications...</div>";
            
            // Send notification to all technicians (exact code from inventory_report.php)
            $technician_stmt = $conn->prepare("SELECT id FROM users WHERE role_name = 'Pharmacy Technician'");
            if ($technician_stmt) {
                echo "<div>Technician query prepared successfully</div>";
                
                $technician_stmt->execute();
                $technician_result = $technician_stmt->get_result();
                $notification_count = 0;
                
                while ($technician = $technician_result->fetch_assoc()) {
                    echo "<div>Found technician ID: " . $technician['id'] . "</div>";
                    
                    $message = "New inventory report for period $inventory_period has been submitted by $reporter and requires your review.";
                    echo "<div>Message to send: " . htmlspecialchars($message) . "</div>";
                    
                    $result = createNotification($technician['id'], $message, 'info', 'inventory_report', 0);
                    
                    if ($result) {
                        $notification_count++;
                        echo "<div style='color: green;'>Notification sent successfully to technician " . $technician['id'] . "</div>";
                    } else {
                        echo "<div style='color: red;'>Failed to send notification to technician " . $technician['id'] . "</div>";
                    }
                }
                $technician_stmt->close();
                
                echo "<div>Total notifications sent: $notification_count</div>";
                
                // Verify the notifications were created
                echo "<h3>Step 3: Verify Notifications</h3>";
                
                $tech_query = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1");
                if ($tech_query && $tech_query->num_rows > 0) {
                    $tech = $tech_query->fetch_assoc();
                    echo "<div>Checking notifications for technician: " . htmlspecialchars($tech['name']) . "</div>";
                    
                    $count = getUnreadNotificationCount($tech['id']);
                    echo "<div>Unread count: $count</div>";
                    
                    $unread = getUnreadNotifications($tech['id'], 5);
                    if (!empty($unread)) {
                        echo "<div>Latest notifications:</div>";
                        foreach ($unread as $notif) {
                            echo "<div>- " . htmlspecialchars($notif['message']) . " (" . $notif['created_at'] . ")</div>";
                        }
                    } else {
                        echo "<div style='color: orange;'>No unread notifications found</div>";
                    }
                }
                
            } else {
                echo "<div style='color: red;'>Failed to prepare technician query</div>";
            }
        }
        
    } else {
        echo "<div style='color: red;'>No intern users found</div>";
    }
    
    echo "<h3>Step 4: Check Database State</h3>";
    
    // Show all notifications
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
        echo "<div style='color: orange;'>No notifications in database</div>";
    }
    
} else {
    echo "<div style='color: red;'>Database connection failed</div>";
}
?>
