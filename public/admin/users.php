<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireSuperAdmin();

$pageTitle = 'Manage Users';
$error = '';
$success = '';

$db = getDbConnection();

// Handle role assignment/removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validatePost()) {
    $action = $_POST['action'] ?? '';
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $organisationId = isset($_POST['organisation_id']) ? (int)$_POST['organisation_id'] : 0;
    
    if ($action === 'assign_admin' && $userId > 0) {
        // Verify user belongs to the organisation
        $stmt = $db->prepare("SELECT organisation_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'User not found.';
        } elseif ($organisationId > 0 && $user['organisation_id'] != $organisationId) {
            $error = 'User does not belong to the specified organisation.';
        } else {
            $result = RBAC::assignRole($userId, 'organisation_admin');
            if ($result['success']) {
                $success = 'Organisation admin role assigned successfully.';
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($action === 'remove_admin' && $userId > 0) {
        // Verify user belongs to the organisation
        $stmt = $db->prepare("SELECT organisation_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'User not found.';
        } elseif ($organisationId > 0 && $user['organisation_id'] != $organisationId) {
            $error = 'User does not belong to the specified organisation.';
        } else {
            $result = RBAC::removeRole($userId, 'organisation_admin');
            if ($result['success']) {
                $success = 'Organisation admin role removed successfully.';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get selected organisation or all organisations
$selectedOrganisationId = isset($_GET['organisation_id']) ? (int)$_GET['organisation_id'] : null;

// Get all organisations
$stmt = $db->query("SELECT id, name FROM organisations ORDER BY name");
$organisations = $stmt->fetchAll();

// Get users
if ($selectedOrganisationId) {
    $users = RBAC::getUsersByOrganisation($selectedOrganisationId);
} else {
    // Get all users across all organisations
    $stmt = $db->query("
        SELECT u.*, 
               o.name as organisation_name,
               GROUP_CONCAT(r.name SEPARATOR ', ') as roles,
               CASE WHEN EXISTS (
                   SELECT 1 FROM user_roles ur2 
                   JOIN roles r2 ON ur2.role_id = r2.id 
                   WHERE ur2.user_id = u.id AND r2.name = 'organisation_admin'
               ) THEN 1 ELSE 0 END as is_organisation_admin
        FROM users u
        LEFT JOIN organisations o ON u.organisation_id = o.id
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        GROUP BY u.id
        ORDER BY o.name, is_organisation_admin DESC, u.last_name, u.first_name
    ");
    $users = $stmt->fetchAll();
}

include INCLUDES_PATH . '/header.php';
?>

<div class="card">
    <h1>Manage Users</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <p>As a super administrator, you can assign or remove organisation admin privileges for users.</p>
    
    <!-- Organisation Filter -->
    <div style="margin-bottom: 2rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="flex: 1;">
                <label for="organisation_id">Filter by Organisation</label>
                <select id="organisation_id" name="organisation_id" onchange="this.form.submit()">
                    <option value="">All Organisations</option>
                    <?php foreach ($organisations as $org): ?>
                        <option value="<?php echo $org['id']; ?>" <?php echo $selectedOrganisationId == $org['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($org['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    
    <?php if (empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.75rem; text-align: left;">Name</th>
                    <th style="padding: 0.75rem; text-align: left;">Email</th>
                    <th style="padding: 0.75rem; text-align: left;">Organisation</th>
                    <th style="padding: 0.75rem; text-align: left;">Roles</th>
                    <th style="padding: 0.75rem; text-align: left;">Status</th>
                    <th style="padding: 0.75rem; text-align: left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 0.75rem;">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php echo htmlspecialchars($user['organisation_name'] ?? 'N/A'); ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php 
                            $roles = $user['roles'] ?? '';
                            if (empty($roles)) {
                                echo '<span style="color: #6b7280;">No roles</span>';
                            } else {
                                $roleArray = explode(', ', $roles);
                                foreach ($roleArray as $role) {
                                    $badgeColor = $role === 'superadmin' ? '#dc2626' : ($role === 'organisation_admin' ? '#3b82f6' : '#6b7280');
                                    echo '<span style="background: ' . $badgeColor . '; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem; margin-right: 0.25rem; display: inline-block;">' . htmlspecialchars($role) . '</span>';
                                }
                            }
                            ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php echo $user['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>'; ?>
                            <?php if ($user['email_verified']): ?>
                                <br><small style="color: #6b7280;">Email verified</small>
                            <?php else: ?>
                                <br><small style="color: #f59e0b;">Email not verified</small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php 
                            $userRoles = RBAC::getUserRolesById($user['id']);
                            $isOrgAdmin = in_array('organisation_admin', $userRoles);
                            $isSuperAdmin = in_array('superadmin', $userRoles);
                            
                            // Don't allow modifying superadmin roles
                            if ($isSuperAdmin) {
                                echo '<span style="color: #6b7280;">Super Admin</span>';
                            } else {
                                if ($isOrgAdmin) {
                                    // Show remove admin button
                                    ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <?php echo CSRF::tokenField(); ?>
                                        <input type="hidden" name="action" value="remove_admin">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="organisation_id" value="<?php echo $user['organisation_id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.875rem;" 
                                                onclick="return confirm('Are you sure you want to remove organisation admin privileges from <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>?');">
                                            <i class="fas fa-user-minus"></i> Remove Admin
                                        </button>
                                    </form>
                                    <?php
                                } else {
                                    // Show assign admin button
                                    ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <?php echo CSRF::tokenField(); ?>
                                        <input type="hidden" name="action" value="assign_admin">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="organisation_id" value="<?php echo $user['organisation_id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <i class="fas fa-user-shield"></i> Make Admin
                                        </button>
                                    </form>
                                    <?php
                                }
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div style="margin-top: 2rem; padding: 1rem; background-color: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 0;">
        <h3 style="margin-top: 0; color: #1e40af;">About Organisation Admin Role</h3>
        <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: #1e40af;">
            <li>Organisation admins have full access to manage staff, job descriptions, and job posts within their organisation</li>
            <li>They can create, edit, and delete staff records</li>
            <li>They can manage organisational units and staff assignments</li>
            <li>They cannot access other organisations' data</li>
            <li>Super admins can access all organisations and manage organisation admins</li>
        </ul>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>


