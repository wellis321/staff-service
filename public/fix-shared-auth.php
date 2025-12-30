<?php
/**
 * Fix nested shared-auth directory structure
 * Run this once to move files from shared-auth/shared-auth/ to shared-auth/
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Shared-Auth Structure</h1>";
echo "<pre>";

$rootPath = dirname(__DIR__);
$sharedAuthPath = $rootPath . '/shared-auth';
$nestedPath = $sharedAuthPath . '/shared-auth';

echo "Root path: $rootPath\n";
echo "Shared-auth path: $sharedAuthPath\n";
echo "Nested path: $nestedPath\n\n";

// Check if nested directory exists
if (is_dir($nestedPath)) {
    echo "✓ Found nested shared-auth directory\n";
    
    // Check if src directory exists in nested path
    $nestedSrc = $nestedPath . '/src';
    if (is_dir($nestedSrc)) {
        echo "✓ Found src directory in nested path\n";
        
        // List what's in the nested directory
        echo "\nContents of nested directory:\n";
        $items = scandir($nestedPath);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $itemPath = $nestedPath . '/' . $item;
                $type = is_dir($itemPath) ? 'DIR' : 'FILE';
                echo "  [$type] $item\n";
            }
        }
        
        // Check if files already exist in parent
        $parentSrc = $sharedAuthPath . '/src';
        if (is_dir($parentSrc)) {
            echo "\n⚠ WARNING: src directory already exists in parent!\n";
            echo "This script will NOT overwrite existing files.\n";
            echo "Please manually check and merge if needed.\n";
        } else {
            echo "\n✓ No existing files in parent - safe to move\n";
            
            // Move all items from nested to parent
            echo "\nMoving files...\n";
            $moved = 0;
            $items = scandir($nestedPath);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..') {
                    $source = $nestedPath . '/' . $item;
                    $dest = $sharedAuthPath . '/' . $item;
                    
                    if (rename($source, $dest)) {
                        echo "  ✓ Moved: $item\n";
                        $moved++;
                    } else {
                        echo "  ✗ Failed to move: $item\n";
                    }
                }
            }
            
            // Remove empty nested directory
            if (rmdir($nestedPath)) {
                echo "\n✓ Removed empty nested directory\n";
            } else {
                echo "\n⚠ Could not remove nested directory (may not be empty)\n";
            }
            
            echo "\n✓ Successfully moved $moved items\n";
        }
    } else {
        echo "✗ src directory not found in nested path\n";
    }
} else {
    echo "✗ Nested shared-auth directory not found\n";
    echo "\nChecking current structure...\n";
    
    // Check what's actually in shared-auth
    if (is_dir($sharedAuthPath)) {
        echo "\nContents of shared-auth directory:\n";
        $items = scandir($sharedAuthPath);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $itemPath = $sharedAuthPath . '/' . $item;
                $type = is_dir($itemPath) ? 'DIR' : 'FILE';
                $size = is_file($itemPath) ? ' (' . filesize($itemPath) . ' bytes)' : '';
                echo "  [$type] $item$size\n";
            }
        }
        
        // Check if src exists
        $srcPath = $sharedAuthPath . '/src';
        if (is_dir($srcPath)) {
            echo "\n✓ src directory exists!\n";
            echo "Contents of src:\n";
            $srcItems = scandir($srcPath);
            foreach ($srcItems as $item) {
                if ($item !== '.' && $item !== '..') {
                    $itemPath = $srcPath . '/' . $item;
                    $type = is_dir($itemPath) ? 'DIR' : 'FILE';
                    echo "  [$type] $item\n";
                }
            }
            
            // Check for Database.php specifically
            $dbFile = $srcPath . '/Database.php';
            if (file_exists($dbFile)) {
                echo "\n✓ Database.php found!\n";
                echo "The structure looks correct now.\n";
            } else {
                echo "\n✗ Database.php NOT found in src/\n";
            }
        } else {
            echo "\n✗ src directory does NOT exist\n";
        }
    } else {
        echo "✗ shared-auth directory does not exist\n";
    }
}

echo "\n=== Verification ===\n";
$dbFile = $sharedAuthPath . '/src/Database.php';
if (file_exists($dbFile)) {
    echo "✓ Database.php found at correct location!\n";
    echo "  Path: $dbFile\n";
    echo "\n✓ Structure is correct. You can now access the site.\n";
} else {
    echo "✗ Database.php still not found\n";
    echo "  Expected: $dbFile\n";
    echo "\nPlease check the file structure manually.\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Try accessing the site now</a></p>";

