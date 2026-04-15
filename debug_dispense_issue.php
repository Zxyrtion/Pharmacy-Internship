<?php
require_once 'config.php';

echo "<h2>Debugging Dispense Issue for Prescriptions #5 and #6</h2>";

// Check prescriptions
echo "<h3>Prescription Data:</h3>";
$result = $conn->query("SELECT * FROM prescriptions WHERE id IN (5, 6) ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Prescription ID</th><th>Customer ID</th><th>Patient ID</th><th>Patient Name</th><th>Doctor</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['prescription_id'] ?? '-') . "</td>";
        echo "<td>{$row['customer_id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
        echo "<td><strong>{$row['status']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No prescriptions found!</p>";
}

// Check purchase orders
echo "<h3>Prescription Orders:</h3>";
$result = $conn->query("SELECT * FROM prescription_orders WHERE prescription_id IN (5, 6) ORDER BY prescription_id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Prescription ID</th><th>Pharmacist ID</th><th>Order Date</th><th>Total Amount</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['total_amount'] == 0) ? "style='background-color: #ffcccc;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['prescription_id']}</td>";
        echo "<td>{$row['pharmacist_id']}</td>";
        echo "<td>{$row['order_date']}</td>";
        echo "<td><strong>₱" . number_format($row['total_amount'], 2) . "</strong></td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:red;'>Red rows indicate total_amount = 0</p>";
} else {
    echo "<p style='color:orange;'>⚠ No prescription orders found! The pharmacist needs to create prescription orders first.</p>";
}

// Check purchase order items
echo "<h3>Prescription Order Items:</h3>";
$result = $conn->query("SELECT poi.*, po.prescription_id 
                        FROM prescription_order_items poi 
                        JOIN prescription_orders po ON poi.order_id = po.id 
                        WHERE po.prescription_id IN (5, 6)
                        ORDER BY po.prescription_id, poi.id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Prescription ID</th><th>Order ID</th><th>Medicine</th><th>Generic</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['prescription_id']}</td>";
        echo "<td>{$row['order_id']}</td>";
        echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['generic_name'] ?? '-') . "</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>₱" . number_format($row['unit_price'], 2) . "</td>";
        echo "<td><strong>₱" . number_format($row['amount'], 2) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>⚠ No prescription order items found!</p>";
}

// Check what the assistant's query would return
echo "<h3>Assistant's Query Result (Processing prescriptions):</h3>";
$result = $conn->query("SELECT p.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name, o.total_amount
    FROM prescriptions p
    LEFT JOIN users u ON p.customer_id = u.id
    LEFT JOIN prescription_orders o ON o.prescription_id = p.id
    WHERE p.status = 'Processing' AND p.id IN (5, 6)
    ORDER BY p.created_at DESC");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Customer</th><th>Status</th><th>Total Amount</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['customer_name'] ?? '-') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td><strong>₱" . number_format($row['total_amount'] ?? 0, 2) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>⚠ No prescriptions with status 'Processing' found for IDs 5 and 6!</p>";
}

echo "<hr>";
echo "<h3>Diagnosis:</h3>";
echo "<ul>";
echo "<li>If no prescription orders exist: The pharmacist needs to process the prescriptions first and set prices.</li>";
echo "<li>If prescription orders exist but total_amount = 0: The pharmacist didn't set unit prices when creating the order.</li>";
echo "<li>If status is not 'Processing': The prescription is in the wrong state for dispensing.</li>";
echo "</ul>";

echo "<p><a href='Users/pharmacist/prescriptions.php'>Go to Pharmacist Prescriptions</a></p>";
echo "<p><a href='Users/assistant/dispense_product.php'>Go to Assistant Dispense</a></p>";
?>
