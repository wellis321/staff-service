<?php
require_once dirname(__DIR__) . '/config/config.php';

Auth::requireLogin();

$path = $_GET['path'] ?? '';
if (empty($path)) {
    http_response_code(400);
    die('Invalid path');
}

// Security: prevent directory traversal
$path = str_replace('..', '', $path);
$path = ltrim($path, '/');

// Determine if this is a signature or photo based on path
$isSignature = strpos($path, 'people/signatures/') === 0;
if ($isSignature) {
    // Remove 'people/signatures/' prefix to get just the filename
    $filename = str_replace('people/signatures/', '', $path);
    $fullPath = SIGNATURE_UPLOAD_PATH . '/' . $filename;
    $realUploadPath = realpath(SIGNATURE_UPLOAD_PATH);
} else {
    // Photo path
    $fullPath = PHOTO_UPLOAD_PATH . '/' . $path;
    $realUploadPath = realpath(PHOTO_UPLOAD_PATH);
}

// Verify file exists and is within upload directory
$realFilePath = realpath($fullPath);

if (!$realFilePath || strpos($realFilePath, $realUploadPath) !== 0) {
    http_response_code(404);
    die('File not found');
}

// Verify file exists
if (!file_exists($fullPath)) {
    http_response_code(404);
    die('File not found');
}

// Get organisation ID for additional security check (optional, for strict isolation)
$organisationId = Auth::getOrganisationId();
$userId = Auth::getUserId();

// Allow viewing if user is admin or if it's their own photo/signature
$allowed = false;
if (RBAC::isAdmin()) {
    $allowed = true;
} else {
    // Check if this is the user's own photo or signature
    $person = Person::findByUserId($userId, $organisationId);
    if ($person) {
        if ($isSignature) {
            // Check signature
            $personSignaturePath = $person['signature_path'] ?? '';
            if ($filename === $personSignaturePath) {
                $allowed = true;
            }
        } else {
            // Check photo
            $personPhotoPath = str_replace(PHOTO_UPLOAD_PATH . '/', '', $person['photo_path'] ?? '');
            $personPendingPath = str_replace(PHOTO_UPLOAD_PATH . '/', '', $person['photo_pending_path'] ?? '');
            if ($path === $personPhotoPath || $path === $personPendingPath) {
                $allowed = true;
            }
        }
    }
}

if (!$allowed) {
    http_response_code(403);
    die('Access denied');
}

// Determine content type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
// finfo_close() is deprecated in PHP 8.5+ - finfo objects are automatically freed

// Set headers and output file
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=3600');

readfile($fullPath);
exit;

