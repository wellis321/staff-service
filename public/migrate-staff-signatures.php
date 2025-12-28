<?php
/**
 * Migration script to add staff signature support
 * Run this via browser: http://localhost:8000/migrate-staff-signatures.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Staff Signature Support Migration</h1>";
echo "<pre>";

try {
    echo "=== Adding signature fields to staff_profiles ===\n";
    $stmt = $db->query("DESCRIBE staff_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $signatureFields = [
        'signature_path' => "VARCHAR(500) NULL COMMENT 'Path to uploaded signature image file'",
        'signature_created_at' => "DATETIME NULL COMMENT 'When the signature was created/uploaded'",
        'signature_method' => "ENUM('upload', 'digital') NULL COMMENT 'Method used to create signature (upload or digital drawing)'"
    ];
    
    foreach ($signatureFields as $fieldName => $fieldDef) {
        if (!in_array($fieldName, $columns)) {
            echo "Adding column: $fieldName...\n";
            try {
                $db->exec("ALTER TABLE staff_profiles ADD COLUMN $fieldName $fieldDef");
                $success[] = "Added column: $fieldName";
            } catch (PDOException $e) {
                // Check if error is due to column already existing (might have been added manually)
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    $success[] = "Column $fieldName already exists (was added manually)";
                } else {
                    $errors[] = "Failed to add column $fieldName: " . $e->getMessage();
                }
            }
        } else {
            $success[] = "Column $fieldName already exists";
        }
    }
    
    // Add index
    echo "\n=== Adding indexes ===\n";
    try {
        $stmt = $db->query("SHOW INDEX FROM staff_profiles WHERE Key_name = 'idx_signature_path'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE staff_profiles ADD INDEX idx_signature_path (signature_path)");
            $success[] = "Added index: idx_signature_path";
        } else {
            $success[] = "Index idx_signature_path already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to add index: " . $e->getMessage();
    }
    
    // Create signature upload directory
    echo "\n=== Creating signature upload directory ===\n";
    $signaturePath = SIGNATURE_UPLOAD_PATH;
    if (!is_dir($signaturePath)) {
        if (mkdir($signaturePath, 0755, true)) {
            $success[] = "Created directory: $signaturePath";
        } else {
            $errors[] = "Failed to create directory: $signaturePath";
        }
    } else {
        $success[] = "Directory already exists: $signaturePath";
    }
    
} catch (Exception $e) {
    $errors[] = "Migration error: " . $e->getMessage();
}

echo "\n=== Results ===\n";
if (!empty($success)) {
    echo "\n✓ Success:\n";
    foreach ($success as $msg) {
        echo "  - $msg\n";
    }
}

if (!empty($errors)) {
    echo "\n✗ Errors:\n";
    foreach ($errors as $msg) {
        echo "  - $msg\n";
    }
}

echo "\n=== Migration Complete ===\n";
echo "</pre>";
echo "<p><a href='" . url('index.php') . "'>Return to Home</a></p>";

