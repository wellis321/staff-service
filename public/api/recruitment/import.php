<?php
/**
 * Recruitment System Import Endpoint
 * Receives new hire data from recruitment systems and creates staff members
 * 
 * Endpoint: POST /api/recruitment/import.php
 * 
 * Authentication: Requires API key
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// CORS Configuration - Use whitelist instead of wildcard
$allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ? explode(',', getenv('CORS_ALLOWED_ORIGINS')) : [];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// If no origins configured, allow same origin only (more secure default)
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
} else {
    header('Access-Control-Allow-Origin: null');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only POST requests are supported.']);
    exit;
}

// Authenticate using API key (required for external systems)
$authenticatedUser = null;
if (class_exists('ApiAuth')) {
    $authenticatedUser = ApiAuth::authenticate();
}

if (!$authenticatedUser) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Authentication required',
        'message' => 'Valid API key required for recruitment system imports.'
    ]);
    exit;
}

$organisationId = $authenticatedUser['organisation_id'];
$importedBy = $authenticatedUser['id'];

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON',
        'message' => json_last_error_msg()
    ]);
    exit;
}

// Validate required fields
if (!isset($data['source_system']) || empty($data['source_system'])) {
    http_response_code(400);
    echo json_encode(['error' => 'source_system is required']);
    exit;
}

if (!isset($data['new_hire']) || !is_array($data['new_hire'])) {
    http_response_code(400);
    echo json_encode(['error' => 'new_hire data is required']);
    exit;
}

$newHire = $data['new_hire'];
$sourceSystem = $data['source_system'];

// Validate new hire required fields
if (empty($newHire['first_name']) || empty($newHire['last_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'first_name and last_name are required']);
    exit;
}

// Check if employee reference already exists (if provided)
$employeeReference = $newHire['employee_reference'] ?? null;
if ($employeeReference) {
    $existing = Person::findByEmployeeReference($organisationId, $employeeReference);
    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'error' => 'Duplicate employee reference',
            'message' => "Employee with reference '{$employeeReference}' already exists",
            'existing_person_id' => $existing['id']
        ]);
        exit;
    }
}

// Log import attempt
$db = getDbConnection();
try {
    $db->beginTransaction();
    
    // Create recruitment import log entry
    $logStmt = $db->prepare("
        INSERT INTO recruitment_imports (
            organisation_id, imported_by, source_system, import_type, 
            import_status, imported_data, total_records
        ) VALUES (?, ?, ?, 'api', 'processing', ?, 1)
    ");
    $logStmt->execute([
        $organisationId,
        $importedBy,
        $sourceSystem,
        json_encode($data)
    ]);
    $importLogId = $db->lastInsertId();
    
    // Prepare staff data
    $staffData = [
        'organisation_id' => $organisationId,
        'first_name' => trim($newHire['first_name']),
        'last_name' => trim($newHire['last_name']),
        'email' => !empty($newHire['email']) ? trim($newHire['email']) : null,
        'phone' => !empty($newHire['phone']) ? trim($newHire['phone']) : null,
        'date_of_birth' => !empty($newHire['date_of_birth']) ? $newHire['date_of_birth'] : null,
        'employee_reference' => $employeeReference,
        'is_active' => true,
    ];
    
    // Staff-specific fields
    if (!empty($newHire['job_title'])) {
        $staffData['job_title'] = trim($newHire['job_title']);
    }
    if (!empty($newHire['employment_start_date'])) {
        $staffData['employment_start_date'] = $newHire['employment_start_date'];
    }
    if (!empty($newHire['employment_end_date'])) {
        $staffData['employment_end_date'] = $newHire['employment_end_date'];
    }
    if (!empty($newHire['emergency_contact_name'])) {
        $staffData['emergency_contact_name'] = trim($newHire['emergency_contact_name']);
    }
    if (!empty($newHire['emergency_contact_phone'])) {
        $staffData['emergency_contact_phone'] = trim($newHire['emergency_contact_phone']);
    }
    
    // Create staff member
    $person = Person::createStaff($staffData);
    
    if (!$person) {
        throw new Exception('Failed to create staff member');
    }
    
    $personId = $person['id'];
    
    // Handle organisational unit assignment if provided
    if (!empty($newHire['organisational_unit']) || !empty($newHire['organisational_unit_id'])) {
        $unitId = null;
        
        // If unit name provided, look it up
        if (!empty($newHire['organisational_unit'])) {
            $unitName = trim($newHire['organisational_unit']);
            $unitStmt = $db->prepare("
                SELECT id FROM organisational_units 
                WHERE organisation_id = ? AND name = ? 
                LIMIT 1
            ");
            $unitStmt->execute([$organisationId, $unitName]);
            $unit = $unitStmt->fetch();
            if ($unit) {
                $unitId = $unit['id'];
            }
        } elseif (!empty($newHire['organisational_unit_id'])) {
            $unitId = (int)$newHire['organisational_unit_id'];
        }
        
        if ($unitId) {
            Person::assignToOrganisationalUnit(
                $personId, 
                $unitId, 
                $newHire['role_in_unit'] ?? 'member',
                !empty($newHire['is_primary_unit']) && $newHire['is_primary_unit'] === true
            );
        }
    }
    
    // Update import log
    $updateStmt = $db->prepare("
        UPDATE recruitment_imports 
        SET import_status = 'completed',
            successful_records = 1,
            completed_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$importLogId]);
    
    $db->commit();
    
    // Get full person data with relationships
    $fullPerson = Person::findById($personId, $organisationId);
    $organisationalUnits = Person::getOrganisationalUnits($personId);
    $fullPerson['organisational_units'] = $organisationalUnits;
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Staff member created successfully',
        'person_id' => $personId,
        'data' => $fullPerson
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $db->rollBack();
    
    // Update import log with error
    if (isset($importLogId)) {
        try {
            $errorStmt = $db->prepare("
                UPDATE recruitment_imports 
                SET import_status = 'failed',
                    failed_records = 1,
                    error_log = ?,
                    completed_at = NOW()
                WHERE id = ?
            ");
            $errorStmt->execute([$e->getMessage(), $importLogId]);
        } catch (Exception $logError) {
            // Ignore log update errors
        }
    }
    
    // Log detailed error server-side
    error_log('API Error in recruitment/import.php: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    
    // Return generic error to client
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while processing your request',
        'error_code' => 'IMPORT_ERROR'
    ]);
}

