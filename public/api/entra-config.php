<?php
/**
 * Entra Configuration API Endpoint
 * Provides Entra integration configuration for other applications
 * 
 * Endpoint: GET /api/entra-config.php
 * 
 * Authentication:
 * - API Key: Include in Authorization header as "Bearer <key>"
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// CORS Configuration
$allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ? explode(',', getenv('CORS_ALLOWED_ORIGINS')) : [];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (empty($allowedOrigins)) {
    $allowedOrigin = '';
} elseif (in_array($origin, $allowedOrigins)) {
    $allowedOrigin = $origin;
} else {
    $allowedOrigin = '';
}

if ($allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check authentication - support API key
require_once SRC_PATH . '/classes/ApiAuth.php';
$apiKey = ApiAuth::getApiKey();

$organisationId = null;

if ($apiKey) {
    // API key authentication
    $apiKeyData = ApiAuth::validateApiKey($apiKey);
    if (!$apiKeyData) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    $organisationId = $apiKeyData['organisation_id'];
} else {
    // Session-based authentication
    if (!Auth::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    $organisationId = Auth::getOrganisationId();
}

if (!$organisationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Organisation ID required']);
    exit;
}

require_once SRC_PATH . '/classes/EntraIntegration.php';

try {
    $config = EntraIntegration::getConfig($organisationId);
    
    // Return only enabled status and IDs (not sensitive data)
    echo json_encode([
        'success' => true,
        'data' => [
            'entra_enabled' => $config['entra_enabled'] ?? false,
            'entra_tenant_id' => $config['entra_enabled'] ? ($config['entra_tenant_id'] ?? null) : null,
            'entra_client_id' => $config['entra_enabled'] ? ($config['entra_client_id'] ?? null) : null
        ]
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    error_log('Error in entra-config.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while processing your request',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}


