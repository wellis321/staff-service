<?php
/**
 * Simple test to see if register.php can load
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Register Test</h1>";
echo "<pre>";

echo "Step 1: Loading config...\n";
require_once dirname(__DIR__) . '/config/config.php';
echo "✓ Config loaded\n";

echo "\nStep 2: Checking Auth::isLoggedIn()...\n";
try {
    $isLoggedIn = Auth::isLoggedIn();
    echo "✓ Auth::isLoggedIn() = " . ($isLoggedIn ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    die();
}

if ($isLoggedIn) {
    echo "\nUser is logged in, would redirect...\n";
} else {
    echo "\nUser not logged in, continuing...\n";
}

echo "\nStep 3: Testing CSRF token generation...\n";
try {
    $tokenField = CSRF::tokenField();
    echo "✓ CSRF token generated\n";
    echo "Token field length: " . strlen($tokenField) . " characters\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    die();
}

echo "\nStep 4: Testing url() function...\n";
try {
    $testUrl = url('login.php');
    echo "✓ url() function works: $testUrl\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    die();
}

echo "\nStep 5: Testing INCLUDES_PATH...\n";
if (defined('INCLUDES_PATH')) {
    echo "✓ INCLUDES_PATH = " . INCLUDES_PATH . "\n";
    $headerPath = INCLUDES_PATH . '/header.php';
    if (file_exists($headerPath)) {
        echo "✓ header.php exists\n";
    } else {
        echo "✗ header.php NOT found at: $headerPath\n";
        die();
    }
} else {
    echo "✗ INCLUDES_PATH not defined\n";
    die();
}

echo "\nStep 6: Testing if we can include header (without output)...\n";
try {
    ob_start();
    $pageTitle = 'Test Register';
    include INCLUDES_PATH . '/header.php';
    $headerOutput = ob_get_clean();
    echo "✓ Header included successfully\n";
    echo "Header output length: " . strlen($headerOutput) . " characters\n";
} catch (Exception $e) {
    echo "✗ Error including header: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
} catch (Throwable $e) {
    echo "✗ Fatal error including header: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
}

echo "\n=== All Tests Passed ===\n";
echo "The register.php file should work. If you're still getting a 500 error,\n";
echo "check your server's PHP error logs for the actual error message.\n";
echo "\nTry accessing: register-debug.php to see detailed error output.\n";

echo "</pre>";

