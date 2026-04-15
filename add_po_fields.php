<?php
require_once 'config.php';

if (isset($conn)) {
    // Add inventory_period column if it doesn't exist
    $conn->query("ALTER TABLE requisition_reports ADD COLUMN IF NOT EXISTS inventory_period VARCHAR(100) AFTER comments");
    
    // Add reporter column if it doesn't exist
    $conn->query("ALTER TABLE requisition_reports ADD COLUMN IF NOT EXISTS reporter VARCHAR(100) AFTER inventory_period");
    
    echo "Database columns added successfully!";
} else {
    echo "Database connection failed!";
}
?>
