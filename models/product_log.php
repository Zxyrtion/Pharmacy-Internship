<?php
require_once __DIR__ . '/../config.php';

class ProductLog {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Create product dispense log
    public function logProductDispense($order_id, $prescription_id, $medicine_id, $medicine_name, $dosage, $quantity, $unit_price, $total_price, $pharmacist_id, $patient_id, $patient_name) {
        $sql = "INSERT INTO product_logs (order_id, prescription_id, medicine_id, medicine_name, dosage, quantity_dispensed, unit_price, total_price, pharmacist_id, patient_id, patient_name, action, log_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Dispensed', NOW())";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing log dispense query: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("isisiidisiss", $order_id, $prescription_id, $medicine_id, $medicine_name, $dosage, $quantity, $unit_price, $total_price, $pharmacist_id, $patient_id, $patient_name);
        return $stmt->execute();
    }
    
    // Get all product logs
    public function getAllProductLogs() {
        $sql = "SELECT pl.*, u.first_name, u.last_name FROM product_logs pl 
                LEFT JOIN users u ON pl.pharmacist_id = u.id 
                ORDER BY pl.log_date DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get product logs by prescription
    public function getLogsByPrescription($prescription_id) {
        $sql = "SELECT pl.*, u.first_name, u.last_name FROM product_logs pl 
                LEFT JOIN users u ON pl.pharmacist_id = u.id 
                WHERE pl.prescription_id = ? 
                ORDER BY pl.log_date DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing logs by prescription query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $prescription_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get product logs by medicine
    public function getLogsByMedicine($medicine_id) {
        $sql = "SELECT pl.*, u.first_name, u.last_name FROM product_logs pl 
                LEFT JOIN users u ON pl.pharmacist_id = u.id 
                WHERE pl.medicine_id = ? 
                ORDER BY pl.log_date DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing logs by medicine query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get product logs by pharmacist
    public function getLogsByPharmacist($pharmacist_id) {
        $sql = "SELECT pl.*, u.first_name, u.last_name FROM product_logs pl 
                LEFT JOIN users u ON pl.pharmacist_id = u.id 
                WHERE pl.pharmacist_id = ? 
                ORDER BY pl.log_date DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing logs by pharmacist query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $pharmacist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get product logs by patient
    public function getLogsByPatient($patient_id) {
        $sql = "SELECT pl.*, u.first_name, u.last_name FROM product_logs pl 
                LEFT JOIN users u ON pl.pharmacist_id = u.id 
                WHERE pl.patient_id = ? 
                ORDER BY pl.log_date DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing logs by patient query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get product logs by date range
    public function getLogsByDateRange($start_date, $end_date) {
        $sql = "SELECT pl.*, u.first_name, u.last_name FROM product_logs pl 
                LEFT JOIN users u ON pl.pharmacist_id = u.id 
                WHERE pl.log_date BETWEEN ? AND ? 
                ORDER BY pl.log_date DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing logs by date range query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get daily dispensing report
    public function getDailyDispensingReport($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $sql = "SELECT 
                COUNT(DISTINCT prescription_id) as total_prescriptions,
                COUNT(*) as total_items,
                SUM(quantity_dispensed) as total_quantity,
                SUM(total_price) as total_revenue,
                COUNT(DISTINCT patient_name) as unique_patients
                FROM product_logs 
                WHERE DATE(log_date) = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing daily dispensing report query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : [];
    }
    
    // Get product sales summary
    public function getProductSalesSummary() {
        $sql = "SELECT 
                medicine_name,
                dosage as generic_name,
                COUNT(*) as dispensed_count,
                SUM(quantity_dispensed) as total_quantity,
                SUM(total_price) as total_revenue,
                AVG(unit_price) as avg_price
                FROM product_logs
                GROUP BY medicine_name, dosage
                ORDER BY total_revenue DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Get pharmacist performance
    public function getPharmacistPerformance($start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $sql = "SELECT 
                pl.pharmacist_id,
                u.first_name,
                u.last_name,
                COUNT(DISTINCT pl.prescription_id) as prescriptions_filled,
                COUNT(*) as items_dispensed,
                SUM(pl.quantity_dispensed) as total_quantity,
                SUM(pl.total_price) as total_revenue
                FROM product_logs pl
                LEFT JOIN users u ON pl.pharmacist_id = u.id
                WHERE DATE(pl.log_date) BETWEEN ? AND ?
                GROUP BY pl.pharmacist_id
                ORDER BY total_revenue DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing pharmacist performance query: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Create product_logs table if not exists
    public function createProductLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS product_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            prescription_id INT NOT NULL,
            medicine_id INT DEFAULT NULL,
            medicine_name VARCHAR(200) NOT NULL,
            dosage VARCHAR(50) DEFAULT NULL,
            quantity_dispensed INT NOT NULL,
            unit_price DECIMAL(10,2) DEFAULT 0.00,
            total_price DECIMAL(10,2) DEFAULT 0.00,
            pharmacist_id INT NOT NULL,
            patient_id INT NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            action ENUM('Dispensed','Returned','Exchanged','Refunded') DEFAULT 'Dispensed',
            notes TEXT DEFAULT NULL,
            log_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (prescription_id) REFERENCES prescriptions(id),
            FOREIGN KEY (medicine_id) REFERENCES medicines(id),
            FOREIGN KEY (pharmacist_id) REFERENCES users(id),
            FOREIGN KEY (patient_id) REFERENCES users(id),
            KEY (log_date),
            KEY (medicine_id),
            KEY (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        return $this->conn->query($sql);
    }
}
?>
