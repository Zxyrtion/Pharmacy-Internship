# Payment System Setup Guide

## Overview

The payment system is now configured to work in **Development Mode** by default, using mock payments for testing. This allows you to test the complete prescription workflow without requiring actual PayMongo API keys.

## Current Setup (Development Mode)

### What's Working:
- ✓ Payment page loads without errors
- ✓ Mock payment flow for testing
- ✓ Complete prescription workflow (Submit → Process → Dispense → Pay)
- ✓ Payment success confirmation
- ✓ Prescription status updates to "Dispensed" after payment

### Files Created/Updated:
1. `core/paymongo.php` - Payment integration with mock mode support
2. `Users/customer/payment.php` - Payment page (updated)
3. `Users/customer/payment_mock.php` - Mock payment page for testing
4. `Users/customer/payment_success.php` - Payment confirmation page (updated)

## How to Test Payment Flow

### Step 1: Submit a Prescription
1. Login as Customer
2. Go to "Submit Prescription"
3. Fill in prescription details
4. Submit

### Step 2: Process Prescription (Pharmacist)
1. Login as Pharmacist
2. Go to "Customer Prescriptions"
3. View the prescription
4. Create Purchase Order

### Step 3: Dispense Medicines (Assistant)
1. Login as Pharmacy Assistant
2. Go to "Dispense Product"
3. Select the prescription
4. Confirm dispensing

### Step 4: Pay for Prescription (Customer)
1. Login as Customer
2. Go to "Track Dispensing"
3. Click "Pay Now" on the Ready prescription
4. Click "Pay Securely via PayMongo"
5. On the mock payment page, click "Simulate Successful Payment"
6. You'll see the success confirmation

### Step 5: Verify Completion
1. Check "Track Dispensing" - should show Step 4 (Dispensed)
2. Check Product Logs - should show the dispensed items

## Switching to Production (Real PayMongo)

When you're ready to use real PayMongo payments:

### 1. Get PayMongo API Keys
- Sign up at https://dashboard.paymongo.com/
- Go to Developers section
- Copy your Secret Key and Public Key

### 2. Create Configuration File
Create `core/paymongo.config.php`:

```php
<?php
// PayMongo Production Configuration
define('PAYMONGO_SECRET_KEY', 'sk_live_YOUR_SECRET_KEY_HERE');
define('PAYMONGO_PUBLIC_KEY', 'pk_live_YOUR_PUBLIC_KEY_HERE');
define('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1');
define('APP_BASE_URL', 'https://yourdomain.com/internship');
?>
```

### 3. Test with PayMongo Test Keys First
Use test keys before going live:
```php
define('PAYMONGO_SECRET_KEY', 'sk_test_YOUR_TEST_KEY');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_YOUR_TEST_KEY');
```

### 4. Update APP_BASE_URL
Change the base URL to match your production domain.

## Payment Methods Supported

When using real PayMongo:
- GCash
- PayMaya
- Credit/Debit Cards
- Bank Transfers (depending on your PayMongo account)

## Troubleshooting

### Payment page shows error
- Check that `core/paymongo.php` exists
- Verify database has `payments` table
- Check prescription status is "Ready"

### Mock payment not working
- Ensure you're clicking "Simulate Successful Payment"
- Check that prescription ID is valid
- Verify database connection

### Real PayMongo not working
- Verify API keys are correct
- Check that `core/paymongo.config.php` exists
- Test with test keys first
- Check PayMongo dashboard for errors

## Database Tables

### payments table
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT,
    order_id INT,
    customer_id INT,
    amount_due DECIMAL(10,2),
    payment_method VARCHAR(50) DEFAULT 'paymongo',
    paymongo_session_id VARCHAR(200) NULL,
    paymongo_payment_id VARCHAR(200) NULL,
    status ENUM('Pending','Paid','Failed') DEFAULT 'Pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Security Notes

- Never commit `paymongo.config.php` to version control
- Keep API keys secure
- Use test keys for development
- Use live keys only in production
- Enable HTTPS in production

## Support

For PayMongo documentation:
- https://developers.paymongo.com/docs

For issues with this implementation:
- Check the error logs
- Verify database structure
- Test with mock mode first
