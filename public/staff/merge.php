<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = '';

// Handle merge action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'merge') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $targetId = (int)($_POST['target_id'] ?? 0);
        $sourceId = (int)($_POST['source_id'] ?? 0);
        
        if ($targetId && $sourceId && $targetId != $sourceId) {
            $result = Person::mergeProfiles($targetId, $sourceId, $organisationId);
            
            if ($result) {
                $success = 'Profiles merged successfully.';
                header('Location: ' . url('staff/view.php?id=' . $targetId . '&success=' . urlencode($success)));
                exit;
            } else {
                $error = 'Failed to merge profiles.';
            }
        } else {
            $error = 'Invalid profile IDs.';
        }
    }
}

// Get person ID to find duplicates for
$personId = $_GET['id'] ?? null;
$duplicates = [];

if ($personId) {
    $person = Person::findById($personId, $organisationId);
    if ($person && $person['organisation_id'] == $organisationId) {
        // Find potential duplicates
        $duplicates = Person::findDuplicates(
            $organisationId,
            $person['email'] ?? null,
            $person['first_name'] ?? null,
            $person['last_name'] ?? null
        );
        
        // Remove the current person from duplicates list
        $duplicates = array_filter($duplicates, function($dup) use ($personId) {
            return $dup['id'] != $personId;
        });
    } else {
        $error = 'Person not found.';
    }
}

$pageTitle = 'Merge Duplicate Profiles';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo $personId ? url('staff/view.php?id=' . $personId) : url('staff/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Merge Duplicate Profiles</h1>
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
                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($person['job_title'] ?? '-'); ?></p>
            </div>
        </div>
        
        <?php if (!empty($duplicates)): ?>
            <h2>Potential Duplicates</h2>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                Select a duplicate profile to merge into the current profile. The duplicate will be deleted and its data merged.
            </p>
            
            <?php foreach ($duplicates as $duplicate): ?>
                <div style="border: 1px solid #e5e7eb; padding: 1.5rem; margin-bottom: 1rem; border-radius: 0;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
                        <div>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($duplicate['first_name'] . ' ' . $duplicate['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($duplicate['email'] ?? $duplicate['user_email'] ?? '-'); ?></p>
                            <p><strong>Employee Reference:</strong> <?php echo htmlspecialchars($duplicate['employee_reference'] ?? '-'); ?></p>
                            <p><strong>Job Title:</strong> <?php echo htmlspecialchars($duplicate['job_title'] ?? '-'); ?></p>
                            <p><strong>User Account:</strong> <?php echo $duplicate['user_id'] ? 'Linked (ID: ' . $duplicate['user_id'] . ')' : 'Not linked'; ?></p>
                        </div>
                        <form method="POST" action="" style="margin: 0;">
                            <?php echo CSRF::tokenField(); ?>
                            <input type="hidden" name="action" value="merge">
                            <input type="hidden" name="target_id" value="<?php echo $personId; ?>">
                            <input type="hidden" name="source_id" value="<?php echo $duplicate['id']; ?>">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to merge this profile into the current one? The duplicate profile will be deleted.');">
                                <i class="fas fa-compress-alt"></i> Merge Into Current
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No potential duplicates found for this profile.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-error">
            Please select a profile to check for duplicates. <a href="<?php echo url('staff/index.php'); ?>">Go to staff list</a>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

