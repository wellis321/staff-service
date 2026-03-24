<?php
/**
 * PMS — Demo Data Seeder
 *
 * Two actions:
 *   seed  — Creates the Sunrise Care org, admin, and 6 staff (each with a login).
 *   users — Adds logins to existing demo staff (use if org already exists from an
 *            earlier seed run that didn't create user accounts).
 *
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

// ── Shared staff definitions ──────────────────────────────────────────────────
$staffDefs = [
    // [first, last, email, dob, ref, job_title, start_date]
    ['Sarah',   'Johnson',  'sarah.johnson@sunrisecare.demo',  '1985-03-12', 'EMP001', 'Registered Manager',    '2018-04-01'],
    ['Michael', 'Chen',     'michael.chen@sunrisecare.demo',   '1979-07-22', 'EMP002', 'Deputy Manager',        '2019-09-01'],
    ['Emma',    'Williams', 'emma.williams@sunrisecare.demo',  '1992-11-05', 'EMP003', 'Senior Support Worker', '2020-02-01'],
    ['James',   'Taylor',   'james.taylor@sunrisecare.demo',   '1988-01-30', 'EMP004', 'Support Worker',        '2021-06-01'],
    ['Rebecca', 'Davies',   'rebecca.davies@sunrisecare.demo', '1995-06-18', 'EMP005', 'Support Worker',        '2022-01-10'],
    ['Thomas',  'Brown',    'thomas.brown@sunrisecare.demo',   '1983-09-14', 'EMP006', 'Night Support Worker',  '2021-11-15'],
];
$password     = 'Sunrise2024!';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validatePost()) {

    $action = $_POST['action'] ?? '';

    // ── Action: full seed ─────────────────────────────────────────────────────
    if ($action === 'seed') {
        try {
            $db->beginTransaction();

            $existing = $db->prepare('SELECT id FROM organisations WHERE domain = ? LIMIT 1');
            $existing->execute(['sunrisecare.demo']);
            $orgId = $existing->fetchColumn();

            if ($orgId) {
                $db->rollBack();
                $error = 'Demo organisation (<strong>sunrisecare.demo</strong>) already exists. '
                       . 'Use <em>Add Staff Logins</em> below to add user accounts to existing staff, '
                       . 'or delete the organisation first to re-seed from scratch.';
            } else {
                // 1. Organisation
                $db->prepare('INSERT INTO organisations (name, domain) VALUES (?, ?)')
                   ->execute(['Sunrise Care', 'sunrisecare.demo']);
                $orgId = (int) $db->lastInsertId();

                // 2. Org admin user
                $db->prepare('
                    INSERT INTO users (organisation_id, email, password_hash, first_name, last_name, is_active, email_verified)
                    VALUES (?, ?, ?, ?, ?, 1, 1)
                ')->execute([$orgId, 'admin@sunrisecare.demo', $passwordHash, 'Demo', 'Admin']);
                RBAC::assignRole((int) $db->lastInsertId(), 'organisation_admin');

                // 3. Staff — create user + person + profile in one pass
                $insertUser = $db->prepare('
                    INSERT INTO users (organisation_id, email, password_hash, first_name, last_name, is_active, email_verified)
                    VALUES (?, ?, ?, ?, ?, 1, 1)
                ');
                $insertPerson = $db->prepare('
                    INSERT INTO people (organisation_id, person_type, user_id, first_name, last_name,
                                       email, date_of_birth, employee_reference, is_active)
                    VALUES (?, "staff", ?, ?, ?, ?, ?, ?, 1)
                ');
                $insertProfile = $db->prepare('
                    INSERT INTO staff_profiles (person_id, job_title, employment_start_date, line_manager_id)
                    VALUES (?, ?, ?, ?)
                ');

                $personIds = [];
                foreach ($staffDefs as $s) {
                    $insertUser->execute([$orgId, $s[2], $passwordHash, $s[0], $s[1]]);
                    $userId = (int) $db->lastInsertId();
                    RBAC::assignRole($userId, 'staff');

                    $insertPerson->execute([$orgId, $userId, $s[0], $s[1], $s[2], $s[3], $s[4]]);
                    $personIds[$s[4]] = (int) $db->lastInsertId();
                }

                // Line manager links: EMP002 → EMP001, EMP003-006 → EMP002
                $lineManagers = [
                    'EMP001' => null,
                    'EMP002' => $personIds['EMP001'],
                    'EMP003' => $personIds['EMP002'],
                    'EMP004' => $personIds['EMP002'],
                    'EMP005' => $personIds['EMP002'],
                    'EMP006' => $personIds['EMP002'],
                ];
                foreach ($staffDefs as $s) {
                    $insertProfile->execute([$personIds[$s[4]], $s[5], $s[6], $lineManagers[$s[4]]]);
                }

                $db->commit();
                $message = 'Demo data created. '
                         . '7 logins created (admin + 6 staff) — all use password <strong>' . htmlspecialchars($password) . '</strong>.<br>'
                         . 'Org admin: <strong>admin@sunrisecare.demo</strong><br>'
                         . 'Staff: sarah.johnson / michael.chen / emma.williams / james.taylor / rebecca.davies / thomas.brown @sunrisecare.demo';
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }

    // ── Action: add logins to existing demo staff ─────────────────────────────
    if ($action === 'add_users') {
        try {
            $existing = $db->prepare('SELECT id FROM organisations WHERE domain = ? LIMIT 1');
            $existing->execute(['sunrisecare.demo']);
            $orgId = $existing->fetchColumn();

            if (!$orgId) {
                $error = 'Demo organisation not found. Run the full seed first.';
            } else {
                $created = [];
                $skipped = [];

                foreach ($staffDefs as $s) {
                    [$first, $last, $email, , $ref] = $s;

                    // Find the people record
                    $personStmt = $db->prepare('
                        SELECT id, user_id FROM people
                        WHERE organisation_id = ? AND employee_reference = ? LIMIT 1
                    ');
                    $personStmt->execute([$orgId, $ref]);
                    $person = $personStmt->fetch();

                    if (!$person) {
                        $skipped[] = "{$ref} — people record not found";
                        continue;
                    }

                    // Already has a linked user?
                    if ($person['user_id']) {
                        $skipped[] = "{$email} — already has a user account";
                        continue;
                    }

                    // Does a user with this email already exist?
                    $userStmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $userStmt->execute([$email]);
                    $existingUser = $userStmt->fetchColumn();

                    if ($existingUser) {
                        // Just link it
                        $db->prepare('UPDATE people SET user_id = ? WHERE id = ?')
                           ->execute([$existingUser, $person['id']]);
                        $skipped[] = "{$email} — user existed, linked";
                        continue;
                    }

                    // Create user
                    $db->prepare('
                        INSERT INTO users (organisation_id, email, password_hash, first_name, last_name, is_active, email_verified)
                        VALUES (?, ?, ?, ?, ?, 1, 1)
                    ')->execute([$orgId, $email, $passwordHash, $first, $last]);
                    $userId = (int) $db->lastInsertId();
                    RBAC::assignRole($userId, 'staff');

                    // Link to people record
                    $db->prepare('UPDATE people SET user_id = ? WHERE id = ?')
                       ->execute([$userId, $person['id']]);

                    $created[] = $email;
                }

                if ($created) {
                    $message = count($created) . ' user account(s) created and linked:<br>'
                             . '<strong>' . implode('<br>', array_map('htmlspecialchars', $created)) . '</strong><br><br>'
                             . 'Password for all: <strong>' . htmlspecialchars($password) . '</strong>';
                    if ($skipped) {
                        $message .= '<br><span class="text-light text-small">Skipped: ' . implode(', ', array_map('htmlspecialchars', $skipped)) . '</span>';
                    }
                } elseif ($skipped) {
                    $message = 'No new accounts created. ' . implode(', ', array_map('htmlspecialchars', $skipped));
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Check current state ───────────────────────────────────────────────────────
$orgExists = false;
$staffWithoutLogins = 0;
$checkOrg = $db->prepare('SELECT id FROM organisations WHERE domain = ? LIMIT 1');
$checkOrg->execute(['sunrisecare.demo']);
$existingOrgId = $checkOrg->fetchColumn();
if ($existingOrgId) {
    $orgExists = true;
    $noLogin = $db->prepare('
        SELECT COUNT(*) FROM people
        WHERE organisation_id = ? AND person_type = "staff" AND (user_id IS NULL OR user_id = 0)
    ');
    $noLogin->execute([$existingOrgId]);
    $staffWithoutLogins = (int) $noLogin->fetchColumn();
}

$pageTitle = 'Seed Demo Data';
include INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-seedling"></i> Seed Demo Data</h1>
        <p class="text-light text-small" style="margin-top:.25rem">
            Creates a <strong>Sunrise Care</strong> demo organisation with 6 staff members, each with their own login.
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<!-- ── Full seed ──────────────────────────────────────────────────────────── -->
<div class="card" style="max-width:680px;margin-bottom:1rem">
    <h3 style="font-weight:600;margin-bottom:.75rem">Full seed (fresh install)</h3>
    <p class="text-light text-small" style="margin-bottom:.75rem">
        Creates everything from scratch. Will refuse to run if the demo org already exists.
    </p>
    <ul style="margin:.25rem 0 1.25rem 1.25rem;line-height:1.9;font-size:.9rem">
        <li>Organisation: <strong>Sunrise Care</strong> (<code>sunrisecare.demo</code>)</li>
        <li>Admin login: <code>admin@sunrisecare.demo</code> / <code><?php echo htmlspecialchars($password); ?></code></li>
        <li>6 staff with logins: sarah.johnson / michael.chen / emma.williams / james.taylor / rebecca.davies / thomas.brown <code>@sunrisecare.demo</code></li>
        <li>Job titles, employment dates and line manager relationships</li>
    </ul>
    <?php if (!$orgExists && !$message): ?>
    <form method="POST">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="seed">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-seedling"></i> Create Demo Data
        </button>
        <a href="<?php echo url('admin/organisations.php'); ?>" class="btn btn-secondary" style="margin-left:.5rem">Cancel</a>
    </form>
    <?php elseif ($orgExists): ?>
        <p class="text-light text-small"><i class="fas fa-check-circle" style="color:#059669"></i> Demo org already exists — use the action below instead.</p>
    <?php endif; ?>
</div>

<!-- ── Add logins to existing staff ──────────────────────────────────────── -->
<?php if ($orgExists): ?>
<div class="card" style="max-width:680px">
    <h3 style="font-weight:600;margin-bottom:.75rem">Add staff logins to existing demo data</h3>
    <p class="text-light text-small" style="margin-bottom:.75rem">
        The demo org exists but <?php echo $staffWithoutLogins; ?> staff member<?php echo $staffWithoutLogins !== 1 ? 's do' : ' does'; ?> not yet have a login.
        This will create user accounts and link them.
    </p>
    <?php if ($staffWithoutLogins > 0): ?>
    <div style="background:#f0fdf9;border:1px solid #99f6e4;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem">
        <strong>All accounts will use password:</strong> <code><?php echo htmlspecialchars($password); ?></code>
    </div>
    <form method="POST">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="add_users">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add Staff Logins (<?php echo $staffWithoutLogins; ?>)
        </button>
    </form>
    <?php else: ?>
        <p class="text-light text-small"><i class="fas fa-check-circle" style="color:#059669"></i> All demo staff already have logins.</p>
        <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-primary" style="margin-top:.5rem">
            <i class="fas fa-users"></i> View Staff
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($message && strpos($message, 'created') !== false): ?>
<div class="card" style="max-width:680px;margin-top:1rem;background:#f8fafc">
    <h3 style="font-weight:600;margin-bottom:.75rem">Login details</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Password</th></tr></thead>
            <tbody>
                <tr><td>Demo Admin</td><td><code>admin@sunrisecare.demo</code></td><td><span class="badge badge-blue">Org Admin</span></td><td><code><?php echo htmlspecialchars($password); ?></code></td></tr>
                <?php foreach ($staffDefs as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s[0] . ' ' . $s[1]); ?></td>
                    <td><code><?php echo htmlspecialchars($s[2]); ?></code></td>
                    <td><span class="badge badge-grey">Staff</span></td>
                    <td><code><?php echo htmlspecialchars($password); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-primary" style="margin-top:1rem">
        <i class="fas fa-users"></i> View Staff
    </a>
</div>
<?php endif; ?>

<?php include INCLUDES_PATH . '/footer.php'; ?>
