<?php
/**
 * Temporary diagnostic — DELETE after use.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config/config.php';

echo '<pre>';
echo "PHP: " . PHP_VERSION . "\n\n";

$db = getDbConnection();

// Find the Sunrise Care org ID
$orgRow = $db->query("SELECT id FROM organisations WHERE domain = 'sunrisecare.demo' LIMIT 1")->fetch();
$orgId  = $orgRow ? (int)$orgRow['id'] : 0;
echo "Sunrise Care org_id = $orgId\n\n";

if (!$orgId) {
    echo "No Sunrise Care org found — seed the demo first.\n";
    exit;
}

// 1. Staff list query (what staff/index.php runs)
try {
    $perPage = ITEMS_PER_PAGE;
    $staff = Person::getStaffByOrganisation($orgId, true, $perPage, 0);
    $total = Person::countStaff($orgId, true);
    echo "[OK] Person::getStaffByOrganisation — $total staff\n";
} catch (Throwable $e) {
    echo "[FAIL] Person::getStaffByOrganisation: " . $e->getMessage() . "\n";
}

// 2. TeamServiceClient with real org ID
try {
    $on = TeamServiceClient::enabled($orgId);
    echo "[OK] TeamServiceClient::enabled($orgId) = " . ($on ? 'true' : 'false') . "\n";
    if ($on) {
        $memberships = TeamServiceClient::getAllStaffMemberships($orgId);
        echo "[OK] getAllStaffMemberships = " . (is_array($memberships) ? count($memberships) . " rows" : "null") . "\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] TeamServiceClient: " . $e->getMessage() . "\n";
}

// 3. StaffRegistration with real org ID
try {
    $regs = StaffRegistration::findByOrganisation($orgId);
    echo "[OK] StaffRegistration::findByOrganisation — " . count($regs) . " rows\n";
} catch (Throwable $e) {
    echo "[FAIL] StaffRegistration: " . $e->getMessage() . "\n";
}

// 4. Find a real person in this org and test nav badge queries
try {
    $personRow = $db->prepare("SELECT id FROM people WHERE organisation_id = ? AND person_type = 'staff' LIMIT 1");
    $personRow->execute([$orgId]);
    $person = $personRow->fetch();
    $personId = $person ? (int)$person['id'] : 0;
    echo "\nReal person_id = $personId\n";

    if ($personId) {
        PendingProfileChange::getPendingCountForManager($personId, $orgId);
        echo "[OK] getPendingCountForManager($personId)\n";

        PendingProfileChange::getUnseenReviewedCountForStaff($personId);
        echo "[OK] getUnseenReviewedCountForStaff($personId)\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] PendingProfileChange: " . $e->getMessage() . "\n";
}

// 5. Test RBAC class loading
try {
    $isAdmin = RBAC::isOrganisationAdmin();
    echo "\n[OK] RBAC::isOrganisationAdmin loaded (returns " . ($isAdmin ? 'true' : 'false') . " — no session)\n";
} catch (Throwable $e) {
    echo "[FAIL] RBAC: " . $e->getMessage() . "\n";
}

// 6. Check seen_by_staff_at column exists
try {
    $db->query("SELECT seen_by_staff_at FROM pending_profile_changes LIMIT 1");
    echo "[OK] pending_profile_changes.seen_by_staff_at column exists\n";
} catch (Throwable $e) {
    echo "[MISSING] pending_profile_changes.seen_by_staff_at: " . $e->getMessage() . "\n";
}

// 7. Check renewal_submitted_at column exists
try {
    $db->query("SELECT renewal_submitted_at FROM staff_registrations LIMIT 1");
    echo "[OK] staff_registrations.renewal_submitted_at column exists\n";
} catch (Throwable $e) {
    echo "[MISSING] staff_registrations.renewal_submitted_at: " . $e->getMessage() . "\n";
}

echo "\n--- renderStaffTable function test ---\n";
// 8. Try including the renderStaffTable logic
try {
    if (!empty($staff)) {
        ob_start();
        // Minimal simulation of what renderStaffTable does
        foreach ($staff as $m) {
            $initials = strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1));
            $avClass  = 'av-' . strtolower(substr($m['first_name'], 0, 1));
        }
        ob_end_clean();
        echo "[OK] Staff row iteration ok\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] Staff row iteration: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
echo '</pre>';
