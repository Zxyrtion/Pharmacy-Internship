<?php
require_once 'config.php';

echo "<h2>Database Debug</h2>";

if (!isset($conn)) {
    die("<div style='color: red;'>Database connection FAILED</div>");
}

echo "<div style='color: green;'>Database connection OK</div>";

// Check if users table exists
echo "<h3>1. Check Users Table</h3>";
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<div style='color: green;'>users table EXISTS</div>";
    
    // Count users
    $count = $conn->query("SELECT COUNT(*) as total FROM users");
    if ($count) {
        $row = $count->fetch_assoc();
        echo "<div>Total users: " . $row['total'] . "</div>";
    }
    
    // Show all users
    $users = $conn->query("SELECT * FROM users LIMIT 10");
    if ($users && $users->num_rows > 0) {
        echo "<h4>All Users:</h4>";
        echo "<table border='1'><tr><th>ID</th><th>First</th><th>Last</th><th>Email</th><th>Role</th></tr>";
        while ($row = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['last_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['role_name'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: red;'>No users found in table</div>";
    }
} else {
    echo "<div style='color: red;'>users table DOES NOT EXIST</div>";
    
    // Show all tables
    echo "<h4>All Tables in Database:</h4>";
    $tables = $conn->query("SHOW TABLES");
    if ($tables) {
        while ($row = $tables->fetch_array()) {
            echo "<div>- " . htmlspecialchars($row[0]) . "</div>";
        }
    }
}

// Check notifications table
echo "<h3>2. Check Notifications Table</h3>";
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<div style='color: green;'>notifications table EXISTS</div>";
} else {
    echo "<div style='color: red;'>notifications table DOES NOT EXIST</div>";
}

echo "<h3>3. Summary</h3>";
echo "<p>If users table doesn't exist, you need to run the database setup script.</p>";
echo "<p><a href='execute_notifications_setup.php'>Run Setup Script</a></p>";
?>
