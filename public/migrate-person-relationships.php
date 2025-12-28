<?php
/**
 * Migration script to create person_relationships table
 * Run this once to set up the table for linking person records
 */

require_once dirname(__DIR__) . '/config/config.php';

// Only allow super admins to run migrations
if (!RBAC::isSuperAdmin()) {
    die('Access denied. Only super administrators can run migrations.');
}

$db = getDbConnection();
$errors = [];
$success = [];

try {
    // Read and execute the migration SQL
    $migrationFile = dirname(__DIR__) . '/sql/migrations/create_person_relationships_table.sql';
    if (file_exists($migrationFile)) {
        $sql = file_get_contents($migrationFile);
        $db->exec($sql);
        $success[] = 'person_relationships table created successfully';
    } else {
        $errors[] = 'Migration file not found: ' . $migrationFile;
    }
    
    // Read and execute the index migration
    $indexFile = dirname(__DIR__) . '/sql/migrations/add_learning_records_employee_ref_index.sql';
    if (file_exists($indexFile)) {
        $sql = file_get_contents($indexFile);
        try {
            $db->exec($sql);
            $success[] = 'Learning records indexes added successfully';
        } catch (PDOException $e) {
            // Check if error is because index already exists
            if (strpos($e->getMessage(), 'Duplicate key name') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                $success[] = 'Learning records indexes already exist (skipped)';
            } else {
                throw $e; // Re-throw if it's a different error
            }
        }
    } else {
        $errors[] = 'Index migration file not found: ' . $indexFile;
    }
    
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Person Relationships Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <h1>Person Relationships Migration</h1>
    
    <?php if (!empty($success)): ?>
        <?php foreach ($success as $msg): ?>
            <div class="success">
                <strong>Success:</strong> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $msg): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (empty($errors) && !empty($success)): ?>
        <p><strong>Migration completed successfully!</strong></p>
        <p>You can now use the person record linking functionality.</p>
        <p><a href="<?php echo url('staff/index.php'); ?>">Go to Staff Management</a></p>
    <?php endif; ?>
</body>
</html>

