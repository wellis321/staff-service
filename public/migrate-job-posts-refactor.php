<?php
/**
 * Migration script to refactor job descriptions and create job posts
 * Run this via browser: http://localhost:8000/migrate-job-posts-refactor.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Job Posts Refactor Migration</h1>";
echo "<pre>";

try {
    // Step 1: Create job_posts table
    echo "=== Step 1: Creating job_posts table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS job_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                organisation_id INT NOT NULL,
                job_description_id INT NOT NULL COMMENT 'Reference to generic job description template',
                title VARCHAR(255) NOT NULL COMMENT 'Specific post title (can override job description title)',
                code VARCHAR(100) NULL COMMENT 'Post code/reference',
                location VARCHAR(255) NULL COMMENT 'Specific location for this post',
                place_of_work VARCHAR(255) NULL COMMENT 'Specific place of work',
                hours_per_week DECIMAL(5,2) NULL COMMENT 'Hours for this specific post',
                contract_type VARCHAR(50) NULL COMMENT 'e.g. Permanent, Temporary, Contract, Part-time, Full-time',
                salary_range_min DECIMAL(10,2) NULL COMMENT 'Minimum salary for this post',
                salary_range_max DECIMAL(10,2) NULL COMMENT 'Maximum salary for this post',
                salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency code',
                reporting_to VARCHAR(255) NULL COMMENT 'Reports to position',
                manager_user_id INT NULL COMMENT 'Specific manager for this post',
                department VARCHAR(255) NULL COMMENT 'Department for this post',
                additional_requirements TEXT NULL COMMENT 'Additional requirements specific to this post',
                specific_attributes TEXT NULL COMMENT 'Specific attributes needed (e.g. gender, language skills)',
                external_system VARCHAR(100) NULL COMMENT 'External system name if synced',
                external_id VARCHAR(100) NULL COMMENT 'ID in external system',
                external_url VARCHAR(500) NULL COMMENT 'URL in external system',
                is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this post is currently active/open',
                is_open BOOLEAN DEFAULT TRUE COMMENT 'Whether this post is open for applications',
                created_by INT NULL COMMENT 'User who created this post',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
                FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE RESTRICT,
                FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_organisation (organisation_id),
                INDEX idx_job_description (job_description_id),
                INDEX idx_location (location),
                INDEX idx_is_active (is_active),
                INDEX idx_is_open (is_open),
                INDEX idx_external (external_system, external_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = "Created job_posts table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "job_posts table already exists";
        } else {
            $errors[] = "Failed to create job_posts table: " . $e->getMessage();
        }
    }
    
    // Step 2: Add job_post_id to staff_profiles
    echo "\n=== Step 2: Adding job_post_id to staff_profiles ===\n";
    $stmt = $db->query("DESCRIBE staff_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('job_post_id', $columns)) {
        try {
            $db->exec("ALTER TABLE staff_profiles ADD COLUMN job_post_id INT NULL COMMENT 'Reference to job_posts table'");
            $success[] = "Added job_post_id column to staff_profiles";
        } catch (PDOException $e) {
            $errors[] = "Failed to add job_post_id column: " . $e->getMessage();
        }
    } else {
        $success[] = "job_post_id column already exists";
    }
    
    // Step 3: Add foreign key constraint for job_post_id
    echo "\n=== Step 3: Adding foreign key constraint ===\n";
    try {
        $checkStmt = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'staff_profiles' 
            AND CONSTRAINT_NAME = 'fk_job_post'
        ");
        
        if ($checkStmt->rowCount() == 0) {
            $db->exec("
                ALTER TABLE staff_profiles 
                ADD CONSTRAINT fk_job_post 
                FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE SET NULL
            ");
            $success[] = "Added foreign key constraint fk_job_post";
        } else {
            $success[] = "Foreign key constraint fk_job_post already exists";
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
    
    echo "\n=== Note ===\n";
    echo "After running this migration, you may want to:\n";
    echo "1. Simplify job_descriptions table (remove position-specific fields)\n";
    echo "2. Migrate existing data from job_descriptions to job_posts if needed\n";
    echo "3. Update staff_profiles to use job_post_id instead of job_description_id\n";
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a></p>";

