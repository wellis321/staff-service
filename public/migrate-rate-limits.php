<?php
/**
 * Migration script to create rate_limits table
 * Run this once to set up rate limiting functionality
 */

require_once dirname(__DIR__) . '/config/config.php';

// Require superadmin
Auth::requireLogin();
if (!RBAC::isSuperAdmin()) {
    die('Access denied. Superadmin required.');
}

$db = getDbConnection();

echo "<h1>Rate Limits Migration</h1>";

// Read SQL file
$sqlFile = ROOT_PATH . '/sql/migrations/create_rate_limits_table.sql';
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Check if table already exists
$stmt = $db->query("SHOW TABLES LIKE 'rate_limits'");
if ($stmt->rowCount() > 0) {
    echo "<p style='color: orange;'>Table 'rate_limits' already exists. Skipping creation.</p>";
} else {
    try {
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Rate limits table created successfully!</p>";
    } catch (PDOException $e) {
        die("<p style='color: red;'>Error creating table: " . htmlspecialchars($e->getMessage()) . "</p>");
    }
}

echo "<p><a href='" . url('index.php') . "'>Back to Home</a></p>";

