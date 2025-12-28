<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

Auth::requireLogin();

$organisationId = Auth::getOrganisationId();
$jdId = $_GET['id'] ?? null;

if (!$jdId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Job description ID required']);
    exit;
}

$jd = JobDescription::findById($jdId, $organisationId);

if (!$jd || $jd['organisation_id'] != $organisationId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Job description not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $jd
]);

