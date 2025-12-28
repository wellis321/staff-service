<?php
/**
 * Migration script to create staff_role_history table
 * Run this script once to create the table for tracking role changes and salary history
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
    $check = $db->query("SHOW TABLES LIKE 'staff_role_history'");
    if ($check->rowCount() == 0) {
        // Create the table
        $sql = "
        CREATE TABLE staff_role_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_id INT NOT NULL COMMENT 'Reference to people.id',
            organisation_id INT NOT NULL COMMENT 'Reference to organisations.id',
            job_post_id INT NULL COMMENT 'Reference to job_posts.id - the role/position',
            job_title VARCHAR(255) NULL COMMENT 'Job title (can be different from job_post title if customized)',
            start_date DATE NOT NULL COMMENT 'Date role started',
            end_date DATE NULL COMMENT 'Date role ended (NULL if current role)',
            is_current BOOLEAN DEFAULT FALSE COMMENT 'Whether this is the current active role',
            salary DECIMAL(10,2) NULL COMMENT 'Salary for this role',
            salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Salary currency',
            hours_per_week DECIMAL(5,2) NULL COMMENT 'Hours per week for this role',
            contract_type VARCHAR(50) NULL COMMENT 'Contract type for this role',
            line_manager_id INT NULL COMMENT 'Line manager for this role',
            place_of_work VARCHAR(255) NULL COMMENT 'Place of work for this role',
            notes TEXT NULL COMMENT 'Notes about this role assignment',
            created_by INT NULL COMMENT 'User who created this record',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
            FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
            FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE SET NULL,
            FOREIGN KEY (line_manager_id) REFERENCES people(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_person_id (person_id),
            INDEX idx_organisation_id (organisation_id),
            INDEX idx_job_post_id (job_post_id),
            INDEX idx_start_date (start_date),
            INDEX idx_end_date (end_date),
            INDEX idx_is_current (is_current),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Tracks role history and salary changes for staff members over time'
        ";
        
        $db->exec($sql);
        $success[] = "Created staff_role_history table";
    } else {
        $success[] = "Table staff_role_history already exists";
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
    <title>Role History Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Staff Role History Table Migration</h1>
    
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

