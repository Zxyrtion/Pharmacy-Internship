<?php
require_once 'config.php';

echo "<h1>Debug Inventory Fields</h1>";

// Test the JavaScript logic by simulating it
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Inventory Fields</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Testing Field Visibility</h2>
        
        <div class="table-responsive">
            <table class="table table-bordered" id="testTable">
                <thead>
                    <tr>
                        <th>Reorder Status</th>
                        <th>Item Number</th>
                        <th>Cost Per Item</th>
                        <th>Stock Qty</th>
                        <th>Inventory Value</th>
                        <th>Reorder Point</th>
                        <th>Item Reorder Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="text" class="form-control form-control-sm bg-light" name="reorder_required[]" readonly>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="item_number_name[]" value="Test Item" required></td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control cost-input" name="cost_per_item[]" value="10.00" required>
                            </div>
                        </td>
                        <td><input type="number" class="form-control form-control-sm stock-input" name="stock_quantity[]" value="50" required></td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="text" class="form-control bg-light value-input" value="500.00" readonly>
                            </div>
                        </td>
                        <td><input type="number" class="form-control form-control-sm bg-light" name="reorder_point[]" readonly></td>
                        <td><input type="number" class="form-control form-control-sm bg-light" name="item_reorder_quantity[]" readonly></td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" class="form-control form-control-sm bg-light" name="reorder_required[]" readonly>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="item_number_name[]" value="Test Item 2" required></td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control cost-input" name="cost_per_item[]" value="15.00" required>
                            </div>
                        </td>
                        <td><input type="number" class="form-control form-control-sm stock-input" name="stock_quantity[]" value="120" required></td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="text" class="form-control bg-light value-input" value="1800.00" readonly>
                            </div>
                        </td>
                        <td><input type="number" class="form-control form-control-sm bg-light" name="reorder_point[]" readonly></td>
                        <td><input type="number" class="form-control form-control-sm bg-light" name="item_reorder_quantity[]" readonly></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-primary" onclick="testLogic()">Test Logic</button>
            <button type="button" class="btn btn-secondary" onclick="clearFields()">Clear Fields</button>
        </div>

        <div id="results"></div>
    </div>

    <script>
        function calculateTotals() {
            const tableBody = document.querySelector('#testTable tbody');
            const rows = tableBody.querySelectorAll('tr');
            
            rows.forEach((row, index) => {
                const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
                const stock = parseFloat(row.querySelector('.stock-input').value) || 0;
                const reorderSelect = row.querySelector('input[name="reorder_required[]"]');
                const reorderQtyInput = row.querySelector('input[name="item_reorder_quantity[]"]');
                const reorderPointInput = row.querySelector('input[name="reorder_point[]"]');
                
                // Calculate inventory value
                row.querySelector('.value-input').value = (cost * stock).toFixed(2);
                
                console.log(`Row ${index + 1}: Stock=${stock}, Reorder Select=${reorderSelect ? reorderSelect.value : 'HIDDEN'}`);
                
                // Auto-check reorder logic
                if (stock < 100) {
                    console.log(`Row ${index + 1}: Setting reorder to YES`);
                    // Stock is below reorder point - automatically set reorder to Yes
                    reorderSelect.value = 'Yes';
                    
                    // Set reorder point to 100 if not already set
                    if (!reorderPointInput.value || parseFloat(reorderPointInput.value) === 0) {
                        reorderPointInput.value = '100';
                        console.log(`Row ${index + 1}: Set reorder point to 100`);
                    }
                    
                    // Calculate reorder quantity to reach minimum 200 units
                    const neededToReach200 = Math.max(0, 200 - stock);
                    if (!reorderQtyInput.value || parseFloat(reorderQtyInput.value) === 0) {
                        reorderQtyInput.value = neededToReach200;
                        console.log(`Row ${index + 1}: Set reorder qty to ${neededToReach200}`);
                    }
                } else {
                    console.log(`Row ${index + 1}: Setting reorder to NO`);
                    // Stock is sufficient - automatically set reorder to No
                    if (!reorderSelect.value || reorderSelect.value === 'No') {
                        reorderSelect.value = 'No';
                        console.log(`Row ${index + 1}: Set reorder status to No`);
                    }
                    
                    // Set reorder point to 100 if not already set
                    if (!reorderPointInput.value || parseFloat(reorderPointInput.value) === 0) {
                        reorderPointInput.value = '100';
                        console.log(`Row ${index + 1}: Set reorder point to 100`);
                    }
                }
            });
        }

        function testLogic() {
            console.log('Testing logic...');
            calculateTotals();
            showResults();
        }

        function clearFields() {
            document.querySelectorAll('.stock-input').forEach(input => input.value = '');
            document.querySelectorAll('input[name="reorder_point[]"]').forEach(input => input.value = '');
            document.querySelectorAll('input[name="item_reorder_quantity[]"]').forEach(input => input.value = '');
            document.querySelectorAll('input[name="reorder_required[]"]').forEach(input => input.value = '');
            setTimeout(() => {
                calculateTotals();
                showResults();
            }, 100);
        }

        function showResults() {
            const results = document.getElementById('results');
            const rows = document.querySelectorAll('#testTable tbody tr');
            let html = '<h3 class="mt-4">Test Results:</h3>';
            
            rows.forEach((row, index) => {
                const stock = parseFloat(row.querySelector('.stock-input').value) || 0;
                const reorderSelect = row.querySelector('input[name="reorder_required[]"]');
                const reorderQtyInput = row.querySelector('input[name="item_reorder_quantity[]"]');
                const reorderPointInput = row.querySelector('input[name="reorder_point[]"]');
                
                const needsReorder = stock < 100;
                const expectedReorder = needsReorder ? 'Yes' : 'No';
                const expectedReorderQty = needsReorder ? Math.max(0, 200 - stock) : 0;
                const expectedReorderPoint = 100;
                
                const reorderCorrect = reorderSelect.value === expectedReorder;
                const qtyCorrect = parseInt(reorderQtyInput.value) === expectedReorderQty;
                const pointCorrect = parseInt(reorderPointInput.value) === expectedReorderPoint;
                
                console.log(`Row ${index + 1} Results:`, {
                    stock,
                    reorderStatus: reorderSelect.value,
                    expectedReorder,
                    reorderCorrect,
                    reorderQty: reorderQtyInput.value,
                    expectedReorderQty,
                    qtyCorrect,
                    reorderPoint: reorderPointInput.value,
                    expectedReorderPoint,
                    pointCorrect
                });
                
                html += `<div class="alert ${reorderCorrect && qtyCorrect && pointCorrect ? 'alert-success' : 'alert-danger'}">`;
                html += `<strong>Item ${index + 1}:</strong><br>`;
                html += `Stock: ${stock} units<br>`;
                html += `Reorder Status: "${reorderSelect.value}" ${reorderCorrect ? '✓' : '✗'} (Expected: "${expectedReorder}")<br>`;
                html += `Reorder Point: ${reorderPointInput.value} ${pointCorrect ? '✓' : '✗'} (Expected: ${expectedReorderPoint})<br>`;
                html += `Reorder Qty: ${reorderQtyInput.value} ${qtyCorrect ? '✓' : '✗'} (Expected: ${expectedReorderQty})<br>`;
                html += `</div>`;
            });
            
            results.innerHTML = html;
        }

        // Run test on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                calculateTotals();
                showResults();
            }, 500);
        });

        // Listen for input changes
        document.querySelector('#testTable tbody').addEventListener('input', function(e) {
            if (e.target.classList.contains('cost-input') || e.target.classList.contains('stock-input')) {
                calculateTotals();
            }
        });
    </script>
</body>
</html>
