<?php
/**
 * Migration script to add Working Time Directive (WTD) agreement support
 * Run this via browser: http://localhost:8000/migrate-wtd-support.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Working Time Directive Support Migration</h1>";
echo "<pre>";

try {
    echo "=== Adding WTD agreement fields to staff_profiles ===\n";
    $stmt = $db->query("DESCRIBE staff_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $wtdFields = [
        'wtd_agreed' => "BOOLEAN DEFAULT FALSE COMMENT 'Whether staff member has agreed to Working Time Directive'",
        'wtd_agreement_date' => "DATE NULL COMMENT 'Date when WTD agreement was signed'",
        'wtd_agreement_version' => "VARCHAR(50) NULL COMMENT 'Version of WTD agreement document'",
        'wtd_opt_out' => "BOOLEAN DEFAULT FALSE COMMENT 'Whether staff member has opted out of 48-hour week limit'",
        'wtd_opt_out_date' => "DATE NULL COMMENT 'Date when opt-out was signed'",
        'wtd_opt_out_expiry_date' => "DATE NULL COMMENT 'Date when opt-out expires (if applicable)'",
        'wtd_notes' => "TEXT NULL COMMENT 'Additional notes about WTD agreement'"
    ];
    
    foreach ($wtdFields as $fieldName => $fieldDef) {
        if (!in_array($fieldName, $columns)) {
            echo "Adding column: $fieldName...\n";
            try {
                $db->exec("ALTER TABLE staff_profiles ADD COLUMN $fieldName $fieldDef");
                $success[] = "Added column: $fieldName";
            } catch (PDOException $e) {
                $errors[] = "Failed to add column $fieldName: " . $e->getMessage();
            }
        } else {
            $success[] = "Column $fieldName already exists";
        }
    }
    
    // Add indexes
    $indexes = [
        'idx_wtd_agreed' => 'wtd_agreed',
        'idx_wtd_opt_out' => 'wtd_opt_out',
        'idx_wtd_opt_out_expiry' => 'wtd_opt_out_expiry_date'
    ];
    
    foreach ($indexes as $indexName => $columnName) {
        try {
            $indexCheck = $db->query("
                SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'staff_profiles' 
                AND INDEX_NAME = '$indexName'
            ");
            if ($indexCheck->fetch()['cnt'] == 0) {
                $db->exec("ALTER TABLE staff_profiles ADD INDEX $indexName ($columnName)");
                $success[] = "Added index $indexName";
            } else {
                $success[] = "Index $indexName already exists";
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to add index $indexName: " . $e->getMessage();
        }
    }
    
    echo "\n=== Results ===\n";
    foreach ($success as $msg) {
        echo "✓ $msg\n";
    }
    
    if (!empty($errors)) {
        echo "\n=== Errors ===\n";
        foreach ($errors as $msg) {
            echo "✗ $msg\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Working Time Directive support added:\n";
    echo "- Track whether staff have agreed to WTD\n";
    echo "- Track agreement date and version\n";
    echo "- Track opt-out status (UK allows opt-out from 48-hour week limit)\n";
    echo "- Track opt-out expiry dates\n";
    echo "- Staff can view/edit their WTD agreement in their profile\n";
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a></p>";

