<?php
/**
 * Migration script to add TUPE support and job post history tracking
 * Run this via browser: http://localhost:8000/migrate-tupe-and-history.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>TUPE and Job Post History Migration</h1>";
echo "<pre>";

try {
    // Step 1: Add TUPE fields to staff_profiles
    echo "=== Step 1: Adding TUPE fields to staff_profiles ===\n";
    $stmt = $db->query("DESCRIBE staff_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tupeFields = [
        'is_tupe' => "BOOLEAN DEFAULT FALSE COMMENT 'Whether this staff member has a TUPE contract'",
        'tupe_transfer_date' => "DATE NULL COMMENT 'Date of TUPE transfer'",
        'tupe_previous_organisation' => "VARCHAR(255) NULL COMMENT 'Previous organisation name'",
        'tupe_previous_employer_ref' => "VARCHAR(100) NULL COMMENT 'Reference/ID from previous employer'",
        'tupe_contract_type' => "VARCHAR(50) NULL COMMENT 'Contract type under TUPE (overrides job post)'",
        'tupe_hours_per_week' => "DECIMAL(5,2) NULL COMMENT 'Hours per week under TUPE (overrides job post)'",
        'tupe_salary' => "DECIMAL(10,2) NULL COMMENT 'Salary under TUPE (overrides job post)'",
        'tupe_salary_currency' => "VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency for TUPE salary'",
        'tupe_notes' => "TEXT NULL COMMENT 'Additional TUPE-related notes'"
    ];
    
    foreach ($tupeFields as $fieldName => $fieldDef) {
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
    
    // Add index for is_tupe if it doesn't exist
    try {
        $indexCheck = $db->query("
            SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'staff_profiles' 
            AND INDEX_NAME = 'idx_is_tupe'
        ");
        if ($indexCheck->fetch()['cnt'] == 0) {
            $db->exec("ALTER TABLE staff_profiles ADD INDEX idx_is_tupe (is_tupe)");
            $success[] = "Added index idx_is_tupe";
        } else {
            $success[] = "Index idx_is_tupe already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to add index: " . $e->getMessage();
    }
    
    // Step 2: Create job_post_history table
    echo "\n=== Step 2: Creating job_post_history table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS job_post_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_post_id INT NOT NULL,
                changed_by INT NULL COMMENT 'User who made the change',
                change_type VARCHAR(50) DEFAULT 'update' COMMENT 'Type of change: create, update, activate, deactivate',
                title VARCHAR(255) NULL,
                code VARCHAR(100) NULL,
                location VARCHAR(255) NULL,
                place_of_work VARCHAR(255) NULL,
                hours_per_week DECIMAL(5,2) NULL,
                contract_type VARCHAR(50) NULL,
                salary_range_min DECIMAL(10,2) NULL,
                salary_range_max DECIMAL(10,2) NULL,
                salary_currency VARCHAR(3) NULL,
                reporting_to VARCHAR(255) NULL,
                manager_user_id INT NULL,
                department VARCHAR(255) NULL,
                additional_requirements TEXT NULL,
                specific_attributes TEXT NULL,
                is_active BOOLEAN NULL,
                is_open BOOLEAN NULL,
                changed_fields JSON NULL COMMENT 'JSON array of field names that changed',
                change_notes TEXT NULL COMMENT 'Notes about this change',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE,
                FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_job_post (job_post_id),
                INDEX idx_created_at (created_at),
                INDEX idx_change_type (change_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = "Created job_post_history table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "job_post_history table already exists";
        } else {
            $errors[] = "Failed to create job_post_history table: " . $e->getMessage();
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
    echo "TUPE support added:\n";
    echo "- Staff can now be marked as having TUPE contracts\n";
    echo "- TUPE terms (contract type, hours, salary) override job post terms\n";
    echo "- Previous organisation and transfer date can be tracked\n";
    echo "\nJob post history tracking added:\n";
    echo "- All changes to job posts are now tracked\n";
    echo "- Can view historical changes (e.g., salary changes over time)\n";
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a></p>";

