<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$userId = Auth::getUserId();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $data = [
            'organisation_id' => $organisationId,
            'title' => trim($_POST['title'] ?? ''),
            'code' => trim($_POST['code'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'responsibilities' => trim($_POST['responsibilities'] ?? ''),
            'requirements' => trim($_POST['requirements'] ?? ''),
            'external_system' => trim($_POST['external_system'] ?? ''),
            'external_id' => trim($_POST['external_id'] ?? ''),
            'external_url' => trim($_POST['external_url'] ?? ''),
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1',
            'created_by' => $userId
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
            $result = JobDescription::create($data);
            if ($result) {
                $success = 'Job description created successfully.';
                header('Location: ' . url('job-descriptions/view.php?id=' . $result['id'] . '&success=' . urlencode($success)));
                exit;
            } else {
                $error = 'Failed to create job description.';
            }
        }
    }
}

$pageTitle = 'Create Job Description';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('job-descriptions/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Create Job Description</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo CSRF::tokenField(); ?>
        
        <div style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 1rem; margin-bottom: 2rem; border-radius: 0;">
            <p style="margin: 0; color: #1e40af;">
                <strong><i class="fas fa-info-circle"></i> Job Description Template</strong><br>
                This is a generic job description template. It describes the role, responsibilities, and requirements in general terms.
                To create a specific position with location, hours, and other details, create a <strong>Job Post</strong> based on this description.
            </p>
        </div>
        
        <h2>Basic Information</h2>
        <div class="form-group">
            <label for="title">Job Title <span style="color: #dc2626;">*</span></label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required placeholder="e.g. Police Officer, Support Worker">
            <small>The generic title for this role type</small>
        </div>
        
        <div class="form-group">
            <label for="code">Job Code</label>
            <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" placeholder="e.g. JD-PO-001">
            <small>Optional: Internal reference code for this job description template</small>
        </div>
        
        <h2 style="margin-top: 2rem;">Role Description</h2>
        <div class="form-group">
            <label for="description">Job Description</label>
            <textarea id="description" name="description" rows="6" placeholder="Describe the role in general terms..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            <small>General overview and purpose of this role</small>
        </div>
        
        <div class="form-group">
            <label for="responsibilities">Key Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" rows="6" placeholder="List the main responsibilities and duties..."><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
            <small>General responsibilities that apply to this role type</small>
        </div>
        
        <div class="form-group">
            <label for="requirements">Requirements</label>
            <textarea id="requirements" name="requirements" rows="6" placeholder="List required qualifications, skills, attributes, and experience..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
            <small>General qualifications, skills, attributes, and experience typically required for this role</small>
        </div>
        
        <h2 style="margin-top: 2rem;">External System Integration (Optional)</h2>
        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
            If this job description template is managed in an external recruitment or HR system, you can link it here.
        </p>
        
        <div class="form-group">
            <label for="external_system">External System</label>
            <input type="text" id="external_system" name="external_system" 
                   value="<?php echo htmlspecialchars($_POST['external_system'] ?? ''); ?>" 
                   placeholder="e.g. Recruitment System, HR Portal">
        </div>
        
        <div class="form-group">
            <label for="external_id">External Reference ID</label>
            <input type="text" id="external_id" name="external_id" 
                   value="<?php echo htmlspecialchars($_POST['external_id'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="external_url">External URL</label>
            <input type="url" id="external_url" name="external_url" 
                   value="<?php echo htmlspecialchars($_POST['external_url'] ?? ''); ?>" 
                   placeholder="https://...">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo !isset($_POST['is_active']) || $_POST['is_active'] === '1' ? 'checked' : ''; ?>>
                Active
            </label>
            <small>Inactive job descriptions won't appear when creating new job posts</small>
        </div>
        
        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Job Description
            </button>
            <a href="<?php echo url('job-descriptions/index.php'); ?>" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

