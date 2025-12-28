<?php
/**
 * Migration script to create organisation_requests table
 * Run this script once to create the table for storing organisation access requests
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

use DigitalID\SharedAuth\CSRF;

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Please log in to run this migration.');
}

$db = getDbConnection();
$errors = [];
$success = [];

try {
    $db->beginTransaction();
    
    // Check if table exists
    $check = $db->query("SHOW TABLES LIKE 'organisation_requests'");
    if ($check->rowCount() == 0) {
        // Read and execute the SQL file
        $sqlFile = dirname(__DIR__) . '/sql/migrations/create_organisation_requests_table.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            // Remove comments and split by semicolon
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^--/', $stmt);
                }
            );
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->exec($statement);
                }
            }
            
            $success[] = "Table 'organisation_requests' created successfully.";
        } else {
            $errors[] = "SQL file not found: $sqlFile";
        }
    } else {
        $success[] = "Table 'organisation_requests' already exists.";
    }
    
    $db->commit();
    
} catch (Exception $e) {
    $db->rollBack();
    $errors[] = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrate Organisation Requests Table</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .success { color: #16a34a; margin: 10px 0; }
        .error { color: #dc2626; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Organisation Requests Table Migration</h1>
    
    <?php if (!empty($success)): ?>
        <div class="success">
            <h2>Success:</h2>
            <ul>
                <?php foreach ($success as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <h2>Errors:</h2>
            <ul>
                <?php foreach ($errors as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <p><a href="<?php echo url('index.php'); ?>">Return to Home</a></p>
</body>
</html>


