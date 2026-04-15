# Requisition Display Fix - Summary

## Problem
Technician-created requisitions were not appearing in the Pharmacist's "Manage Requisitions" page.

## Root Cause
The `requisitions` table was missing two critical columns:
1. `department` - Required by the create requisition form
2. `date_required` - Required by the create requisition form

When technicians submitted requisitions, the INSERT query was failing silently because these columns didn't exist in the database.

## Solution Applied

### 1. Database Schema Updates
- Added `department` column (VARCHAR(100), default 'Pharmacy')
- Added `date_required` column (DATE, nullable)
- Updated `database/purchase_order_tables.sql` to include these columns for future installations

### 2. Model Updates
- Updated `createRequisition()` method in `models/purchase_order.php` to properly insert department and date_required values
- Added ORDER BY clause to `getAllRequisitionsWithFilter()` to show newest requisitions first

### 3. Files Modified
- `models/purchase_order.php` - Updated createRequisition method
- `database/purchase_order_tables.sql` - Updated schema definition
- Created `fix_requisitions_department.php` - Migration script to add missing columns

## Verification
After running the fix:
- 4 submitted requisitions from technician "Jasmine Duran" are now visible
- Total requisitions: 5 (4 Submitted, 1 Approved)
- All requisitions display correctly with department information

## Testing
Run `test_requisitions_display.php` to verify requisitions are being retrieved correctly.

## Next Steps
1. Navigate to the Pharmacist's dashboard
2. Click on "Manage Requisitions"
3. You should now see all submitted requisitions from technicians
4. You can approve/reject them as needed

## Database Changes Applied
```sql
ALTER TABLE requisitions 
ADD COLUMN department VARCHAR(100) DEFAULT 'Pharmacy' AFTER pharmacist_name;

ALTER TABLE requisitions 
ADD COLUMN date_required DATE NULL AFTER requisition_date;
```

The fix has been successfully applied and requisitions should now flow properly from Technician → Pharmacist.
