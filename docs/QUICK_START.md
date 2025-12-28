# Quick Start: Recruitment System Integration

This is a quick guide to get you started with sending data from a recruitment system to the Staff Service.

## 5-Minute Setup

### Step 1: Create an API Key

```bash
# Find your user ID and organisation ID first
# Then run:
php scripts/create-api-key.php 1 1 "Recruitment System"
```

Save the API key that's displayed - you'll need it for API requests.

### Step 2: Test with Example Script

```bash
# Edit examples/recruitment-system-example.php
# Update: $apiKey = 'your-actual-api-key-here';

# Run it:
php examples/recruitment-system-example.php
```

If successful, you should see:
```
SUCCESS: Staff member created!
Person ID: 123
Name: Jane Smith
Employee Reference: EMP2024001
```

### Step 3: Check in Staff Service

1. Log in to Staff Service web interface
2. Navigate to "Manage Staff"
3. You should see the new staff member created from the API

## Example: Send New Hire Data

Here's a complete example in PHP:

```php
<?php
$staffServiceUrl = 'http://localhost:8000';
$apiKey = 'your-api-key-here';

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
    echo "Success! Staff member created with ID: {$result['person_id']}\n";
} else {
    echo "Error: " . $response . "\n";
}
?>
```

## What Happens When You Send Data

1. **Recruitment system** sends POST request to `/api/recruitment/import.php`
2. **Staff Service** validates the API key
3. **Staff Service** validates the new hire data
4. **Staff Service** creates the staff member in the `people` table
5. **Staff Service** creates staff profile in `staff_profiles` table
6. **Staff Service** assigns to organisational unit (if specified)
7. **Staff Service** logs the import in `recruitment_imports` table
8. **Staff Service** returns the created staff member data

## Required vs Optional Fields

**Required**:
- `first_name`
- `last_name`

**Optional but Recommended**:
- `email`
- `employee_reference` (must be unique if provided)
- `job_title`
- `employment_start_date`
- `organisational_unit` or `organisational_unit_id`

## Troubleshooting

**401 Unauthorized**: Check your API key is correct
**409 Conflict**: Employee reference already exists
**400 Bad Request**: Check required fields are provided
**500 Error**: Check error message in response or recruitment_imports table

## Next Steps

- Read [Getting Started Guide](GETTING_STARTED.md) for detailed instructions
- Read [API Reference](API.md) for complete API documentation
- Set up webhooks for real-time updates (coming soon)

