<?php
/**
 * Staff Data API Endpoint v1
 * Provides JSON API for accessing staff data
 * Requires authentication (session-based or API key)
 * 
 * Endpoint: GET /api/staff-data.php
 * 
 * Query Parameters:
 * - id: Get single staff member by ID
 * - search: Search staff by name, email, or employee reference
 * - include_inactive: Set to 1 to include inactive staff
 * - page: Page number for pagination (default: 1)
 * - limit: Items per page (default: 20, max: 100)
 * - organisational_unit_id: Filter by organisational unit
 * - format: Response format (json only)
 * 
 * Authentication:
 * - Session-based: User must be logged in
 * - API Key: Include in Authorization header as "Bearer <key>" or "ApiKey <key>", or X-API-Key header, or api_key query parameter
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// CORS Configuration - Use whitelist instead of wildcard
$allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ? explode(',', getenv('CORS_ALLOWED_ORIGINS')) : [];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// If no origins configured, allow same origin only (more secure default)
if (empty($allowedOrigins)) {
    // Only allow same origin requests
    $allowedOrigin = '';
} elseif (in_array($origin, $allowedOrigins)) {
    $allowedOrigin = $origin;
} else {
    $allowedOrigin = '';
}

if ($allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // No CORS for unauthorized origins
    header('Access-Control-Allow-Origin: null');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only GET requests are supported.']);
    exit;
}

// Authenticate (try API key first, then session)
$authenticatedUser = null;
if (class_exists('ApiAuth')) {
    $authenticatedUser = ApiAuth::authenticate();
    if (!$authenticatedUser) {
        // Fall back to session auth
        if (Auth::isLoggedIn()) {
            $authenticatedUser = Auth::getUser();
        }
    }
} else {
    // Fall back to session-only auth
    if (Auth::isLoggedIn()) {
        $authenticatedUser = Auth::getUser();
    }
}

if (!$authenticatedUser) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Authentication required',
        'message' => 'Please provide a valid API key or be logged in via session.'
    ]);
    exit;
}

$organisationId = $authenticatedUser['organisation_id'] ?? Auth::getOrganisationId();
if (!$organisationId) {
    http_response_code(403);
    echo json_encode(['error' => 'Organisation ID not found']);
    exit;
}

// Get query parameters
$format = $_GET['format'] ?? 'json';
$personId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$activeOnly = !isset($_GET['include_inactive']) || $_GET['include_inactive'] !== '1';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
$organisationalUnitId = isset($_GET['organisational_unit_id']) ? (int)$_GET['organisational_unit_id'] : null;
$offset = ($page - 1) * $limit;

// Get staff data
try {
    if ($personId) {
        // Single person
        $person = Person::findById($personId, $organisationId);
        if (!$person || $person['organisation_id'] != $organisationId) {
            http_response_code(404);
            echo json_encode(['error' => 'Person not found']);
            exit;
        }
        
        // Get organisational units
        $organisationalUnits = Person::getOrganisationalUnits($personId);
        $person['organisational_units'] = $organisationalUnits;
        
        // Remove sensitive data
        unset($person['notes']);
        
        echo json_encode([
            'success' => true,
            'data' => $person
        ], JSON_PRETTY_PRINT);
    } else {
        // List staff with filtering and pagination
        if (!empty($search)) {
            $allStaff = Person::searchStaff($organisationId, $search, $activeOnly);
        } else {
            $allStaff = Person::getStaffByOrganisation($organisationId, $activeOnly);
        }
        
        // Filter by organisational unit if specified
        if ($organisationalUnitId) {
            $allStaff = array_filter($allStaff, function($member) use ($organisationalUnitId) {
                $units = Person::getOrganisationalUnits($member['id']);
                foreach ($units as $unit) {
                    if ($unit['organisational_unit_id'] == $organisationalUnitId) {
                        return true;
                    }
                }
                return false;
            });
            $allStaff = array_values($allStaff); // Re-index array
        }
        
        $totalCount = count($allStaff);
        $totalPages = ceil($totalCount / $limit);
        
        // Apply pagination
        $staff = array_slice($allStaff, $offset, $limit);
        
        // Include organisational units for each person
        foreach ($staff as &$member) {
            $units = Person::getOrganisationalUnits($member['id']);
            $member['organisational_units'] = $units;
            // Remove sensitive data
            unset($member['notes']);
        }
        
        echo json_encode([
            'success' => true,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'data' => $staff
        ], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    // Log detailed error server-side
    error_log('API Error in staff-data.php: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    
    // Return generic error to client
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while processing your request',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}

