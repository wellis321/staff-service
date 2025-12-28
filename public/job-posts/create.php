<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$userId = Auth::getUserId();
$error = '';
$success = '';

// Get active job descriptions for selection
$jobDescriptions = JobDescription::getAllByOrganisation($organisationId, true);

// Get all users for manager selection
$db = getDbConnection();
$stmt = $db->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email
    FROM users u
    WHERE u.organisation_id = ? AND u.is_active = TRUE
    ORDER BY u.last_name, u.first_name
");
$stmt->execute([$organisationId]);
$users = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $data = [
            'organisation_id' => $organisationId,
            'job_description_id' => !empty($_POST['job_description_id']) ? (int)$_POST['job_description_id'] : null,
            'title' => trim($_POST['title'] ?? ''),
            'code' => trim($_POST['code'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'place_of_work' => trim($_POST['place_of_work'] ?? ''),
            'hours_per_week' => !empty($_POST['hours_per_week']) ? (float)$_POST['hours_per_week'] : null,
            'contract_type' => trim($_POST['contract_type'] ?? ''),
            'salary_range_min' => !empty($_POST['salary_range_min']) ? (float)$_POST['salary_range_min'] : null,
            'salary_range_max' => !empty($_POST['salary_range_max']) ? (float)$_POST['salary_range_max'] : null,
            'salary_currency' => trim($_POST['salary_currency'] ?? 'GBP'),
            'reporting_to' => trim($_POST['reporting_to'] ?? ''),
            'manager_user_id' => !empty($_POST['manager_user_id']) ? (int)$_POST['manager_user_id'] : null,
            'department' => trim($_POST['department'] ?? ''),
            'additional_requirements' => trim($_POST['additional_requirements'] ?? ''),
            'specific_attributes' => trim($_POST['specific_attributes'] ?? ''),
            'external_system' => trim($_POST['external_system'] ?? ''),
            'external_id' => trim($_POST['external_id'] ?? ''),
            'external_url' => trim($_POST['external_url'] ?? ''),
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1',
            'is_open' => isset($_POST['is_open']) && $_POST['is_open'] === '1',
            'created_by' => $userId
        ];
        
        // Remove empty strings
        foreach ($data as $key => $value) {
            if ($value === '' && in_array($key, ['code', 'location', 'place_of_work', 'contract_type', 
                                                   'reporting_to', 'department', 'additional_requirements', 
                                                   'specific_attributes', 'external_system', 'external_id', 'external_url'])) {
                $data[$key] = null;
            }
        }
        
        // Validate required fields
        if (empty($data['job_description_id'])) {
            $error = 'Job description is required.';
        } else {
            // If title is empty, use job description title
            if (empty($data['title'])) {
                $jd = JobDescription::findById($data['job_description_id'], $organisationId);
                if ($jd) {
                    $data['title'] = $jd['title'];
                }
            }
            
            $result = JobPost::create($data);
            if ($result) {
                $success = 'Job post created successfully.';
                header('Location: ' . url('job-posts/view.php?id=' . $result['id'] . '&success=' . urlencode($success)));
                exit;
            } else {
                $error = 'Failed to create job post.';
            }
        }
    }
}

$pageTitle = 'Create Job Post';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('job-posts/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Create Job Post</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 1rem; margin-bottom: 2rem; border-radius: 0;">
        <p style="margin: 0; color: #1e40af;">
            <strong><i class="fas fa-info-circle"></i> Job Post</strong><br>
            A job post is a specific position based on a job description template. It includes location, hours, manager, and other position-specific details.
        </p>
    </div>
    
    <form method="POST" action="">
        <?php echo CSRF::tokenField(); ?>
        
        <h2>Job Description Template</h2>
        <div class="form-group">
            <label for="job_description_id">Based on Job Description <span style="color: #dc2626;">*</span></label>
            <select id="job_description_id" name="job_description_id" required>
                <option value="">Select a job description...</option>
                <?php foreach ($jobDescriptions as $jd): ?>
                    <option value="<?php echo $jd['id']; ?>" <?php echo (isset($_POST['job_description_id']) && $_POST['job_description_id'] == $jd['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jd['title']); ?>
                        <?php if ($jd['code']): ?>
                            (<?php echo htmlspecialchars($jd['code']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Select the job description template this post is based on</small>
        </div>
        
        <div class="form-group">
            <label for="title">Post Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="Will auto-fill from job description">
            <small>Optional: Override the job description title for this specific post</small>
        </div>
        
        <div class="form-group">
            <label for="code">Post Code</label>
            <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" placeholder="e.g. POST-001">
            <small>Optional: Internal reference code for this post</small>
        </div>
        
        <h2 style="margin-top: 2rem;">Position Details</h2>
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" placeholder="e.g. Central Glasgow, Main Office">
            <small>Specific location for this position</small>
        </div>
        
        <div class="form-group">
            <label for="place_of_work">Place of Work</label>
            <input type="text" id="place_of_work" name="place_of_work" value="<?php echo htmlspecialchars($_POST['place_of_work'] ?? ''); ?>" placeholder="e.g. Office, Remote, Site A">
            <small>Specific place of work for this position</small>
        </div>
        
        <div class="form-group">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="reporting_to">Reports To</label>
            <input type="text" id="reporting_to" name="reporting_to" value="<?php echo htmlspecialchars($_POST['reporting_to'] ?? ''); ?>" placeholder="e.g. Head of Department">
        </div>
        
        <div class="form-group">
            <label for="manager_user_id">Manager</label>
            <select id="manager_user_id" name="manager_user_id">
                <option value="">None</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($_POST['manager_user_id']) && $_POST['manager_user_id'] == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Specific manager for this position</small>
        </div>
        
        <h2 style="margin-top: 2rem;">Employment Terms</h2>
        <div class="form-group">
            <label for="contract_type">Contract Type</label>
            <select id="contract_type" name="contract_type">
                <option value="">Select...</option>
                <option value="Permanent" <?php echo (isset($_POST['contract_type']) && $_POST['contract_type'] === 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                <option value="Temporary" <?php echo (isset($_POST['contract_type']) && $_POST['contract_type'] === 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                <option value="Contract" <?php echo (isset($_POST['contract_type']) && $_POST['contract_type'] === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                <option value="Part-time" <?php echo (isset($_POST['contract_type']) && $_POST['contract_type'] === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                <option value="Full-time" <?php echo (isset($_POST['contract_type']) && $_POST['contract_type'] === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="hours_per_week">Hours per Week</label>
            <input type="number" id="hours_per_week" name="hours_per_week" 
                   value="<?php echo htmlspecialchars($_POST['hours_per_week'] ?? ''); ?>" 
                   step="0.5" min="0" max="168" placeholder="e.g. 37.5">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="salary_range_min">Salary Range (Min)</label>
                <input type="number" id="salary_range_min" name="salary_range_min" 
                       value="<?php echo htmlspecialchars($_POST['salary_range_min'] ?? ''); ?>" 
                       step="0.01" min="0" placeholder="e.g. 25000">
            </div>
            
            <div class="form-group">
                <label for="salary_range_max">Salary Range (Max)</label>
                <input type="number" id="salary_range_max" name="salary_range_max" 
                       value="<?php echo htmlspecialchars($_POST['salary_range_max'] ?? ''); ?>" 
                       step="0.01" min="0" placeholder="e.g. 35000">
            </div>
            
            <div class="form-group">
                <label for="salary_currency">Currency</label>
                <select id="salary_currency" name="salary_currency">
                    <option value="GBP" <?php echo (isset($_POST['salary_currency']) && $_POST['salary_currency'] === 'GBP') ? 'selected' : 'selected'; ?>>GBP (£)</option>
                    <option value="USD" <?php echo (isset($_POST['salary_currency']) && $_POST['salary_currency'] === 'USD') ? 'selected' : ''; ?>>USD ($)</option>
                    <option value="EUR" <?php echo (isset($_POST['salary_currency']) && $_POST['salary_currency'] === 'EUR') ? 'selected' : ''; ?>>EUR (€)</option>
                </select>
            </div>
        </div>
        
        <h2 style="margin-top: 2rem;">Post-Specific Requirements</h2>
        <div class="form-group">
            <label for="additional_requirements">Additional Requirements</label>
            <textarea id="additional_requirements" name="additional_requirements" rows="4" placeholder="Any additional requirements specific to this post..."><?php echo htmlspecialchars($_POST['additional_requirements'] ?? ''); ?></textarea>
            <small>Requirements in addition to the job description (e.g. specific skills, certifications)</small>
        </div>
        
        <div class="form-group">
            <label for="specific_attributes">Specific Attributes</label>
            <textarea id="specific_attributes" name="specific_attributes" rows="4" placeholder="e.g. Must be female, Family liaison facilitation skills, Bilingual..."><?php echo htmlspecialchars($_POST['specific_attributes'] ?? ''); ?></textarea>
            <small>Specific attributes needed for this post (e.g. gender, language skills, specializations)</small>
        </div>
        
        <h2 style="margin-top: 2rem;">External System Integration (Optional)</h2>
        <div class="form-group">
            <label for="external_system">External System</label>
            <input type="text" id="external_system" name="external_system" 
                   value="<?php echo htmlspecialchars($_POST['external_system'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="external_id">External Reference ID</label>
            <input type="text" id="external_id" name="external_id" 
                   value="<?php echo htmlspecialchars($_POST['external_id'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="external_url">External URL</label>
            <input type="url" id="external_url" name="external_url" 
                   value="<?php echo htmlspecialchars($_POST['external_url'] ?? ''); ?>">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 2rem;">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo !isset($_POST['is_active']) || $_POST['is_active'] === '1' ? 'checked' : ''; ?>>
                    Active
                </label>
                <small>Inactive posts won't appear in selection lists</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_open" value="1" <?php echo !isset($_POST['is_open']) || $_POST['is_open'] === '1' ? 'checked' : ''; ?>>
                    Open for Applications
                </label>
                <small>Whether this post is currently accepting applications</small>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Job Post
            </button>
            <a href="<?php echo url('job-posts/index.php'); ?>" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
// Auto-fill title when job description is selected
document.getElementById('job_description_id').addEventListener('change', function() {
    const jobDescId = this.value;
    const titleField = document.getElementById('title');
    
    if (jobDescId) {
        fetch('<?php echo url('api/job-description.php?id='); ?>' + jobDescId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    if (!titleField.value) {
                        titleField.value = data.data.title;
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching job description:', error);
            });
    }
});
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

