# Getting Started with Staff Service

This guide will help you get started using the Staff Service, including setting it up and integrating with recruitment systems to automatically create staff members.

## Quick Start Checklist

1. Install and configure the Staff Service (see [LOCAL_SETUP.md](../LOCAL_SETUP.md))
2. Set up the database (run schema files)
3. Create your first user account (register via web interface)
4. Log in and verify the application works
5. Create staff members (manually or via API)

## Step-by-Step Guide

### 1. Initial Setup

Follow the installation guide in [LOCAL_SETUP.md](../LOCAL_SETUP.md) to:
- Set up the database (including the new API integration tables)
- Configure environment variables
- Start the application

**Important**: Make sure you run the migration for API integration tables:

```bash
mysql -u root -p your_database < sql/migrations/add_api_integration_tables.sql
```

Or if setting up fresh, the `complete_schema.sql` includes these tables.

### 2. Create Your First User

1. Visit the registration page: `http://localhost:8000/register.php`
2. Fill in your details:
   - First Name
   - Last Name
   - Email
   - Organisation Domain (e.g., `example.com`)
   - Password
3. Check your email for verification link
4. Click the verification link
5. Log in at `http://localhost:8000/login.php`

### 3. Create Staff Members Manually

1. Log in as an administrator
2. Navigate to "Manage Staff"
3. Click "Add Staff Member"
4. Fill in the staff details:
   - Personal information (name, email, phone)
   - Employment details (job title, start date)
   - Organisational unit assignment (optional)
5. Click "Create Staff Member"

### 3a. Getting Digital ID Cards for Staff Members

After creating a staff member in Staff Service, they need an employee record in Digital ID to get a digital ID card. The process depends on whether Digital ID is integrated with Staff Service.

**First, set up the integration** (if not already done):

1. **Create API Key in Staff Service**:
   
   **Web Interface (Recommended)**:
   - Log in to Staff Service as an organisation administrator
   - Go to **Admin** → **API Keys**
   - Click **"Create API Key"**
   - Enter a name (e.g., "Digital ID Integration")
   - Copy the API key immediately - it won't be shown again!
   
   **Command Line (Alternative)**:
   ```bash
   # Find your user ID and organisation ID first
   # Then run (replace <user_id> and <organisation_id> with your actual IDs):
   php scripts/create-api-key.php <user_id> <organisation_id> "Digital ID Integration"
   
   # Example (user_id=3, organisation_id=1):
   php scripts/create-api-key.php 3 1 "Digital ID Integration"
   ```
   Save the API key that's displayed - you'll need it for Digital ID configuration.

2. **Configure Digital ID `.env` File**:
   Add to Digital ID's `.env` file:
   ```env
   USE_STAFF_SERVICE=true
   STAFF_SERVICE_URL=http://localhost:8000
   STAFF_SERVICE_API_KEY=your-api-key-from-step-1
   STAFF_SYNC_INTERVAL=3600
   ```
   Replace `http://localhost:8000` with your actual Staff Service URL and paste the API key from step 1.

**Then, sync staff members**:

#### Option A: With Staff Service Integration (Recommended)

When Digital ID is configured to use Staff Service as the source of truth:

1. **Automatic Sync**: If the staff member has a user account linked, Digital ID will automatically detect and sync them when:
   - The staff member accesses their Digital ID card page
   - An admin runs the "Sync from Staff Service" function in Digital ID
   - A webhook is received from Staff Service (if configured)

2. **Manual Sync**: 
   - Go to Digital ID admin panel → "Manage Employees"
   - Click "Sync from Staff Service" button
   - This will sync all staff members from Staff Service to Digital ID

3. **Create Employee Record** (if sync doesn't create it automatically):
   - Go to Digital ID admin panel → "Manage Employees"
   - Click "Create New Employee"
   - Select the user from the dropdown (they should appear if they have a user account)
   - Enter the employee number (from HR/payroll system)
   - The system will automatically link to Staff Service if a matching person is found

#### Option B: Without Staff Service Integration (Standalone)

If Digital ID is not integrated with Staff Service:

1. **Create User Account** (if not already created):
   - Staff member registers at Digital ID registration page, OR
   - Admin creates user account in Digital ID

2. **Create Employee Record**:
   - Go to Digital ID admin panel → "Manage Employees"
   - Click "Create New Employee"
   - Select the user from the dropdown
   - Enter employee number and display reference
   - Click "Create Employee"

3. **Upload Photo** (optional):
   - Staff member can upload their photo through their profile
   - Admin can upload/approve photos through the admin panel

4. **View Digital ID Card**:
   - Staff member logs into Digital ID
   - Navigate to "Digital ID Card" page
   - The ID card will be generated automatically

#### Important Notes

- **Employee Number**: This should match the employee number from your HR/payroll system. It's used for integration and cannot be changed after creation.
- **User Account**: Staff members need a user account in Digital ID to access their digital ID card. If they don't have one, they can register or an admin can create one.
- **Photo**: A photo is recommended but not required for the digital ID card to be generated.
- **Signature**: If Staff Service integration is enabled and the staff member has a signature in Staff Service, it will automatically appear on their Digital ID card.

### 4. Create Staff Members via API (Recruitment System)

The Staff Service can receive new hire data from recruitment systems via API, automatically creating staff members.

#### Step 1: Create an API Key

**Option A: Using the Helper Script (Recommended for Testing)**

```bash
# First, find your user ID and organisation ID
# Log in to Staff Service and check the URL or database

# Then run:
php scripts/create-api-key.php <user_id> <organisation_id> "Recruitment System"

# Example:
php scripts/create-api-key.php 1 1 "Recruitment System"
```

The script will output your API key - **save this securely** as it won't be shown again.

**Option B: Manual Database Creation**

```sql
-- Generate a secure API key (do this in PHP or use random generator)
-- $apiKey = bin2hex(random_bytes(32)); // Generates a 64-character hex string

-- Hash it and store in database:
INSERT INTO api_keys (
    user_id, 
    organisation_id, 
    name, 
    api_key_hash, 
    is_active
) VALUES (
    1, -- User ID (admin user)
    1, -- Organisation ID
    'Recruitment System',
    SHA2('your-api-key-here', 256), -- Hash of your API key
    TRUE
);
```

#### Step 2: Test the API

Use the provided example script to test:

```bash
# Edit the example file to set your API key
nano examples/recruitment-system-example.php

# Update these lines:
# $staffServiceUrl = 'http://localhost:8000';
# $apiKey = 'your-actual-api-key-here';

# Run the example
php examples/recruitment-system-example.php
```

#### Step 3: Integrate with Your Recruitment System

Use the recruitment import endpoint to send new hire data:

**Endpoint**: `POST /api/recruitment/import.php`

**Request Headers**:
```
Authorization: Bearer <your-api-key>
Content-Type: application/json
```

**Request Body**:
```json
{
  "source_system": "recruitment-system-v1",
  "new_hire": {
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane.smith@example.com",
    "phone": "01234567890",
    "date_of_birth": "1990-05-15",
    "employee_reference": "EMP2024001",
    "job_title": "Support Worker",
    "employment_start_date": "2024-02-01",
    "organisational_unit": "Care Team A",
    "role_in_unit": "member",
    "is_primary_unit": true,
    "emergency_contact_name": "John Smith",
    "emergency_contact_phone": "09876543210"
  }
}
```

**Response (Success - HTTP 201)**:
```json
{
  "success": true,
  "message": "Staff member created successfully",
  "person_id": 123,
  "data": {
    "id": 123,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane.smith@example.com",
    "employee_reference": "EMP2024001",
    "job_title": "Support Worker",
    ...
  }
}
```

**Response (Error - HTTP 409 if duplicate)**:
```json
{
  "error": "Duplicate employee reference",
  "message": "Employee with reference 'EMP2024001' already exists",
  "existing_person_id": 123
}
```

## Example Code

### PHP Example

```php
<?php
// Configuration
$staffServiceUrl = 'https://staff.example.com';
$apiKey = 'your-api-key-here';

// New hire data from recruitment system
$newHire = [
    'source_system' => 'recruitment-system-v1',
    'new_hire' => [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.com',
        'employee_reference' => 'EMP2024001',
        'job_title' => 'Support Worker',
        'employment_start_date' => '2024-02-01',
        'organisational_unit' => 'Care Team A'
    ]
];

// Send to Staff Service
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $staffServiceUrl . '/api/recruitment/import.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newHire));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201) {
    $result = json_decode($response, true);
    echo "Staff member created: ID {$result['person_id']}\n";
} else {
    echo "Error: HTTP {$httpCode}\n";
    $error = json_decode($response, true);
    echo "Message: {$error['message']}\n";
}
?>
```

### JavaScript/Node.js Example

```javascript
const axios = require('axios');

const staffServiceUrl = 'https://staff.example.com';
const apiKey = 'your-api-key-here';

const newHire = {
    source_system: 'recruitment-system-v1',
    new_hire: {
        first_name: 'Jane',
        last_name: 'Smith',
        email: 'jane.smith@example.com',
        employee_reference: 'EMP2024001',
        job_title: 'Support Worker',
        employment_start_date: '2024-02-01',
        organisational_unit: 'Care Team A'
    }
};

axios.post(`${staffServiceUrl}/api/recruitment/import.php`, newHire, {
    headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    console.log('Staff member created:', response.data.person_id);
})
.catch(error => {
    if (error.response) {
        console.error('Error:', error.response.status, error.response.data);
    } else {
        console.error('Error:', error.message);
    }
});
```

### Python Example

```python
import requests

staff_service_url = 'https://staff.example.com'
api_key = 'your-api-key-here'

new_hire = {
    'source_system': 'recruitment-system-v1',
    'new_hire': {
        'first_name': 'Jane',
        'last_name': 'Smith',
        'email': 'jane.smith@example.com',
        'employee_reference': 'EMP2024001',
        'job_title': 'Support Worker',
        'employment_start_date': '2024-02-01',
        'organisational_unit': 'Care Team A'
    }
}

response = requests.post(
    f'{staff_service_url}/api/recruitment/import.php',
    json=new_hire,
    headers={
        'Authorization': f'Bearer {api_key}',
        'Content-Type': 'application/json'
    }
)

if response.status_code == 201:
    result = response.json()
    print(f"Staff member created: ID {result['person_id']}")
else:
    print(f"Error: {response.status_code}")
    print(response.json())
```

## Field Requirements

### Required Fields
- `first_name`: Staff member's first name
- `last_name`: Staff member's last name

### Optional Fields
- `email`: Email address
- `phone`: Phone number
- `date_of_birth`: Date of birth (YYYY-MM-DD format)
- `employee_reference`: Employee reference number (must be unique within organisation)
- `job_title`: Job title
- `employment_start_date`: Start date (YYYY-MM-DD format)
- `employment_end_date`: End date (YYYY-MM-DD format)
- `organisational_unit`: Organisational unit name (will be looked up by name)
- `organisational_unit_id`: Organisational unit ID (alternative to name)
- `role_in_unit`: Role in unit (default: 'member')
- `is_primary_unit`: Boolean, set to true if this is primary unit
- `emergency_contact_name`: Emergency contact name
- `emergency_contact_phone`: Emergency contact phone

**Note**: If `organisational_unit` is provided, the system will search for a unit with that name. If not found, the assignment will be skipped without error (staff member will still be created).

## Error Handling

The API returns appropriate HTTP status codes:

- `201 Created`: Staff member created successfully
- `400 Bad Request`: Invalid JSON or missing required fields
- `401 Unauthorized`: Invalid or missing API key
- `409 Conflict`: Employee reference already exists
- `500 Internal Server Error`: Server error

Always check the response for error details:

```json
{
  "error": "Duplicate employee reference",
  "message": "Employee with reference 'EMP2024001' already exists",
  "existing_person_id": 123
}
```

## Import Logging

All imports are logged in the `recruitment_imports` table for audit purposes. You can query this table to see:
- When imports occurred
- Who initiated them (which API key/user)
- Success/failure status
- Error messages
- Original import data

Query example:
```sql
SELECT * FROM recruitment_imports 
WHERE organisation_id = 1 
ORDER BY created_at DESC 
LIMIT 10;
```

## Testing Your Integration

1. **Test API Key**: First verify your API key works:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        http://localhost:8000/api/staff-data.php
   ```

2. **Test Recruitment Import**: Use the example script:
   ```bash
   php examples/recruitment-system-example.php
   ```

3. **Check the Database**: Verify staff member was created:
   ```sql
   SELECT * FROM people WHERE organisation_id = 1 ORDER BY id DESC LIMIT 5;
   ```

4. **Check Import Logs**: Verify import was logged:
   ```sql
   SELECT * FROM recruitment_imports ORDER BY created_at DESC LIMIT 1;
   ```

## Workflow Example

Here's a typical workflow for a recruitment system integration:

1. **Recruitment system** receives offer acceptance from candidate
2. **Recruitment system** sends POST request to Staff Service `/api/recruitment/import.php`
3. **Staff Service** validates data and creates staff member
4. **Staff Service** assigns to organisational unit if specified
5. **Staff Service** returns person ID and full staff data
6. **Recruitment system** stores person ID for future reference
7. **Staff member** appears in Staff Service and can be managed there
8. **Other apps** (Digital ID, Medication App) can query Staff Service for this staff member

## Next Steps

1. ✅ **Test the API**: Use the example scripts to verify integration works
2. ✅ **Set up webhooks**: Subscribe to webhooks for real-time updates (when implemented)
3. ✅ **Integrate with other apps**: Connect Digital ID or other apps to Staff Service
4. ✅ **Configure external systems**: Set up Entra, HR, Finance, LMS integrations

## Additional Resources

- [API Reference](API.md) - Complete API documentation
- [Integration Guide](INTEGRATION.md) - How to integrate other apps
- [External Systems](EXTERNAL_SYSTEMS.md) - External system integration
- [Architecture](ARCHITECTURE.md) - Architecture overview

## Troubleshooting

### API Key Not Working
- Verify API key is correct (64-character hex string)
- Check API key is active in database: `SELECT * FROM api_keys WHERE is_active = TRUE`
- Verify API key hasn't expired
- Check organisation ID matches

### Staff Member Not Created
- Check recruitment_imports table for error messages
- Verify all required fields are provided
- Check employee_reference doesn't already exist
- Ensure organisational unit exists if specified

### Organisational Unit Not Assigned
- Verify unit name matches exactly (case-sensitive)
- Check unit exists: `SELECT * FROM organisational_units WHERE name = 'Care Team A'`
- Staff member will still be created even if unit assignment fails

## Support

For help getting started, contact: [CONTACT_EMAIL from .env]
