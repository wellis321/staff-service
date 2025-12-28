<?php
/**
 * Recruitment System Integration Example
 * 
 * This file demonstrates how a recruitment system would send new hire data
 * to the Staff Service to create staff members automatically.
 * 
 * Usage:
 * php examples/recruitment-system-example.php
 */

// Configuration
$staffServiceUrl = 'http://localhost:8000'; // Change to your Staff Service URL
$apiKey = 'your-api-key-here'; // Replace with actual API key

// Example: New hire data from recruitment system
$newHireData = [
    'source_system' => 'recruitment-system-v1',
    'new_hire' => [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.com',
        'phone' => '01234567890',
        'date_of_birth' => '1990-05-15',
        'employee_reference' => 'EMP2024001',
        'job_title' => 'Support Worker',
        'employment_start_date' => '2024-02-01',
        'organisational_unit' => 'Care Team A', // Unit name (will be looked up)
        // OR use organisational_unit_id: 3
        'role_in_unit' => 'member',
        'is_primary_unit' => true,
        'emergency_contact_name' => 'John Smith',
        'emergency_contact_phone' => '09876543210'
    ]
];

// Send to Staff Service
$url = $staffServiceUrl . '/api/recruitment/import.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newHireData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Display results
echo "=== Recruitment System Integration Example ===\n\n";
echo "Sending new hire data to Staff Service...\n";
echo "URL: {$url}\n\n";

if ($curlError) {
    echo "Error: {$curlError}\n";
    exit(1);
}

echo "HTTP Status Code: {$httpCode}\n\n";

$result = json_decode($response, true);
if ($result) {
    if ($result['success'] ?? false) {
        echo "SUCCESS: Staff member created!\n";
        echo "Person ID: {$result['person_id']}\n";
        echo "Name: {$result['data']['first_name']} {$result['data']['last_name']}\n";
        echo "Employee Reference: {$result['data']['employee_reference']}\n";
        if (!empty($result['data']['organisational_units'])) {
            echo "Organisational Units: ";
            foreach ($result['data']['organisational_units'] as $unit) {
                echo $unit['unit_name'];
                if ($unit['is_primary']) {
                    echo ' (Primary)';
                }
                echo ', ';
            }
            echo "\n";
        }
    } else {
        echo "ERROR: {$result['error']}\n";
        if (isset($result['message'])) {
            echo "Message: {$result['message']}\n";
        }
    }
} else {
    echo "Response:\n";
    echo $response . "\n";
}

echo "\n=== Example Complete ===\n";

