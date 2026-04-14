<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Inventory Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-result { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Inventory Form Reorder Logic Test</h1>
        
        <div class="alert alert-info">
            <h5>Expected Behavior:</h5>
            <ul>
                <li>Stock < 100 → Reorder = "Yes"</li>
                <li>Stock ≥ 100 → Reorder = "No"</li>
                <li>Reorder Point = 100 (auto-set)</li>
                <li>Reorder Qty = 200 - Stock (if reorder needed)</li>
            </ul>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="testTable">
                <thead>
                    <tr>
                        <th>Reorder?</th>
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
                            <select class="form-select form-select-sm" name="reorder_required[]">
                                <option value="Yes">Yes</option>
                                <option value="No" selected>No</option>
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="item_number_name[]" value="Test Item"></td>
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
                        <td><input type="number" class="form-control form-control-sm" name="reorder_point[]" value=""></td>
                        <td><input type="number" class="form-control form-control-sm" name="item_reorder_quantity[]" value=""></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-select form-select-sm" name="reorder_required[]">
                                <option value="Yes">Yes</option>
                                <option value="No" selected>No</option>
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="item_number_name[]" value="Test Item 2"></td>
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
                        <td><input type="number" class="form-control form-control-sm" name="reorder_point[]" value=""></td>
                        <td><input type="number" class="form-control form-control-sm" name="item_reorder_quantity[]" value=""></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-primary" onclick="testReorderLogic()">Test Reorder Logic</button>
            <button type="button" class="btn btn-secondary" onclick="clearAndTest()">Clear & Test</button>
        </div>

        <div id="results"></div>

        <div class="mt-4">
            <a href="Users/intern/inventory_report.php" class="btn btn-success">Go to Actual Inventory Form</a>
        </div>
    </div>

    <script>
        function calculateTotals() {
            const tableBody = document.querySelector('#testTable tbody');
            const rows = tableBody.querySelectorAll('tr');
            
            rows.forEach((row, index) => {
                const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
                const stock = parseFloat(row.querySelector('.stock-input').value) || 0;
                const reorderSelect = row.querySelector('select[name="reorder_required[]"]');
                const reorderQtyInput = row.querySelector('input[name="item_reorder_quantity[]"]');
                const reorderPointInput = row.querySelector('input[name="reorder_point[]"]');
                
                // Calculate inventory value
                row.querySelector('.value-input').value = (cost * stock).toFixed(2);
                
                // Auto-check reorder logic
                if (stock < 100) {
                    // Stock is below reorder point - automatically set reorder to Yes
                    reorderSelect.value = 'Yes';
                    
                    // Set reorder point to 100 if not already set
                    if (!reorderPointInput.value || parseFloat(reorderPointInput.value) === 0) {
                        reorderPointInput.value = '100';
                    }
                    
                    // Calculate reorder quantity to reach minimum 200 units
                    const neededToReach200 = Math.max(0, 200 - stock);
                    if (!reorderQtyInput.value || parseFloat(reorderQtyInput.value) === 0) {
                        reorderQtyInput.value = neededToReach200;
                    }
                } else {
                    // Stock is sufficient - automatically set reorder to No
                    if (!reorderSelect.value || reorderSelect.value === 'No') {
                        reorderSelect.value = 'No';
                    }
                    
                    // Set reorder point to 100 if not already set
                    if (!reorderPointInput.value || parseFloat(reorderPointInput.value) === 0) {
                        reorderPointInput.value = '100';
                    }
                }
            });
        }

        function testReorderLogic() {
            calculateTotals();
            showResults();
        }

        function clearAndTest() {
            document.querySelectorAll('.stock-input').forEach(input => input.value = '');
            document.querySelectorAll('input[name="reorder_point[]"]').forEach(input => input.value = '');
            document.querySelectorAll('input[name="item_reorder_quantity[]"]').forEach(input => input.value = '');
            document.querySelectorAll('select[name="reorder_required[]"]').forEach(select => select.value = 'No');
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
                const reorderSelect = row.querySelector('select[name="reorder_required[]"]');
                const reorderQtyInput = row.querySelector('input[name="item_reorder_quantity[]"]');
                const reorderPointInput = row.querySelector('input[name="reorder_point[]"]');
                
                const needsReorder = stock < 100;
                const expectedReorder = needsReorder ? 'Yes' : 'No';
                const expectedReorderQty = needsReorder ? Math.max(0, 200 - stock) : 0;
                
                const reorderCorrect = reorderSelect.value === expectedReorder;
                const qtyCorrect = parseInt(reorderQtyInput.value) === expectedReorderQty;
                const pointCorrect = parseInt(reorderPointInput.value) === 100;
                
                html += `<div class="test-result ${reorderCorrect && qtyCorrect && pointCorrect ? 'success' : 'info'}">`;
                html += `<strong>Item ${index + 1}:</strong><br>`;
                html += `Stock: ${stock} units<br>`;
                html += `Reorder Status: ${reorderSelect.value} ${reorderCorrect ? '✓' : '✗'} (Expected: ${expectedReorder})<br>`;
                html += `Reorder Point: ${reorderPointInput.value} ${pointCorrect ? '✓' : '✗'} (Expected: 100)<br>`;
                html += `Reorder Qty: ${reorderQtyInput.value} ${qtyCorrect ? '✓' : '✗'} (Expected: ${expectedReorderQty})<br>`;
                html += `Stock After Reorder: ${stock + parseInt(reorderQtyInput.value)} units`;
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
