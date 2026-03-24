<?php
/**
 * Temporary diagnostic — DELETE after use.
 * Accessible without login so we can see the raw error.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config/config.php';

echo '<pre>';
echo "PHP: " . PHP_VERSION . "\n\n";

// 1. DB connection
try {
    $db = getDbConnection();
    echo "[OK] DB connection\n";
} catch (Throwable $e) {
    echo "[FAIL] DB connection: " . $e->getMessage() . "\n";
    exit;
}

// 2. Key tables
$tables = [
    'organisations', 'users', 'people', 'staff_profiles',
    'staff_registrations', 'pending_profile_changes',
    'organisation_settings', 'registration_notifications',
    'rate_limits',
];
foreach ($tables as $t) {
    try {
        $db->query("SELECT 1 FROM `$t` LIMIT 1");
        echo "[OK] table: $t\n";
    } catch (Throwable $e) {
        echo "[MISSING] table: $t — " . $e->getMessage() . "\n";
    }
}

// 3. OrgSettings::ensureTable
echo "\n";
try {
    OrgSettings::get(1, 'test_key', '');
    echo "[OK] OrgSettings::get\n";
} catch (Throwable $e) {
    echo "[FAIL] OrgSettings: " . $e->getMessage() . "\n";
}

// 4. TeamServiceClient::enabled
try {
    $on = TeamServiceClient::enabled(1);
    echo "[OK] TeamServiceClient::enabled = " . ($on ? 'true' : 'false') . "\n";
} catch (Throwable $e) {
    echo "[FAIL] TeamServiceClient::enabled: " . $e->getMessage() . "\n";
}

// 5. Person::getStaffByOrganisation
try {
    $rows = Person::getStaffByOrganisation(1, true, 1, 0);
    echo "[OK] Person::getStaffByOrganisation (" . count($rows) . " rows)\n";
} catch (Throwable $e) {
    echo "[FAIL] Person::getStaffByOrganisation: " . $e->getMessage() . "\n";
}

// 6. StaffRegistration::findByOrganisation
try {
    $regs = StaffRegistration::findByOrganisation(1);
    echo "[OK] StaffRegistration::findByOrganisation (" . count($regs) . " rows)\n";
} catch (Throwable $e) {
    echo "[FAIL] StaffRegistration::findByOrganisation: " . $e->getMessage() . "\n";
}

// 7. PendingProfileChange nav badge methods
try {
    PendingProfileChange::getPendingCountForManager(1, 1);
    echo "[OK] PendingProfileChange::getPendingCountForManager\n";
} catch (Throwable $e) {
    echo "[FAIL] PendingProfileChange::getPendingCountForManager: " . $e->getMessage() . "\n";
}
try {
    PendingProfileChange::getUnseenReviewedCountForStaff(1);
    echo "[OK] PendingProfileChange::getUnseenReviewedCountForStaff\n";
} catch (Throwable $e) {
    echo "[FAIL] PendingProfileChange::getUnseenReviewedCountForStaff: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
echo '</pre>';
