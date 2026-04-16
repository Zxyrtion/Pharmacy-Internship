<?php
require_once 'config.php';

echo "<h2>Task Notification Debug</h2>";

// Check notifications table structure
echo "<h3>1. Notifications Table Structure:</h3>";
$result = $conn->query("DESCRIBE notifications");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

// Check internship_routine table structure
echo "<h3>2. Internship_Routine Table Structure:</h3>";
$result = $conn->query("DESCRIBE internship_routine");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

// Check HR users
echo "<h3>3. HR Personnel Users:</h3>";
$hr_sql = "SELECT u.id, u.first_name, u.last_name, u.email, r.role_name 
           FROM users u 
           JOIN user_roles ur ON u.id = ur.user_id 
           JOIN roles r ON ur.role_id = r.id 
           WHERE r.role_name = 'HR Personnel'";
$result = $conn->query($hr_sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>No HR Personnel found!</p>";
    }
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

// Check recent tasks
echo "<h3>4. Recent Tasks (Last 5):</h3>";
$task_sql = "SELECT id, title, assigned_to, assigned_by_user_id, status, created_at 
             FROM internship_routine 
             ORDER BY created_at DESC 
             LIMIT 5";
$result = $conn->query($task_sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Assigned By</th><th>Status</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_to']) . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_by_user_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>No tasks found!</p>";
    }
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

// Check recent notifications
echo "<h3>5. Recent Notifications (Last 10):</h3>";
$notif_sql = "SELECT n.id, n.user_id, n.type, n.title, n.message, n.is_read, n.created_at,
              u.first_name, u.last_name
              FROM notifications n
              LEFT JOIN users u ON n.user_id = u.id
              ORDER BY n.created_at DESC
              LIMIT 10";
$result = $conn->query($notif_sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . " (ID: " . $row['user_id'] . ")</td>";
            echo "<td>" . htmlspecialchars($row['type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['title'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['message']) . "</td>";
            echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>No notifications found!</p>";
    }
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

// Test notification insert
echo "<h3>6. Test Notification Insert:</h3>";
$test_hr_id = 12; // Joanna's ID based on your screenshot
$test_msg = "TEST: Task completed notification";
$test_title = "Test Notification";
$test_task_id = 1;

// Check if title column exists
$has_title = false;
$result = $conn->query("SHOW COLUMNS FROM notifications LIKE 'title'");
if ($result && $result->num_rows > 0) {
    $has_title = true;
}

if ($has_title) {
    $test_sql = "INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) 
                 VALUES (?, 'task_completed', ?, ?, ?, 0, NOW())";
    $stmt = $conn->prepare($test_sql);
    if ($stmt) {
        $stmt->bind_param("issi", $test_hr_id, $test_title, $test_msg, $test_task_id);
        if ($stmt->execute()) {
            echo "<p style='color:green'>✓ Test notification inserted successfully! (ID: " . $stmt->insert_id . ")</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to insert: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠ 'title' column does not exist in notifications table</p>";
    $test_sql = "INSERT INTO notifications (user_id, message, type, related_type, related_id, is_read, created_at) 
                 VALUES (?, ?, 'info', 'task', ?, 0, NOW())";
    $stmt = $conn->prepare($test_sql);
    if ($stmt) {
        $stmt->bind_param("isi", $test_hr_id, $test_msg, $test_task_id);
        if ($stmt->execute()) {
            echo "<p style='color:green'>✓ Test notification inserted successfully (without title)! (ID: " . $stmt->insert_id . ")</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to insert: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='Users/hr/dashboard.php'>Go to HR Dashboard</a> | <a href='Users/intern/tasks.php'>Go to Intern Tasks</a></p>";
?>
