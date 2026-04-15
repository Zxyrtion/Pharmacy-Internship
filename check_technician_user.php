<?php
require_once 'config.php';

echo "=== Checking Technician User ===\n\n";

$result = $conn->query("SELECT id, first_name, last_name, role_name FROM users WHERE role_name = 'Pharmacy Technician'");

if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        echo "User ID: " . $user['id'] . "\n";
        echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "\n";
        echo "Role: " . $user['role_name'] . "\n\n";
        
        // Check requisitions for this user
        $req_result = $conn->query("SELECT COUNT(*) as count FROM requisitions WHERE pharmacist_id = " . $user['id']);
        $req_count = $req_result->fetch_assoc();
        echo "Requisitions: " . $req_count['count'] . "\n";
    }
} else {
    echo "No Pharmacy Technician found\n";
}

// Check all requisitions
echo "\n=== All Requisitions ===\n";
$result = $conn->query("SELECT requisition_id, pharmacist_id, pharmacist_name, status FROM requisitions ORDER BY created_at DESC LIMIT 5");
while ($req = $result->fetch_assoc()) {
    echo "ID: " . $req['requisition_id'] . " | User ID: " . $req['pharmacist_id'] . " | Name: " . $req['pharmacist_name'] . " | Status: " . $req['status'] . "\n";
}

$conn->close();
?>
