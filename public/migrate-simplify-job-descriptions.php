<?php
/**
 * Migration script to simplify job_descriptions table
 * Remove position-specific fields that belong in job_posts
 * Run this via browser: http://localhost:8000/migrate-simplify-job-descriptions.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Simplify Job Descriptions Migration</h1>";
echo "<pre>";

try {
    echo "=== Removing position-specific fields from job_descriptions ===\n";
    echo "These fields should be in job_posts, not job_descriptions.\n\n";
    
    // Get current columns
    $stmt = $db->query("DESCRIBE job_descriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fields to remove (position-specific)
    $fieldsToRemove = [
        'location',
        'hours_per_week',
        'salary_range_min',
        'salary_range_max',
        'salary_currency',
        'contract_type',
        'reporting_to',
        'department'
    ];
    
    foreach ($fieldsToRemove as $fieldName) {
        if (in_array($fieldName, $columns)) {
            echo "Removing column: $fieldName...\n";
            try {
                $db->exec("ALTER TABLE job_descriptions DROP COLUMN $fieldName");
                $success[] = "Removed column: $fieldName";
            } catch (PDOException $e) {
                $errors[] = "Failed to remove column $fieldName: " . $e->getMessage();
            }
        } else {
            $success[] = "Column $fieldName does not exist (already removed or never existed)";
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
    echo "Job descriptions table has been simplified to contain only generic template fields:\n";
    echo "- title, code, description, responsibilities, requirements\n";
    echo "- external_system, external_id, external_url\n";
    echo "- is_active, version, created_by, created_at, updated_at\n";
    echo "\nPosition-specific fields (location, hours, salary, etc.) should now be in job_posts.\n";
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a></p>";

