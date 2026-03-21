<?php
/**
 * Staff Self-Service Profile Page
 *
 * Lets the logged-in staff member view and update their own personal details.
 * Changes that require approval are queued in pending_profile_changes for
 * their line manager to review. Changes marked as no-approval (e.g. emergency
 * contact) are applied immediately.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();

$organisationId = Auth::getOrganisationId();
$userId         = Auth::getUserId();
$error          = '';
$success        = '';

// ─── Find the person record linked to the current user ────────────────────────
$db   = getDbConnection();
$stmt = $db->prepare("
    SELECT p.*, sp.job_title, sp.employment_start_date, sp.line_manager_id,
           sp.emergency_contact_name, sp.emergency_contact_phone,
           sp.ni_number, sp.bank_sort_code, sp.bank_account_number, sp.bank_account_name,
           sp.address_line1, sp.address_line2, sp.address_city,
           sp.address_county, sp.address_postcode, sp.address_country,
           sp.signature_path, sp.signature_created_at, sp.signature_method
    FROM people p
    LEFT JOIN staff_profiles sp ON p.id = sp.person_id
    WHERE p.user_id = ? AND p.organisation_id = ?
    LIMIT 1
");
$stmt->execute([$userId, $organisationId]);
$person = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$person) {
    // This user has no linked person record — nothing to self-service
    $pageTitle = 'My Profile';
    include dirname(__DIR__, 2) . '/includes/header.php';
    ?>
    <div class="card">
        <h1>My Profile</h1>
        <div class="alert alert-error">
            <i class="fas fa-info-circle"></i>
            Your account is not yet linked to a staff record. Please contact your manager or HR.
        </div>
    </div>
    <?php
    include dirname(__DIR__, 2) . '/includes/footer.php';
    exit;
}

$personId = (int) $person['id'];

// Mark any approved/rejected changes as seen — clears the nav badge
PendingProfileChange::markReviewedAsSeen($personId);

// ─── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'update_details';

        // ── File upload: profile photo ────────────────────────────────────────
        if ($action === 'upload_photo') {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo   = finfo_open(FILEINFO_MIME_TYPE);
                $mime    = finfo_file($finfo, $_FILES['photo']['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowed, true)) {
                    $error = 'Only JPEG, PNG, GIF or WebP images are accepted.';
                } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                    $error = 'Photo must be under 5 MB.';
                } else {
                    $ext        = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $pendingDir = UPLOADS_PATH . '/people/pending';
                    if (!is_dir($pendingDir)) {
                        mkdir($pendingDir, 0755, true);
                    }
                    $filename = 'pending_photo_' . $personId . '_' . time() . '.' . strtolower($ext);
                    $destPath = $pendingDir . '/' . $filename;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                        $relativePending = 'people/pending/' . $filename;

                        // Queue as a pending change (file_path type)
                        $db->prepare("
                            DELETE FROM pending_profile_changes
                            WHERE person_id = ? AND field_name = 'photo_path' AND status = 'pending'
                        ")->execute([$personId]);

                        $db->prepare("
                            INSERT INTO pending_profile_changes
                                (organisation_id, person_id, submitted_by_user_id,
                                 table_name, field_name, field_label, field_type,
                                 current_value, proposed_value, pending_file_path, status, submitted_at)
                            VALUES (?, ?, ?, 'people', 'photo_path', 'Profile photo', 'file_path',
                                    ?, ?, ?, 'pending', NOW())
                        ")->execute([
                            $organisationId, $personId, $userId,
                            $person['photo_path'], $relativePending, $relativePending
                        ]);

                        $success = 'Your photo has been submitted and is awaiting approval.';
                        ProfileChangeNotifications::dispatchManagerNotification(
                            $db, $person, $organisationId, ['Profile photo']
                        );
                    } else {
                        $error = 'Could not save the uploaded file. Please try again.';
                    }
                }
            } else {
                $error = 'No file was received. Please select an image.';
            }

        // ── File upload: signature ────────────────────────────────────────────
        } elseif ($action === 'upload_signature') {
            if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo   = finfo_open(FILEINFO_MIME_TYPE);
                $mime    = finfo_file($finfo, $_FILES['signature']['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowed, true)) {
                    $error = 'Only JPEG, PNG, GIF or WebP images are accepted for signatures.';
                } elseif ($_FILES['signature']['size'] > 2 * 1024 * 1024) {
                    $error = 'Signature image must be under 2 MB.';
                } else {
                    $ext        = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
                    $pendingDir = UPLOADS_PATH . '/people/pending';
                    if (!is_dir($pendingDir)) {
                        mkdir($pendingDir, 0755, true);
                    }
                    $filename = 'pending_sig_' . $personId . '_' . time() . '.' . strtolower($ext);
                    $destPath = $pendingDir . '/' . $filename;

                    if (move_uploaded_file($_FILES['signature']['tmp_name'], $destPath)) {
                        $relativePending = 'people/pending/' . $filename;

                        $db->prepare("
                            DELETE FROM pending_profile_changes
                            WHERE person_id = ? AND field_name = 'signature_path' AND status = 'pending'
                        ")->execute([$personId]);

                        $db->prepare("
                            INSERT INTO pending_profile_changes
                                (organisation_id, person_id, submitted_by_user_id,
                                 table_name, field_name, field_label, field_type,
                                 current_value, proposed_value, pending_file_path, status, submitted_at)
                            VALUES (?, ?, ?, 'staff_profiles', 'signature_path', 'Signature', 'file_path',
                                    ?, ?, ?, 'pending', NOW())
                        ")->execute([
                            $organisationId, $personId, $userId,
                            $person['signature_path'], $relativePending, $relativePending
                        ]);

                        $success = 'Your signature has been submitted and is awaiting approval.';
                        ProfileChangeNotifications::dispatchManagerNotification(
                            $db, $person, $organisationId, ['Signature']
                        );
                    } else {
                        $error = 'Could not save the uploaded file. Please try again.';
                    }
                }
            } else {
                $error = 'No file was received. Please select an image.';
            }

        // ── Text/date field changes ───────────────────────────────────────────
        } else {
            // Gather only the fields that staff are permitted to submit
            $textFields = [
                'first_name', 'last_name', 'date_of_birth', 'phone',
                'address_line1', 'address_line2', 'address_city',
                'address_county', 'address_postcode', 'address_country',
                'emergency_contact_name', 'emergency_contact_phone',
                'ni_number', 'bank_sort_code', 'bank_account_number', 'bank_account_name',
            ];

            $changes = [];
            foreach ($textFields as $field) {
                if (!isset($_POST[$field])) {
                    continue;
                }
                $newVal = trim($_POST[$field]);
                // Only queue a change if the value is actually different
                if ($newVal !== (string)($person[$field] ?? '')) {
                    $changes[$field] = $newVal;
                }
            }

            if (empty($changes)) {
                $success = 'No changes were detected.';
            } else {
                $result = PendingProfileChange::submit(
                    $personId,
                    $organisationId,
                    $userId,
                    $changes,
                    $person
                );

                $parts = [];
                if (!empty($result['applied'])) {
                    $parts[] = count($result['applied']) . ' change(s) saved immediately';
                }
                if (!empty($result['pending'])) {
                    $parts[] = count($result['pending']) . ' change(s) sent to your manager for approval';

                    // Notify the manager (or HR if no line manager)
                    ProfileChangeNotifications::dispatchManagerNotification(
                        $db,
                        $person,
                        $organisationId,
                        array_map(
                            fn($f) => PendingProfileChange::STAFF_EDITABLE_FIELDS[$f]['label'] ?? $f,
                            $result['pending']
                        )
                    );
                }
                $success = implode('; ', $parts) . '.';
            }
        }

        // Reload person data so the page reflects any immediate changes
        $stmt->execute([$userId, $organisationId]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ─── Load pending changes for this person ─────────────────────────────────────
$pendingChanges = PendingProfileChange::getPendingForPerson($personId);

// Index pending changes by field so we can show them inline
$pendingByField = [];
foreach ($pendingChanges as $pc) {
    $pendingByField[$pc['field_name']] = $pc;
}

// Load recent history (approved + rejected) to show staff feedback
$historyStmt = $db->prepare("
    SELECT * FROM pending_profile_changes
    WHERE person_id = ? AND status IN ('approved', 'rejected')
    ORDER BY reviewed_at DESC
    LIMIT 10
");
$historyStmt->execute([$personId]);
$recentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Photo URL helper ─────────────────────────────────────────────────────────
$photoUrl = null;
if ($person['photo_path'] && $person['photo_approval_status'] === 'approved') {
    $photoUrl = url('view-image.php?path=' . urlencode($person['photo_path']));
}

$pendingPhotoUrl = null;
if (isset($pendingByField['photo_path'])) {
    $pendingPhotoUrl = url('view-image.php?path=' . urlencode($pendingByField['photo_path']['pending_file_path']));
}

$pageTitle = 'My Profile';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">

    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Photo -->
        <div style="flex-shrink: 0;">
            <?php if ($photoUrl): ?>
                <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile photo"
                     style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #e5e7eb;">
            <?php else: ?>
                <div style="width: 80px; height: 80px; background: #f3f4f6; border: 1px solid #e5e7eb;
                            display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user fa-2x" style="color: #9ca3af;"></i>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h1 style="margin: 0;"><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h1>
            <?php if ($person['job_title']): ?>
                <p style="color: #6b7280; margin: 0.25rem 0 0;"><?php echo htmlspecialchars($person['job_title']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($pendingChanges)): ?>
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 1rem; margin-bottom: 2rem;">
            <p style="margin: 0; color: #92400e;">
                <i class="fas fa-clock"></i>
                <strong><?php echo count($pendingChanges); ?> change(s)</strong> are awaiting approval from your manager:
                <?php echo implode(', ', array_column($pendingChanges, 'field_label')); ?>.
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="update_details">

        <!-- ── Personal Details ─────────────────────────────────────────────── -->
        <h2>Personal Details</h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin-top: -0.5rem; margin-bottom: 1.5rem;">
            Changes to your name and date of birth will be sent to your manager for approval.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <?php foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label): ?>
                <div>
                    <label for="<?php echo $field; ?>" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">
                        <?php echo $label; ?>
                        <?php if (isset($pendingByField[$field])): ?>
                            <span style="color: #f59e0b; font-size: 0.75rem; font-weight: normal;">
                                <i class="fas fa-clock"></i> pending: &ldquo;<?php echo htmlspecialchars($pendingByField[$field]['proposed_value']); ?>&rdquo;
                            </span>
                        <?php endif; ?>
                    </label>
                    <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>"
                           value="<?php echo htmlspecialchars($person[$field] ?? ''); ?>"
                           class="form-control" style="width: 100%;">
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label for="date_of_birth" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">
                    Date of birth
                    <?php if (isset($pendingByField['date_of_birth'])): ?>
                        <span style="color: #f59e0b; font-size: 0.75rem; font-weight: normal;">
                            <i class="fas fa-clock"></i> pending: <?php echo htmlspecialchars($pendingByField['date_of_birth']['proposed_value']); ?>
                        </span>
                    <?php endif; ?>
                </label>
                <input type="date" id="date_of_birth" name="date_of_birth"
                       value="<?php echo htmlspecialchars($person['date_of_birth'] ?? ''); ?>"
                       class="form-control" style="width: 100%;">
            </div>
            <div>
                <label for="phone" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Phone number</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($person['phone'] ?? ''); ?>"
                       class="form-control" style="width: 100%;">
                <small style="color: #6b7280;">Applied immediately — no approval needed.</small>
            </div>
        </div>

        <!-- ── Home Address ─────────────────────────────────────────────────── -->
        <h2 style="margin-top: 2rem;">Home Address</h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin-top: -0.5rem; margin-bottom: 1.5rem;">
            Address changes are sent to your manager for approval.
        </p>

        <?php
        $addressFields = [
            'address_line1'   => ['label' => 'Address line 1',  'cols' => 2],
            'address_line2'   => ['label' => 'Address line 2',  'cols' => 2],
            'address_city'    => ['label' => 'City / Town',     'cols' => 1],
            'address_county'  => ['label' => 'County',          'cols' => 1],
            'address_postcode'=> ['label' => 'Postcode',        'cols' => 1],
            'address_country' => ['label' => 'Country',         'cols' => 1],
        ];
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <?php foreach ($addressFields as $field => $meta): ?>
                <div style="<?php echo $meta['cols'] === 2 ? 'grid-column: span 2;' : ''; ?>">
                    <label for="<?php echo $field; ?>" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">
                        <?php echo $meta['label']; ?>
                        <?php if (isset($pendingByField[$field])): ?>
                            <span style="color: #f59e0b; font-size: 0.75rem; font-weight: normal;">
                                <i class="fas fa-clock"></i> pending
                            </span>
                        <?php endif; ?>
                    </label>
                    <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>"
                           value="<?php echo htmlspecialchars($person[$field] ?? ''); ?>"
                           class="form-control" style="width: 100%;">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Emergency Contact ────────────────────────────────────────────── -->
        <h2 style="margin-top: 2rem;">Emergency Contact</h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin-top: -0.5rem; margin-bottom: 1.5rem;">
            Applied immediately — no approval needed.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label for="emergency_contact_name" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Name</label>
                <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                       value="<?php echo htmlspecialchars($person['emergency_contact_name'] ?? ''); ?>"
                       class="form-control" style="width: 100%;">
            </div>
            <div>
                <label for="emergency_contact_phone" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Phone</label>
                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                       value="<?php echo htmlspecialchars($person['emergency_contact_phone'] ?? ''); ?>"
                       class="form-control" style="width: 100%;">
            </div>
        </div>

        <!-- ── Bank Details ─────────────────────────────────────────────────── -->
        <h2 style="margin-top: 2rem;">Bank Details</h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin-top: -0.5rem; margin-bottom: 1.5rem;">
            Bank detail changes require manager approval before taking effect.
        </p>

        <?php
        $bankFields = [
            'ni_number'           => 'National Insurance number',
            'bank_account_name'   => 'Account holder name',
            'bank_sort_code'      => 'Sort code',
            'bank_account_number' => 'Account number',
        ];
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <?php foreach ($bankFields as $field => $label): ?>
                <div>
                    <label for="<?php echo $field; ?>" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">
                        <?php echo $label; ?>
                        <?php if (isset($pendingByField[$field])): ?>
                            <span style="color: #f59e0b; font-size: 0.75rem; font-weight: normal;">
                                <i class="fas fa-clock"></i> pending approval
                            </span>
                        <?php endif; ?>
                    </label>
                    <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>"
                           value="<?php echo htmlspecialchars($person[$field] ?? ''); ?>"
                           autocomplete="off"
                           class="form-control" style="width: 100%;">
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>

    <!-- ── Profile Photo ──────────────────────────────────────────────────────── -->
    <hr style="margin: 2.5rem 0; border-color: #e5e7eb;">
    <h2>Profile Photo</h2>
    <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem;">
        Your photo must be approved by your manager before it appears on your profile.
        Please upload a clear, professional photo of your face only.
    </p>

    <div style="display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <p style="font-weight: 600; margin-bottom: 0.5rem;">Current photo</p>
            <?php if ($photoUrl): ?>
                <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Current photo"
                     style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb;">
            <?php else: ?>
                <div style="width: 120px; height: 120px; background: #f3f4f6; border: 1px solid #e5e7eb;
                            display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user fa-3x" style="color: #9ca3af;"></i>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($pendingPhotoUrl): ?>
            <div>
                <p style="font-weight: 600; margin-bottom: 0.5rem; color: #f59e0b;">
                    <i class="fas fa-clock"></i> Pending approval
                </p>
                <img src="<?php echo htmlspecialchars($pendingPhotoUrl); ?>" alt="Pending photo"
                     style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #f59e0b;">
            </div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
          enctype="multipart/form-data">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="upload_photo">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                   style="flex: 1;">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-upload"></i> Upload Photo
            </button>
        </div>
        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
            JPEG, PNG, GIF or WebP. Maximum 5 MB.
        </small>
    </form>

    <!-- ── Signature ──────────────────────────────────────────────────────────── -->
    <hr style="margin: 2.5rem 0; border-color: #e5e7eb;">
    <h2>Signature</h2>
    <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem;">
        Only you can update your signature. It will be reviewed before being applied to documents.
    </p>

    <?php if ($person['signature_path']): ?>
        <div style="margin-bottom: 1rem;">
            <p style="font-weight: 600; margin-bottom: 0.5rem;">Current signature</p>
            <img src="<?php echo htmlspecialchars(url('view-image.php?path=' . urlencode($person['signature_path']))); ?>"
                 alt="Current signature"
                 style="max-width: 300px; border: 1px solid #e5e7eb; padding: 0.5rem; background: #fff;">
            <?php if ($person['signature_created_at']): ?>
                <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">
                    Added <?php echo date(DATE_FORMAT, strtotime($person['signature_created_at'])); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($pendingByField['signature_path'])): ?>
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 0.75rem; margin-bottom: 1rem;">
            <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                <i class="fas fa-clock"></i> A new signature is awaiting approval.
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
          enctype="multipart/form-data">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="upload_signature">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <input type="file" name="signature" id="signature"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   style="flex: 1;">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-upload"></i> Upload Signature
            </button>
        </div>
        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
            JPEG, PNG, GIF or WebP. Maximum 2 MB. Plain white background recommended.
        </small>
    </form>

    <!-- ── Recent Change History ──────────────────────────────────────────────── -->
    <?php if (!empty($recentHistory)): ?>
        <hr style="margin: 2.5rem 0; border-color: #e5e7eb;">
        <h2>Recent Change History</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="text-align: left; padding: 0.5rem;">Field</th>
                    <th style="text-align: left; padding: 0.5rem;">Outcome</th>
                    <th style="text-align: left; padding: 0.5rem;">Reviewed</th>
                    <th style="text-align: left; padding: 0.5rem;">Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentHistory as $row): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.5rem;"><?php echo htmlspecialchars($row['field_label']); ?></td>
                        <td style="padding: 0.5rem;">
                            <?php if ($row['status'] === 'approved'): ?>
                                <span style="color: #10b981;"><i class="fas fa-check-circle"></i> Approved</span>
                            <?php else: ?>
                                <span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.5rem; color: #6b7280;">
                            <?php echo $row['reviewed_at'] ? date(DATE_FORMAT, strtotime($row['reviewed_at'])) : '—'; ?>
                        </td>
                        <td style="padding: 0.5rem; color: #6b7280;">
                            <?php echo $row['rejection_reason'] ? htmlspecialchars($row['rejection_reason']) : '—'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
