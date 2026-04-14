-- Additional tables for prescription and order management

-- Table structure for prescriptions
CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `instructions` text DEFAULT NULL,
  `doctor_name` varchar(100) DEFAULT NULL,
  `date_prescribed` date NOT NULL,
  `status` enum('Pending','Processing','Dispensed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescription_id` (`prescription_id`),
  KEY `patient_id` (`patient_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for orders
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(20) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `order_type` enum('Prescription','Over-the-Counter') DEFAULT 'Prescription',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pending','Processing','Ready','Completed','Cancelled') DEFAULT 'Pending',
  `pharmacist_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `ready_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `customer_id` (`customer_id`),
  KEY `pharmacist_id` (`pharmacist_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for order items
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for medicines/inventory
CREATE TABLE `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Discontinued') DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_name` (`medicine_name`, `dosage`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample medicines
INSERT INTO `medicines` (`medicine_name`, `dosage`, `description`, `category`, `unit_price`, `stock_quantity`, `reorder_level`) VALUES
('Paracetamol', '500mg', 'Pain reliever and fever reducer', 'Analgesic', 15.50, 100, 20),
('Amoxicillin', '250mg', 'Antibiotic for bacterial infections', 'Antibiotic', 45.75, 50, 15),
('Ibuprofen', '400mg', 'Anti-inflammatory pain reliever', 'Analgesic', 25.00, 75, 20),
('Loperamide', '2mg', 'Anti-diarrheal medication', 'Gastrointestinal', 35.25, 30, 10),
('Salbutamol', '100mcg', 'Bronchodilator for asthma', 'Respiratory', 120.00, 25, 10);

-- Insert sample prescriptions
INSERT INTO `prescriptions` (`prescription_id`, `patient_id`, `patient_name`, `medicine_name`, `dosage`, `quantity`, `instructions`, `doctor_name`, `date_prescribed`, `status`) VALUES
('PRX001', 1, 'Juan Dela Cruz', 'Paracetamol', '500mg', 20, 'Take 1 tablet every 6 hours as needed for fever', 'Dr. Smith', '2024-04-14', 'Pending'),
('PRX002', 2, 'Maria Santos', 'Amoxicillin', '250mg', 30, 'Take 1 capsule every 8 hours for 7 days', 'Dr. Johnson', '2024-04-14', 'Dispensed');
