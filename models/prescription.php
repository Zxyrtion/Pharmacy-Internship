<?php
require_once __DIR__ . '/../config.php';

class Prescription {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Get all prescriptions
    public function getAllPrescriptions() {
        $sql = "SELECT * FROM prescriptions ORDER BY date_prescribed DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get prescription by ID
    public function getPrescriptionById($id) {
        $sql = "SELECT * FROM prescriptions WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Get prescription by prescription_id
    public function getPrescriptionByPrescriptionId($prescription_id) {
        $sql = "SELECT * FROM prescriptions WHERE prescription_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $prescription_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Update prescription status
    public function updateStatus($prescription_id, $status) {
        $sql = "UPDATE prescriptions SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE prescription_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $status, $prescription_id);
        return $stmt->execute();
    }
    
    // Check product availability (Process 16)
    public function checkProductAvailability($medicine_name, $dosage, $quantity_needed) {
        $medicine = $this->getMedicineDetails($medicine_name, $dosage);
        
        if (!$medicine) {
            return [
                'available' => false,
                'status' => 'Not Found',
                'reason' => 'Medicine not found in inventory'
            ];
        }
        
        if ($medicine['stock_quantity'] <= 0) {
            return [
                'available' => false,
                'status' => 'Out of Stock',
                'stock' => 0,
                'needed' => $quantity_needed,
                'reason' => 'Product is completely out of stock'
            ];
        }
        
        if ($medicine['stock_quantity'] < $quantity_needed) {
            return [
                'available' => true,
                'partial' => true,
                'status' => 'Partial Stock',
                'stock' => $medicine['stock_quantity'],
                'needed' => $quantity_needed,
                'reason' => 'Only ' . $medicine['stock_quantity'] . ' units available, but ' . $quantity_needed . ' requested'
            ];
        }
        
        return [
            'available' => true,
            'partial' => false,
            'status' => 'Available',
            'stock' => $medicine['stock_quantity'],
            'needed' => $quantity_needed,
            'price' => $medicine['unit_price'],
            'medicine' => $medicine,
            'reason' => 'Sufficient stock available'
        ];
    }
    
    // Get product availability view
    public function getProductAvailability() {
        $sql = "SELECT * FROM product_availability ORDER BY stock_level DESC, medicine_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Generate order from prescription
    public function generateOrder($prescription_id, $pharmacist_id) {
        // Get prescription details
        $prescription = $this->getPrescriptionByPrescriptionId($prescription_id);
        
        if (!$prescription) {
            return false;
        }
        
        // Check product availability first (Process 16)
        $availability = $this->checkProductAvailability($prescription['medicine_name'], $prescription['dosage'], $prescription['quantity']);
        
        if (!$availability['available']) {
            return [
                'success' => false,
                'error' => 'Product not available: ' . $availability['reason']
            ];
        }
        
        // Get medicine details for pricing
        $medicine = $this->getMedicineDetails($prescription['medicine_name'], $prescription['dosage']);
        
        // Generate order ID
        $order_id = 'ORD' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate total amount
        $unit_price = $medicine ? $medicine['unit_price'] : 0;
        $total_amount = $unit_price * $prescription['quantity'];
        
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Create order
            $order_sql = "INSERT INTO orders (order_id, prescription_id, customer_id, customer_name, order_type, total_amount, status, pharmacist_id) 
                         VALUES (?, ?, ?, ?, 'Prescription', ?, 'Processing', ?)";
            $stmt = $this->conn->prepare($order_sql);
            $stmt->bind_param("siisdi", $order_id, $prescription['id'], $prescription['patient_id'], 
                             $prescription['patient_name'], $total_amount, $pharmacist_id);
            $stmt->execute();
            $order_db_id = $stmt->insert_id;
            
            // Create order item
            $order_item_sql = "INSERT INTO order_items (order_id, medicine_name, dosage, quantity, unit_price, total_price) 
                              VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($order_item_sql);
            $stmt->bind_param("issidd", $order_db_id, $prescription['medicine_name'], $prescription['dosage'], 
                             $prescription['quantity'], $unit_price, $total_amount);
            $stmt->execute();
            
            // Update prescription status
            $this->updateStatus($prescription_id, 'Processing');
            
            // Update medicine stock if medicine exists
            if ($medicine) {
                $this->updateMedicineStock($medicine['id'], $prescription['quantity']);
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'total_amount' => $total_amount
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Get medicine details
    private function getMedicineDetails($medicine_name, $dosage) {
        $sql = "SELECT * FROM medicines WHERE medicine_name = ? AND dosage = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $medicine_name, $dosage);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Update medicine stock
    private function updateMedicineStock($medicine_id, $quantity) {
        $sql = "UPDATE medicines SET stock_quantity = stock_quantity - ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND stock_quantity >= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $quantity, $medicine_id, $quantity);
        return $stmt->execute();
    }
    
    // Get pending prescriptions
    public function getPendingPrescriptions() {
        $sql = "SELECT * FROM prescriptions WHERE status = 'Pending' ORDER BY date_prescribed DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get prescription statistics
    public function getPrescriptionStats() {
        $stats = [];
        
        // Total prescriptions
        $sql = "SELECT COUNT(*) as total FROM prescriptions";
        $result = $this->conn->query($sql);
        $stats['total'] = $result->fetch_assoc()['total'];
        
        // Pending prescriptions
        $sql = "SELECT COUNT(*) as pending FROM prescriptions WHERE status = 'Pending'";
        $result = $this->conn->query($sql);
        $stats['pending'] = $result->fetch_assoc()['pending'];
        
        // Dispensed prescriptions
        $sql = "SELECT COUNT(*) as dispensed FROM prescriptions WHERE status = 'Dispensed'";
        $result = $this->conn->query($sql);
        $stats['dispensed'] = $result->fetch_assoc()['dispensed'];
        
        return $stats;
    }
}
?>