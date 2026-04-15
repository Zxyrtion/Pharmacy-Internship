# Payment Total Amount Fix

## Issue
**Problem:** Total Amount Due showing ₱0.00 on payment page instead of the actual prescription cost.

## Root Cause

The payment.php file was looking for order data in the wrong tables:

### What payment.php was looking for:
- `purchase_orders` table
- `purchase_order_items` table

### Where the data actually was:
- `prescription_orders` table (4 records)
- `prescription_order_items` table (4 records)

The system has both sets of tables, but the Ready prescriptions had their data in the `prescription_orders` tables, not the `purchase_orders` tables.

## Solution Applied

Updated `Users/customer/payment.php` to check both table sets with a fallback mechanism:

### Before:
```php
$so = $conn->prepare("SELECT * FROM purchase_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
$so->bind_param('i', $rx_numeric_id);
$so->execute();
$order = $so->get_result()->fetch_assoc();

$si = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id=?");
$order_id_val = (int)($order['id'] ?? 0);
$si->bind_param('i', $order_id_val);
$si->execute();
$order_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);

$amount_due = (float)($order['total_amount'] ?? 0);
```

### After:
```php
$so = $conn->prepare("SELECT * FROM purchase_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
$so->bind_param('i', $rx_numeric_id);
$so->execute();
$order = $so->get_result()->fetch_assoc();

// If no purchase_orders, try prescription_orders
if (!$order) {
    $so = $conn->prepare("SELECT * FROM prescription_orders WHERE prescription_id=? ORDER BY id DESC LIMIT 1");
    $so->bind_param('i', $rx_numeric_id);
    $so->execute();
    $order = $so->get_result()->fetch_assoc();
}

$si = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id=?");
$order_id_val = (int)($order['id'] ?? 0);
$si->bind_param('i', $order_id_val);
$si->execute();
$order_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);

// If no purchase_order_items, try prescription_order_items
if (empty($order_items) && $order_id_val > 0) {
    $si = $conn->prepare("SELECT * FROM prescription_order_items WHERE order_id=?");
    $si->bind_param('i', $order_id_val);
    $si->execute();
    $order_items = $si->get_result()->fetch_all(MYSQLI_ASSOC);
}

$amount_due = (float)($order['total_amount'] ?? 0);
```

## Test Results

After the fix, prescriptions now show correct amounts:

| Prescription ID | Patient | Total Amount | Status |
|----------------|---------|--------------|--------|
| RX-20260415-4189 | customer guy | ₱1,500.00 | ✓ OK |
| RX-20260415-2305 | customer guy | ₱30.00 | ✓ OK |

## Table Structure

The system has both table sets:

| Table Name | Exists | Row Count |
|-----------|--------|-----------|
| purchase_orders | ✓ Yes | 1 |
| prescription_orders | ✓ Yes | 4 |
| purchase_order_items | ✓ Yes | 5 |
| prescription_order_items | ✓ Yes | 4 |

## Benefits

1. **Flexible Data Retrieval:** Works with both table naming conventions
2. **Backward Compatible:** Doesn't break existing functionality
3. **Robust:** Handles cases where data might be in either table set
4. **Future-Proof:** Will work regardless of which table is used going forward

## Files Modified

- `Users/customer/payment.php` - Added fallback logic for order data retrieval

## Verification

Run `test_payment_total.php` to verify:
- Total amounts display correctly
- Both table sets are checked
- Fallback mechanism works properly

## Notes

The system appears to have two different table naming conventions for orders:
1. `purchase_orders` / `purchase_order_items` (older or alternative naming)
2. `prescription_orders` / `prescription_order_items` (current naming)

The fix ensures the payment page works with both, providing maximum compatibility.
