<?php
/**
 * Migration script to create staff_learning_records table
 * Run this script once to create the table for tracking qualifications and learning
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

use DigitalID\SharedAuth\CSRF;

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Please log in to run this migration.');
}

$db = getDB();
$errors = [];
$success = [];

try {
    $db->beginTransaction();
    
    // Check if table exists
    $check = $db->query("SHOW TABLES LIKE 'staff_learning_records'");
    if ($check->rowCount() == 0) {
        // Create the table
        $sql = "
        CREATE TABLE staff_learning_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_id INT NOT NULL COMMENT 'Reference to people.id',
            organisation_id INT NOT NULL COMMENT 'Reference to organisations.id',
            record_type VARCHAR(50) NOT NULL COMMENT 'Type: qualification, course, training, certification, etc.',
            title VARCHAR(255) NOT NULL COMMENT 'Title of qualification/course/learning',
            description TEXT NULL COMMENT 'Description of the learning/qualification',
            provider VARCHAR(255) NULL COMMENT 'Provider/institution (e.g., university, training company)',
            qualification_level VARCHAR(100) NULL COMMENT 'Level (e.g., Level 2, Level 3, Degree, Masters)',
            subject_area VARCHAR(255) NULL COMMENT 'Subject or field of study',
            completion_date DATE NULL COMMENT 'Date completed/achieved',
            expiry_date DATE NULL COMMENT 'Expiry date if applicable (e.g., for certifications)',
            grade VARCHAR(50) NULL COMMENT 'Grade or result achieved',
            credits DECIMAL(5,2) NULL COMMENT 'Credits or hours (if applicable)',
            certificate_number VARCHAR(100) NULL COMMENT 'Certificate or qualification number',
            certificate_path VARCHAR(500) NULL COMMENT 'Path to uploaded certificate document',
            external_url VARCHAR(500) NULL COMMENT 'URL to external record (e.g., LMS link)',
            source_system VARCHAR(50) NULL COMMENT 'Source system: recruitment, lms, hr, manual',
            external_id VARCHAR(100) NULL COMMENT 'External ID from source system',
            is_mandatory BOOLEAN DEFAULT FALSE COMMENT 'Whether this is mandatory training',
            is_required_for_role BOOLEAN DEFAULT FALSE COMMENT 'Whether required for their current role',
            status VARCHAR(50) DEFAULT 'completed' COMMENT 'Status: completed, in_progress, expired, pending',
            notes TEXT NULL COMMENT 'Additional notes',
            created_by INT NULL COMMENT 'User who created this record',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_synced_at TIMESTAMP NULL COMMENT 'Last time synced from external system',
            
            FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
            FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_person_id (person_id),
            INDEX idx_organisation_id (organisation_id),
            INDEX idx_record_type (record_type),
            INDEX idx_completion_date (completion_date),
            INDEX idx_expiry_date (expiry_date),
            INDEX idx_status (status),
            INDEX idx_source_system (source_system),
            INDEX idx_external_id (external_id),
            INDEX idx_is_mandatory (is_mandatory)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Tracks qualifications and learning records for staff members, including from recruitment, LMS, and manual entries'
        ";
        
        $db->exec($sql);
        $success[] = "Created staff_learning_records table";
    } else {
        $success[] = "Table staff_learning_records already exists";
    }
    
    $db->commit();
    
} catch (Exception $e) {
    $db->rollBack();
    $errors[] = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Learning Records Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Staff Learning Records Table Migration</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <h2>Errors:</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="success">
            <h2>Results:</h2>
            <ul>
                <?php foreach ($success as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <p><a href="/">Return to Home</a></p>
</body>
</html>

