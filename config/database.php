<?php
/**
 * Database Configuration
 * Reads from .env file if available, otherwise uses defaults
 */

// Load environment variables (only if not already loaded)
if (!function_exists('loadEnv')) {
    require_once __DIR__ . '/env_loader.php';
}

// Database connection settings (from .env or defaults)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'digital_id');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

