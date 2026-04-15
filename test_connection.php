<?php
// Test database connection and users table
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";

// Test basic connection
if ($conn->connect_error) {
    die("<div style='color: red;'>Connection failed: " . $conn->connect_error . "</div>");
} else {
    echo "<div style='color: green;'>✓ Database connection successful!</div>";
}

// Test if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "<div style='color: green;'>✓ Users table exists!</div>";
    
    // Show table structure
    echo "<h3>Users Table Structure:</h3>";
    $result = $conn->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there are any users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "<p><strong>Total users:</strong> " . $row['count'] . "</p>";
    
    // Show roles
    echo "<h3>Available Roles:</h3>";
    $result = $conn->query("SELECT * FROM roles");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Role Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['role_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<div style='color: red;'>✗ Users table does not exist!</div>";
    echo "<p>Please import the SQL file first: <code>internship_system.sql</code></p>";
}

$conn->close();
?>

<p><a href="index.php">← Back to Home</a></p>
