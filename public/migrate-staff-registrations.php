<?php
/**
 * Migration script to create staff_registrations table
 * Run this script once to create the table for tracking professional registrations
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
    $check = $db->query("SHOW TABLES LIKE 'staff_registrations'");
    if ($check->rowCount() == 0) {
        // Create the table
        $sql = "
        CREATE TABLE staff_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_id INT NOT NULL COMMENT 'Reference to staff_profiles.person_id',
            organisation_id INT NOT NULL COMMENT 'Reference to organisations.id',
            registration_type VARCHAR(100) NOT NULL COMMENT 'Type of registration (e.g., Social Services, HCPC, NMC, etc.)',
            registration_number VARCHAR(100) NULL COMMENT 'Registration number or reference',
            registration_body VARCHAR(255) NULL COMMENT 'Issuing body (e.g., Social Care Wales, HCPC, etc.)',
            issue_date DATE NULL COMMENT 'Date registration was issued',
            expiry_date DATE NOT NULL COMMENT 'Date registration expires',
            renewal_date DATE NULL COMMENT 'Date registration should be renewed',
            is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this registration is currently active',
            is_required_for_role BOOLEAN DEFAULT TRUE COMMENT 'Whether this registration is required for their role',
            notes TEXT NULL COMMENT 'Additional notes about the registration',
            document_path VARCHAR(500) NULL COMMENT 'Path to uploaded registration document',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
            FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
            
            INDEX idx_person_id (person_id),
            INDEX idx_organisation_id (organisation_id),
            INDEX idx_expiry_date (expiry_date),
            INDEX idx_registration_type (registration_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Tracks professional registrations and certifications for staff members'
        ";
        
        $db->exec($sql);
        $success[] = "Created staff_registrations table";
    } else {
        $success[] = "Table staff_registrations already exists";
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
    <title>Staff Registrations Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Staff Registrations Table Migration</h1>
    
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

