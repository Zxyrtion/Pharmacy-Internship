<?php
// Debug registration connection
require_once 'config.php';

echo "<h1>Registration Debug</h1>";

// Test database connection
echo "<h2>Database Connection Test:</h2>";
if (isset($conn)) {
    echo "<div style='color: green;'>✓ Database connection successful!</div>";
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<div style='color: green;'>✓ Users table exists!</div>";
        
        // Check table structure
        $result = $conn->query("DESCRIBE users");
        echo "<h3>Users Table Structure:</h3>";
        echo "<table border='1'>";
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
        
        // Test a simple insert
        echo "<h3>Test Insert:</h3>";
        $test_username = 'testuser' . time();
        $test_email = 'test' . time() . '@example.com';
        $test_phone = '09' . substr(time(), -9);
        $test_password = password_hash('testpassword123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, first_name, last_name, phone, email, password_hash, role_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $first_name = 'Test';
        $last_name = 'User';
        $role_id = 4;
        
        $stmt->bind_param("ssssssi", $test_username, $first_name, $last_name, $test_phone, $test_email, $test_password, $role_id);
        
        if ($stmt->execute()) {
            echo "<div style='color: green;'>✓ Test insert successful! User ID: " . $stmt->insert_id . "</div>";
            
            // Clean up test record
            $conn->query("DELETE FROM users WHERE email = '$test_email'");
            echo "<div style='color: blue;'>✓ Test record cleaned up</div>";
        } else {
            echo "<div style='color: red;'>✗ Test insert failed: " . $stmt->error . "</div>";
        }
        
    } else {
        echo "<div style='color: red;'>✗ Users table does not exist!</div>";
    }
} else {
    echo "<div style='color: red;'>✗ Database connection failed!</div>";
}

// Check session status
echo "<h2>Session Status:</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div style='color: green;'>✓ Session is active</div>";
} else {
    echo "<div style='color: red;'>✗ Session is not active</div>";
}

// Check if required functions exist
echo "<h2>Required Functions:</h2>";
if (function_exists('isLoggedIn')) {
    echo "<div style='color: green;'>✓ isLoggedIn() function exists</div>";
} else {
    echo "<div style='color: red;'>✗ isLoggedIn() function missing</div>";
}

if (function_exists('getUserRole')) {
    echo "<div style='color: green;'>✓ getUserRole() function exists</div>";
} else {
    echo "<div style='color: red;'>✗ getUserRole() function missing</div>";
}

if (function_exists('redirectByRole')) {
    echo "<div style='color: green;'>✓ redirectByRole() function exists</div>";
} else {
    echo "<div style='color: red;'>✗ redirectByRole() function missing</div>";
}

echo "<p><a href='views/auth/register.php'>Go to Registration Page</a></p>";
?>
