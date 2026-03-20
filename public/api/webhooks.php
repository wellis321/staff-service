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

// Accept either session auth (admin) or API key auth (for programmatic registration)
$organisationId = null;

$apiKey = class_exists('ApiAuth') ? ApiAuth::getApiKey() : null;
if ($apiKey) {
    $keyData = ApiAuth::validateApiKey($apiKey);
    if ($keyData) {
        $organisationId = (int) $keyData['organisation_id'];
    }
}

if (!$organisationId) {
    if (!Auth::isLoggedIn() || !RBAC::isAdmin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    $organisationId = Auth::getOrganisationId();
}

// Support JSON body (API key callers) or POST fields (form callers)
$jsonBody = json_decode(file_get_contents('php://input'), true);
$action = $jsonBody['action'] ?? $_POST['action'] ?? '';

// Handle webhook subscription management
$db = getDbConnection();

if ($action === 'subscribe') {
    $url    = $jsonBody['url']    ?? $_POST['url']    ?? '';
    $events = $jsonBody['events'] ?? $_POST['events'] ?? [];
    $secret = $jsonBody['secret'] ?? $_POST['secret'] ?? '';

    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid webhook URL is required']);
        exit;
    }
    if (empty($secret)) {
        http_response_code(400);
        echo json_encode(['error' => 'A webhook secret is required']);
        exit;
    }
    if (empty($events) || !is_array($events)) {
        http_response_code(400);
        echo json_encode(['error' => 'At least one event must be specified']);
        exit;
    }

    // Upsert — if a subscription for this URL already exists for this org, update it
    $stmt = $db->prepare("
        SELECT id FROM webhook_subscriptions
        WHERE organisation_id = ? AND url = ?
        LIMIT 1
    ");
    $stmt->execute([$organisationId, $url]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("
            UPDATE webhook_subscriptions
            SET events = ?, secret = ?, is_active = 1, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([json_encode($events), $secret, $existing['id']]);
        echo json_encode(['success' => true, 'message' => 'Webhook subscription updated', 'webhook_id' => $existing['id']]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO webhook_subscriptions (organisation_id, url, events, secret, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$organisationId, $url, json_encode($events), $secret]);
        echo json_encode(['success' => true, 'message' => 'Webhook subscription created', 'webhook_id' => $db->lastInsertId()]);
    }

} elseif ($action === 'unsubscribe') {
    $url       = $jsonBody['url']        ?? $_POST['url']        ?? '';
    $webhookId = (int)($jsonBody['webhook_id'] ?? $_POST['webhook_id'] ?? 0);

    if ($webhookId) {
        $stmt = $db->prepare("DELETE FROM webhook_subscriptions WHERE id = ? AND organisation_id = ?");
        $stmt->execute([$webhookId, $organisationId]);
    } elseif ($url) {
        $stmt = $db->prepare("DELETE FROM webhook_subscriptions WHERE url = ? AND organisation_id = ?");
        $stmt->execute([$url, $organisationId]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Provide either webhook_id or url to unsubscribe']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Webhook subscription removed']);

} elseif ($action === 'list') {
    $stmt = $db->prepare("
        SELECT id, url, events, is_active, last_triggered_at, last_success_at, last_failure_at, failure_count
        FROM webhook_subscriptions
        WHERE organisation_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$organisationId]);
    echo json_encode(['success' => true, 'webhooks' => $stmt->fetchAll()]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action. Use: subscribe, unsubscribe, list']);
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

