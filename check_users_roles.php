<?php
require_once 'config.php';

echo "<h2>Checking Users and Their Roles</h2>";

// Get all users with their roles
$result = $conn->query("SELECT id, first_name, last_name, email, role_name FROM users ORDER BY role_name, first_name");

if ($result) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role Name</th></tr>";
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td><strong>{$row['role_name']}</strong></td>";
        echo "</tr>";
        
        if (!in_array($row['role_name'], $roles)) {
            $roles[] = $row['role_name'];
        }
    }
    echo "</table>";
    
    echo "<br><h3>Unique Roles Found:</h3>";
    echo "<ul>";
    foreach ($roles as $role) {
        echo "<li><strong>$role</strong></li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}

// Check Jasmine Duran specifically
echo "<br><h3>Checking Jasmine Duran:</h3>";
$jasmine = $conn->query("SELECT * FROM users WHERE first_name = 'Jasmine' AND last_name = 'Duran'");
if ($jasmine && $jasmine->num_rows > 0) {
    $user = $jasmine->fetch_assoc();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "Jasmine Duran not found!";
}
?>
