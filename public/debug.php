<?php
/**
 * Debug script for production deployment
 * Remove this file after fixing issues
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Production Deployment Debug</h1>";
echo "<pre>";

// Check .env file
echo "=== .env File Check ===\n";
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    echo "✓ .env file exists at: $envPath\n";
    echo "File size: " . filesize($envPath) . " bytes\n";
    echo "File readable: " . (is_readable($envPath) ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ .env file NOT found at: $envPath\n";
}

// Check shared-auth
echo "\n=== Shared Auth Check ===\n";
$sharedAuthPath = dirname(__DIR__) . '/shared-auth';
if (file_exists($sharedAuthPath)) {
    echo "✓ shared-auth directory exists\n";
    $requiredFiles = [
        '/src/Database.php',
        '/src/Auth.php',
        '/src/RBAC.php'
    ];
    foreach ($requiredFiles as $file) {
        if (file_exists($sharedAuthPath . $file)) {
            echo "  ✓ $file exists\n";
        } else {
            echo "  ✗ $file MISSING\n";
        }
    }
} else {
    echo "✗ shared-auth directory NOT found at: $sharedAuthPath\n";
    // Check if digital-id sibling exists
    $digitalIdPath = dirname(dirname(__DIR__)) . '/digital-id/shared-auth';
    if (file_exists($digitalIdPath)) {
        echo "  → Found at: $digitalIdPath (sibling directory)\n";
    }
}

// Check config file
echo "\n=== Config File Check ===\n";
$configPath = dirname(__DIR__) . '/config/config.php';
if (file_exists($configPath)) {
    echo "✓ config.php exists\n";
} else {
    echo "✗ config.php NOT found\n";
}

// Try to load .env
echo "\n=== Environment Variables ===\n";
if (file_exists($envPath)) {
    require_once dirname(__DIR__) . '/config/env_loader.php';
    
    $envVars = [
        'APP_ENV',
        'APP_NAME',
        'APP_URL',
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'DB_CHARSET',
        'CONTACT_EMAIL'
    ];
    
    foreach ($envVars as $var) {
        $value = getenv($var);
        if ($value !== false) {
            // Mask password
            if ($var === 'DB_PASS') {
                echo "  $var = " . str_repeat('*', strlen($value)) . "\n";
            } else {
                echo "  $var = $value\n";
            }
        } else {
            echo "  ✗ $var NOT SET\n";
        }
    }
}

// Try database connection
echo "\n=== Database Connection Test ===\n";
try {
    if (file_exists($configPath)) {
        require_once dirname(__DIR__) . '/config/config.php';
        require_once dirname(__DIR__) . '/config/database.php';
        
        $db = getDbConnection();
        echo "✓ Database connection successful\n";
        
        // Test query
        $stmt = $db->query("SELECT COUNT(*) as count FROM organisations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Organisations table: " . $result['count'] . " records\n";
    } else {
        echo "✗ Cannot test database - config.php not found\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

// Check uploads directory
echo "\n=== Uploads Directory Check ===\n";
$uploadsPath = dirname(__DIR__) . '/uploads';
if (file_exists($uploadsPath)) {
    echo "✓ uploads directory exists\n";
    echo "  Writable: " . (is_writable($uploadsPath) ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ uploads directory NOT found\n";
    echo "  Attempting to create...\n";
    if (mkdir($uploadsPath, 0755, true)) {
        echo "  ✓ Created successfully\n";
    } else {
        echo "  ✗ Failed to create\n";
    }
}

// Check PHP version
echo "\n=== PHP Configuration ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";

// Check required PHP extensions
echo "\n=== PHP Extensions ===\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($requiredExtensions as $ext) {
    echo "  $ext: " . (extension_loaded($ext) ? '✓ Loaded' : '✗ NOT LOADED') . "\n";
}

echo "\n=== End of Debug Info ===\n";
echo "</pre>";

