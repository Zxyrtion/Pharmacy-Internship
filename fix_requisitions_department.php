<?php
require_once 'config.php';

echo "=== Fixing Requisitions Table - Adding Department Column ===\n\n";

// Check if department column exists
$check_sql = "SHOW COLUMNS FROM requisitions LIKE 'department'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    echo "Department column not found. Adding it now...\n";
    
    // Add department column after pharmacist_name
    $alter_sql = "ALTER TABLE requisitions 
                  ADD COLUMN department VARCHAR(100) DEFAULT 'Pharmacy' AFTER pharmacist_name";
    
    if ($conn->query($alter_sql)) {
        echo "✓ Successfully added department column to requisitions table\n";
    } else {
        echo "✗ Error adding department column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Department column already exists\n";
}

// Check if date_required column exists
$check_sql = "SHOW COLUMNS FROM requisitions LIKE 'date_required'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    echo "\nDate_required column not found. Adding it now...\n";
    
    // Add date_required column after requisition_date
    $alter_sql = "ALTER TABLE requisitions 
                  ADD COLUMN date_required DATE NULL AFTER requisition_date";
    
    if ($conn->query($alter_sql)) {
        echo "✓ Successfully added date_required column to requisitions table\n";
    } else {
        echo "✗ Error adding date_required column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Date_required column already exists\n";
}

// Verify the table structure
echo "\n=== Current Requisitions Table Structure ===\n";
$result = $conn->query("DESCRIBE requisitions");
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-20s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
}

// Check for any requisitions
echo "\n=== Checking Requisitions Data ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM requisitions");
$row = $result->fetch_assoc();
echo "Total requisitions in database: " . $row['count'] . "\n";

if ($row['count'] > 0) {
    echo "\nRecent requisitions:\n";
    $result = $conn->query("SELECT requisition_id, pharmacist_name, department, status, requisition_date, total_amount 
                           FROM requisitions 
                           ORDER BY created_at DESC 
                           LIMIT 5");
    while ($req = $result->fetch_assoc()) {
        echo sprintf("ID: %-10s | Name: %-20s | Dept: %-15s | Status: %-10s | Date: %s | Amount: ₱%.2f\n",
                    $req['requisition_id'],
                    $req['pharmacist_name'],
                    $req['department'] ?? 'N/A',
                    $req['status'],
                    $req['requisition_date'],
                    $req['total_amount']);
    }
}

echo "\n=== Fix Complete ===\n";
echo "You can now create requisitions as a Technician and they will appear in the Pharmacist's Manage Requisitions page.\n";

$conn->close();
?>
