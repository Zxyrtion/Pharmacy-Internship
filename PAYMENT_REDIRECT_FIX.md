# Payment Redirect Bug Fix

## Issue
**Problem:** Clicking "Pay Now" button redirects to dashboard instead of showing the payment page.

**Error Behavior:**
- User clicks "Pay Now" on Track Dispensing page
- Gets redirected to dashboard.php
- No error message shown
- Cannot proceed with payment

## Root Cause

The issue was caused by an ID mismatch:

1. **track_dispensing.php** was passing `prescription_id` (string like "RX-20260415-2305")
   ```php
   <a href="payment.php?rx_id=<?= $rx['prescription_id'] ?>">Pay Now</a>
   ```

2. **payment.php** was expecting numeric `id` (integer like 5 or 6)
   ```php
   $rx_id = (int)($_GET['rx_id'] ?? 0);  // This converts "RX-20260415-2305" to 0
   if (!$rx_id) { header('Location: dashboard.php'); exit(); }
   ```

3. When the string prescription_id was cast to int, it became 0, triggering the redirect

## Solution Applied

### 1. Updated payment.php to Accept Both ID Formats

**File:** `Users/customer/payment.php`

```php
$rx_id = $_GET['rx_id'] ?? '';
if (!$rx_id) { header('Location: dashboard.php'); exit(); }

// Check if rx_id is numeric (database id) or string (prescription_id)
if (is_numeric($rx_id)) {
    // It's a database ID
    $s = $conn->prepare("SELECT p.* FROM prescriptions p WHERE p.id=? AND p.customer_id=?");
    $s->bind_param('ii', $rx_id, $_SESSION['user_id']);
} else {
    // It's a prescription_id string (like RX-20260415-2305)
    $s = $conn->prepare("SELECT p.* FROM prescriptions p WHERE p.prescription_id=? AND p.customer_id=? LIMIT 1");
    $s->bind_param('si', $rx_id, $_SESSION['user_id']);
}
$s->execute();
$rx = $s->get_result()->fetch_assoc();

if (!$rx) { 
    header('Location: dashboard.php'); 
    exit(); 
}

// Get the numeric ID for later use
$rx_numeric_id = (int)$rx['id'];

// Check if prescription is ready for payment
if ($rx['status'] !== 'Ready') { 
    // Redirect to track dispensing with a message
    header('Location: track_dispensing.php?error=not_ready'); 
    exit(); 
}
```

### 2. Updated track_dispensing.php to Pass Numeric ID

**File:** `Users/customer/track_dispensing.php`

Changed from:
```php
<a href="payment.php?rx_id=<?= htmlspecialchars($rx['prescription_id']) ?>">Pay Now</a>
```

To:
```php
<a href="payment.php?rx_id=<?= $rx['id'] ?>">Pay Now</a>
```

### 3. Added Error Message Display

**File:** `Users/customer/track_dispensing.php`

```php
<?php if (isset($_GET['error']) && $_GET['error'] === 'not_ready'): ?>
    <div class="alert alert-warning mt-2">
        <i class="bi bi-exclamation-triangle"></i> This prescription is not ready for payment yet. 
        Please wait for the pharmacy to dispense your medicines.
    </div>
<?php endif; ?>
```

## Benefits of the Fix

1. **Flexible ID Handling:** Payment page now accepts both numeric ID and prescription_id string
2. **Better User Experience:** Clear error messages instead of silent redirects
3. **Backward Compatible:** Works with existing links that use either ID format
4. **Proper Error Handling:** Redirects to appropriate page with helpful message

## Testing

### Test Cases:
1. ✓ Click "Pay Now" with numeric ID → Shows payment page
2. ✓ Click "Pay Now" with string prescription_id → Shows payment page
3. ✓ Try to pay for non-Ready prescription → Shows error message
4. ✓ Try to pay for non-existent prescription → Redirects to dashboard
5. ✓ Complete payment flow → Updates status correctly

### Test Script:
Run `test_payment_redirect_fix.php` to verify the fix

## Files Modified

1. `Users/customer/payment.php` - Updated ID handling logic
2. `Users/customer/track_dispensing.php` - Updated Pay Now link and added error display

## Related Issues Fixed

- Payment page now properly validates prescription status
- Better error messages for users
- Consistent ID usage across the application

## Verification

Current test results show:
- ✓ 2 prescriptions ready for payment (IDs 5 and 6)
- ✓ Both numeric and string ID formats work
- ✓ Payment page loads correctly
- ✓ No unexpected redirects

## Next Steps

Users can now:
1. View prescriptions on Track Dispensing page
2. Click "Pay Now" for Ready prescriptions
3. Complete payment via mock payment system
4. See prescription status update to "Dispensed"
