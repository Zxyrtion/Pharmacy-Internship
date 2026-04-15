# Technician Requisitions View Feature

## Overview
Added a complete feature for technicians to view and track their submitted requisitions.

## New Files Created

### 1. Users/technician/my_requisitions.php
- Main page for viewing all requisitions submitted by the technician
- Features:
  - Statistics dashboard showing Total, Submitted, Approved, Rejected, and Processed counts
  - Filter by status (Draft, Submitted, Approved, Rejected, Processed)
  - Table view with all requisition details
  - Color-coded status and urgency badges
  - Links to view individual requisition details

### 2. Users/technician/view_requisition.php
- Detailed view of a specific requisition
- Features:
  - Complete requisition information (ID, department, dates, urgency, status)
  - Full items table with medicine names, quantities, prices, and suppliers
  - Total amount calculation
  - Status-specific alerts (Pending, Approved, Rejected, Processed)
  - Print functionality
  - Security: Only allows viewing own requisitions

### 3. Model Updates (models/purchase_order.php)
Added new methods:
- `getRequisitionsByUserId($user_id)` - Get all requisitions for a specific user
- `getUserRequisitionStats($user_id)` - Get statistics for a specific user's requisitions

### 4. Dashboard Updates (Users/technician/dashboard.php)
- Added "My Requisitions" card in the feature grid
- Added requisition statistics section showing counts at a glance
- Quick link to view all requisitions

## Features

### Statistics Dashboard
Shows real-time counts of:
- Total requisitions
- Submitted (pending approval)
- Approved (awaiting PO generation)
- Rejected
- Processed (PO generated)

### Filtering
- Filter requisitions by status
- Clear filter option to view all

### Status Tracking
Color-coded badges for easy identification:
- Gray: Submitted
- Green: Approved
- Red: Rejected
- Blue: Processed
- Yellow: Draft

### Urgency Levels
- Green: Normal
- Yellow: Urgent
- Red: Critical

### Security
- Users can only view their own requisitions
- Automatic redirect if trying to access another user's requisition

## User Flow

1. Technician logs in
2. Dashboard shows requisition statistics
3. Click "My Requisitions" or "View All" button
4. View list of all submitted requisitions
5. Filter by status if needed
6. Click "View" to see detailed requisition information
7. Print requisition if needed

## Navigation

From Technician Dashboard:
- "Request Stocks" → Create new requisition
- "My Requisitions" → View all requisitions
- Statistics section → Quick overview with "View All" link

From My Requisitions page:
- Click "View" → See requisition details
- Click "Create New Requisition" → Submit new request
- Click "Back to Dashboard" → Return to dashboard

From View Requisition page:
- Click "Print" → Print requisition
- Click "Back to My Requisitions" → Return to list

## Database Schema
No changes required - uses existing tables:
- `requisitions` - Main requisition data
- `requisition_items` - Items in each requisition
- `users` - User information

## Testing
Run `test_technician_requisitions.php` to verify:
- Requisitions are retrieved correctly
- Statistics are calculated properly
- Items are displayed correctly

## Benefits
1. Full transparency for technicians on requisition status
2. Easy tracking of pending, approved, and rejected requests
3. Historical record of all submissions
4. Print capability for record-keeping
5. No need to ask pharmacist for status updates
