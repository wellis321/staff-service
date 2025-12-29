<?php
/**
 * Create API Key Script
 * Helper script to create an API key for testing and external system integration
 * 
 * Usage:
 * php scripts/create-api-key.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Get user ID from command line arguments
$userId = $argv[1] ?? null;
$organisationId = $argv[2] ?? null;
$keyName = $argv[3] ?? 'API Key';

if (!$userId || !$organisationId) {
    echo "Usage: php scripts/create-api-key.php <user_id> <organisation_id> [key_name]\n";
    echo "\n";
    echo "Example:\n";
    echo "  php scripts/create-api-key.php 1 1 'Recruitment System'\n";
    echo "\n";
    exit(1);
}

// Verify user exists
$db = getDbConnection();
$stmt = $db->prepare("SELECT id, organisation_id, email FROM users WHERE id = ? AND organisation_id = ?");
$stmt->execute([$userId, $organisationId]);
$user = $stmt->fetch();

if (!$user) {
    echo "Error: User not found or doesn't belong to the specified organisation.\n";
    exit(1);
}

// Generate API key
$apiKey = bin2hex(random_bytes(32)); // 64-character hex string
$apiKeyHash = hash('sha256', $apiKey);

// Store in database
try {
    $stmt = $db->prepare("
        INSERT INTO api_keys (
            user_id, organisation_id, name, api_key_hash, is_active
        ) VALUES (?, ?, ?, ?, TRUE)
    ");
    $stmt->execute([$userId, $organisationId, $keyName, $apiKeyHash]);
    
    $keyId = $db->lastInsertId();
    
    echo "=== API Key Created ===\n\n";
    echo "Key ID: {$keyId}\n";
    echo "Name: {$keyName}\n";
    echo "User: {$user['email']}\n";
    echo "Organisation ID: {$organisationId}\n";
    echo "\n";
    echo "API KEY (store this securely - it won't be shown again):\n";
    echo "{$apiKey}\n";
    echo "\n";
    echo "Use this key in your API requests:\n";
    echo "  Authorization: Bearer {$apiKey}\n";
    echo "\n";
    $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost:8000';
    echo "Example curl command:\n";
    echo "  curl -H 'Authorization: Bearer {$apiKey}' \\\n";
    echo "       {$appUrl}/api/staff-data.php\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "Error creating API key: " . $e->getMessage() . "\n";
    exit(1);
}

