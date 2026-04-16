<?php
require_once 'config.php';
require_once 'notification_helper.php';

echo "<h2>Creating Missing Notifications for Existing Requisitions</h2>";

// Get all pharmacists and pharmacy assistants using JOIN with roles table
$recipients = [];

// Get pharmacists
$pharmacist_stmt = $conn->prepare("
    SELECT u.id, u.first_name, u.last_name, r.role_name 
    FROM users u 
    INNER JOIN roles r ON u.role_id = r.id 
    WHERE r.role_name = 'Pharmacist'
");
if ($pharmacist_stmt) {
    $pharmacist_stmt->execute();
    $pharmacist_result = $pharmacist_stmt->get_result();
    while ($user = $pharmacist_result->fetch_assoc()) {
        $recipients[] = $user;
        echo "Found {$user['role_name']}: {$user['first_name']} {$user['last_name']} (ID: {$user['id']})<br>";
    }
    $pharmacist_stmt->close();
}

// Get pharmacy assistants
$assistant_stmt = $conn->prepare("
    SELECT u.id, u.first_name, u.last_name, r.role_name 
    FROM users u 
    INNER JOIN roles r ON u.role_id = r.id 
    WHERE r.role_name = 'Pharmacy Assistant'
");
if ($assistant_stmt) {
    $assistant_stmt->execute();
    $assistant_result = $assistant_stmt->get_result();
    while ($user = $assistant_result->fetch_assoc()) {
        $recipients[] = $user;
        echo "Found {$user['role_name']}: {$user['first_name']} {$user['last_name']} (ID: {$user['id']})<br>";
    }
    $assistant_stmt->close();
}

if (empty($recipients)) {
    echo "<p style='color: red;'>No pharmacists or pharmacy assistants found in the system!</p>";
    exit;
}

// Get all submitted requisitions that don't have notifications yet
$req_stmt = $conn->prepare("
    SELECT r.*, u.first_name, u.last_name 
    FROM requisitions r 
    LEFT JOIN users u ON r.pharmacist_id = u.id 
    WHERE r.status = 'Submitted'
    ORDER BY r.created_at DESC
");

if ($req_stmt) {
    $req_stmt->execute();
    $req_result = $req_stmt->get_result();
    
    $count = 0;
    while ($req = $req_result->fetch_assoc()) {
        $requisition_id = $req['requisition_id'];
        $technician_name = $req['pharmacist_name']; // This is actually the technician's name
        $total_amount = $req['total_amount'];
        $urgency = $req['urgency'];
        $req_db_id = $req['id'];
        
        echo "<br><strong>Processing Requisition: $requisition_id</strong><br>";
        echo "Submitted by: $technician_name<br>";
        echo "Total Amount: ₱" . number_format($total_amount, 2) . "<br>";
        echo "Urgency: $urgency<br>";
        
        // Create notification for each recipient (pharmacists and assistants)
        foreach ($recipients as $recipient) {
            // Check if notification already exists
            $check_stmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND related_type = 'requisition' AND related_id = ?");
            $check_stmt->bind_param("ii", $recipient['id'], $req_db_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Create notification
                $message = "New stock requisition $requisition_id has been submitted by $technician_name. Total Amount: ₱" . number_format($total_amount, 2) . ". Urgency: $urgency";
                $result = createNotification($recipient['id'], $message, 'info', 'requisition', $req_db_id);
                
                if ($result) {
                    echo "✓ Created notification for {$recipient['first_name']} {$recipient['last_name']} ({$recipient['role_name']})<br>";
                    $count++;
                } else {
                    echo "✗ Failed to create notification for {$recipient['first_name']} {$recipient['last_name']}<br>";
                }
            } else {
                echo "- Notification already exists for {$recipient['first_name']} {$recipient['last_name']}<br>";
            }
            $check_stmt->close();
        }
    }
    
    $req_stmt->close();
    
    echo "<br><h3 style='color: green;'>Done! Created $count notifications.</h3>";
    echo "<p><a href='Users/pharmacist/dashboard.php'>Go to Pharmacist Dashboard</a></p>";
} else {
    echo "<p style='color: red;'>Error querying requisitions!</p>";
}
?>
