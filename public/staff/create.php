<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = '';

// Get all users for linking (including those who already have profiles)
$db = getDbConnection();
$stmt = $db->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email,
           CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END as has_profile
    FROM users u
    LEFT JOIN people p ON p.user_id = u.id AND p.organisation_id = ?
    WHERE u.organisation_id = ? AND u.is_active = TRUE
    ORDER BY u.last_name, u.first_name
");
$stmt->execute([$organisationId, $organisationId]);
$availableUsers = $stmt->fetchAll();

// Get all staff for line manager selection
$staffForManager = Person::getStaffByOrganisation($organisationId, true);

// Get organisational units
$allUnits = OrganisationalUnits::getAllByOrganisation($organisationId);

// Get active job descriptions for selection
$jobDescriptions = JobDescription::getAllByOrganisation($organisationId, true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $data = [
            'organisation_id' => $organisationId,
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'employee_reference' => trim($_POST['employee_reference'] ?? ''),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'user_id' => !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null,
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1',
        ];
        
        // Staff-specific fields
        $data['job_title'] = trim($_POST['job_title'] ?? '');
        $data['job_description_id'] = !empty($_POST['job_description_id']) ? (int)$_POST['job_description_id'] : null;
        $data['employment_start_date'] = !empty($_POST['employment_start_date']) ? $_POST['employment_start_date'] : null;
        $data['employment_end_date'] = !empty($_POST['employment_end_date']) ? $_POST['employment_end_date'] : null;
        $data['line_manager_id'] = !empty($_POST['line_manager_id']) ? (int)$_POST['line_manager_id'] : null;
        $data['emergency_contact_name'] = trim($_POST['emergency_contact_name'] ?? '');
        $data['emergency_contact_phone'] = trim($_POST['emergency_contact_phone'] ?? '');
        $data['notes'] = trim($_POST['notes'] ?? '');
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            $error = 'First name and last name are required.';
        } else {
            // Remove empty strings but keep nulls for optional fields
            foreach ($data as $key => $value) {
                if ($value === '' && in_array($key, ['email', 'phone', 'employee_reference', 'date_of_birth', 'job_title', 'employment_start_date', 'employment_end_date', 'line_manager_id', 'emergency_contact_name', 'emergency_contact_phone', 'notes'])) {
                    $data[$key] = null;
                }
            }
            
            $result = Person::createStaff($data);
            if ($result) {
                $personId = $result['id'];
                
                // Assign to organisational units if provided
                if (isset($_POST['organisational_units']) && is_array($_POST['organisational_units'])) {
                    foreach ($_POST['organisational_units'] as $unitData) {
                        $unitId = (int)($unitData['id'] ?? 0);
                        $roleInUnit = trim($unitData['role'] ?? 'member');
                        $isPrimary = isset($unitData['primary']) && $unitData['primary'] === '1';
                        
                        if ($unitId > 0) {
                            Person::assignToOrganisationalUnit($personId, $unitId, $roleInUnit, $isPrimary);
                        }
                    }
                }
                
                $success = 'Staff member created successfully.';
                header('Location: ' . url('staff/view.php?id=' . $personId . '&success=' . urlencode($success)));
                exit;
            } else {
                $error = 'Failed to create staff member.';
            }
        }
    }
}

$pageTitle = 'Create Staff Member';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('staff/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Create Staff Member</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo CSRF::tokenField(); ?>
        
        <h2>Personal Information</h2>
        <div class="form-group">
            <label for="first_name">First Name <span style="color: #dc2626;">*</span></label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name <span style="color: #dc2626;">*</span></label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="user_id">Link to User Account (Optional)</label>
            <select id="user_id" name="user_id">
                <option value="">None - Create without user account link</option>
                <?php foreach ($availableUsers as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($_POST['user_id']) && $_POST['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                        <?php 
                        $display = htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')');
                        if ($user['has_profile']) {
                            $display .= ' [Already has profile]';
                        }
                        echo $display;
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>If linking to a user account, name and email will be pre-filled from the user account. Note: Users who already have profiles can still be linked (this will update the existing profile).</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="employee_reference">Employee Reference</label>
            <input type="text" id="employee_reference" name="employee_reference" value="<?php echo htmlspecialchars($_POST['employee_reference'] ?? ''); ?>">
            <small>Optional: Employee reference number. Must be unique within your organisation.</small>
        </div>
        
        <h2 style="margin-top: 2rem;">Employment Information</h2>
        <div class="form-group">
            <label for="job_description_id">Job Description</label>
            <select id="job_description_id" name="job_description_id">
                <option value="">None - Enter job title manually</option>
                <?php foreach ($jobDescriptions as $jd): ?>
                    <option value="<?php echo $jd['id']; ?>" <?php echo (isset($_POST['job_description_id']) && $_POST['job_description_id'] == $jd['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jd['title']); ?>
                        <?php if ($jd['code']): ?>
                            (<?php echo htmlspecialchars($jd['code']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Select a job description from the library, or leave blank to enter job title manually</small>
        </div>
        
        <div class="form-group">
            <label for="job_title">Job Title</label>
            <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>">
            <small>Will be auto-filled if a job description is selected above</small>
        </div>
        
        <div class="form-group">
            <label for="employment_start_date">Employment Start Date</label>
            <input type="date" id="employment_start_date" name="employment_start_date" value="<?php echo htmlspecialchars($_POST['employment_start_date'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="employment_end_date">Employment End Date</label>
            <input type="date" id="employment_end_date" name="employment_end_date" value="<?php echo htmlspecialchars($_POST['employment_end_date'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="line_manager_id">Line Manager</label>
            <select id="line_manager_id" name="line_manager_id">
                <option value="">None</option>
                <?php foreach ($staffForManager as $staff): ?>
                    <option value="<?php echo $staff['id']; ?>" <?php echo (isset($_POST['line_manager_id']) && $_POST['line_manager_id'] == $staff['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <h3 style="margin-top: 1.5rem;">Emergency Contact</h3>
        <div class="form-group">
            <label for="emergency_contact_name">Emergency Contact Name</label>
            <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="emergency_contact_phone">Emergency Contact Phone</label>
            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo !isset($_POST['is_active']) || $_POST['is_active'] === '1' ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <h2 style="margin-top: 2rem;">Organisational Units (Optional)</h2>
        <p>You can assign organisational units after creating the staff member, or select them below.</p>
        
        <?php if (!empty($allUnits)): ?>
            <div style="display: grid; gap: 1rem; margin-top: 1rem;">
                <?php foreach ($allUnits as $unit): ?>
                    <div style="border: 1px solid #e5e7eb; padding: 1rem; border-radius: 0;">
                        <label style="display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" name="organisational_units[<?php echo $unit['id']; ?>][id]" value="<?php echo $unit['id']; ?>">
                            <div style="flex: 1;">
                                <strong><?php echo htmlspecialchars($unit['name']); ?></strong>
                                <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                    <input type="text" name="organisational_units[<?php echo $unit['id']; ?>][role]" placeholder="Role (e.g. member)" value="member" style="flex: 1;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="checkbox" name="organisational_units[<?php echo $unit['id']; ?>][primary]" value="1">
                                        Primary
                                    </label>
                                </div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #6b7280;">No organisational units available. Create organisational units first to assign staff to them.</p>
        <?php endif; ?>
        
        <div style="margin-top: 2rem;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Create Staff Member
        </button>
        <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-secondary">
            Cancel
        </a>
    </div>
</form>
</div>

<script>
// Auto-fill job title when job description is selected
document.getElementById('job_description_id').addEventListener('change', function() {
    const jobDescId = this.value;
    const jobTitleField = document.getElementById('job_title');
    
    if (jobDescId) {
        // Fetch job description details
        fetch('<?php echo url('api/job-description.php?id='); ?>' + jobDescId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    if (!jobTitleField.value || confirm('Replace current job title with "' + data.data.title + '"?')) {
                        jobTitleField.value = data.data.title;
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

