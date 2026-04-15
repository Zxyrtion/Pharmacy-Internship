-- Purchase Order and Requisition Management Tables

-- Table structure for requisitions
CREATE TABLE IF NOT EXISTS `requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` varchar(20) NOT NULL,
  `pharmacist_id` int(11) NOT NULL,
  `pharmacist_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT 'Pharmacy',
  `requisition_date` date NOT NULL,
  `date_required` date DEFAULT NULL,
  `urgency` enum('Normal','Urgent','Critical') DEFAULT 'Normal',
  `status` enum('Draft','Submitted','Approved','Rejected','Processed') DEFAULT 'Draft',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requisition_id` (`requisition_id`),
  KEY `pharmacist_id` (`pharmacist_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for requisition items
CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 0,
  `requested_quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `medicine_id` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for purchase orders
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` varchar(20) NOT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `supplier_contact` varchar(100) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('Pending','Confirmed','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_terms` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_id` (`purchase_order_id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `supplier_name` (`supplier_name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for purchase order items
CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `medicine_id` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for suppliers
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `delivery_time` int(11) DEFAULT 7,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_name` (`supplier_name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample suppliers
INSERT INTO `suppliers` (`supplier_name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `delivery_time`) VALUES
('MediSupply Philippines', 'Juan Reyes', '+632-555-0001', 'orders@medisupply.ph', 'Manila, Philippines', 'Net 30', 5),
('PharmaLink Inc.', 'Maria Santos', '+632-555-0002', 'sales@pharmalink.com', 'Quezon City, Philippines', 'Net 15', 7),
('HealthCare Distributors', 'Roberto Cruz', '+632-555-0003', 'info@healthcare-dist.ph', 'Makati, Philippines', 'COD', 3);

-- Insert sample requisition
INSERT INTO `requisitions` (`requisition_id`, `pharmacist_id`, `pharmacist_name`, `requisition_date`, `urgency`, `status`, `total_amount`) VALUES
('REQ001', 1, 'Dr. Smith', '2024-04-14', 'Normal', 'Approved', 3100.00);

-- Insert sample requisition items
INSERT INTO `requisition_items` (`requisition_id`, `medicine_name`, `dosage`, `current_stock`, `reorder_level`, `requested_quantity`, `unit_price`, `total_price`, `supplier`) VALUES
(1, 'Paracetamol', '500mg', 15, 20, 100, 15.50, 1550.00, 'MediSupply Philippines'),
(1, 'Amoxicillin', '250mg', 10, 15, 50, 45.75, 2287.50, 'PharmaLink Inc.'),
(1, 'Ibuprofen', '400mg', 20, 20, 50, 25.00, 1250.00, 'MediSupply Philippines');
