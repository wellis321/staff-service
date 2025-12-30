<?php
/**
 * Debug Log Viewer (TEMPORARY - Remove after debugging)
 * This file should be deleted after fixing the issue
 */

// Simple authentication - only allow if logged in as admin
require_once dirname(__DIR__) . '/config/config.php';

Auth::requireLogin();

if (!RBAC::isSuperAdmin() && !RBAC::isOrganisationAdmin()) {
    die('Access denied. Admin access required.');
}

$logPath = ROOT_PATH . '/.cursor/debug.log';

header('Content-Type: text/plain');

if (file_exists($logPath)) {
    $content = file_get_contents($logPath);
    if ($content === false) {
        echo "Error: Could not read log file.\n";
    } else {
        echo "=== Debug Log ===\n";
        echo "File: $logPath\n";
        echo "Size: " . filesize($logPath) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($logPath)) . "\n";
        echo "\n=== Log Content ===\n\n";
        echo $content;
    }
} else {
    echo "Log file not found at: $logPath\n";
    echo "The log file will be created when profile creation is attempted.\n";
}

