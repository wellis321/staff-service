<?php
/**
 * PMS — Professional Registrations Overview
 *
 * Shows all active staff registrations for the organisation,
 * colour-coded by expiry status. Admins can see at a glance
 * who needs to act and when.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();

// ── Filters ───────────────────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';   // all | action_needed | expired

$registrations = StaffRegistration::findByOrganisation($organisationId);

// Count by status
$counts = ['active' => 0, 'expiring_soon' => 0, 'expiring_critical' => 0, 'expired' => 0];
foreach ($registrations as $r) {
    if (isset($counts[$r['reg_status']])) $counts[$r['reg_status']]++;
}
$actionNeeded = $counts['expiring_soon'] + $counts['expiring_critical'] + $counts['expired'];

// Apply filter
$filtered = match ($filter) {
    'action_needed' => array_filter($registrations, fn($r) => in_array($r['reg_status'], ['expiring_soon','expiring_critical','expired'])),
    'expired'       => array_filter($registrations, fn($r) => $r['reg_status'] === 'expired'),
    default         => $registrations,
};

$pageTitle = 'Professional Registrations';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-id-badge"></i> Professional Registrations</h1>
        <p class="text-light text-small" style="margin-top:.25rem">
            SSSC and other professional registrations across all staff.
            Registrations are added and updated from each staff member's profile.
        </p>
    </div>
    <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Staff
    </a>
</div>

<!-- ── Summary cards ──────────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
    <?php
    $summaryCards = [
        ['label' => 'Total registrations', 'count' => count($registrations),          'icon' => 'fa-id-badge',             'colour' => '#475569'],
        ['label' => 'Active',              'count' => $counts['active'],              'icon' => 'fa-check-circle',         'colour' => '#059669'],
        ['label' => 'Due for renewal',     'count' => $counts['expiring_soon'] + $counts['expiring_critical'], 'icon' => 'fa-clock', 'colour' => '#d97706'],
        ['label' => 'Expired',             'count' => $counts['expired'],             'icon' => 'fa-times-circle',         'colour' => '#dc2626'],
    ];
    foreach ($summaryCards as $card):
    ?>
    <div class="card" style="padding:1.25rem 1.5rem">
        <div style="display:flex;align-items:center;gap:.75rem">
            <i class="fas <?php echo $card['icon']; ?>" style="font-size:1.4rem;color:<?php echo $card['colour']; ?>"></i>
            <div>
                <div style="font-size:1.6rem;font-weight:700;line-height:1;color:<?php echo $card['colour']; ?>"><?php echo $card['count']; ?></div>
                <div class="text-small text-light" style="margin-top:.2rem"><?php echo $card['label']; ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Filter tabs ────────────────────────────────────────────────────────── -->
<div style="display:flex;gap:.375rem;margin-bottom:1rem">
    <a href="?filter=all"
       class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
        All (<?php echo count($registrations); ?>)
    </a>
    <a href="?filter=action_needed"
       class="btn btn-sm <?php echo $filter === 'action_needed' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i class="fas fa-exclamation-triangle"></i> Action needed (<?php echo $actionNeeded; ?>)
    </a>
    <a href="?filter=expired"
       class="btn btn-sm <?php echo $filter === 'expired' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i class="fas fa-times-circle"></i> Expired (<?php echo $counts['expired']; ?>)
    </a>
</div>

<!-- ── Registrations table ────────────────────────────────────────────────── -->
<div class="card">
    <?php if (empty($filtered)): ?>
        <p class="text-light" style="padding:2rem;text-align:center">
            <?php echo $filter === 'all'
                ? 'No registrations recorded yet. Add them via each staff member\'s profile.'
                : 'No registrations in this category.'; ?>
        </p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Staff member</th>
                    <th>Registration</th>
                    <th>Number</th>
                    <th>Expiry date</th>
                    <th>Status</th>
                    <th>Line manager</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($filtered as $reg):
                $days    = (int) $reg['days_until'];
                $status  = $reg['reg_status'];
                $initials = strtoupper(substr($reg['first_name'], 0, 1) . substr($reg['last_name'], 0, 1));
                $avClass  = 'av-' . strtolower(substr($reg['first_name'], 0, 1));

                $rowBg = match ($status) {
                    'expired'           => 'background:#fff5f5',
                    'expiring_critical' => 'background:#fffbeb',
                    default             => '',
                };
            ?>
            <tr style="<?php echo $rowBg; ?>">
                <td>
                    <div class="staff-name-cell">
                        <span class="staff-avatar <?php echo htmlspecialchars($avClass); ?>"><?php echo htmlspecialchars($initials); ?></span>
                        <div>
                            <a href="<?php echo url('staff/view.php?id=' . (int)$reg['person_id']); ?>" class="staff-name">
                                <?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?>
                            </a>
                            <?php if ($reg['job_title']): ?>
                                <div class="staff-title"><?php echo htmlspecialchars($reg['job_title']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="text-small"><?php echo htmlspecialchars($reg['registration_type']); ?></td>
                <td class="text-light text-small"><?php echo htmlspecialchars($reg['registration_number'] ?? '—'); ?></td>
                <td class="text-small" style="white-space:nowrap">
                    <?php echo date(DATE_FORMAT, strtotime($reg['expiry_date'])); ?>
                    <?php if ($reg['renewal_submitted_at']): ?>
                        <div class="text-small" style="color:#0d9488;margin-top:.1rem">
                            <i class="fas fa-paper-plane" style="font-size:.7rem"></i>
                            Renewal submitted <?php echo date(DATE_FORMAT, strtotime($reg['renewal_submitted_at'])); ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $badgeClass = StaffRegistration::statusBadgeClass($status);
                    $label      = StaffRegistration::statusLabel($status);
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </span>
                    <?php if ($days >= 0): ?>
                        <div class="text-small text-light" style="margin-top:.2rem"><?php echo $days; ?> days</div>
                    <?php else: ?>
                        <div class="text-small" style="color:#dc2626;margin-top:.2rem"><?php echo abs($days); ?> days ago</div>
                    <?php endif; ?>
                </td>
                <td class="text-small text-light">
                    <?php if ($reg['mgr_first']): ?>
                        <?php echo htmlspecialchars($reg['mgr_first'] . ' ' . $reg['mgr_last']); ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td style="text-align:right">
                    <a href="<?php echo url('staff/edit.php?id=' . (int)$reg['person_id'] . '#section-registrations'); ?>"
                       class="btn btn-secondary btn-sm" title="Edit registration">
                        <i class="fas fa-pen"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Cron setup info ────────────────────────────────────────────────────── -->
<?php if (RBAC::isSuperAdmin()): ?>
<div class="card" style="margin-top:1.5rem;padding:1.25rem 1.5rem;background:#f8fafc">
    <h3 style="font-size:.9rem;font-weight:600;margin-bottom:.5rem">
        <i class="fas fa-clock text-light"></i> Automated email notifications
    </h3>
    <p class="text-small text-light" style="margin-bottom:.75rem">
        Staff and managers are automatically emailed at 90, 60, 30, 14, 7 and 0 days before expiry, then weekly for 4 weeks after.
        Set up a daily cron job in Hostinger pointing to the script below.
    </p>
    <code style="background:#1c2b3a;color:#a5f3fc;padding:.5rem 1rem;border-radius:6px;font-size:.8rem;display:block">
        php <?php echo htmlspecialchars(PUBLIC_PATH); ?>/cron/check-registrations.php
    </code>
    <p class="text-small text-light" style="margin-top:.5rem">
        Or via HTTP: add <code>CRON_SECRET</code> to your <code>.env</code> and call
        <code>/cron/check-registrations.php?secret=YOUR_SECRET</code>
    </p>
</div>
<?php endif; ?>

<?php

// Add extra badge colours not in the main CSS yet
echo '<style>
.badge-red    { background:#fee2e2; color:#991b1b; }
.badge-amber  { background:#fef3c7; color:#92400e; }
.badge-yellow { background:#fef9c3; color:#854d0e; }
</style>';

include dirname(__DIR__, 2) . '/includes/footer.php';
?>
