<?php
require_once 'config.php';

echo "<h2>Checking Roles Table</h2>";

// Get all roles
$result = $conn->query("SELECT * FROM roles ORDER BY id");

if ($result) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Role Name</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['role_name']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<br><h3>Users with their Roles (using JOIN):</h3>";

// Get users with their role names using JOIN
$result2 = $conn->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.role_id, r.role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY r.role_name, u.first_name
");

if ($result2) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Role ID</th><th>Role Name</th></tr>";
    
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['role_id']}</td>";
        echo "<td><strong>{$row['role_name']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check what role_id 6 is
echo "<br><h3>What is role_id 6 (Jasmine's role)?</h3>";
$role6 = $conn->query("SELECT * FROM roles WHERE id = 6");
if ($role6 && $role6->num_rows > 0) {
    $role = $role6->fetch_assoc();
    echo "Role ID 6 = <strong>{$role['role_name']}</strong>";
} else {
    echo "Role ID 6 not found!";
}
?>
