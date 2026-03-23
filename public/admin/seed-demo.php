<?php
/**
 * PMS — Demo Data Seeder
 *
 * Creates a "Sunrise Care" demo organisation with 6 staff members.
 * Safe to run multiple times — skips if the demo org already exists.
 * Super admin access only.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
if (!RBAC::isSuperAdmin()) {
    header('Location: ' . url('index.php'));
    exit;
}

$db      = getDbConnection();
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validatePost()) {

    try {
        $db->beginTransaction();

        // ── 1. Organisation ───────────────────────────────────────────────────
        $existing = $db->prepare('SELECT id FROM organisations WHERE domain = ? LIMIT 1');
        $existing->execute(['sunrisecare.demo']);
        $orgId = $existing->fetchColumn();

        if ($orgId) {
            $db->rollBack();
            $error = 'Demo data already exists (organisation with domain <strong>sunrisecare.demo</strong> found). '
                   . 'Delete it first if you want to re-seed.';
        } else {
            $db->prepare('INSERT INTO organisations (name, domain) VALUES (?, ?)')
               ->execute(['Sunrise Care', 'sunrisecare.demo']);
            $orgId = (int) $db->lastInsertId();

            // ── 2. Admin user ─────────────────────────────────────────────────
            $db->prepare('
                INSERT INTO users (organisation_id, email, password_hash, first_name, last_name, is_active, email_verified)
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ')->execute([
                $orgId,
                'admin@sunrisecare.demo',
                password_hash('Sunrise2024!', PASSWORD_DEFAULT),
                'Demo',
                'Admin',
            ]);
            $adminUserId = (int) $db->lastInsertId();
            RBAC::assignRole($adminUserId, 'organisation_admin');

            // ── 3. Staff members ──────────────────────────────────────────────
            $staff = [
                ['Sarah',   'Johnson',  'sarah.johnson@sunrisecare.demo',  '1985-03-12', 'EMP001'],
                ['Michael', 'Chen',     'michael.chen@sunrisecare.demo',    '1979-07-22', 'EMP002'],
                ['Emma',    'Williams', 'emma.williams@sunrisecare.demo',   '1992-11-05', 'EMP003'],
                ['James',   'Taylor',   'james.taylor@sunrisecare.demo',    '1988-01-30', 'EMP004'],
                ['Rebecca', 'Davies',   'rebecca.davies@sunrisecare.demo',  '1995-06-18', 'EMP005'],
                ['Thomas',  'Brown',    'thomas.brown@sunrisecare.demo',    '1983-09-14', 'EMP006'],
            ];

            $profileData = [
                ['EMP001', 'Registered Manager',       '2018-04-01', null],
                ['EMP002', 'Deputy Manager',           '2019-09-01', null],
                ['EMP003', 'Senior Support Worker',    '2020-02-01', null],
                ['EMP004', 'Support Worker',           '2021-06-01', null],
                ['EMP005', 'Support Worker',           '2022-01-10', null],
                ['EMP006', 'Night Support Worker',     '2021-11-15', null],
            ];

            $insertPerson = $db->prepare('
                INSERT INTO people
                    (organisation_id, person_type, first_name, last_name, email, date_of_birth,
                     employee_reference, is_active)
                VALUES (?, "staff", ?, ?, ?, ?, ?, 1)
            ');

            $insertProfile = $db->prepare('
                INSERT INTO staff_profiles (person_id, job_title, employment_start_date, line_manager_id)
                VALUES (?, ?, ?, ?)
            ');

            $personIds = [];
            foreach ($staff as $i => $s) {
                $insertPerson->execute([$orgId, $s[0], $s[1], $s[2], $s[3], $s[4]]);
                $personIds[$s[4]] = (int) $db->lastInsertId();
            }

            // Now insert profiles with line manager references
            // EMP001 (Registered Manager) → no line manager
            // EMP002 (Deputy) → EMP001
            // EMP003–EMP006 → EMP002
            $lineManagers = [
                'EMP001' => null,
                'EMP002' => $personIds['EMP001'],
                'EMP003' => $personIds['EMP002'],
                'EMP004' => $personIds['EMP002'],
                'EMP005' => $personIds['EMP002'],
                'EMP006' => $personIds['EMP002'],
            ];

            foreach ($profileData as $i => $p) {
                $insertProfile->execute([
                    $personIds[$p[0]],
                    $p[1],
                    $p[2],
                    $lineManagers[$p[0]],
                ]);
            }

            $db->commit();

            $message = 'Demo data created successfully.'
                     . ' Organisation: <strong>Sunrise Care</strong> (ID: ' . $orgId . '),'
                     . ' 6 staff members seeded.'
                     . ' Admin login: <strong>admin@sunrisecare.demo</strong> / <strong>Sunrise2024!</strong>';
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Database error: ' . htmlspecialchars($e->getMessage());
    }
}

$pageTitle = 'Seed Demo Data';
include INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-seedling"></i> Seed Demo Data</h1>
        <p class="text-light text-small" style="margin-top:.25rem">
            Creates a <strong>Sunrise Care</strong> demo organisation with staff. Safe to inspect before running.
        </p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="card" style="max-width:640px">
    <h3 style="font-weight:600;margin-bottom:.75rem">What this will create</h3>
    <ul style="margin:.5rem 0 1.25rem 1.25rem;line-height:1.8">
        <li>Organisation: <strong>Sunrise Care</strong> (domain: <code>sunrisecare.demo</code>)</li>
        <li>Admin user: <code>admin@sunrisecare.demo</code> / <code>Sunrise2024!</code></li>
        <li>6 staff members with job titles and line manager relationships</li>
    </ul>
    <p class="text-light text-small" style="margin-bottom:1.25rem">
        The script is idempotent — it will refuse to run if the demo domain already exists.
    </p>

    <?php if (!$message): ?>
    <form method="POST">
        <?php echo CSRF::tokenField(); ?>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-seedling"></i> Create Demo Data
        </button>
        <a href="<?php echo url('admin/organisations.php'); ?>" class="btn btn-secondary" style="margin-left:.5rem">Cancel</a>
    </form>
    <?php else: ?>
    <a href="<?php echo url('admin/organisations.php'); ?>" class="btn btn-primary">
        <i class="fas fa-building"></i> View Organisations
    </a>
    <?php endif; ?>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
