<?php
/**
 * API endpoint to retrieve staff signatures
 * Used by forms across the organisation to insert signatures
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// Require authentication
Auth::requireLogin();

$organisationId = Auth::getOrganisationId();
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $personId = $_GET['person_id'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    if (!$personId && !$userId) {
        // If no ID provided, return current user's signature
        $userId = Auth::getUserId();
    }
    
    $person = null;
    if ($personId) {
        $person = Person::findById($personId, $organisationId);
    } elseif ($userId) {
        $person = Person::findByUserId($userId, $organisationId);
    }
    
    if (!$person) {
        $response['message'] = 'Person not found.';
        echo json_encode($response);
        exit;
    }
    
    // Check permissions - users can only access their own signature unless they're admin
    $currentUserId = Auth::getUserId();
    if ($person['user_id'] != $currentUserId && !RBAC::isOrganisationAdmin() && !RBAC::isSuperAdmin()) {
        $response['message'] = 'Access denied.';
        echo json_encode($response);
        exit;
    }
    
    if ($person['signature_path']) {
        $signatureUrl = url('view-image.php?path=' . urlencode('people/signatures/' . $person['signature_path']));
        $response['success'] = true;
        $response['data'] = [
            'signature_url' => $signatureUrl,
            'signature_path' => $person['signature_path'],
            'signature_method' => $person['signature_method'],
            'signature_created_at' => $person['signature_created_at'],
            'person_id' => $person['id'],
            'person_name' => $person['first_name'] . ' ' . $person['last_name']
        ];
    } else {
        $response['success'] = false;
        $response['message'] = 'No signature found for this person.';
    }
    
} catch (Exception $e) {
    error_log("Error retrieving signature: " . $e->getMessage());
    $response['message'] = 'An error occurred while retrieving the signature.';
}

echo json_encode($response);

