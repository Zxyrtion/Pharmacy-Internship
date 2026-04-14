<?php
require_once 'config.php';

echo "<h2>Setting Up Notification System</h2>";

if (isset($conn)) {
    // Create notifications table
    echo "<h3>Creating notifications table...</h3>";
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
        echo "<div style='color: green;'>Notifications table created successfully!</div>";
    } else {
        echo "<div style='color: red;'>Error creating notifications table: " . $conn->error . "</div>";
    }
    
    // Add status column to requisition_reports table if it doesn't exist
    echo "<h3>Adding status column to requisition_reports...</h3>";
    $conn->query("ALTER TABLE requisition_reports ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    echo "<div>Status column added (or already exists)</div>";
    
    // Add rejection_reason column to requisition_reports table if it doesn't exist
    echo "<h3>Adding rejection_reason column to requisition_reports...</h3>";
    $conn->query("ALTER TABLE requisition_reports ADD COLUMN IF NOT EXISTS rejection_reason TEXT");
    echo "<div>Rejection reason column added (or already exists)</div>";
    
    echo "<h3>Setup Complete!</h3>";
    echo "<a href='debug_notifications.php'>Go to Debug Notifications</a>";
    
} else {
    echo "<div style='color: red;'>Database connection failed</div>";
}
?>
