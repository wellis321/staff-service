<?php
require_once dirname(__DIR__) . '/config/config.php';

$path = $_GET['path'] ?? '';
if (empty($path)) {
    http_response_code(400);
    die('Invalid path');
}

// Security: only allow safe path characters (alphanumerics, hyphens, underscores, dots, forward slashes)
$path = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $path);
$path = ltrim($path, '/');

// Authenticate — API key callers (external apps) or session users
$apiCaller = null;
if (class_exists('ApiAuth')) {
    $apiCaller = ApiAuth::authenticate();
}

if ($apiCaller) {
    // API key authenticated — org-scoped access
    $organisationId = (int)($apiCaller['organisation_id'] ?? 0);
    if (!$organisationId) {
        http_response_code(403);
        die('Access denied');
    }

    // Determine if this is a signature or photo based on path
    $isSignature = strpos($path, 'people/signatures/') === 0;
    if ($isSignature) {
        $filename = str_replace('people/signatures/', '', $path);
        $fullPath = SIGNATURE_UPLOAD_PATH . '/' . $filename;
        $realUploadPath = realpath(SIGNATURE_UPLOAD_PATH);
    } else {
        $fullPath = PHOTO_UPLOAD_PATH . '/' . $path;
        $realUploadPath = realpath(PHOTO_UPLOAD_PATH);
    }

    $realFilePath = realpath($fullPath);
    if (!$realFilePath || strpos($realFilePath, $realUploadPath) !== 0 || !file_exists($fullPath)) {
        http_response_code(404);
        die('File not found');
    }

    // Verify the file belongs to a person in the caller's organisation
    $db = getDbConnection();
    if ($isSignature) {
        $ownerStmt = $db->prepare("SELECT p.organisation_id FROM people p JOIN staff_profiles sp ON sp.person_id = p.id WHERE sp.signature_path = ? LIMIT 1");
        $ownerStmt->execute([$filename]);
    } else {
        $ownerStmt = $db->prepare("SELECT organisation_id FROM people WHERE photo_path = ? LIMIT 1");
        $ownerStmt->execute([$path]);
    }
    $owner = $ownerStmt->fetch();

    if (!$owner || (int)$owner['organisation_id'] !== $organisationId) {
        http_response_code(403);
        die('Access denied');
    }
} else {
    // Fall back to session authentication
    Auth::requireLogin();

    // Determine if this is a signature or photo based on path
    $isSignature = strpos($path, 'people/signatures/') === 0;
    if ($isSignature) {
        $filename = str_replace('people/signatures/', '', $path);
        $fullPath = SIGNATURE_UPLOAD_PATH . '/' . $filename;
        $realUploadPath = realpath(SIGNATURE_UPLOAD_PATH);
    } else {
        $fullPath = PHOTO_UPLOAD_PATH . '/' . $path;
        $realUploadPath = realpath(PHOTO_UPLOAD_PATH);
    }

    $realFilePath = realpath($fullPath);
    if (!$realFilePath || strpos($realFilePath, $realUploadPath) !== 0 || !file_exists($fullPath)) {
        http_response_code(404);
        die('File not found');
    }

    $organisationId = Auth::getOrganisationId();
    $userId = Auth::getUserId();

    $allowed = false;
    if (RBAC::isAdmin()) {
        $allowed = true;
    } else {
        // Non-admin: only their own photo or signature
        $person = Person::findByUserId($userId, $organisationId);
        if ($person) {
            if ($isSignature) {
                $allowed = ($filename === ($person['signature_path'] ?? ''));
            } else {
                $personPhotoPath = str_replace(PHOTO_UPLOAD_PATH . '/', '', $person['photo_path'] ?? '');
                $personPendingPath = str_replace(PHOTO_UPLOAD_PATH . '/', '', $person['photo_pending_path'] ?? '');
                $allowed = ($path === $personPhotoPath || $path === $personPendingPath);
            }
        }
    }

    if (!$allowed) {
        http_response_code(403);
        die('Access denied');
    }
}

// Determine content type and restrict to images only
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
// finfo_close() is deprecated in PHP 8.5+ - finfo objects are automatically freed

$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedMimeTypes, true)) {
    http_response_code(403);
    die('File type not permitted');
}

// Set headers and output file
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=3600');

readfile($fullPath);
exit;

