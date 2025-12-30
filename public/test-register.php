<?php
/**
 * Test script to diagnose register.php 500 error
 * This will help identify what's failing
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Register.php Diagnostic Test</h1>";
echo "<pre>";

$errors = [];
$success = [];

// Test 1: Load config
echo "=== Test 1: Loading config.php ===\n";
try {
    require_once dirname(__DIR__) . '/config/config.php';
    echo "✓ config.php loaded successfully\n";
    $success[] = "Config loaded";
} catch (Exception $e) {
    echo "✗ Failed to load config.php\n";
    echo "  Error: " . $e->getMessage() . "\n";
    $errors[] = "Config load failed: " . $e->getMessage();
    die("Cannot continue without config\n");
}

// Test 2: Check Auth class
echo "\n=== Test 2: Auth class ===\n";
if (class_exists('Auth')) {
    echo "✓ Auth class exists\n";
    if (method_exists('Auth', 'isLoggedIn')) {
        echo "✓ Auth::isLoggedIn() method exists\n";
    } else {
        echo "✗ Auth::isLoggedIn() method NOT found\n";
        $errors[] = "Auth::isLoggedIn() missing";
    }
} else {
    echo "✗ Auth class NOT found\n";
    $errors[] = "Auth class missing";
}

// Test 3: Check CSRF class
echo "\n=== Test 3: CSRF class ===\n";
if (class_exists('CSRF')) {
    echo "✓ CSRF class exists\n";
    if (method_exists('CSRF', 'validatePost')) {
        echo "✓ CSRF::validatePost() method exists\n";
    } else {
        echo "✗ CSRF::validatePost() method NOT found\n";
        $errors[] = "CSRF::validatePost() missing";
    }
} else {
    echo "✗ CSRF class NOT found\n";
    $errors[] = "CSRF class missing";
}

// Test 4: Check RateLimiter class
echo "\n=== Test 4: RateLimiter class ===\n";
if (class_exists('RateLimiter')) {
    echo "✓ RateLimiter class exists\n";
    if (method_exists('RateLimiter', 'getClientIp')) {
        echo "✓ RateLimiter::getClientIp() method exists\n";
    } else {
        echo "✗ RateLimiter::getClientIp() method NOT found\n";
        $errors[] = "RateLimiter::getClientIp() missing";
    }
    if (method_exists('RateLimiter', 'check')) {
        echo "✓ RateLimiter::check() method exists\n";
    } else {
        echo "✗ RateLimiter::check() method NOT found\n";
        $errors[] = "RateLimiter::check() missing";
    }
} else {
    echo "✗ RateLimiter class NOT found\n";
    $errors[] = "RateLimiter class missing";
}

// Test 5: Check Person class
echo "\n=== Test 5: Person class ===\n";
if (class_exists('Person')) {
    echo "✓ Person class exists\n";
    if (method_exists('Person', 'createStaff')) {
        echo "✓ Person::createStaff() method exists\n";
    } else {
        echo "✗ Person::createStaff() method NOT found\n";
        $errors[] = "Person::createStaff() missing";
    }
} else {
    echo "✗ Person class NOT found\n";
    $errors[] = "Person class missing";
}

// Test 6: Database connection
echo "\n=== Test 6: Database connection ===\n";
try {
    if (function_exists('getDbConnection')) {
        $db = getDbConnection();
        echo "✓ Database connection successful\n";
        
        // Test query
        $stmt = $db->query("SELECT COUNT(*) as count FROM organisations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Organisations table: " . $result['count'] . " records\n";
        $success[] = "Database connection works";
    } else {
        echo "✗ getDbConnection() function NOT found\n";
        $errors[] = "getDbConnection() missing";
    }
} catch (Exception $e) {
    echo "✗ Database connection FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
    $errors[] = "Database connection failed: " . $e->getMessage();
}

// Test 7: Check required constants
echo "\n=== Test 7: Required constants ===\n";
$requiredConstants = ['PASSWORD_MIN_LENGTH', 'INCLUDES_PATH'];
foreach ($requiredConstants as $const) {
    if (defined($const)) {
        echo "✓ $const = " . constant($const) . "\n";
    } else {
        echo "✗ $const NOT defined\n";
        $errors[] = "Constant $const missing";
    }
}

// Test 8: Check includes path
echo "\n=== Test 8: Includes path ===\n";
if (defined('INCLUDES_PATH')) {
    $headerPath = INCLUDES_PATH . '/header.php';
    if (file_exists($headerPath)) {
        echo "✓ header.php exists at: $headerPath\n";
    } else {
        echo "✗ header.php NOT found at: $headerPath\n";
        $errors[] = "header.php missing";
    }
}

// Test 9: Try to call Auth::isLoggedIn()
echo "\n=== Test 9: Calling Auth::isLoggedIn() ===\n";
try {
    $isLoggedIn = Auth::isLoggedIn();
    echo "✓ Auth::isLoggedIn() returned: " . ($isLoggedIn ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "✗ Auth::isLoggedIn() threw exception\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $errors[] = "Auth::isLoggedIn() exception: " . $e->getMessage();
}

// Test 10: Try to get rate limiter IP
echo "\n=== Test 10: RateLimiter::getClientIp() ===\n";
try {
    $ip = RateLimiter::getClientIp();
    echo "✓ RateLimiter::getClientIp() returned: $ip\n";
} catch (Exception $e) {
    echo "✗ RateLimiter::getClientIp() threw exception\n";
    echo "  Error: " . $e->getMessage() . "\n";
    $errors[] = "RateLimiter::getClientIp() exception: " . $e->getMessage();
}

// Summary
echo "\n=== Summary ===\n";
if (empty($errors)) {
    echo "✓ All tests passed! The issue might be elsewhere.\n";
    echo "\nTry accessing register.php now and check server error logs.\n";
} else {
    echo "✗ Found " . count($errors) . " error(s):\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✓ Successful checks: " . count($success) . "\n";

echo "</pre>";

