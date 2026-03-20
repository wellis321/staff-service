<?php
/**
 * Authentication Endpoint
 * Allows connected apps to verify a user's credentials against PMS.
 * On success, returns user data so the calling app can provision a local account.
 *
 * IMPORTANT: This endpoint transmits passwords. HTTPS is required in production.
 *
 * Endpoint: POST /api/auth-token.php
 *
 * Authentication:
 *   API key required — Authorization: Bearer <key>  or  X-API-Key: <key>
 *   The API key determines which organisation is being queried.
 *
 * Request body (JSON):
 *   { "email": "user@example.com", "password": "their-password" }
 *
 * Responses:
 *   200 { "authenticated": true,  "user": { id, email, first_name, last_name, organisation_id, organisation_domain } }
 *   200 { "authenticated": false, "reason": "invalid_credentials|inactive|wrong_organisation" }
 *   400 { "error": "..." }
 *   401 { "error": "..." }
 *   405 { "error": "..." }
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// Only allow POST — credentials must never appear in query strings (which get logged)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Require API key — determines which organisation we are authenticating against
$apiKey = ApiAuth::getApiKey();
if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required. Provide a valid API key.']);
    exit;
}

$keyData = ApiAuth::validateApiKey($apiKey);
if (!$keyData) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired API key.']);
    exit;
}

$organisationId = (int) $keyData['organisation_id'];

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
$email    = isset($body['email'])    ? trim($body['email'])    : '';
$password = $body['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Request body must include email and password.']);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->prepare("
        SELECT u.id, u.organisation_id, u.email, u.first_name, u.last_name,
               u.password_hash, u.is_active,
               o.domain AS organisation_domain
        FROM users u
        LEFT JOIN organisations o ON u.organisation_id = o.id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Use identical response for not-found and wrong-password to prevent user enumeration
    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['authenticated' => false, 'reason' => 'invalid_credentials']);
        exit;
    }

    // User must belong to the same organisation as the API key
    if ((int) $user['organisation_id'] !== $organisationId) {
        echo json_encode(['authenticated' => false, 'reason' => 'wrong_organisation']);
        exit;
    }

    if (!$user['is_active']) {
        echo json_encode(['authenticated' => false, 'reason' => 'inactive']);
        exit;
    }

    // Record the login in PMS
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id'                  => (int) $user['id'],
            'email'               => $user['email'],
            'first_name'          => $user['first_name'],
            'last_name'           => $user['last_name'],
            'organisation_id'     => (int) $user['organisation_id'],
            'organisation_domain' => $user['organisation_domain'],
        ]
    ]);

} catch (Exception $e) {
    error_log('auth-token API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
}
