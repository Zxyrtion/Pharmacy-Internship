<?php
require_once 'config.php';

if (isset($conn)) {
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        related_type ENUM('inventory_report', 'purchase_order') NOT NULL,
        related_id INT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Notifications table created successfully!";
    } else {
        echo "Error creating notifications table: " . $conn->error;
    }
    
    // Add status column to requisition_reports table if it doesn't exist
    $conn->query("ALTER TABLE requisition_reports ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    
    // Add rejection_reason column to requisition_reports table if it doesn't exist
    $conn->query("ALTER TABLE requisition_reports ADD COLUMN IF NOT EXISTS rejection_reason TEXT");
    
    echo "<br>Database schema updated for notifications!";
} else {
    echo "Database connection failed!";
}
?>
