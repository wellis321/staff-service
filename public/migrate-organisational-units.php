<?php
/**
 * Migration script to create organisational_unit_members table
 * Run this once via browser: http://localhost:8000/migrate-organisational-units.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Simple security - only allow in development
if (getenv('APP_ENV') === 'production') {
    die('This script is not available in production.');
}

$db = getDbConnection();
$errors = [];
$success = [];

echo "<h1>Organisational Units Migration</h1>";
echo "<pre>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'organisational_unit_members'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Creating organisational_unit_members table...\n";
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS organisational_unit_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unit_id INT NOT NULL,
                user_id INT NOT NULL,
                role VARCHAR(100) DEFAULT 'member',
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (unit_id) REFERENCES organisational_units(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_unit_member (unit_id, user_id),
                INDEX idx_unit (unit_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $success[] = "organisational_unit_members table created successfully";
    } else {
        $success[] = "organisational_unit_members table already exists";
    }
    
    // Check and add missing columns to organisational_units
    $stmt = $db->query("DESCRIBE organisational_units");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [
        'is_active' => "BOOLEAN DEFAULT TRUE",
        'metadata' => "JSON NULL",
        'manager_user_id' => "INT NULL",
        'display_order' => "INT DEFAULT 0",
        'unit_type' => "VARCHAR(100) NULL"
    ];
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $columns)) {
            echo "Adding column: $columnName...\n";
            try {
                $db->exec("ALTER TABLE organisational_units ADD COLUMN $columnName $columnDef");
                $success[] = "Added column: $columnName";
            } catch (PDOException $e) {
                $errors[] = "Failed to add column $columnName: " . $e->getMessage();
            }
        } else {
            $success[] = "Column $columnName already exists";
        }
    }
    
    // Add foreign key for manager_user_id if column exists but constraint doesn't
    if (in_array('manager_user_id', $columns)) {
        $stmt = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'organisational_units' 
            AND CONSTRAINT_NAME = 'fk_organisational_units_manager'
        ");
        
        if ($stmt->rowCount() == 0) {
            echo "Adding foreign key constraint for manager_user_id...\n";
            try {
                $db->exec("
                    ALTER TABLE organisational_units 
                    ADD CONSTRAINT fk_organisational_units_manager 
                    FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL
                ");
                $success[] = "Added foreign key constraint for manager_user_id";
            } catch (PDOException $e) {
                $errors[] = "Failed to add foreign key: " . $e->getMessage();
            }
        }
    }
    
    // Add indexes
    $indexesToAdd = [
        'idx_is_active' => 'is_active',
        'idx_manager_user' => 'manager_user_id'
    ];
    
    foreach ($indexesToAdd as $indexName => $columnName) {
        if (in_array($columnName, $columns)) {
            try {
                $db->exec("ALTER TABLE organisational_units ADD INDEX $indexName ($columnName)");
                $success[] = "Added index: $indexName";
            } catch (PDOException $e) {
                // Index might already exist, that's okay
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    $errors[] = "Failed to add index $indexName: " . $e->getMessage();
                }
            }
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
    
    // Verify table exists
    echo "\n=== Verification ===\n";
    $stmt = $db->query("SHOW TABLES LIKE 'organisational_unit_members'");
    if ($stmt->rowCount() > 0) {
        echo "✓ organisational_unit_members table exists\n";
        $stmt = $db->query("DESCRIBE organisational_unit_members");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Columns: " . implode(', ', array_column($cols, 'Field')) . "\n";
    } else {
        echo "✗ organisational_unit_members table NOT found\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a></p>";

