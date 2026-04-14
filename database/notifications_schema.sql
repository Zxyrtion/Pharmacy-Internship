-- =====================================================
-- Notification System Database Schema
-- =====================================================
-- This SQL file creates the necessary tables and updates
-- for the pharmacy notification system
-- =====================================================

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    `related_type` ENUM('inventory_report', 'purchase_order') NOT NULL,
    `related_id` INT NOT NULL DEFAULT 0,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_notifications` (`user_id`, `is_read`, `created_at`),
    INDEX `idx_related` (`related_type`, `related_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add status column to requisition_reports table (if not exists)
ALTER TABLE `requisition_reports` 
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';

-- Add rejection_reason column to requisition_reports table (if not exists)
ALTER TABLE `requisition_reports` 
ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_requisition_reports_status` ON `requisition_reports` (`status`);

-- =====================================================
-- Sample Data (Optional - for testing only)
-- =====================================================

-- Uncomment the following lines to add sample notifications for testing
/*
-- Sample notification for a technician
INSERT INTO `notifications` (`user_id`, `message`, `type`, `related_type`, `related_id`) 
VALUES (
    (SELECT id FROM users WHERE role_name = 'Pharmacy Technician' LIMIT 1),
    'New inventory report for period 2025-04 has been submitted and requires your review.',
    'info',
    'inventory_report',
    0
);

-- Sample notification for an intern
INSERT INTO `notifications` (`user_id`, `message`, `type`, `related_type`, `related_id`) 
VALUES (
    (SELECT id FROM users WHERE role_name = 'Intern' LIMIT 1),
    'Your inventory report for period 2025-04 has been approved by the technician.',
    'success',
    'inventory_report',
    0
);

-- Sample notification for a pharmacist
INSERT INTO `notifications` (`user_id`, `message`, `type`, `related_type`, `related_id`) 
VALUES (
    (SELECT id FROM users WHERE role_name = 'Pharmacist' LIMIT 1),
    'New Purchase Order #PO-2025-001 has been created and requires your review.',
    'info',
    'purchase_order',
    1
);
*/

-- =====================================================
-- Verification Queries
-- =====================================================

-- Check if tables were created successfully
SHOW TABLES LIKE 'notifications';
SHOW COLUMNS FROM notifications;
SHOW COLUMNS FROM requisition_reports LIKE 'status';
SHOW COLUMNS FROM requisition_reports LIKE 'rejection_reason';

-- Count users by role (for verification)
SELECT role_name, COUNT(*) as user_count 
FROM users 
GROUP BY role_name 
ORDER BY role_name;
