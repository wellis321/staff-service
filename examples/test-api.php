<?php
/**
 * Test API Endpoint
 * Simple script to test the Staff Service API
 * 
 * Usage:
 * php examples/test-api.php
 */

// Configuration
$staffServiceUrl = 'http://localhost:8000'; // Change to your Staff Service URL

echo "=== Staff Service API Test ===\n\n";

// Test 1: List all staff
echo "Test 1: List all staff\n";
echo "GET {$staffServiceUrl}/api/staff-data.php\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $staffServiceUrl . '/api/staff-data.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Note: For testing, you may need to be logged in via session
// Or use an API key via Authorization header

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "Staff count: {$data['pagination']['total']}\n";
    if (!empty($data['data'])) {
        echo "First staff member: {$data['data'][0]['first_name']} {$data['data'][0]['last_name']}\n";
    }
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}

echo "\n";

// Test 2: Search staff
echo "Test 2: Search staff\n";
echo "GET {$staffServiceUrl}/api/staff-data.php?search=john\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $staffServiceUrl . '/api/staff-data.php?search=john');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
$data = json_decode($response, true);
if ($data && isset($data['success'])) {
    echo "Search results: {$data['pagination']['total']} found\n";
}

echo "\n=== Test Complete ===\n";
echo "\nNote: These tests require authentication (session or API key).\n";
echo "For full API access, you'll need to:\n";
echo "1. Log in via the web interface, OR\n";
echo "2. Use an API key in the Authorization header\n";

