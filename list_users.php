<?php
require_once 'config.php';

$result = $conn->query("SELECT id, first_name, last_name, role_name FROM users ORDER BY role_name, id");

echo "<h2>System Users</h2>";

if (!$result) {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
    exit;
}

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";

while ($user = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
    echo "<td><strong>{$user['role_name']}</strong></td>";
    echo "</tr>";
}

echo "</table>";
?>
