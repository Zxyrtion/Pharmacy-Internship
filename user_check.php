<?php
require_once 'config.php';

echo "<h2>Quick User Check</h2>";

if (!isset($conn)) {
    die("Database connection failed");
}

// Check all users
$users = $conn->query("SELECT id, first_name, last_name, email, role_name FROM users ORDER BY role_name");
if ($users) {
    echo "<h3>All Users:</h3>";
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    while ($row = $users->fetch_assoc()) {
        $name = $row['first_name'] . ' ' . $row['last_name'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($name) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='background: " . ($row['role_name'] === 'Pharmacy Technician' ? '#90EE90' : 'white') . ";'>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Count by role
echo "<h3>User Count by Role:</h3>";
$roles = $conn->query("SELECT role_name, COUNT(*) as count FROM users GROUP BY role_name");
if ($roles) {
    while ($row = $roles->fetch_assoc()) {
        $color = $row['count'] > 0 ? 'green' : 'red';
        echo "<div style='color: $color;'>" . htmlspecialchars($row['role_name']) . ": " . $row['count'] . "</div>";
    }
}

echo "<h3>Required Roles:</h3>";
echo "<ul>";
echo "<li>Intern - to submit inventory reports</li>";
echo "<li>Pharmacy Technician - to receive and approve reports (HIGHLIGHTED in green above)</li>";
echo "<li>Pharmacist - to receive PO notifications</li>";
echo "</ul>";

echo "<p><strong>If you don't see 'Pharmacy Technician' users above, that's why notifications aren't working.</strong></p>";
?>
