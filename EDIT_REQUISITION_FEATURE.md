# Edit Requisition Feature Documentation

## Overview
Added complete CRUD (Create, Read, Update, Delete) functionality for technician requisitions, allowing them to edit and delete their requisitions under certain conditions.

## New Features

### 1. Edit Requisition
Technicians can now edit their requisitions that are in **Draft** or **Submitted** status.

#### File: `Users/technician/edit_requisition.php`
- Full form pre-populated with existing requisition data
- Edit all fields: department, dates, urgency, reason, and items
- Add or remove items dynamically
- Real-time total calculation
- Validation to ensure at least one item exists
- Security: Only allows editing own requisitions
- Status restriction: Only Draft or Submitted can be edited

#### Features:
- Pre-filled form with current requisition data
- Dynamic item management (add/remove rows)
- Automatic total calculation
- Supplier dropdown selection
- Date validation
- Success/error messaging
- Redirect to view page after successful update

### 2. Delete Requisition
Technicians can delete requisitions that are in **Draft** status only.

#### Features:
- Delete button available only for Draft requisitions
- Confirmation modal before deletion
- Cascading delete (removes items and requisition)
- Security: Only allows deleting own requisitions
- Success/error messaging

### 3. Model Updates

#### New Methods in `models/purchase_order.php`:

**updateRequisitionWithItems($requisition_id, $department, $requisition_date, $date_required, $urgency, $reason, $items)**
- Updates requisition header information
- Deletes existing items
- Inserts updated items
- Recalculates total amount
- Uses transaction for data integrity
- Returns success status and new total

**deleteRequisition($requisition_id)**
- Deletes all requisition items
- Deletes the requisition record
- Uses transaction for data integrity
- Returns success status

### 4. UI Updates

#### My Requisitions Page (`my_requisitions.php`)
- Added "Edit" button for Draft and Submitted requisitions
- Added "Delete" button for Draft requisitions
- Delete confirmation modal
- Success/error message display

#### View Requisition Page (`view_requisition.php`)
- Added "Edit" button (visible for Draft/Submitted)
- Added "Delete" button (visible for Draft only)
- Delete confirmation modal
- Success/error message display from edit operations

## Business Rules

### Edit Permissions
- **Allowed Status**: Draft, Submitted
- **Not Allowed**: Approved, Rejected, Processed
- **Reason**: Once approved or processed, requisitions are part of the procurement workflow and shouldn't be modified

### Delete Permissions
- **Allowed Status**: Draft only
- **Not Allowed**: Submitted, Approved, Rejected, Processed
- **Reason**: Once submitted, requisitions are in the approval workflow and should be tracked

### Security
- Users can only edit/delete their own requisitions
- Ownership verified via `pharmacist_id` field
- Status checked before allowing operations
- Automatic redirect if unauthorized access attempted

## User Flow

### Edit Flow
1. Technician views requisition list
2. Clicks "Edit" button (visible for Draft/Submitted)
3. Form loads with current data
4. Makes changes to fields or items
5. Clicks "Update Requisition"
6. System validates and saves changes
7. Redirects to view page with success message

### Delete Flow
1. Technician views requisition (Draft status)
2. Clicks "Delete" button
3. Confirmation modal appears
4. Confirms deletion
5. System deletes requisition and items
6. Redirects to list page with success message

## Database Operations

### Update Operation
```sql
-- Transaction starts
UPDATE requisitions SET department=?, requisition_date=?, date_required=?, urgency=?, total_amount=?, notes=? WHERE id=?
DELETE FROM requisition_items WHERE requisition_id=?
INSERT INTO requisition_items (requisition_id, medicine_name, ...) VALUES (?, ?, ...)
-- Transaction commits
```

### Delete Operation
```sql
-- Transaction starts
DELETE FROM requisition_items WHERE requisition_id=?
DELETE FROM requisitions WHERE id=?
-- Transaction commits
```

## Error Handling

### Edit Errors
- Requisition not found → Redirect to list
- Not owned by user → Redirect to list
- Invalid status → Redirect to view with error
- No items provided → Show error message
- Database error → Rollback transaction, show error

### Delete Errors
- Requisition not found → Error message
- Not owned by user → Error message
- Invalid status → Error message
- Database error → Rollback transaction, error message

## Testing

### Test File: `test_edit_requisition.php`
Tests the update functionality:
- Retrieves a submitted requisition
- Displays current items
- Updates item quantities
- Verifies the update in database
- Confirms total amount recalculation

### Test Results
```
✓ Update successful!
✓ Total Amount recalculated correctly
✓ Items updated in database
✓ Transaction integrity maintained
```

## UI Components

### Buttons
- **Edit Button** (Warning/Yellow): Visible for Draft and Submitted
- **Delete Button** (Danger/Red): Visible for Draft only
- **View Button** (Info/Blue): Always visible

### Modals
- **Delete Confirmation Modal**: Prevents accidental deletion
  - Shows requisition ID
  - Warning message
  - Confirm/Cancel buttons

### Alerts
- **Success Alert** (Green): Operation completed successfully
- **Error Alert** (Red): Operation failed with reason

## Status-Based Permissions Matrix

| Status     | View | Edit | Delete | Approve | Generate PO |
|------------|------|------|--------|---------|-------------|
| Draft      | ✓    | ✓    | ✓      | ✗       | ✗           |
| Submitted  | ✓    | ✓    | ✗      | ✓       | ✗           |
| Approved   | ✓    | ✗    | ✗      | ✗       | ✓           |
| Rejected   | ✓    | ✗    | ✗      | ✗       | ✗           |
| Processed  | ✓    | ✗    | ✗      | ✗       | ✗           |

## Benefits

1. **Flexibility**: Technicians can correct mistakes before approval
2. **Efficiency**: No need to create new requisitions for minor changes
3. **Data Integrity**: Transaction-based updates ensure consistency
4. **User Control**: Draft management allows preparation before submission
5. **Audit Trail**: Updated_at timestamp tracks modifications
6. **Safety**: Confirmation modals prevent accidental deletions

## Future Enhancements

Potential improvements:
1. Change history/audit log
2. Reason for edit field
3. Email notification on edit
4. Bulk delete for drafts
5. Clone/duplicate requisition feature
6. Draft auto-save functionality
7. Version comparison view

## Files Modified

1. `models/purchase_order.php` - Added update and delete methods
2. `Users/technician/my_requisitions.php` - Added edit/delete buttons and handlers
3. `Users/technician/view_requisition.php` - Added edit/delete buttons and handlers
4. `Users/technician/edit_requisition.php` - New file for editing

## Files Created

1. `Users/technician/edit_requisition.php` - Edit form page
2. `test_edit_requisition.php` - Testing script
3. `EDIT_REQUISITION_FEATURE.md` - This documentation
