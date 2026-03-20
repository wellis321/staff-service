<?php
/**
 * User Verification API Endpoint
 * Allows connected apps to verify whether a user is active in PMS.
 *
 * Any app with a valid API key for an organisation can use this endpoint.
 * If the user is active in PMS for that organisation, the app should grant them access.
 *
 * Endpoint: GET /api/verify-user.php
 *
 * Authentication:
 *   API key required — Authorization: Bearer <key>  or  X-API-Key: <key>
 *   The API key determines which organisation is being queried.
 *
 * Query Parameters:
 *   email    — look up by email address (recommended)
 *   user_id  — look up by PMS user ID (alternative)
 *
 * Responses:
 *   200 { "verified": true,  "user": { id, email, first_name, last_name, organisation_id } }
 *   200 { "verified": false, "reason": "not_found|inactive|wrong_organisation" }
 *   400 { "error": "email or user_id parameter required" }
 *   401 { "error": "Authentication required" }
 *   405 { "error": "Method not allowed" }
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.']);
    exit;
}

// Require API key — session auth is not accepted here (this is a machine-to-machine endpoint)
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

// Require at least one lookup parameter
$email  = isset($_GET['email'])   ? trim($_GET['email'])    : null;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id']  : null;

if (!$email && !$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Provide either an email or user_id parameter.']);
    exit;
}

try {
    $db = getDbConnection();

    if ($email) {
        $stmt = $db->prepare("
            SELECT id, organisation_id, email, first_name, last_name, is_active
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
    } else {
        $stmt = $db->prepare("
            SELECT id, organisation_id, email, first_name, last_name, is_active
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
    }

    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['verified' => false, 'reason' => 'not_found']);
        exit;
    }

    // User must belong to the same organisation as the API key
    if ((int) $user['organisation_id'] !== $organisationId) {
        echo json_encode(['verified' => false, 'reason' => 'wrong_organisation']);
        exit;
    }

    if (!$user['is_active']) {
        echo json_encode(['verified' => false, 'reason' => 'inactive']);
        exit;
    }

    echo json_encode([
        'verified' => true,
        'user' => [
            'id'              => (int) $user['id'],
            'email'           => $user['email'],
            'first_name'      => $user['first_name'],
            'last_name'       => $user['last_name'],
            'organisation_id' => (int) $user['organisation_id'],
        ]
    ]);

} catch (Exception $e) {
    error_log('verify-user API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
}
