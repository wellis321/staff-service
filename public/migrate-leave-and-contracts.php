<?php
/**
 * Migration script to add leave management and contract type fields
 * Run this script once to add the new columns to staff_profiles table
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
    
    // Check and add leave management columns
    $leaveColumns = [
        'annual_leave_allocation' => "DECIMAL(5,2) NULL COMMENT 'Annual leave allocation in days from job post'",
        'annual_leave_used' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Annual leave used in days'",
        'annual_leave_carry_over' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Annual leave carried over from previous year'",
        'time_in_lieu_hours' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Time in lieu hours accrued'",
        'time_in_lieu_used' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Time in lieu hours used'",
        'lying_time_hours' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Lying time hours accrued'",
        'lying_time_used' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Lying time hours used'",
        'leave_year_start_date' => "DATE NULL COMMENT 'Start date of current leave year'",
        'leave_year_end_date' => "DATE NULL COMMENT 'End date of current leave year'",
    ];
    
    foreach ($leaveColumns as $column => $definition) {
        $check = $db->query("SHOW COLUMNS FROM staff_profiles LIKE '$column'");
        if ($check->rowCount() == 0) {
            $db->exec("ALTER TABLE staff_profiles ADD COLUMN $column $definition");
            $success[] = "Added column: $column";
        } else {
            $success[] = "Column already exists: $column";
        }
    }
    
    // Check and add contract type columns
    $contractColumns = [
        'contract_type' => "VARCHAR(50) NULL COMMENT 'Contract type: permanent, fixed_term, zero_hours, bank, apprentice, agency, etc.'",
        'is_bank_staff' => "BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member is bank/casual staff'",
        'is_apprentice' => "BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member is an apprentice'",
        'has_visa' => "BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member requires a visa'",
        'visa_type' => "VARCHAR(100) NULL COMMENT 'Type of visa (e.g., Tier 2, Skilled Worker, etc.)'",
        'visa_number' => "VARCHAR(100) NULL COMMENT 'Visa reference number'",
        'visa_issue_date' => "DATE NULL COMMENT 'Visa issue date'",
        'visa_expiry_date' => "DATE NULL COMMENT 'Visa expiry date'",
        'visa_sponsor' => "VARCHAR(255) NULL COMMENT 'Visa sponsor organisation'",
        'apprenticeship_start_date' => "DATE NULL COMMENT 'Apprenticeship start date'",
        'apprenticeship_end_date' => "DATE NULL COMMENT 'Apprenticeship expected end date'",
        'apprenticeship_level' => "VARCHAR(50) NULL COMMENT 'Apprenticeship level (e.g., Level 2, Level 3, etc.)'",
        'apprenticeship_provider' => "VARCHAR(255) NULL COMMENT 'Apprenticeship training provider'",
    ];
    
    foreach ($contractColumns as $column => $definition) {
        $check = $db->query("SHOW COLUMNS FROM staff_profiles LIKE '$column'");
        if ($check->rowCount() == 0) {
            $db->exec("ALTER TABLE staff_profiles ADD COLUMN $column $definition");
            $success[] = "Added column: $column";
        } else {
            $success[] = "Column already exists: $column";
        }
    }
    
    // Add indexes if they don't exist
    $indexes = [
        'idx_leave_year' => '(leave_year_start_date, leave_year_end_date)',
        'idx_contract_type' => '(contract_type)',
        'idx_bank_staff' => '(is_bank_staff)',
        'idx_visa_expiry' => '(visa_expiry_date)',
        'idx_apprenticeship' => '(is_apprentice)',
    ];
    
    foreach ($indexes as $indexName => $columns) {
        $check = $db->query("SHOW INDEX FROM staff_profiles WHERE Key_name = '$indexName'");
        if ($check->rowCount() == 0) {
            $db->exec("ALTER TABLE staff_profiles ADD INDEX $indexName $columns");
            $success[] = "Added index: $indexName";
        } else {
            $success[] = "Index already exists: $indexName";
        }
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
    <title>Leave & Contract Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Leave Management & Contract Types Migration</h1>
    
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

