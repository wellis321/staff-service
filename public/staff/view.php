<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = '';

// Get person ID
$personId = $_GET['id'] ?? null;
if (!$personId) {
    header('Location: ' . url('staff/index.php?error=invalid_id'));
    exit;
}

// Get person
$person = Person::findById($personId, $organisationId);
if (!$person || $person['organisation_id'] != $organisationId) {
    header('Location: ' . url('staff/index.php?error=not_found'));
    exit;
}

// Get organisational units
$organisationalUnits = Person::getOrganisationalUnits($personId);

// Get line manager
$lineManager = null;
if ($person['line_manager_id']) {
    $lineManager = Person::findById($person['line_manager_id'], $organisationId);
}

// Get linked person records
$linkedRecords = Person::getLinkedPersonRecords($personId, $organisationId);

// Get learning records (including from linked records)
$learningRecords = [];
$qualifications = [];
$courses = [];
if ($person['person_type'] === 'staff') {
    $learningRecords = StaffLearningRecord::getByPersonId($personId, $organisationId);
    $qualifications = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'qualification']);
    $courses = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'course']);
}

// Get all organisational units for organisation (for assignment)
$db = getDbConnection();
$stmt = $db->prepare("
    SELECT id, name, code 
    FROM organisational_units 
    WHERE organisation_id = ? 
    ORDER BY name
");
$stmt->execute([$organisationId]);
$allUnits = $stmt->fetchAll();

$pageTitle = 'View Staff Member';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('staff/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex: 1;">
            <h1><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h1>
            <p style="color: #6b7280; margin-top: 0.5rem;">
                <?php if ($person['job_title']): ?>
                    <?php echo htmlspecialchars($person['job_title']); ?>
                <?php endif; ?>
                <?php if ($person['employee_reference']): ?>
                    | Reference: <?php echo htmlspecialchars($person['employee_reference']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?php echo url('staff/link-records.php?id=' . $personId); ?>" class="btn btn-secondary">
                <i class="fas fa-link"></i> Link Previous Records
            </a>
            <a href="<?php echo url('staff/merge.php?id=' . $personId); ?>" class="btn btn-secondary">
                <i class="fas fa-compress-alt"></i> Merge Duplicates
            </a>
            <a href="<?php echo url('staff/edit.php?id=' . $personId); ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem; margin-top: 2rem;">
        <div>
            <?php
            $photoPath = null;
            if ($person['photo_path'] && $person['photo_approval_status'] === 'approved') {
                $photoPath = url('view-image.php?path=' . urlencode($person['photo_path']));
            } elseif ($person['photo_pending_path']) {
                $photoPath = url('view-image.php?path=' . urlencode($person['photo_pending_path']));
            }
            ?>
            <?php if ($photoPath): ?>
                <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Profile Photo" style="width: 100%; border-radius: 0; border: 1px solid #e5e7eb;">
            <?php else: ?>
                <div style="width: 100%; aspect-ratio: 1; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border: 1px solid #e5e7eb;">
                    <i class="fas fa-user fa-3x" style="color: #9ca3af;"></i>
                </div>
            <?php endif; ?>
            
            <?php if ($person['photo_approval_status'] === 'pending'): ?>
                <p style="font-size: 0.875rem; color: #f59e0b; margin-top: 0.5rem; text-align: center;">
                    <i class="fas fa-clock"></i> Pending approval
                </p>
            <?php elseif ($person['photo_approval_status'] === 'rejected'): ?>
                <p style="font-size: 0.875rem; color: #ef4444; margin-top: 0.5rem; text-align: center;">
                    <i class="fas fa-times-circle"></i> Rejected
                </p>
            <?php endif; ?>
        </div>
        
        <div>
            <h2>Personal Information</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem; font-weight: 600; width: 200px;">Name</td>
                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></td>
                </tr>
                <?php if ($person['email']): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem; font-weight: 600;">Email</td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($person['email']); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($person['phone']): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem; font-weight: 600;">Phone</td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($person['phone']); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($person['date_of_birth']): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem; font-weight: 600;">Date of Birth</td>
                        <td style="padding: 0.75rem;"><?php echo date(DATE_FORMAT, strtotime($person['date_of_birth'])); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($person['employee_reference']): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem; font-weight: 600;">Employee Reference</td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($person['employee_reference']); ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem; font-weight: 600;">Status</td>
                    <td style="padding: 0.75rem;">
                        <?php if ($person['is_active']): ?>
                            <span style="color: #10b981; font-weight: 500;">
                                <i class="fas fa-check-circle"></i> Active
                            </span>
                        <?php else: ?>
                            <span style="color: #6b7280;">
                                <i class="fas fa-times-circle"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php if ($person['person_type'] === 'staff'): ?>
                <h2 style="margin-top: 2rem;">Employment Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <?php if ($person['job_title']): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; font-weight: 600; width: 200px;">Job Title</td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($person['job_title']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($person['employment_start_date']): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; font-weight: 600;">Employment Start Date</td>
                            <td style="padding: 0.75rem;"><?php echo date(DATE_FORMAT, strtotime($person['employment_start_date'])); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($person['employment_end_date']): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; font-weight: 600;">Employment End Date</td>
                            <td style="padding: 0.75rem;"><?php echo date(DATE_FORMAT, strtotime($person['employment_end_date'])); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($lineManager): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; font-weight: 600;">Line Manager</td>
                            <td style="padding: 0.75rem;">
                                <a href="<?php echo url('staff/view.php?id=' . $lineManager['id']); ?>" style="color: #2563eb; text-decoration: none;">
                                    <?php echo htmlspecialchars($lineManager['first_name'] . ' ' . $lineManager['last_name']); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($person['emergency_contact_name']): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; font-weight: 600;">Emergency Contact</td>
                            <td style="padding: 0.75rem;">
                                <?php echo htmlspecialchars($person['emergency_contact_name']); ?>
                                <?php if ($person['emergency_contact_phone']): ?>
                                    <br><small><?php echo htmlspecialchars($person['emergency_contact_phone']); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($person['notes']): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; font-weight: 600;">Notes</td>
                            <td style="padding: 0.75rem; white-space: pre-wrap;"><?php echo htmlspecialchars($person['notes']); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
            
            <?php if (!empty($organisationalUnits)): ?>
                <h2 style="margin-top: 2rem;">Organisational Units</h2>
                <ul>
                    <?php foreach ($organisationalUnits as $unit): ?>
                        <li style="margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($unit['unit_name']); ?>
                            <?php if ($unit['is_primary']): ?>
                                <span style="color: #2563eb; font-weight: 600;">(Primary)</span>
                            <?php endif; ?>
                            <?php if ($unit['role_in_unit'] && $unit['role_in_unit'] !== 'member'): ?>
                                - <?php echo htmlspecialchars($unit['role_in_unit']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (!empty($linkedRecords)): ?>
                <h2 style="margin-top: 2rem;">Linked Previous Records</h2>
                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                    These records are linked to show learning history from previous employment periods.
                </p>
                <?php foreach ($linkedRecords as $linked): ?>
                    <div style="border: 1px solid #e5e7eb; padding: 1rem; margin-bottom: 1rem; border-radius: 0; background: #f9fafb;">
                        <p><strong><?php echo htmlspecialchars($linked['first_name'] . ' ' . $linked['last_name']); ?></strong></p>
                        <p style="font-size: 0.875rem; color: #6b7280;">
                            Employee Ref: <?php echo htmlspecialchars($linked['employee_reference'] ?? '-'); ?>
                            <?php if ($linked['employment_start_date']): ?>
                                | <?php echo date('Y', strtotime($linked['employment_start_date'])); ?>
                                <?php if ($linked['employment_end_date']): ?>
                                    - <?php echo date('Y', strtotime($linked['employment_end_date'])); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                <a href="<?php echo url('staff/link-records.php?id=' . $personId); ?>" class="btn btn-secondary" style="margin-top: 0.5rem;">
                    <i class="fas fa-link"></i> Manage Links
                </a>
            <?php endif; ?>
            
            <?php if ($person['person_type'] === 'staff' && !empty($learningRecords)): ?>
                <h2 style="margin-top: 2rem;">Learning & Qualifications</h2>
                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                    <?php 
                    $currentRecords = array_filter($learningRecords, function($r) { return empty($r['is_from_linked_record']); });
                    $linkedRecordsCount = count($learningRecords) - count($currentRecords);
                    ?>
                    Total: <?php echo count($learningRecords); ?> records
                    <?php if ($linkedRecordsCount > 0): ?>
                        (<?php echo count($currentRecords); ?> current, <?php echo $linkedRecordsCount; ?> from previous employment)
                    <?php endif; ?>
                </p>
                
                <?php if (!empty($qualifications)): ?>
                    <h3 style="font-size: 1rem; margin-top: 1rem; margin-bottom: 0.5rem;">Qualifications</h3>
                    <ul style="margin-bottom: 1rem;">
                        <?php foreach (array_slice($qualifications, 0, 5) as $qual): ?>
                            <li style="margin-bottom: 0.25rem;">
                                <?php echo htmlspecialchars($qual['title']); ?>
                                <?php if (!empty($qual['is_from_linked_record'])): ?>
                                    <span style="color: #6b7280; font-size: 0.875rem;">(from previous employment)</span>
                                <?php endif; ?>
                                <?php if ($qual['completion_date']): ?>
                                    <span style="color: #6b7280; font-size: 0.875rem;">- <?php echo date('Y', strtotime($qual['completion_date'])); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($qualifications) > 5): ?>
                            <li style="color: #6b7280; font-style: italic;">... and <?php echo count($qualifications) - 5; ?> more</li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($courses)): ?>
                    <h3 style="font-size: 1rem; margin-top: 1rem; margin-bottom: 0.5rem;">Courses & Training</h3>
                    <ul>
                        <?php foreach (array_slice($courses, 0, 5) as $course): ?>
                            <li style="margin-bottom: 0.25rem;">
                                <?php echo htmlspecialchars($course['title']); ?>
                                <?php if (!empty($course['is_from_linked_record'])): ?>
                                    <span style="color: #6b7280; font-size: 0.875rem;">(from previous employment)</span>
                                <?php endif; ?>
                                <?php if ($course['completion_date']): ?>
                                    <span style="color: #6b7280; font-size: 0.875rem;">- <?php echo date('Y', strtotime($course['completion_date'])); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($courses) > 5): ?>
                            <li style="color: #6b7280; font-style: italic;">... and <?php echo count($courses) - 5; ?> more</li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                
                <a href="<?php echo url('staff/edit.php?id=' . $personId . '#section-learning'); ?>" class="btn btn-secondary" style="margin-top: 1rem;">
                    <i class="fas fa-graduation-cap"></i> View All Learning Records
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

