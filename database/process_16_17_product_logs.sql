-- Process 16 & 17: Product Availability Check and Product Dispensing Logs

-- Table structure for product logs (Process 17)
CREATE TABLE IF NOT EXISTS `product_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity_dispensed` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `pharmacist_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `action` enum('Dispensed','Returned','Exchanged','Refunded') DEFAULT 'Dispensed',
  `notes` text DEFAULT NULL,
  `log_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `log_date` (`log_date`),
  KEY `action` (`action`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`),
  FOREIGN KEY (`pharmacist_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add availability check view (Process 16)
CREATE OR REPLACE VIEW `product_availability` AS
SELECT 
  m.id,
  m.medicine_name,
  m.dosage,
  m.stock_quantity,
  m.reorder_level,
  CASE 
    WHEN m.stock_quantity <= 0 THEN 'Out of Stock'
    WHEN m.stock_quantity <= m.reorder_level THEN 'Low Stock'
    WHEN m.stock_quantity <= (m.reorder_level * 1.5) THEN 'Medium Stock'
    ELSE 'Available'
  END AS availability_status,
  CASE 
    WHEN m.stock_quantity <= 0 THEN 'Critical'
    WHEN m.stock_quantity <= m.reorder_level THEN 'Warning'
    WHEN m.stock_quantity <= (m.reorder_level * 1.5) THEN 'Caution'
    ELSE 'Adequate'
  END AS stock_level,
  m.unit_price,
  m.manufacturer,
  m.category,
  m.expiry_date,
  m.updated_at
FROM medicines m
WHERE m.status = 'Active'
ORDER BY m.stock_quantity ASC;

-- Index for faster product availability queries
CREATE INDEX idx_medicine_stock ON medicines(stock_quantity, reorder_level);
CREATE INDEX idx_medicine_status ON medicines(status);
