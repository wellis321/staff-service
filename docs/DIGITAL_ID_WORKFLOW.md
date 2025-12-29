# Getting Digital ID Cards for Staff Members

This guide explains how staff members created in Staff Service get digital ID cards in the Digital ID application.

## Overview

When a new staff member is created in Staff Service, they need an employee record in Digital ID to generate their digital ID card. The process varies depending on whether Digital ID is integrated with Staff Service.

## Prerequisites: Setting Up Staff Service Integration

Before you can sync staff from Staff Service to Digital ID, you need to configure the integration:

### Step 1: Create API Key in Staff Service

**Method A: Web Interface (Recommended - No technical knowledge required)**

1. **Log in to Staff Service** as an organisation administrator
2. **Navigate to Admin** → **API Keys** (in the Admin dropdown menu)
3. **Click "Create API Key"**
4. **Enter a descriptive name** (e.g., "Digital ID Integration")
5. **Click "Create API Key"**
6. **Copy the API key immediately** - it will only be shown once!
   - The key will be displayed in a yellow warning box
   - Use the "Copy" button or manually select and copy the key
   - Store it securely - you won't be able to see it again

**Method B: Command Line (Alternative - Requires command line access)**

If you prefer using the command line:

1. **Find Your User ID and Organisation ID**:
   - Log in to Staff Service
   - Check the URL when viewing your profile (e.g., `profile.php?id=1` shows user ID 1)
   - Or query the database: `SELECT id, organisation_id, email FROM users WHERE email = 'your-email@example.com';`

2. **Create the API Key**:
   ```bash
   # Navigate to Staff Service directory
   cd /path/to/people-management-service
   
   # Run the create API key script
   # Replace <user_id> and <organisation_id> with your actual IDs
   php scripts/create-api-key.php <user_id> <organisation_id> "Digital ID Integration"
   
   # Example (user_id=3, organisation_id=1):
   php scripts/create-api-key.php 3 1 "Digital ID Integration"
   ```

3. **Save the API Key**:
   - The script will output an API key (64-character hex string)
   - **IMPORTANT**: Save this key securely - it won't be shown again!

### Step 2: Configure Digital ID Settings

**Where to paste the API key:** Copy the API key from Staff Service and configure it in Digital ID's web interface (no need to access `.env` files).

#### Method A: Web Interface (Recommended - No File Access Required)

1. **Log in to Digital ID** as an organisation administrator
2. **Navigate to Admin** → **Organisation** → **Staff Service** (in the dropdown menu)
3. **Enable Staff Service Integration**:
   - Check the "Enable Staff Service Integration" checkbox
   - Enter the **Staff Service URL** (e.g., `http://localhost:8000` or `https://staff.yourdomain.com`)
   - Paste the **API Key** you copied from Staff Service in Step 1
   - Set the **Sync Interval** (default: 3600 seconds = 1 hour)
4. **Test the Connection** (optional):
   - Click "Test Connection" to verify the URL and API key are correct
5. **Save Settings**:
   - Click "Save Settings"
   - Settings are stored in the database and take effect immediately

**That's it!** No need to edit `.env` files or restart the server. The settings are stored in the database and work immediately.

#### Method B: .env File (Alternative - For Server Administrators)

If you prefer to configure via `.env` file:

1. **Locate or Create `.env` File** in Digital ID project root
2. **Add Configuration**:
   ```env
   USE_STAFF_SERVICE=true
   STAFF_SERVICE_URL=http://localhost:8000
   STAFF_SERVICE_API_KEY=your-api-key-from-staff-service-here
   STAFF_SYNC_INTERVAL=3600
   ```
3. **Restart web server** after editing

**Note:** Settings configured via the web interface take precedence over `.env` file settings.

### Troubleshooting Configuration

If you see "Staff Service is not available" when trying to sync:

1. **Check `.env` File**:
   - Verify `USE_STAFF_SERVICE=true` (not `false` or commented out)
   - Verify `STAFF_SERVICE_URL` is correct (no trailing slash)
   - Verify `STAFF_SERVICE_API_KEY` matches the key from Staff Service
   - Check for typos or extra spaces

2. **Test API Key**:
   ```bash
   # Test if API key works (replace with your values)
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        http://localhost:8000/api/staff-data.php
   ```
   - Should return JSON data or a 401/403 error (not 404 or connection error)

3. **Test Network Connectivity**:
   ```bash
   # Test if Digital ID can reach Staff Service
   curl http://localhost:8000/api/staff-data.php
   ```
   - Should return a response (even if it's an authentication error)

4. **Check Error Logs**:
   - Check PHP error logs for connection errors
   - Check Digital ID application logs
   - Check Staff Service logs for API authentication issues

5. **Verify API Key is Active**:
   ```sql
   -- In Staff Service database
   SELECT id, name, user_id, organisation_id, is_active, created_at 
   FROM api_keys 
   WHERE is_active = TRUE 
   ORDER BY created_at DESC;
   ```

## Workflow: Staff Service → Digital ID

### Step 1: Create Staff Member in Staff Service

1. Log in to Staff Service as an administrator
2. Navigate to **Staff** → **Create Staff Member**
3. Fill in staff details:
   - Personal information (name, email, phone)
   - Employee reference (from HR/payroll system)
   - Employment details
   - Link to user account (if they have one)

### Step 2: Ensure User Account Exists

The staff member needs a user account to access Digital ID. This can be:

- **Already exists**: If the staff member was created with a linked user account
- **Created separately**: Staff member registers at Digital ID registration page
- **Created by admin**: Admin creates user account in Digital ID

### Step 3: Create Employee Record in Digital ID

The method depends on whether Staff Service integration is enabled:

#### With Staff Service Integration (Recommended)

**Automatic Method** (Recommended):

1. Go to Digital ID admin panel → **Admin** → **Manage Employees**
2. Click **"Sync from Staff Service"** button
3. This will:
   - Find all staff members in Staff Service
   - Match them with existing users in Digital ID (by email or user_id)
   - Create employee records for matched staff
   - Link employee records to Staff Service person records
   - Sync staff data (name, photo, employee reference, signature)

**Manual Method** (if automatic sync doesn't work):

1. Go to Digital ID admin panel → **Admin** → **Manage Employees**
2. Click **"Create New Employee"**
3. Select the user from the dropdown (staff members with user accounts will appear)
4. Enter the **Employee Number** (must match the employee reference from Staff Service)
5. Optionally enter a **Display Reference** (or leave blank to auto-generate)
6. Click **"Create Employee"**
7. The system will automatically:
   - Link to Staff Service if a matching person is found
   - Sync staff data from Staff Service

#### Without Staff Service Integration (Standalone)

1. Go to Digital ID admin panel → **Admin** → **Manage Employees**
2. Click **"Create New Employee"**
3. Select the user from the dropdown
4. Enter:
   - **Employee Number** (required, from HR/payroll system)
   - **Display Reference** (optional, shown on ID card)
5. Click **"Create Employee"**

### Step 4: Upload Photo (Optional but Recommended)

**Staff Member Self-Service**:
1. Staff member logs into Digital ID
2. Navigates to their profile
3. Clicks **"Upload Photo"**
4. Photo is uploaded and pending admin approval
5. Admin approves photo in **Admin** → **Photo Approvals**

**Admin Upload**:
1. Admin goes to **Admin** → **Manage Employees** → **Edit Employee**
2. Uploads photo directly (immediately approved)

### Step 5: View Digital ID Card

Once the employee record exists:
1. Staff member logs into Digital ID
2. Navigates to **"Digital ID Card"** page
3. Digital ID card is automatically generated with:
   - Name
   - Photo (if uploaded and approved)
   - Employee reference/display reference
   - Organisation name
   - QR code for verification
   - Signature (if Staff Service integration enabled and signature exists)

## Integration Benefits

When Staff Service integration is enabled:

- **Automatic Data Sync**: Staff data (name, photo, employee reference, signature) syncs from Staff Service
- **Real-Time Updates**: Changes in Staff Service automatically sync to Digital ID via webhooks
- **Single Source of Truth**: Staff Service is the authoritative source for staff information
- **Reduced Duplication**: No need to manually enter staff data in both systems

## Troubleshooting

### Staff Member Not Appearing in Digital ID

**Check**:
1. Does the staff member have a user account in Digital ID?
2. Is Staff Service integration enabled in Digital ID?
3. Has the sync been run? (Click "Sync from Staff Service")
4. Does the employee reference match between systems?

**Solution**:
- Create user account if missing
- Run manual sync from Digital ID admin panel
- Verify employee reference matches

### Employee Record Not Linking to Staff Service

**Check**:
1. Is Staff Service integration enabled?
2. Does the employee reference match exactly?
3. Are both systems using the same organisation?

**Solution**:
- Verify `USE_STAFF_SERVICE=true` in Digital ID `.env`
- Check employee reference matches exactly (case-sensitive)
- Run manual sync or create employee record manually

### Photo Not Syncing

**Check**:
1. Is photo approved in Staff Service?
2. Has sync been run since photo was uploaded?
3. Is Staff Service integration enabled?

**Solution**:
- Approve photo in Staff Service first
- Run sync from Digital ID admin panel
- Check photo path is accessible

## Best Practices

1. **Enable Staff Service Integration**: Always use Staff Service as the source of truth when available
2. **Use Employee References**: Ensure employee references match between systems for automatic linking
3. **Run Periodic Syncs**: Set up a cron job to sync staff data periodically (e.g., hourly)
4. **Configure Webhooks**: Enable webhooks for real-time updates when staff data changes
5. **Verify User Accounts**: Ensure staff members have user accounts before creating employee records

## Related Documentation

- [Staff Service Integration Guide](../digital-id/INTEGRATION.md) - Complete integration setup
- [Digital ID User Guide](../digital-id/README.md) - Digital ID application documentation
- [API Reference](API.md) - Staff Service API documentation

