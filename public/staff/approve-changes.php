<?php
/**
 * Manager / HR Approval Inbox
 *
 * Shows pending profile-change requests for the logged-in manager's direct
 * reports. HR admins also see top-of-chain requests (staff with no line manager).
 * Each field change can be approved or rejected independently.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();

$organisationId   = Auth::getOrganisationId();
$userId           = Auth::getUserId();
$isAdmin          = RBAC::isOrganisationAdmin() || RBAC::isSuperAdmin();
$error            = '';
$success          = '';

// ─── Find the manager's own person record ─────────────────────────────────────
$db   = getDbConnection();
$stmt = $db->prepare("SELECT id FROM people WHERE user_id = ? AND organisation_id = ? LIMIT 1");
$stmt->execute([$userId, $organisationId]);
$managerPerson = $stmt->fetch(PDO::FETCH_ASSOC);
$managerPersonId = $managerPerson ? (int) $managerPerson['id'] : null;

// ─── Handle approve / reject actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $action   = $_POST['action']    ?? '';
        $changeId = (int)($_POST['change_id'] ?? 0);
        $reason   = trim($_POST['rejection_reason'] ?? '');

        if ($changeId > 0) {
            // Verify this manager is actually the approver for this change
            $change = PendingProfileChange::findById($changeId);

            if (!$change || $change['organisation_id'] != $organisationId) {
                $error = 'Change not found.';
            } else {
                // Confirm the manager is authorised: either
                //   (a) they are the direct line manager of the person, or
                //   (b) the person has no line manager and the reviewer is an admin
                $authStmt = $db->prepare("
                    SELECT sp.line_manager_id
                    FROM staff_profiles sp
                    WHERE sp.person_id = ?
                ");
                $authStmt->execute([$change['person_id']]);
                $spRow = $authStmt->fetch(PDO::FETCH_ASSOC);

                $isLineManager   = $managerPersonId && $spRow && (int)$spRow['line_manager_id'] === $managerPersonId;
                $isTopOfChain    = $isAdmin && $spRow && $spRow['line_manager_id'] === null;
                $isAuthorised    = $isLineManager || $isTopOfChain || $isAdmin;

                if (!$isAuthorised) {
                    $error = 'You are not authorised to review this change.';
                } elseif ($action === 'approve') {
                    if (PendingProfileChange::approve($changeId, $userId)) {
                        $success = 'Change approved and applied to the staff record.';
                        ProfileChangeNotifications::dispatchStaffApprovedNotification($db, $change);
                    } else {
                        $error = 'Could not approve this change. It may have already been reviewed.';
                    }
                } elseif ($action === 'reject') {
                    if (empty($reason)) {
                        $error = 'Please provide a reason for the rejection so the staff member knows what to correct.';
                    } elseif (PendingProfileChange::reject($changeId, $userId, $reason)) {
                        $success = 'Change rejected. The staff member has been notified.';
                        ProfileChangeNotifications::dispatchStaffRejectedNotification($db, $change, $reason);
                    } else {
                        $error = 'Could not reject this change. It may have already been reviewed.';
                    }
                }
            }
        }
    }
}

// ─── Load pending changes ──────────────────────────────────────────────────────
$myTeamChanges   = $managerPersonId
    ? PendingProfileChange::getPendingForManager($managerPersonId, $organisationId)
    : [];

$topChainChanges = $isAdmin
    ? PendingProfileChange::getPendingTopOfChain($organisationId)
    : [];

// Group by person for cleaner display
function groupByPerson(array $changes): array {
    $grouped = [];
    foreach ($changes as $c) {
        $grouped[$c['person_id']]['name']    = $c['person_name'];
        $grouped[$c['person_id']]['changes'][] = $c;
    }
    return $grouped;
}

$myTeamGrouped   = groupByPerson($myTeamChanges);
$topChainGrouped = groupByPerson($topChainChanges);

$totalPending = count($myTeamChanges) + count($topChainChanges);

$pageTitle = 'Approve Profile Changes';
include dirname(__DIR__, 2) . '/includes/header.php';

// Helper to render a change row with approve/reject controls
function renderChangeRow(array $change, bool $canReview): void {
    $isFile = $change['field_type'] === 'file_path';
    ?>
    <div style="border: 1px solid #e5e7eb; padding: 1rem; margin-bottom: 1rem; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">

            <div style="flex: 1; min-width: 0;">
                <p style="font-weight: 600; margin: 0 0 0.5rem;">
                    <?php echo htmlspecialchars($change['field_label']); ?>
                </p>

                <?php if ($isFile): ?>
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                        <?php if ($change['current_value']): ?>
                            <div>
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 0 0 0.25rem;">Current</p>
                                <img src="<?php echo htmlspecialchars(url('view-image.php?path=' . urlencode($change['current_value']))); ?>"
                                     alt="Current" style="max-width: 150px; border: 1px solid #e5e7eb;">
                            </div>
                        <?php endif; ?>
                        <?php if ($change['pending_file_path']): ?>
                            <div>
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 0 0 0.25rem;">Proposed</p>
                                <img src="<?php echo htmlspecialchars(url('view-image.php?path=' . urlencode($change['pending_file_path']))); ?>"
                                     alt="Proposed" style="max-width: 150px; border: 2px solid #f59e0b;">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem; margin-top: 0.5rem;">
                        <div>
                            <span style="color: #6b7280; display: block; font-size: 0.75rem; margin-bottom: 0.125rem;">Current value</span>
                            <span style="font-family: monospace; background: #f3f4f6; padding: 0.25rem 0.5rem; display: inline-block;">
                                <?php echo $change['current_value'] !== null && $change['current_value'] !== ''
                                    ? htmlspecialchars($change['current_value'])
                                    : '<em style="color:#9ca3af">empty</em>'; ?>
                            </span>
                        </div>
                        <div>
                            <span style="color: #6b7280; display: block; font-size: 0.75rem; margin-bottom: 0.125rem;">Proposed value</span>
                            <span style="font-family: monospace; background: #ecfdf5; padding: 0.25rem 0.5rem; display: inline-block; border-left: 3px solid #10b981;">
                                <?php echo $change['proposed_value'] !== null && $change['proposed_value'] !== ''
                                    ? htmlspecialchars($change['proposed_value'])
                                    : '<em style="color:#9ca3af">empty</em>'; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <p style="font-size: 0.75rem; color: #6b7280; margin: 0.5rem 0 0;">
                    Submitted by <?php echo htmlspecialchars($change['submitted_by_name']); ?>
                    on <?php echo date('d M Y H:i', strtotime($change['submitted_at'])); ?>
                </p>
            </div>

            <?php if ($canReview): ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; flex-shrink: 0; min-width: 200px;">
                    <!-- Approve -->
                    <form method="post" style="margin: 0;">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="hidden" name="action"    value="approve">
                        <input type="hidden" name="change_id" value="<?php echo (int)$change['id']; ?>">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>

                    <!-- Reject (collapsible) -->
                    <div>
                        <button type="button" class="btn btn-secondary" style="width: 100%;"
                                onclick="this.closest('div').querySelector('.reject-form').style.display='block'; this.style.display='none';">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <form method="post" class="reject-form" style="display: none; margin-top: 0.5rem;">
                            <?php echo CSRF::tokenField(); ?>
                            <input type="hidden" name="action"    value="reject">
                            <input type="hidden" name="change_id" value="<?php echo (int)$change['id']; ?>">
                            <textarea name="rejection_reason" rows="2"
                                      placeholder="Reason for rejection (required)…"
                                      class="form-control" style="width: 100%; margin-bottom: 0.5rem;"
                                      required></textarea>
                            <button type="submit" class="btn btn-danger" style="width: 100%;">
                                <i class="fas fa-times-circle"></i> Confirm Rejection
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>

<div class="card">

    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <div style="flex: 1;">
            <h1>Approve Profile Changes</h1>
            <p style="color: #6b7280; margin: 0;">
                Review and approve (or reject) self-service profile updates from your team.
            </p>
        </div>
        <?php if ($totalPending > 0): ?>
            <span style="background: #ef4444; color: white; border-radius: 9999px;
                         padding: 0.25rem 0.75rem; font-weight: 700; font-size: 0.875rem;">
                <?php echo $totalPending; ?> pending
            </span>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($myTeamGrouped) && empty($topChainGrouped)): ?>
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <i class="fas fa-check-circle fa-3x" style="display: block; margin-bottom: 1rem; color: #10b981;"></i>
            <p style="font-size: 1.125rem; margin: 0;">No pending changes to review.</p>
        </div>

    <?php else: ?>

        <!-- ── My Team ───────────────────────────────────────────────────────── -->
        <?php if (!empty($myTeamGrouped)): ?>
            <h2>My Team</h2>
            <?php foreach ($myTeamGrouped as $pid => $group): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($group['name']); ?>
                        <span style="color: #6b7280; font-weight: normal; font-size: 0.875rem;">
                            — <?php echo count($group['changes']); ?> pending change(s)
                        </span>
                    </h3>
                    <?php foreach ($group['changes'] as $change): ?>
                        <?php renderChangeRow($change, true); ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── Top of Chain (HR / Admin) ─────────────────────────────────────── -->
        <?php if (!empty($topChainGrouped)): ?>
            <h2 style="<?php echo !empty($myTeamGrouped) ? 'margin-top: 2rem;' : ''; ?>">
                No Line Manager Assigned
                <span style="color: #6b7280; font-weight: normal; font-size: 0.875rem;">— HR / Admin review</span>
            </h2>
            <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">
                These staff members have no line manager set. As an admin, you are the approver.
            </p>
            <?php foreach ($topChainGrouped as $pid => $group): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($group['name']); ?>
                        <span style="color: #6b7280; font-weight: normal; font-size: 0.875rem;">
                            — <?php echo count($group['changes']); ?> pending change(s)
                        </span>
                    </h3>
                    <?php foreach ($group['changes'] as $change): ?>
                        <?php renderChangeRow($change, true); ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
