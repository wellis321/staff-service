<?php
/**
 * Diagnostic script for staff/edit.php issues
 * Access this at: /public/staff/edit-debug.php?id=2
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Staff Edit Page Diagnostics</h1>";
echo "<pre>";

try {
    echo "1. Loading config...\n";
    require_once dirname(__DIR__, 2) . '/config/config.php';
    echo "   ✓ Config loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "2. Checking authentication...\n";
    if (!Auth::isLoggedIn()) {
        echo "   ✗ Not logged in\n";
        die("Please log in first.");
    }
    echo "   ✓ Logged in\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

try {
    echo "3. Checking authorization...\n";
    if (!RBAC::isAdmin()) {
        echo "   ✗ Not authorized (not admin)\n";
        die("Admin access required.");
    }
    echo "   ✓ Authorized\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

try {
    echo "4. Getting organisation ID...\n";
    $organisationId = Auth::getOrganisationId();
    echo "   ✓ Organisation ID: " . ($organisationId ?? 'NULL') . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

try {
    echo "5. Getting person ID from GET...\n";
    $personId = $_GET['id'] ?? null;
    if (!$personId) {
        echo "   ✗ No person ID provided\n";
        die("Please provide ?id=2 in URL");
    }
    echo "   ✓ Person ID: $personId\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

try {
    echo "6. Finding person...\n";
    $person = Person::findById($personId, $organisationId);
    if (!$person) {
        echo "   ✗ Person not found\n";
        die();
    }
    echo "   ✓ Person found: " . ($person['first_name'] ?? '') . " " . ($person['last_name'] ?? '') . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "7. Getting database connection...\n";
    $db = getDbConnection();
    echo "   ✓ Database connected\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

try {
    echo "8. Querying users...\n";
    $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email,
               CASE WHEN p.id IS NOT NULL AND p.id != ? THEN 1 ELSE 0 END as has_other_profile
        FROM users u
        LEFT JOIN people p ON p.user_id = u.id AND p.organisation_id = ? AND p.id != ?
        WHERE u.organisation_id = ? AND u.is_active = TRUE
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$personId, $organisationId, $personId, $organisationId]);
    $users = $stmt->fetchAll();
    echo "   ✓ Found " . count($users) . " users\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "9. Getting staff by organisation...\n";
    $staffForManager = Person::getStaffByOrganisation($organisationId, true);
    echo "   ✓ Found " . count($staffForManager) . " staff members\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "10. Getting organisational units for person...\n";
    $organisationalUnits = Person::getOrganisationalUnits($personId);
    echo "   ✓ Found " . count($organisationalUnits) . " organisational units\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "11. Checking OrganisationalUnits class...\n";
    if (!class_exists('OrganisationalUnits')) {
        echo "   ✗ OrganisationalUnits class not found\n";
        echo "   Using fallback query...\n";
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT id, name, code 
            FROM organisational_units 
            WHERE organisation_id = ? 
            ORDER BY name
        ");
        $stmt->execute([$organisationId]);
        $allUnits = $stmt->fetchAll();
        echo "   ✓ Fallback query successful, found " . count($allUnits) . " units\n\n";
    } else {
        echo "   ✓ OrganisationalUnits class exists\n";
        if (!method_exists('OrganisationalUnits', 'getAllByOrganisation')) {
            echo "   ✗ getAllByOrganisation() method not found\n";
            echo "   Using fallback query...\n";
            $db = getDbConnection();
            $stmt = $db->prepare("
                SELECT id, name, code 
                FROM organisational_units 
                WHERE organisation_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$organisationId]);
            $allUnits = $stmt->fetchAll();
            echo "   ✓ Fallback query successful, found " . count($allUnits) . " units\n\n";
        } else {
            echo "   ✓ getAllByOrganisation() method exists\n";
            $allUnits = OrganisationalUnits::getAllByOrganisation($organisationId);
            echo "   ✓ Found " . (is_array($allUnits) ? count($allUnits) : 'N/A') . " units\n\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "12. Getting job descriptions...\n";
    $jobDescriptions = JobDescription::getAllByOrganisation($organisationId, true);
    echo "   ✓ Found " . count($jobDescriptions) . " job descriptions\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

try {
    echo "13. Getting job posts...\n";
    $jobPosts = JobPost::getAllByOrganisation($organisationId, true);
    echo "   ✓ Found " . count($jobPosts) . " job posts\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    die();
}

echo "✓ All checks passed! The issue might be in the HTML rendering or later code.\n";
echo "</pre>";
echo "<p><a href='edit.php?id=$personId'>Try accessing edit.php now</a></p>";

