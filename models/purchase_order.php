<?php
require_once __DIR__ . '/../config.php';

class PurchaseOrder {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Get all requisitions
    public function getAllRequisitions() {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM requisitions r 
                LEFT JOIN users u ON r.pharmacist_id = u.id 
                ORDER BY r.requisition_date DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get requisition by ID
    public function getRequisitionById($id) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM requisitions r 
                LEFT JOIN users u ON r.pharmacist_id = u.id 
                WHERE r.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Get requisition items
    public function getRequisitionItems($requisition_id) {
        $sql = "SELECT * FROM requisition_items WHERE requisition_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $requisition_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get requisitions by technician/pharmacist ID
    public function getRequisitionsByUserId($user_id) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM requisitions r 
                LEFT JOIN users u ON r.pharmacist_id = u.id 
                WHERE r.pharmacist_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get requisition statistics for a specific user
    public function getUserRequisitionStats($user_id) {
        $stats = ['total' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0, 'processed' => 0];

        $r = $this->conn->query("SELECT COUNT(*) as total FROM requisitions WHERE pharmacist_id = $user_id");
        if ($r) $stats['total'] = $r->fetch_assoc()['total'];

        $r = $this->conn->query("SELECT COUNT(*) as submitted FROM requisitions WHERE pharmacist_id = $user_id AND status = 'Submitted'");
        if ($r) $stats['submitted'] = $r->fetch_assoc()['submitted'];

        $r = $this->conn->query("SELECT COUNT(*) as approved FROM requisitions WHERE pharmacist_id = $user_id AND status = 'Approved'");
        if ($r) $stats['approved'] = $r->fetch_assoc()['approved'];

        $r = $this->conn->query("SELECT COUNT(*) as rejected FROM requisitions WHERE pharmacist_id = $user_id AND status = 'Rejected'");
        if ($r) $stats['rejected'] = $r->fetch_assoc()['rejected'];

        $r = $this->conn->query("SELECT COUNT(*) as processed FROM requisitions WHERE pharmacist_id = $user_id AND status = 'Processed'");
        if ($r) $stats['processed'] = $r->fetch_assoc()['processed'];

        return $stats;
    }
    
    // Get approved requisitions for purchase order generation
    public function getApprovedRequisitions() {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM requisitions r 
                LEFT JOIN users u ON r.pharmacist_id = u.id 
                WHERE r.status = 'Approved' 
                ORDER BY r.urgency DESC, r.requisition_date ASC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Generate purchase order from requisition
    public function generatePurchaseOrder($requisition_id, $supplier_name, $expected_delivery_date, $payment_terms, $created_by, $notes = '') {
        // Get requisition details
        $requisition = $this->getRequisitionById($requisition_id);
        $requisition_items = $this->getRequisitionItems($requisition_id);
        
        if (!$requisition || empty($requisition_items)) {
            return ['success' => false, 'error' => 'Requisition not found or has no items'];
        }
        
        // Generate purchase order ID
        $purchase_order_id = 'PO' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($requisition_items as $item) {
            $total_amount += $item['total_price'];
        }
        
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Create purchase order
            $po_sql = "INSERT INTO purchase_orders (purchase_order_id, requisition_id, supplier_name, expected_delivery_date, payment_terms, total_amount, notes, created_by, order_date) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
            $stmt = $this->conn->prepare($po_sql);
            $stmt->bind_param("sisssdss", $purchase_order_id, $requisition_id, $supplier_name, $expected_delivery_date, $payment_terms, $total_amount, $notes, $created_by);
            $stmt->execute();
            $po_db_id = $stmt->insert_id;
            
            // Create purchase order items
            foreach ($requisition_items as $item) {
                $poi_sql = "INSERT INTO purchase_order_items (purchase_order_id, medicine_name, dosage, quantity_ordered, unit_price, total_price) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($poi_sql);
                $stmt->bind_param("issidd", $po_db_id, $item['medicine_name'], $item['dosage'], 
                                 $item['requested_quantity'], $item['unit_price'], $item['total_price']);
                $stmt->execute();
            }
            
            // Update requisition status to 'Processed'
            $this->updateRequisitionStatus($requisition_id, 'Processed');
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'purchase_order_id' => $purchase_order_id,
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
    
    // Update requisition status
    public function updateRequisitionStatus($requisition_id, $status) {
        $sql = "UPDATE requisitions SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $requisition_id);
        return $stmt->execute();
    }
    
    // Get all purchase orders
    public function getAllPurchaseOrders() {
        $sql = "SELECT po.*, u.first_name, u.last_name 
                FROM purchase_orders po 
                LEFT JOIN users u ON po.created_by = u.id 
                ORDER BY po.order_date DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get purchase order by ID
    public function getPurchaseOrderById($id) {
        $sql = "SELECT po.*, u.first_name, u.last_name 
                FROM purchase_orders po 
                LEFT JOIN users u ON po.created_by = u.id 
                WHERE po.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Get purchase order items
    public function getPurchaseOrderItems($purchase_order_id) {
        $sql = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $purchase_order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get suppliers
    public function getSuppliers() {
        $sql = "SELECT * FROM suppliers WHERE status = 'Active' ORDER BY supplier_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get requisition statistics
    public function getRequisitionStats() {
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'processed' => 0];

        $r = $this->conn->query("SELECT COUNT(*) as total FROM requisitions");
        if ($r) $stats['total'] = $r->fetch_assoc()['total'];

        $r = $this->conn->query("SELECT COUNT(*) as pending FROM requisitions WHERE status = 'Submitted'");
        if ($r) $stats['pending'] = $r->fetch_assoc()['pending'];

        $r = $this->conn->query("SELECT COUNT(*) as approved FROM requisitions WHERE status = 'Approved'");
        if ($r) $stats['approved'] = $r->fetch_assoc()['approved'];

        $r = $this->conn->query("SELECT COUNT(*) as processed FROM requisitions WHERE status = 'Processed'");
        if ($r) $stats['processed'] = $r->fetch_assoc()['processed'];

        return $stats;
    }
    
    // Create new requisition
    public function createRequisition($pharmacist_id, $pharmacist_name, $department, $requisition_date, $date_required, $urgency, $reason, $items) {
        // Generate requisition ID
        $requisition_id = 'REQ' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['quantity'] * $item['unit_price'];
        }
        
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Create requisition
            $req_sql = "INSERT INTO requisitions (requisition_id, pharmacist_id, pharmacist_name, department, requisition_date, date_required, urgency, status, total_amount, notes) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'Submitted', ?, ?)";
            $stmt = $this->conn->prepare($req_sql);
            $stmt->bind_param("sisssssds", $requisition_id, $pharmacist_id, $pharmacist_name, $department, $requisition_date, $date_required, $urgency, $total_amount, $reason);
            $stmt->execute();
            $req_db_id = $stmt->insert_id;
            
            // Create requisition items
            foreach ($items as $item) {
                $item_total = $item['quantity'] * $item['unit_price'];
                
                // Create temporary variables for bind_param
                $medicine_name = $item['medicine_name'];
                $dosage = $item['dosage'];
                $current_stock = $item['current_stock'];
                $reorder_level = $item['reorder_level'];
                $requested_quantity = $item['quantity'];
                $unit_price = $item['unit_price'];
                $total_item_price = $item_total;
                $supplier = $item['supplier'];
                
                $item_sql = "INSERT INTO requisition_items (requisition_id, medicine_name, dosage, current_stock, reorder_level, requested_quantity, unit_price, total_price, supplier) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($item_sql);
                $stmt->bind_param("issiiidds", $req_db_id, $medicine_name, $dosage, 
                                 $current_stock, $reorder_level, $requested_quantity, 
                                 $unit_price, $total_item_price, $supplier);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'requisition_id' => $requisition_id,
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

    // Approve requisition
    public function approveRequisition($requisition_id, $approver_id) {
        $sql = "UPDATE requisitions SET status = 'Approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $requisition_id);
        return $stmt->execute();
    }
    
    // Reject requisition
    public function rejectRequisition($requisition_id, $approver_id, $reason) {
        $sql = "UPDATE requisitions SET status = 'Rejected', notes = CONCAT(IFNULL(notes, ''), ' Rejection reason: ', ?), updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $reason, $requisition_id);
        return $stmt->execute();
    }
    
    // Get all requisitions with filtering
    public function getAllRequisitionsWithFilter($status_filter = '') {
        $sql = "SELECT r.*, r.department, u.first_name, u.last_name 
                FROM requisitions r 
                LEFT JOIN users u ON r.pharmacist_id = u.id";
        
        if (!empty($status_filter)) {
            $sql .= " WHERE r.status = ?";
            $sql .= " ORDER BY r.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $status_filter);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql .= " ORDER BY r.created_at DESC";
            $result = $this->conn->query($sql);
        }
        
        if (!$result) {
            return [];
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Update requisition
    public function updateRequisition($requisition_id, $data) {
        $sql = "UPDATE requisitions SET department = ?, requisition_date = ?, urgency = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $data['department'], $data['requisition_date'], $data['urgency'], $data['notes'], $requisition_id);
        return $stmt->execute();
    }
    
    // Update requisition with items
    public function updateRequisitionWithItems($requisition_id, $department, $requisition_date, $date_required, $urgency, $reason, $items) {
        // Calculate total amount
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['quantity'] * $item['unit_price'];
        }
        
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Update requisition
            $req_sql = "UPDATE requisitions SET department = ?, requisition_date = ?, date_required = ?, urgency = ?, total_amount = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($req_sql);
            $stmt->bind_param("ssssdsi", $department, $requisition_date, $date_required, $urgency, $total_amount, $reason, $requisition_id);
            $stmt->execute();
            
            // Delete existing items
            $delete_sql = "DELETE FROM requisition_items WHERE requisition_id = ?";
            $stmt = $this->conn->prepare($delete_sql);
            $stmt->bind_param("i", $requisition_id);
            $stmt->execute();
            
            // Insert updated items
            foreach ($items as $item) {
                $item_total = $item['quantity'] * $item['unit_price'];
                
                $medicine_name = $item['medicine_name'];
                $dosage = $item['dosage'];
                $current_stock = $item['current_stock'] ?? 0;
                $reorder_level = $item['reorder_level'] ?? 0;
                $requested_quantity = $item['quantity'];
                $unit_price = $item['unit_price'];
                $total_item_price = $item_total;
                $supplier = $item['supplier'];
                
                $item_sql = "INSERT INTO requisition_items (requisition_id, medicine_name, dosage, current_stock, reorder_level, requested_quantity, unit_price, total_price, supplier) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($item_sql);
                $stmt->bind_param("issiiidds", $requisition_id, $medicine_name, $dosage, 
                                 $current_stock, $reorder_level, $requested_quantity, 
                                 $unit_price, $total_item_price, $supplier);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
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
    
    // Delete requisition
    public function deleteRequisition($requisition_id) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Delete items first
            $delete_items_sql = "DELETE FROM requisition_items WHERE requisition_id = ?";
            $stmt = $this->conn->prepare($delete_items_sql);
            $stmt->bind_param("i", $requisition_id);
            $stmt->execute();
            
            // Delete requisition
            $delete_req_sql = "DELETE FROM requisitions WHERE id = ?";
            $stmt = $this->conn->prepare($delete_req_sql);
            $stmt->bind_param("i", $requisition_id);
            $stmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }

    // Get purchase order statistics
    public function getPurchaseOrderStats() {
        $stats = ['total' => 0, 'pending' => 0, 'delivered' => 0];

        $r = $this->conn->query("SELECT COUNT(*) as total FROM purchase_orders");
        if ($r) $stats['total'] = $r->fetch_assoc()['total'];

        $r = $this->conn->query("SELECT COUNT(*) as pending FROM purchase_orders WHERE status = 'Pending'");
        if ($r) $stats['pending'] = $r->fetch_assoc()['pending'];

        $r = $this->conn->query("SELECT COUNT(*) as delivered FROM purchase_orders WHERE status = 'Delivered'");
        if ($r) $stats['delivered'] = $r->fetch_assoc()['delivered'];

        return $stats;
    }
}
?>
