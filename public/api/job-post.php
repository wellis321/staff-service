<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

Auth::requireLogin();

$organisationId = Auth::getOrganisationId();
$postId = $_GET['id'] ?? null;

if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Job post ID required']);
    exit;
}

$post = JobPost::findById($postId, $organisationId);

if (!$post || $post['organisation_id'] != $organisationId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Job post not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $post
]);

