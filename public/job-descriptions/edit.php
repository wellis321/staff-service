<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = '';

// Get job description ID
$jdId = $_GET['id'] ?? null;
if (!$jdId) {
    header('Location: ' . url('job-descriptions/index.php?error=invalid_id'));
    exit;
}

// Get job description
$jd = JobDescription::findById($jdId, $organisationId);
if (!$jd || $jd['organisation_id'] != $organisationId) {
    header('Location: ' . url('job-descriptions/index.php?error=not_found'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'code' => trim($_POST['code'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'responsibilities' => trim($_POST['responsibilities'] ?? ''),
            'requirements' => trim($_POST['requirements'] ?? ''),
            'external_system' => trim($_POST['external_system'] ?? ''),
            'external_id' => trim($_POST['external_id'] ?? ''),
            'external_url' => trim($_POST['external_url'] ?? ''),
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1'
        ];
        
        // Remove empty strings
        foreach ($data as $key => $value) {
            if ($value === '' && in_array($key, ['code', 'description', 'responsibilities', 'requirements', 
                                                   'external_system', 'external_id', 'external_url'])) {
                $data[$key] = null;
            }
        }
        
        // Validate required fields
        if (empty($data['title'])) {
            $error = 'Job title is required.';
        } else {
            $result = JobDescription::update($jdId, $data, $organisationId);
            if ($result) {
                $success = 'Job description updated successfully.';
                $jd = $result; // Refresh data
            } else {
                $error = 'Failed to update job description.';
            }
        }
    }
}

$pageTitle = 'Edit Job Description';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('job-descriptions/view.php?id=' . $jdId); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Edit Job Description</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 1rem; margin-bottom: 2rem; border-radius: 0;">
        <p style="margin: 0; color: #1e40af;">
            <strong><i class="fas fa-info-circle"></i> Job Description Template</strong><br>
            This is a generic job description template. Position-specific details (location, hours, salary, etc.) should be set in Job Posts.
        </p>
    </div>
    
    <form method="POST" action="">
        <?php echo CSRF::tokenField(); ?>
        
        <h2>Basic Information</h2>
        <div class="form-group">
            <label for="title">Job Title <span style="color: #dc2626;">*</span></label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($jd['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="code">Job Code</label>
            <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($jd['code'] ?? ''); ?>" placeholder="e.g. JD-001">
        </div>
        
        <h2 style="margin-top: 2rem;">Role Description</h2>
        <div class="form-group">
            <label for="description">Job Description</label>
            <textarea id="description" name="description" rows="6"><?php echo htmlspecialchars($jd['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="responsibilities">Key Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" rows="6"><?php echo htmlspecialchars($jd['responsibilities'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="requirements">Requirements</label>
            <textarea id="requirements" name="requirements" rows="6"><?php echo htmlspecialchars($jd['requirements'] ?? ''); ?></textarea>
        </div>
        
        <h2 style="margin-top: 2rem;">External System Integration (Optional)</h2>
        <div class="form-group">
            <label for="external_system">External System</label>
            <input type="text" id="external_system" name="external_system" 
                   value="<?php echo htmlspecialchars($jd['external_system'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="external_id">External Reference ID</label>
            <input type="text" id="external_id" name="external_id" 
                   value="<?php echo htmlspecialchars($jd['external_id'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="external_url">External URL</label>
            <input type="url" id="external_url" name="external_url" 
                   value="<?php echo htmlspecialchars($jd['external_url'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $jd['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="<?php echo url('job-descriptions/view.php?id=' . $jdId); ?>" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

