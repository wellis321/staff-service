<?php
/**
 * Migration script to add financial and identification fields to staff_profiles
 * Run this via browser: http://localhost:8000/migrate-staff-fields.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Staff Profile Fields Migration</h1>";
echo "<pre>";

try {
    // Check existing columns
    $stmt = $db->query("DESCRIBE staff_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $fieldsToAdd = [
        'ni_number' => "VARCHAR(20) NULL COMMENT 'National Insurance number (UK)'",
        'bank_sort_code' => "VARCHAR(10) NULL COMMENT 'Bank sort code'",
        'bank_account_number' => "VARCHAR(20) NULL COMMENT 'Bank account number'",
        'bank_account_name' => "VARCHAR(255) NULL COMMENT 'Account holder name'",
        'address_line1' => "VARCHAR(255) NULL COMMENT 'Address line 1'",
        'address_line2' => "VARCHAR(255) NULL COMMENT 'Address line 2'",
        'address_city' => "VARCHAR(100) NULL COMMENT 'City/Town'",
        'address_county' => "VARCHAR(100) NULL COMMENT 'County/State'",
        'address_postcode' => "VARCHAR(20) NULL COMMENT 'Postcode/ZIP'",
        'address_country' => "VARCHAR(100) NULL DEFAULT 'United Kingdom' COMMENT 'Country'"
    ];
    
    foreach ($fieldsToAdd as $fieldName => $fieldDef) {
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
    
    // Add index for NI number if it doesn't exist
    try {
        $db->exec("CREATE INDEX idx_ni_number ON staff_profiles(ni_number)");
        $success[] = "Added index: idx_ni_number";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            $errors[] = "Failed to add index: " . $e->getMessage();
        } else {
            $success[] = "Index idx_ni_number already exists";
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
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a></p>";

