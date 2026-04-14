<?php
class Inventory {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Get all medicines with stock levels
    public function getAllMedicinesWithStock() {
        $sql = "SELECT m.*, 
                       CASE 
                           WHEN m.stock_quantity <= m.reorder_level THEN 'Critical'
                           WHEN m.stock_quantity <= (m.reorder_level * 1.5) THEN 'Low'
                           ELSE 'Normal'
                       END as stock_status
                FROM medicines m 
                ORDER BY m.medicine_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get medicines by stock status
    public function getMedicinesByStockStatus($status) {
        $sql = "SELECT m.*, 
                       CASE 
                           WHEN m.stock_quantity <= m.reorder_level THEN 'Critical'
                           WHEN m.stock_quantity <= (m.reorder_level * 1.5) THEN 'Low'
                           ELSE 'Normal'
                       END as stock_status
                FROM medicines m 
                HAVING stock_status = ?
                ORDER BY m.medicine_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get inventory statistics
    public function getInventoryStats() {
        $stats = [];
        
        // Total medicines
        $sql = "SELECT COUNT(*) as total FROM medicines";
        $result = $this->conn->query($sql);
        $stats['total_medicines'] = $result->fetch_assoc()['total'];
        
        // Low stock items
        $sql = "SELECT COUNT(*) as low_stock FROM medicines WHERE stock_quantity <= reorder_level";
        $result = $this->conn->query($sql);
        $stats['low_stock'] = $result->fetch_assoc()['low_stock'];
        
        // Critical stock items
        $sql = "SELECT COUNT(*) as critical_stock FROM medicines WHERE stock_quantity <= (reorder_level * 0.5)";
        $result = $this->conn->query($sql);
        $stats['critical_stock'] = $result->fetch_assoc()['critical_stock'];
        
        // Total stock value
        $sql = "SELECT SUM(stock_quantity * unit_price) as total_value FROM medicines";
        $result = $this->conn->query($sql);
        $stats['total_value'] = $result->fetch_assoc()['total_value'] ?? 0;
        
        return $stats;
    }
    
    // Update stock level
    public function updateStockLevel($medicine_id, $new_stock, $reason = '') {
        $sql = "UPDATE medicines SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $new_stock, $medicine_id);
        
        // Log stock change
        if ($stmt->execute()) {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $this->logStockChange($medicine_id, $new_stock, $reason, $user_id);
            return true;
        }
        return false;
    }
    
    // Log stock changes
    private function logStockChange($medicine_id, $new_stock, $reason, $user_id) {
        $sql = "INSERT INTO stock_logs (medicine_id, user_id, previous_stock, new_stock, reason, change_date) 
                SELECT ?, ?, stock_quantity, ?, ?, NOW() FROM medicines WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiisi", $medicine_id, $user_id, $new_stock, $reason, $medicine_id);
        $stmt->execute();
    }
    
    // Get stock change logs
    public function getStockLogs($medicine_id = null, $limit = 50) {
        $sql = "SELECT sl.*, m.medicine_name, u.first_name, u.last_name 
                FROM stock_logs sl 
                LEFT JOIN medicines m ON sl.medicine_id = m.id 
                LEFT JOIN users u ON sl.user_id = u.id";
        
        if ($medicine_id) {
            $sql .= " WHERE sl.medicine_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $medicine_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql .= " ORDER BY sl.change_date DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Add new medicine
    public function addMedicine($data) {
        $sql = "INSERT INTO medicines (medicine_name, dosage, stock_quantity, reorder_level, unit_price, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssiddd", $data['medicine_name'], $data['dosage'], $data['stock_quantity'], 
                         $data['reorder_level'], $data['unit_price'], $data['description']);
        return $stmt->execute();
    }
    
    // Update medicine
    public function updateMedicine($medicine_id, $data) {
        $sql = "UPDATE medicines SET medicine_name = ?, dosage = ?, reorder_level = ?, unit_price = ?, 
                description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssidisi", $data['medicine_name'], $data['dosage'], $data['reorder_level'], 
                         $data['unit_price'], $data['description'], $medicine_id);
        return $stmt->execute();
    }
    
    // Get medicine by ID
    public function getMedicineById($medicine_id) {
        $sql = "SELECT * FROM medicines WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Search medicines
    public function searchMedicines($search_term) {
        $sql = "SELECT m.*, 
                       CASE 
                           WHEN m.stock_quantity <= m.reorder_level THEN 'Critical'
                           WHEN m.stock_quantity <= (m.reorder_level * 1.5) THEN 'Low'
                           ELSE 'Normal'
                       END as stock_status
                FROM medicines m 
                WHERE m.medicine_name LIKE ? OR m.dosage LIKE ?
                ORDER BY m.medicine_name";
        $search_param = "%$search_term%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get medicines needing reorder
    public function getMedicinesNeedingReorder() {
        $sql = "SELECT m.* FROM medicines m WHERE m.stock_quantity <= m.reorder_level ORDER BY m.stock_quantity ASC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Create stock logs table if not exists
    public function createStockLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS stock_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medicine_id INT NOT NULL,
            user_id INT NOT NULL,
            previous_stock INT NOT NULL,
            new_stock INT NOT NULL,
            reason TEXT,
            change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $this->conn->query($sql);
    }
}
?>