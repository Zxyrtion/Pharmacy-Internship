<?php
require_once 'config.php';

echo "<h2>Fix Existing Tasks - Backfill assigned_by_user_id</h2>";

// Get the first HR Personnel user ID as default
$hr_sql = "SELECT u.id, u.first_name, u.last_name 
           FROM users u 
           WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'HR Personnel' LIMIT 1)
           LIMIT 1";
$hr_result = $conn->query($hr_sql);

if ($hr_result && $hr_result->num_rows > 0) {
    $hr_row = $hr_result->fetch_assoc();
    $default_hr_id = $hr_row['id'];
    $hr_name = $hr_row['first_name'] . ' ' . $hr_row['last_name'];
    
    echo "<p>Default HR Personnel: <strong>" . htmlspecialchars($hr_name) . "</strong> (ID: $default_hr_id)</p>";
    
    // Update all tasks with NULL assigned_by_user_id
    $update_sql = "UPDATE internship_routine 
                   SET assigned_by_user_id = ? 
                   WHERE assigned_by_user_id IS NULL";
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("i", $default_hr_id);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            echo "<p style='color:green'>✓ Updated $affected tasks with default HR assignee</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to update: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    }
    
    // Show updated tasks
    echo "<h3>Updated Tasks:</h3>";
    $result = $conn->query("SELECT id, title, assigned_to, assigned_by_user_id, status 
                           FROM internship_routine 
                           ORDER BY created_at DESC 
                           LIMIT 10");
    if ($result) {
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Assigned By</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_to']) . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_by_user_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color:red'>✗ No HR Personnel found in database!</p>";
}

echo "<hr>";
echo "<p><a href='Users/hr/dashboard.php'>Go to HR Dashboard</a> | <a href='Users/intern/tasks.php'>Go to Intern Tasks</a></p>";
?>
