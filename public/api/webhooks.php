<?php
/**
 * Webhooks API Endpoint
 * Handles webhook subscriptions and delivery for staff data changes
 * 
 * Endpoint: POST /api/webhooks.php
 * 
 * Webhook Events:
 * - person.created: New staff member created
 * - person.updated: Staff member updated
 * - person.deactivated: Staff member deactivated
 * - person.photo.approved: Staff photo approved
 * - person.unit.assigned: Staff assigned to organisational unit
 * - person.unit.removed: Staff removed from organisational unit
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check authentication
if (!Auth::isLoggedIn() || !RBAC::isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$organisationId = Auth::getOrganisationId();
$action = $_POST['action'] ?? '';

// Handle webhook subscription management
if ($action === 'subscribe') {
    // Subscribe to webhook events
    $url = $_POST['url'] ?? '';
    $events = $_POST['events'] ?? [];
    $secret = $_POST['secret'] ?? '';
    
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid webhook URL required']);
        exit;
    }
    
    // TODO: Store webhook subscription in database
    // For now, return success
    echo json_encode([
        'success' => true,
        'message' => 'Webhook subscription created (not yet implemented)',
        'webhook_id' => 1
    ]);
} elseif ($action === 'unsubscribe') {
    // Unsubscribe from webhook events
    $webhookId = isset($_POST['webhook_id']) ? (int)$_POST['webhook_id'] : 0;
    
    // TODO: Remove webhook subscription from database
    echo json_encode([
        'success' => true,
        'message' => 'Webhook subscription removed (not yet implemented)'
    ]);
} elseif ($action === 'list') {
    // List webhook subscriptions
    // TODO: Retrieve from database
    echo json_encode([
        'success' => true,
        'webhooks' => []
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}

/**
 * Trigger webhook for an event
 * This function should be called when staff data changes occur
 * 
 * @param string $event Event name (e.g., 'person.created')
 * @param array $data Event data
 * @param int $organisationId Organisation ID
 */
function triggerWebhook($event, $data, $organisationId) {
    // TODO: Retrieve active webhook subscriptions from database
    // TODO: Send HTTP POST to each subscribed URL with event data
    // TODO: Include HMAC signature using webhook secret
    // TODO: Log delivery attempts and failures
    
    // Placeholder implementation
    error_log("Webhook triggered: {$event} for organisation {$organisationId}");
}

