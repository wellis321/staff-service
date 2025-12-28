<?php
/**
 * Migration script to add employment details fields and create job descriptions tables
 * Run this via browser: http://localhost:8000/migrate-employment-fields.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Employment Details Migration</h1>";
echo "<pre>";

try {
    // Step 1: Add employment fields to staff_profiles
    echo "=== Step 1: Adding employment fields to staff_profiles ===\n";
    $stmt = $db->query("DESCRIBE staff_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $employmentFields = [
        'contracted_hours' => "DECIMAL(5,2) NULL COMMENT 'Contracted hours per week'",
        'place_of_work' => "VARCHAR(255) NULL COMMENT 'Primary place of work/location'",
        'job_description_id' => "INT NULL COMMENT 'Reference to job_descriptions table'",
        'external_job_description_url' => "VARCHAR(500) NULL COMMENT 'URL to job description in external system'",
        'external_job_description_ref' => "VARCHAR(100) NULL COMMENT 'Reference ID for job description in external system'"
    ];
    
    foreach ($employmentFields as $fieldName => $fieldDef) {
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
    
    // Step 2: Create job_descriptions table
    echo "\n=== Step 2: Creating job_descriptions table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS job_descriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                organisation_id INT NOT NULL,
                title VARCHAR(255) NOT NULL COMMENT 'Job title',
                code VARCHAR(100) NULL COMMENT 'Job code/reference',
                description TEXT NULL COMMENT 'Full job description',
                responsibilities TEXT NULL COMMENT 'Key responsibilities',
                requirements TEXT NULL COMMENT 'Required qualifications/skills',
                salary_range_min DECIMAL(10,2) NULL COMMENT 'Minimum salary',
                salary_range_max DECIMAL(10,2) NULL COMMENT 'Maximum salary',
                salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency code',
                hours_per_week DECIMAL(5,2) NULL COMMENT 'Standard hours per week',
                contract_type VARCHAR(50) NULL COMMENT 'e.g. Permanent, Temporary, Contract',
                location VARCHAR(255) NULL COMMENT 'Standard location',
                department VARCHAR(255) NULL COMMENT 'Department',
                reporting_to VARCHAR(255) NULL COMMENT 'Reports to position',
                external_system VARCHAR(100) NULL COMMENT 'External system name if synced',
                external_id VARCHAR(100) NULL COMMENT 'ID in external system',
                external_url VARCHAR(500) NULL COMMENT 'URL in external system',
                is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this job description is currently active',
                version INT DEFAULT 1 COMMENT 'Version number for tracking changes',
                created_by INT NULL COMMENT 'User who created this',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_organisation (organisation_id),
                INDEX idx_title (title),
                INDEX idx_code (code),
                INDEX idx_external (external_system, external_id),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = "Created job_descriptions table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "job_descriptions table already exists";
        } else {
            $errors[] = "Failed to create job_descriptions table: " . $e->getMessage();
        }
    }
    
    // Step 3: Create job_description_documents table
    echo "\n=== Step 3: Creating job_description_documents table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS job_description_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_description_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_type VARCHAR(100) NULL,
                file_size INT NULL COMMENT 'Size in bytes',
                description TEXT NULL,
                uploaded_by INT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_job_description (job_description_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = "Created job_description_documents table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "job_description_documents table already exists";
        } else {
            $errors[] = "Failed to create job_description_documents table: " . $e->getMessage();
        }
    }
    
    // Step 4: Create staff_documents table
    echo "\n=== Step 4: Creating staff_documents table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS staff_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                person_id INT NOT NULL,
                document_type VARCHAR(100) NOT NULL COMMENT 'e.g. contract, offer_letter, id_verification, training_certificate',
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                filename VARCHAR(255) NULL COMMENT 'If stored locally',
                file_path VARCHAR(500) NULL COMMENT 'Path to local file',
                external_url VARCHAR(500) NULL COMMENT 'URL if stored in external system',
                external_system VARCHAR(100) NULL COMMENT 'External system name',
                external_id VARCHAR(100) NULL COMMENT 'ID in external system',
                file_type VARCHAR(100) NULL COMMENT 'MIME type',
                file_size INT NULL COMMENT 'Size in bytes',
                expiry_date DATE NULL COMMENT 'If document has expiry date',
                uploaded_by INT NULL COMMENT 'User who uploaded/created reference',
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_person (person_id),
                INDEX idx_document_type (document_type),
                INDEX idx_external (external_system, external_id),
                INDEX idx_expiry_date (expiry_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = "Created staff_documents table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "staff_documents table already exists";
        } else {
            $errors[] = "Failed to create staff_documents table: " . $e->getMessage();
        }
    }
    
    // Step 5: Add foreign key constraint for job_description_id
    echo "\n=== Step 5: Adding foreign key constraint ===\n";
    try {
        // Check if constraint already exists
        $checkStmt = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'staff_profiles' 
            AND CONSTRAINT_NAME = 'fk_job_description'
        ");
        
        if ($checkStmt->rowCount() == 0) {
            $db->exec("
                ALTER TABLE staff_profiles 
                ADD CONSTRAINT fk_job_description 
                FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE SET NULL
            ");
            $success[] = "Added foreign key constraint fk_job_description";
        } else {
            $success[] = "Foreign key constraint fk_job_description already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to add foreign key: " . $e->getMessage();
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

