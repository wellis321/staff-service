<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the test environment
 */

// Set test environment
define('TESTING', true);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

// Load the application configuration
require_once dirname(__DIR__) . '/config/config.php';

// Set up test database connection if needed
// You may want to use a separate test database
// For now, we'll use the same database but with test isolation

// Clear any existing session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start session for testing
// Note: We can't prevent headers in PHP 8.5+, but tests will handle this
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Mock functions for testing
if (!function_exists('getenv')) {
    function getenv($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

