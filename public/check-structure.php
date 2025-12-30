<?php
/**
 * Structure Check Script for Production
 * This will help identify where files are located
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Production Structure Check</h1>";
echo "<pre>";

// Get current file paths
$currentFile = __FILE__;
$currentDir = __DIR__;
$parentDir = dirname($currentDir);
$rootPath = dirname($parentDir);

echo "=== File Paths ===\n";
echo "Current file: $currentFile\n";
echo "Current directory (public/): $currentDir\n";
echo "Parent directory (project root): $parentDir\n";
echo "Root path (two levels up): $rootPath\n";

// Check where config.php is
echo "\n=== Config File Location ===\n";
$configPath1 = $parentDir . '/config/config.php';
$configPath2 = $rootPath . '/config/config.php';
$configPath3 = dirname($currentDir) . '/config/config.php';

echo "Expected config path (parent/config/): $configPath1\n";
echo "  Exists: " . (file_exists($configPath1) ? 'YES' : 'NO') . "\n";
echo "Expected config path (root/config/): $configPath2\n";
echo "  Exists: " . (file_exists($configPath2) ? 'YES' : 'NO') . "\n";
echo "Expected config path (dirname/config/): $configPath3\n";
echo "  Exists: " . (file_exists($configPath3) ? 'YES' : 'NO') . "\n";

// Find config.php
$foundConfig = null;
$searchPaths = [
    $parentDir . '/config/config.php',
    $rootPath . '/config/config.php',
    dirname($currentDir) . '/config/config.php',
    dirname(dirname($currentDir)) . '/config/config.php'
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $foundConfig = $path;
        echo "\n✓ Found config.php at: $path\n";
        break;
    }
}

if ($foundConfig) {
    // Calculate ROOT_PATH based on where config.php is
    $calculatedRoot = dirname(dirname($foundConfig));
    echo "Calculated ROOT_PATH: $calculatedRoot\n";
    
    // Check for shared-auth in various locations
    echo "\n=== Shared-Auth Location Check ===\n";
    $sharedAuthPaths = [
        $calculatedRoot . '/shared-auth',
        $parentDir . '/shared-auth',
        $rootPath . '/shared-auth',
        dirname($currentDir) . '/shared-auth',
        '/home/u248320297/domains/salmon-tarsier-739827.hostingersite.com/public_html/shared-auth'
    ];
    
    foreach ($sharedAuthPaths as $path) {
        echo "Checking: $path\n";
        if (file_exists($path)) {
            echo "  ✓ EXISTS!\n";
            if (is_dir($path)) {
                echo "  ✓ Is a directory\n";
                $dbFile = $path . '/src/Database.php';
                if (file_exists($dbFile)) {
                    echo "  ✓ Database.php found\n";
                } else {
                    echo "  ✗ Database.php NOT found\n";
                }
                // List contents
                echo "  Contents:\n";
                $contents = scandir($path);
                foreach ($contents as $item) {
                    if ($item !== '.' && $item !== '..') {
                        $itemPath = $path . '/' . $item;
                        $type = is_dir($itemPath) ? 'DIR' : 'FILE';
                        echo "    [$type] $item\n";
                    }
                }
            } else {
                echo "  ✗ Not a directory (might be a symlink or file)\n";
            }
        } else {
            echo "  ✗ NOT FOUND\n";
        }
    }
} else {
    echo "\n✗ Could not find config.php\n";
}

// Check document root
echo "\n=== Server Information ===\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "\n";

// List directory structure
echo "\n=== Directory Listing (parent) ===\n";
if (is_dir($parentDir)) {
    $items = scandir($parentDir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..') {
            $itemPath = $parentDir . '/' . $item;
            $type = is_dir($itemPath) ? 'DIR' : 'FILE';
            echo "  [$type] $item\n";
        }
    }
}

echo "\n=== Instructions ===\n";
echo "Based on the paths above:\n";
echo "1. Find where 'shared-auth' should be located\n";
echo "2. Upload the shared-auth directory to that location\n";
echo "3. Make sure it contains src/Database.php, src/Auth.php, etc.\n";
echo "4. Refresh this page to verify\n";

echo "</pre>";

