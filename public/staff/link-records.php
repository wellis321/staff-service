<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = '';

// Handle link action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'link') {
            $primaryPersonId = (int)($_POST['primary_person_id'] ?? 0);
            $linkedPersonId = (int)($_POST['linked_person_id'] ?? 0);
            $relationshipType = trim($_POST['relationship_type'] ?? 'previous_employment');
            $notes = trim($_POST['notes'] ?? '');
            
            if ($primaryPersonId && $linkedPersonId && $primaryPersonId != $linkedPersonId) {
                $result = Person::linkPersonRecords($primaryPersonId, $linkedPersonId, $organisationId, $relationshipType, $notes);
                
                if ($result) {
                    $success = 'Person records linked successfully. Learning records from the previous record are now accessible.';
                    header('Location: ' . url('staff/link-records.php?id=' . $primaryPersonId . '&success=' . urlencode($success)));
                    exit;
                } else {
                    $error = 'Failed to link person records. They may already be linked.';
                }
            } else {
                $error = 'Invalid person IDs.';
            }
        } elseif ($action === 'unlink') {
            $primaryPersonId = (int)($_POST['primary_person_id'] ?? 0);
            $linkedPersonId = (int)($_POST['linked_person_id'] ?? 0);
            
            if ($primaryPersonId && $linkedPersonId) {
                $result = Person::unlinkPersonRecords($primaryPersonId, $linkedPersonId, $organisationId);
                
                if ($result) {
                    $success = 'Person records unlinked successfully.';
                    header('Location: ' . url('staff/link-records.php?id=' . $primaryPersonId . '&success=' . urlencode($success)));
                    exit;
                } else {
                    $error = 'Failed to unlink person records.';
                }
            } else {
                $error = 'Invalid person IDs.';
            }
        }
    }
}

// Get person ID
$personId = $_GET['id'] ?? null;
$person = null;
$potentialMatches = [];
$linkedRecords = [];
$linkedLearningRecords = [];

if ($personId) {
    $person = Person::findById($personId, $organisationId);
    if ($person && $person['organisation_id'] == $organisationId) {
        // Find potential matches using enhanced finder
        $potentialMatches = Person::findPotentialMatches(
            $organisationId,
            $person['email'] ?? null,
            $person['first_name'] ?? null,
            $person['last_name'] ?? null,
            $person['date_of_birth'] ?? null
        );
        
        // Remove the current person from matches
        $potentialMatches = array_filter($potentialMatches, function($match) use ($personId) {
            return $match['id'] != $personId;
        });
        
        // Get existing linked records
        $linkedRecords = Person::getLinkedPersonRecords($personId, $organisationId);
        
        // Get learning records from linked persons
        if (!empty($linkedRecords)) {
            foreach ($linkedRecords as $linked) {
                $linkedId = $linked['linked_person_id'];
                $learning = StaffLearningRecord::getByPersonId($linkedId, $organisationId, [], false); // Don't include nested links
                if (!empty($learning)) {
                    $linkedLearningRecords[$linkedId] = [
                        'person' => $linked,
                        'learning' => $learning
                    ];
                }
            }
        }
    } else {
        $error = 'Person not found.';
    }
}

$pageTitle = 'Link Previous Records';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo $personId ? url('staff/view.php?id=' . $personId) : url('staff/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Link Previous Records</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($personId && $person): ?>
        <div style="margin-bottom: 2rem;">
            <h2>Current Profile</h2>
            <div style="background: #f9fafb; padding: 1rem; border-radius: 0; margin-top: 1rem;">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($person['email'] ?? $person['user_email'] ?? '-'); ?></p>
                <p><strong>Employee Reference:</strong> <?php echo htmlspecialchars($person['employee_reference'] ?? '-'); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo $person['date_of_birth'] ? date('d/m/Y', strtotime($person['date_of_birth'])) : '-'; ?></p>
                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($person['job_title'] ?? '-'); ?></p>
            </div>
        </div>
        
        <!-- Existing Linked Records -->
        <?php if (!empty($linkedRecords)): ?>
            <div style="margin-bottom: 2rem;">
                <h2>Linked Previous Records</h2>
                <p style="color: #6b7280; margin-bottom: 1rem;">
                    These records are linked to the current profile. Learning records from these profiles are accessible below.
                </p>
                
                <?php foreach ($linkedRecords as $linked): ?>
                    <div style="border: 1px solid #e5e7eb; padding: 1.5rem; margin-bottom: 1rem; border-radius: 0;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
                            <div>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($linked['first_name'] . ' ' . $linked['last_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($linked['email'] ?? $linked['user_email'] ?? '-'); ?></p>
                                <p><strong>Employee Reference:</strong> <?php echo htmlspecialchars($linked['employee_reference'] ?? '-'); ?></p>
                                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($linked['job_title'] ?? '-'); ?></p>
                                <?php if ($linked['employment_start_date']): ?>
                                    <p><strong>Employment Period:</strong> 
                                        <?php echo date('d/m/Y', strtotime($linked['employment_start_date'])); ?>
                                        <?php if ($linked['employment_end_date']): ?>
                                            - <?php echo date('d/m/Y', strtotime($linked['employment_end_date'])); ?>
                                        <?php else: ?>
                                            - Present
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <p><strong>Relationship Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $linked['relationship_type'])); ?></p>
                                <p><strong>Linked:</strong> <?php echo date('d/m/Y H:i', strtotime($linked['linked_at'])); ?></p>
                                <?php if ($linked['notes']): ?>
                                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($linked['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="" style="margin: 0;">
                                <?php echo CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="unlink">
                                <input type="hidden" name="primary_person_id" value="<?php echo $personId; ?>">
                                <input type="hidden" name="linked_person_id" value="<?php echo $linked['linked_person_id']; ?>">
                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to unlink this record? Learning records will no longer be accessible.');">
                                    <i class="fas fa-unlink"></i> Unlink
                                </button>
                            </form>
                        </div>
                        
                        <!-- Learning Records from this Linked Person -->
                        <?php if (isset($linkedLearningRecords[$linked['linked_person_id']])): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                <h4 style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">
                                    <i class="fas fa-graduation-cap"></i> Learning Records from Previous Employment (<?php echo count($linkedLearningRecords[$linked['linked_person_id']]['learning']); ?>)
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.875rem; color: #6b7280;">
                                    <?php foreach (array_slice($linkedLearningRecords[$linked['linked_person_id']]['learning'], 0, 5) as $record): ?>
                                        <li><?php echo htmlspecialchars($record['title']); ?> 
                                            <?php if ($record['completion_date']): ?>
                                                (<?php echo date('Y', strtotime($record['completion_date'])); ?>)
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($linkedLearningRecords[$linked['linked_person_id']]['learning']) > 5): ?>
                                        <li><em>... and <?php echo count($linkedLearningRecords[$linked['linked_person_id']]['learning']) - 5; ?> more</em></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Potential Matches -->
        <?php if (!empty($potentialMatches)): ?>
            <div style="margin-bottom: 2rem;">
                <h2>Potential Matches</h2>
                <p style="color: #6b7280; margin-bottom: 1rem;">
                    These profiles may be from previous employment periods. Link them to make learning records accessible.
                </p>
                
                <?php foreach ($potentialMatches as $match): ?>
                    <div style="border: 1px solid #e5e7eb; padding: 1.5rem; margin-bottom: 1rem; border-radius: 0;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
                            <div>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($match['first_name'] . ' ' . $match['last_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($match['email'] ?? $match['user_email'] ?? '-'); ?></p>
                                <p><strong>Employee Reference:</strong> <?php echo htmlspecialchars($match['employee_reference'] ?? '-'); ?></p>
                                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($match['job_title'] ?? '-'); ?></p>
                                <?php if ($match['employment_start_date']): ?>
                                    <p><strong>Employment Period:</strong> 
                                        <?php echo date('d/m/Y', strtotime($match['employment_start_date'])); ?>
                                        <?php if ($match['employment_end_date']): ?>
                                            - <?php echo date('d/m/Y', strtotime($match['employment_end_date'])); ?>
                                        <?php else: ?>
                                            - Present
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (isset($match['match_score'])): ?>
                                    <p style="color: #6b7280; font-size: 0.875rem;">
                                        <i class="fas fa-star"></i> Match Score: <?php echo $match['match_score']; ?>/23
                                    </p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="" style="margin: 0;">
                                <?php echo CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="link">
                                <input type="hidden" name="primary_person_id" value="<?php echo $personId; ?>">
                                <input type="hidden" name="linked_person_id" value="<?php echo $match['id']; ?>">
                                <input type="hidden" name="relationship_type" value="previous_employment">
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-link"></i> Link Records
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No potential matches found. You can manually search for previous records by employee reference or name.
            </div>
        <?php endif; ?>
        
        <!-- Manual Link Form -->
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
            <h2>Manual Link</h2>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                If you know the employee reference of a previous record, you can link it manually.
            </p>
            <form method="GET" action="<?php echo url('staff/index.php'); ?>" style="display: flex; gap: 1rem; align-items: end;">
                <div style="flex: 1;">
                    <label for="search_employee_ref">Search by Employee Reference:</label>
                    <input type="text" id="search_employee_ref" name="search" placeholder="Enter employee reference" style="width: 100%;">
                </div>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
        
    <?php else: ?>
        <div class="alert alert-error">
            Please select a profile to link records. <a href="<?php echo url('staff/index.php'); ?>">Go to staff list</a>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

