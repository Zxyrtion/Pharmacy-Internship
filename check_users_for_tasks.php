<?php
require_once 'config.php';

echo "Checking Users in Database...\n\n";

// Check for interns
$intern_sql = "SELECT u.id, u.first_name, u.last_name, u.email, r.role_name 
               FROM users u 
               JOIN roles r ON u.role_id = r.id 
               WHERE r.role_name = 'Intern'";
$intern_result = $conn->query($intern_sql);

echo "========================================\n";
echo "INTERNS:\n";
echo "========================================\n";
if ($intern_result && $intern_result->num_rows > 0) {
    while ($row = $intern_result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['first_name']} {$row['last_name']} ({$row['email']})\n";
    }
} else {
    echo "No interns found.\n";
}

// Check for HR Personnel
$hr_sql = "SELECT u.id, u.first_name, u.last_name, u.email, r.role_name 
           FROM users u 
           JOIN roles r ON u.role_id = r.id 
           WHERE r.role_name = 'HR Personnel'";
$hr_result = $conn->query($hr_sql);

echo "\n========================================\n";
echo "HR PERSONNEL:\n";
echo "========================================\n";
if ($hr_result && $hr_result->num_rows > 0) {
    while ($row = $hr_result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['first_name']} {$row['last_name']} ({$row['email']})\n";
    }
} else {
    echo "No HR personnel found.\n";
}

// Check all users
$all_sql = "SELECT u.id, u.first_name, u.last_name, u.email, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id";
$all_result = $conn->query($all_sql);

echo "\n========================================\n";
echo "ALL USERS:\n";
echo "========================================\n";
if ($all_result && $all_result->num_rows > 0) {
    while ($row = $all_result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['first_name']} {$row['last_name']} - Role: {$row['role_name']}\n";
    }
} else {
    echo "No users found.\n";
}

$conn->close();
?>
