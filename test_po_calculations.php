<?php
require_once 'config.php';

echo "<h1>Test PO Calculations</h1>";

// Test with sample data
$subtotal = 3000.00;

echo "<h2>Sample PO Calculations (Subtotal: ₱" . number_format($subtotal, 2) . ")</h2>";

// Tax calculation (12% VAT)
$tax = $subtotal * 0.12;
echo "<p><strong>Tax (12%):</strong> ₱" . number_format($tax, 2) . "</p>";

// Shipping calculation (₱150 flat or 5% of subtotal, whichever is higher)
$flatShipping = 150;
$percentShipping = $subtotal * 0.05;
$shipping = max($flatShipping, $percentShipping);
echo "<p><strong>Shipping:</strong> ₱" . number_format($shipping, 2) . " (₱" . number_format($flatShipping, 2) . " flat vs ₱" . number_format($percentShipping, 2) . " 5%)</p>";

// Other costs (₱50 flat fee)
$other = 50.00;
echo "<p><strong>Other Costs:</strong> ₱" . number_format($other, 2) . " (Processing fee)</p>";

// Grand total
$grandTotal = $subtotal + $tax + $shipping + $other;
echo "<p><strong>Grand Total:</strong> ₱" . number_format($grandTotal, 2) . "</p>";

echo "<h3>Calculation Summary:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Component</th><th>Formula</th><th>Amount</th></tr>";
echo "<tr><td>Subtotal</td><td>Item totals</td><td>₱" . number_format($subtotal, 2) . "</td></tr>";
echo "<tr><td>Tax</td><td>12% of subtotal</td><td>₱" . number_format($tax, 2) . "</td></tr>";
echo "<tr><td>Shipping</td><td>max(₱150, 5% of subtotal)</td><td>₱" . number_format($shipping, 2) . "</td></tr>";
echo "<tr><td>Other Costs</td><td>Flat processing fee</td><td>₱" . number_format($other, 2) . "</td></tr>";
echo "<tr><td><strong>Grand Total</strong></td><td>Sum of all components</td><td><strong>₱" . number_format($grandTotal, 2) . "</strong></td></tr>";
echo "</table>";

echo "<h3>Test Different Subtotals:</h3>";
$testSubtotals = [500, 1000, 2000, 5000, 10000];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Subtotal</th><th>Tax (12%)</th><th>Shipping</th><th>Other</th><th>Grand Total</th></tr>";

foreach ($testSubtotals as $sub) {
    $testTax = $sub * 0.12;
    $testShipping = max(150, $sub * 0.05);
    $testOther = 50;
    $testGrand = $sub + $testTax + $testShipping + $testOther;
    
    echo "<tr>";
    echo "<td>₱" . number_format($sub, 2) . "</td>";
    echo "<td>₱" . number_format($testTax, 2) . "</td>";
    echo "<td>₱" . number_format($testShipping, 2) . "</td>";
    echo "<td>₱" . number_format($testOther, 2) . "</td>";
    echo "<td><strong>₱" . number_format($testGrand, 2) . "</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='Users/technician/create_po.php'>Test PO Creation Form</a></p>";
?>
