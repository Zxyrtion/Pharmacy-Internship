-- Enhanced Inventory System Schema
-- This creates a comprehensive product_inventory table with all necessary features

-- Modify existing product_inventory table to add new columns
ALTER TABLE `product_inventory` 
ADD COLUMN IF NOT EXISTS `barcode` VARCHAR(50) NULL AFTER `product_name`,
ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `manufacturer` VARCHAR(100) NULL AFTER `category`,
ADD COLUMN IF NOT EXISTS `unit` VARCHAR(20) DEFAULT 'pcs' AFTER `quantity`,
ADD COLUMN IF NOT EXISTS `expiry_date` DATE NULL AFTER `price`,
ADD COLUMN IF NOT EXISTS `batch_number` VARCHAR(50) NULL AFTER `expiry_date`,
ADD COLUMN IF NOT EXISTS `supplier_id` INT NULL AFTER `batch_number`,
ADD COLUMN IF NOT EXISTS `reorder_level` INT DEFAULT 10 AFTER `total_price`,
ADD COLUMN IF NOT EXISTS `max_stock_level` INT DEFAULT 100 AFTER `reorder_level`,
ADD COLUMN IF NOT EXISTS `location` VARCHAR(100) NULL AFTER `max_stock_level`,
ADD COLUMN IF NOT EXISTS `status` ENUM('Active', 'Inactive', 'Expired', 'Discontinued') DEFAULT 'Active' AFTER `location`,
ADD COLUMN IF NOT EXISTS `last_restocked` DATETIME NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS `idx_barcode` ON `product_inventory` (`barcode`);
CREATE INDEX IF NOT EXISTS `idx_category` ON `product_inventory` (`category`);
CREATE INDEX IF NOT EXISTS `idx_status` ON `product_inventory` (`status`);
CREATE INDEX IF NOT EXISTS `idx_expiry_date` ON `product_inventory` (`expiry_date`);
CREATE INDEX IF NOT EXISTS `idx_reorder` ON `product_inventory` (`quantity`, `reorder_level`);

-- Create suppliers table
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` VARCHAR(200) NOT NULL,
  `contact_person` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_name` (`supplier_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create inventory movements table for tracking stock changes
CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `movement_type` ENUM('Stock In', 'Stock Out', 'Adjustment', 'Transfer', 'Expired', 'Damaged') NOT NULL,
  `quantity` INT(11) NOT NULL,
  `previous_quantity` INT(11) NOT NULL,
  `new_quantity` INT(11) NOT NULL,
  `unit_cost` DECIMAL(10,2) NULL,
  `total_cost` DECIMAL(10,2) NULL,
  `reference_number` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `user_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `movement_type` (`movement_type`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create low stock alerts table
CREATE TABLE IF NOT EXISTS `stock_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `alert_type` ENUM('Low Stock', 'Out of Stock', 'Expiring Soon', 'Expired') NOT NULL,
  `current_quantity` INT(11) NOT NULL,
  `reorder_level` INT(11) NOT NULL,
  `expiry_date` DATE NULL,
  `days_to_expiry` INT NULL,
  `is_acknowledged` BOOLEAN DEFAULT FALSE,
  `acknowledged_by` INT(11) NULL,
  `acknowledged_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `alert_type` (`alert_type`),
  KEY `is_acknowledged` (`is_acknowledged`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample suppliers
INSERT INTO `suppliers` (`supplier_name`, `contact_person`, `phone`, `email`, `address`) VALUES
('MediSupply Corp', 'John Smith', '09123456789', 'john@medisupply.com', '123 Medical Ave, Manila'),
('PharmaCorp Inc', 'Jane Doe', '09987654321', 'jane@pharmacorp.com', '456 Health St, Quezon City'),
('Global Pharma', 'Mike Johnson', '09111222333', 'mike@globalpharma.com', '789 Medicine Blvd, Makati');

-- Add foreign key constraints (optional, for data integrity)
-- ALTER TABLE `product_inventory` ADD FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`);
-- ALTER TABLE `inventory_movements` ADD FOREIGN KEY (`product_id`) REFERENCES `product_inventory`(`id`);
-- ALTER TABLE `inventory_movements` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`);
-- ALTER TABLE `stock_alerts` ADD FOREIGN KEY (`product_id`) REFERENCES `product_inventory`(`id`);

-- Create indexes for better performance
CREATE INDEX `idx_product_inventory_expiry` ON `product_inventory` (`expiry_date`);
CREATE INDEX `idx_product_inventory_status` ON `product_inventory` (`status`);
CREATE INDEX `idx_product_inventory_reorder` ON `product_inventory` (`quantity`, `reorder_level`);
CREATE INDEX `idx_stock_alerts_unacknowledged` ON `stock_alerts` (`is_acknowledged`, `created_at`);