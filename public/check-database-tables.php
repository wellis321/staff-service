<?php
/**
 * Check which database tables exist
 * This will help identify if any tables are missing
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/config/config.php';

echo "<h1>Database Tables Check</h1>";
echo "<pre>";

try {
    $db = getDbConnection();
    
    // Get all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== All Tables in Database ===\n";
    echo "Total tables: " . count($tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }
    
    // Check for required core auth tables
    echo "\n=== Required Core Auth Tables (from shared-auth) ===\n";
    $requiredCoreTables = [
        'organisations',
        'users',
        'roles',
        'user_roles'
    ];
    
    $missingCore = [];
    foreach ($requiredCoreTables as $table) {
        if (in_array($table, $tables)) {
            // Check if table has data
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  ✓ $table (" . $result['count'] . " records)\n";
        } else {
            echo "  ✗ $table - MISSING!\n";
            $missingCore[] = $table;
        }
    }
    
    // Check for organisational units tables
    echo "\n=== Organisational Units Tables ===\n";
    $orgUnitTables = [
        'organisational_unit_types',
        'organisational_units',
        'person_organisational_units'
    ];
    
    foreach ($orgUnitTables as $table) {
        if (in_array($table, $tables)) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  ✓ $table (" . $result['count'] . " records)\n";
        } else {
            echo "  ✗ $table - MISSING\n";
        }
    }
    
    // Check for rate_limits table
    echo "\n=== Rate Limiting Table ===\n";
    if (in_array('rate_limits', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM rate_limits");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ✓ rate_limits (" . $result['count'] . " records)\n";
    } else {
        echo "  ✗ rate_limits - MISSING (RateLimiter will work but won't persist limits)\n";
    }
    
    // Check for people management tables
    echo "\n=== People Management Core Tables ===\n";
    $peopleTables = [
        'people',
        'staff_profiles',
        'person_relationships'
    ];
    
    foreach ($peopleTables as $table) {
        if (in_array($table, $tables)) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  ✓ $table (" . $result['count'] . " records)\n";
        } else {
            echo "  ✗ $table - MISSING\n";
        }
    }
    
    // Check for API tables
    echo "\n=== API Integration Tables ===\n";
    $apiTables = [
        'api_keys',
        'webhook_subscriptions',
        'external_system_sync',
        'entra_sync'
    ];
    
    foreach ($apiTables as $table) {
        if (in_array($table, $tables)) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  ✓ $table (" . $result['count'] . " records)\n";
        } else {
            echo "  ✗ $table - MISSING\n";
        }
    }
    
    // Summary
    echo "\n=== Summary ===\n";
    if (empty($missingCore)) {
        echo "✓ All core auth tables exist!\n";
    } else {
        echo "✗ Missing core tables: " . implode(', ', $missingCore) . "\n";
        echo "\nYou need to run the production_schema.sql file.\n";
        echo "The missing tables are required for the application to work.\n";
    }
    
    // Check if roles are populated
    if (in_array('roles', $tables)) {
        echo "\n=== Default Roles Check ===\n";
        $stmt = $db->query("SELECT name, description FROM roles");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $expectedRoles = ['superadmin', 'organisation_admin', 'staff'];
        $foundRoles = array_column($roles, 'name');
        
        foreach ($expectedRoles as $role) {
            if (in_array($role, $foundRoles)) {
                echo "  ✓ $role role exists\n";
            } else {
                echo "  ✗ $role role MISSING\n";
            }
        }
        
        if (count($roles) === 0) {
            echo "\n⚠ No roles found! You may need to run:\n";
            echo "INSERT IGNORE INTO roles (name, description) VALUES\n";
            echo "('superadmin', 'Super administrator with full system access'),\n";
            echo "('organisation_admin', 'Organisation administrator with full access to their organisation'),\n";
            echo "('staff', 'Standard staff member');\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error checking database: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";

