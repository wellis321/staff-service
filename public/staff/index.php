<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error   = $_GET['error']   ?? '';
$success = $_GET['success'] ?? '';

// Search / filter
$search     = $_GET['search']        ?? '';
$activeOnly = !isset($_GET['show_inactive']) || $_GET['show_inactive'] !== '1';

// Pagination (flat view only)
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset  = ($page - 1) * $perPage;

// ── Fetch staff ───────────────────────────────────────────────────────────────
if (!empty($search)) {
    $staff      = Person::searchStaff($organisationId, $search, $activeOnly);
    $totalCount = count($staff);
    $staffPaged = array_slice($staff, $offset, $perPage);
} else {
    $staff      = Person::getStaffByOrganisation($organisationId, $activeOnly, $perPage, $offset);
    $totalCount = Person::countStaff($organisationId, $activeOnly);
    $staffPaged = $staff;
}
$totalPages = ceil($totalCount / $perPage);

// ── Team Service — fetch memberships for grouped view ─────────────────────────
try {
    $teamServiceOn = TeamServiceClient::enabled($organisationId);
    $memberships   = $teamServiceOn ? TeamServiceClient::getAllStaffMemberships($organisationId) : null;
} catch (Throwable $e) {
    error_log('TeamServiceClient failed on staff list: ' . $e->getMessage());
    $teamServiceOn = false;
    $memberships   = null;
}

// Build map: staff_id (external_id) => [team names]
$staffTeamMap = [];
$teamGroups   = []; // team_id => ['name' => ..., 'members' => [staff_id => true]]
if (is_array($memberships)) {
    foreach ($memberships as $m) {
        $sid = (int) $m['external_id'];
        $staffTeamMap[$sid][] = $m['team_name'];

        $tid = (int) $m['team_id'];
        if (!isset($teamGroups[$tid])) {
            $teamGroups[$tid] = ['name' => $m['team_name'], 'staff_ids' => []];
        }
        $teamGroups[$tid]['staff_ids'][$sid] = true;
    }
    uasort($teamGroups, fn($a, $b) => strcmp($a['name'], $b['name']));
}

// Full staff list (all, not paged) for grouped view
$allStaff = empty($search)
    ? Person::getStaffByOrganisation($organisationId, $activeOnly)
    : $staff;

// Index all staff by their id for fast lookup
$staffById = [];
foreach ($allStaff as $s) {
    $staffById[(int)$s['id']] = $s;
}

$pageTitle = 'Manage Staff';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-users"></i> Staff</h1>
    </div>
    <a href="<?php echo url('staff/create.php'); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Staff Member
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- ── Controls ────────────────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:1.25rem;padding:1rem 1.5rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
        <div style="flex:1;min-width:220px;position:relative">
            <i class="fas fa-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--clr-muted);font-size:.8rem;pointer-events:none"></i>
            <input type="text" id="search" name="search" class="form-control"
                   style="padding-left:2.25rem;margin:0"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Name, email or reference…">
        </div>
        <label style="display:flex;align-items:center;gap:.4rem;font-size:.875rem;color:var(--clr-muted);white-space:nowrap;cursor:pointer">
            <input type="checkbox" name="show_inactive" value="1" <?php echo !$activeOnly ? 'checked' : ''; ?>>
            Show inactive
        </label>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search) || !$activeOnly): ?>
            <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Clear
            </a>
        <?php endif; ?>
        <a href="<?php echo url('staff/search-learning.php'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-graduation-cap"></i> Learning
        </a>

        <?php if ($teamServiceOn && is_array($memberships)): ?>
        <div style="margin-left:auto;display:flex;gap:.375rem">
            <button type="button" id="btn-grouped" class="btn btn-secondary btn-sm" onclick="setView('grouped')">
                <i class="fas fa-layer-group"></i> By Team
            </button>
            <button type="button" id="btn-flat" class="btn btn-secondary btn-sm" onclick="setView('flat')">
                <i class="fas fa-list"></i> List
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- GROUPED VIEW                                                              -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<?php if ($teamServiceOn && is_array($memberships)): ?>
<div id="view-grouped">

    <?php if (empty($teamGroups) && empty($staffById)): ?>
        <div class="card"><p class="text-light" style="text-align:center;padding:2rem">No staff found.</p></div>

    <?php else: ?>

        <?php foreach ($teamGroups as $teamId => $group): ?>
        <div class="card" style="margin-bottom:1rem">
            <div class="card-header">
                <h2><i class="fas fa-people-group"></i> <?php echo htmlspecialchars($group['name']); ?></h2>
                <span class="badge badge-blue"><?php echo count($group['staff_ids']); ?> member<?php echo count($group['staff_ids']) !== 1 ? 's' : ''; ?></span>
            </div>
            <?php echo renderStaffTable(
                array_values(array_filter($allStaff, fn($s) => isset($group['staff_ids'][(int)$s['id']]))),
                $staffTeamMap, false
            ); ?>
        </div>
        <?php endforeach; ?>

        <?php
        // Unassigned — staff with no team membership
        $assignedIds = [];
        foreach ($teamGroups as $g) {
            foreach ($g['staff_ids'] as $sid => $_) $assignedIds[$sid] = true;
        }
        $unassigned = array_values(array_filter($allStaff, fn($s) => !isset($assignedIds[(int)$s['id']])));
        ?>
        <?php if (!empty($unassigned)): ?>
        <div class="card" style="margin-bottom:1rem">
            <div class="card-header">
                <h2><i class="fas fa-user-slash"></i> Unassigned</h2>
                <span class="badge badge-grey"><?php echo count($unassigned); ?> member<?php echo count($unassigned) !== 1 ? 's' : ''; ?></span>
            </div>
            <?php echo renderStaffTable($unassigned, $staffTeamMap, false); ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- FLAT VIEW                                                                 -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="view-flat">
    <div class="card">
        <?php if (empty($staffPaged)): ?>
            <p class="text-light" style="text-align:center;padding:2rem">
                <?php echo !empty($search) ? 'No staff found matching your search.' : 'No staff yet. <a href="' . url('staff/create.php') . '">Add your first staff member</a>.'; ?>
            </p>
        <?php else: ?>
            <?php echo renderStaffTable($staffPaged, $staffTeamMap, $teamServiceOn && is_array($memberships)); ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div style="margin-top:1.5rem;display:flex;justify-content:center;align-items:center;gap:1rem">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?><?php echo !$activeOnly ? '&show_inactive=1' : ''; ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                <span class="text-light text-small">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?><?php echo !$activeOnly ? '&show_inactive=1' : ''; ?>" class="btn btn-secondary">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Export -->
<div style="margin-top:1rem;display:flex;gap:.5rem">
    <a href="<?php echo url('api/export-staff.php?format=csv'); ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-download"></i> Export CSV
    </a>
    <a href="<?php echo url('api/export-staff.php?format=json'); ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-download"></i> Export JSON
    </a>
</div>

<?php
// ── Reusable table renderer ───────────────────────────────────────────────────
function renderStaffTable(array $members, array $teamMap, bool $showTeamCol): string
{
    if (empty($members)) {
        return '<p class="text-light" style="padding:1rem 1.5rem">No staff in this group.</p>';
    }
    ob_start();
    ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Ref</th>
                    <th>Email</th>
                    <?php if ($showTeamCol): ?><th>Teams</th><?php endif; ?>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                <?php
                    $initials  = strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1));
                    $avClass   = 'av-' . strtolower(substr($m['first_name'], 0, 1));
                    $jobTitle  = $m['job_title'] ?? null;
                    $teams     = $teamMap[(int)$m['id']] ?? [];
                ?>
                <tr>
                    <td>
                        <div class="staff-name-cell">
                            <span class="staff-avatar <?php echo htmlspecialchars($avClass); ?>"><?php echo htmlspecialchars($initials); ?></span>
                            <div>
                                <a href="<?php echo url('staff/view.php?id=' . $m['id']); ?>" class="staff-name">
                                    <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?>
                                </a>
                                <?php if ($jobTitle): ?>
                                    <div class="staff-title"><?php echo htmlspecialchars($jobTitle); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-light text-small"><?php echo htmlspecialchars($m['employee_reference'] ?? '—'); ?></td>
                    <td class="text-light text-small"><?php echo htmlspecialchars($m['user_email'] ?? $m['email'] ?? '—'); ?></td>
                    <?php if ($showTeamCol): ?>
                    <td class="text-small">
                        <?php if ($teams): ?>
                            <?php foreach (array_unique($teams) as $t): ?>
                                <span class="badge badge-teal" style="margin:.1rem .1rem .1rem 0"><?php echo htmlspecialchars($t); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-light">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <?php if ($m['is_active']): ?>
                            <span class="badge badge-green">Active</span>
                        <?php else: ?>
                            <span class="badge badge-grey">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?php echo url('staff/view.php?id=' . $m['id']); ?>" class="btn btn-secondary btn-sm" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo url('staff/edit.php?id=' . $m['id']); ?>" class="btn btn-secondary btn-sm" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
?>

<script>
(function () {
    var hasTeamService = <?php echo ($teamServiceOn && is_array($memberships)) ? 'true' : 'false'; ?>;
    if (!hasTeamService) return;

    var grouped = document.getElementById('view-grouped');
    var flat    = document.getElementById('view-flat');
    var btnG    = document.getElementById('btn-grouped');
    var btnF    = document.getElementById('btn-flat');

    function setView(v) {
        localStorage.setItem('staffView', v);
        if (v === 'grouped') {
            grouped.style.display = '';
            flat.style.display    = 'none';
            btnG.classList.add('btn-primary');    btnG.classList.remove('btn-secondary');
            btnF.classList.remove('btn-primary'); btnF.classList.add('btn-secondary');
        } else {
            grouped.style.display = 'none';
            flat.style.display    = '';
            btnF.classList.add('btn-primary');    btnF.classList.remove('btn-secondary');
            btnG.classList.remove('btn-primary'); btnG.classList.add('btn-secondary');
        }
    }

    // Expose globally so onclick attributes work
    window.setView = setView;

    // Restore saved preference (default: grouped)
    setView(localStorage.getItem('staffView') || 'grouped');
}());
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
