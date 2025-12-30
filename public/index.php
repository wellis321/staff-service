<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Home';

// Require login
Auth::requireLogin();

try {
    $organisationId = Auth::getOrganisationId();
    $userId = Auth::getUserId();
    
    // Get current user data
    $user = Auth::getUser();
    
    if (!$user) {
        throw new Exception("Unable to retrieve user data. Please try logging in again.");
    }
} catch (Exception $e) {
    error_log("Index.php error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    // Redirect to login if there's an error
    header('Location: ' . url('login.php') . '?error=' . urlencode('Session error. Please log in again.'));
    exit;
}

// Handle profile creation request
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_profile']) && CSRF::validatePost()) {
    if (!$organisationId) {
        $error = 'Organisation not found. Please contact your administrator.';
    } else {
        try {
            // Check if profile already exists
            $person = Person::findByUserId($userId, $organisationId);
            
            if (!$person) {
                // Create a basic staff profile for the user
                $staffData = [
                    'organisation_id' => $organisationId,
                    'user_id' => $userId,
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'is_active' => true
                ];
                
                $person = Person::createStaff($staffData);
                
                if ($person) {
                    header('Location: ' . url('index.php') . '?profile_created=1');
                    exit;
                } else {
                    $error = 'Failed to create profile. Please try again or contact your administrator.';
                }
            }
        } catch (Exception $e) {
            error_log("Error creating profile: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            $error = 'An error occurred while creating your profile. Please try again.';
        }
    }
}

// Get current user's person record (only if organisationId is set)
$person = null;
if ($organisationId) {
    try {
        $person = Person::findByUserId($userId, $organisationId);
    } catch (Exception $e) {
        error_log("Error finding person record: " . $e->getMessage());
        // Continue without person record - user can create profile
    }
}

include INCLUDES_PATH . '/header.php';
?>

<div class="card">
    <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></h1>
    
    <?php 
    $isSuperAdmin = RBAC::isSuperAdmin();
    
    if ($isSuperAdmin): 
        // Super admins don't need staff profiles
    ?>
        <div style="margin-top: 2rem;">
            <div class="alert alert-info">
                <p><strong>Super Administrator Account</strong></p>
                <p>You have super administrator privileges. You can manage users, organisations, and system settings.</p>
                <p>Super administrators don't require staff profiles as you have system-wide access.</p>
            </div>
        </div>
    <?php elseif ($person): ?>
        <div style="margin-top: 2rem;">
            <h2>Your Profile</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></p>
            <?php if ($person['employee_reference']): ?>
                <p><strong>Employee Reference:</strong> <?php echo htmlspecialchars($person['employee_reference']); ?></p>
            <?php endif; ?>
            <?php if ($person['job_title']): ?>
                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($person['job_title']); ?></p>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem;">
                <a href="<?php echo url('profile.php'); ?>" class="btn btn-primary">
                    <i class="fas fa-user"></i> View/Edit Profile
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info" style="margin-top: 2rem;">
            <p><strong>No profile found.</strong> Create your staff profile to get started.</p>
            <?php if ($organisationId): ?>
                <form method="POST" action="" style="margin-top: 1rem;">
                    <?php echo CSRF::tokenField(); ?>
                    <input type="hidden" name="create_profile" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create My Profile
                    </button>
                </form>
            <?php else: ?>
                <p style="margin-top: 1rem; color: #dc2626;">
                    <strong>Organisation not found.</strong> Please contact your administrator to assign you to an organisation.
                </p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error" style="margin-top: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['profile_created']) && $_GET['profile_created'] == '1'): ?>
        <div class="alert alert-success" style="margin-top: 2rem;">
            <p><strong>Profile created successfully!</strong> Your staff profile has been created.</p>
        </div>
    <?php endif; ?>
    
    <?php 
    try {
        if (RBAC::isOrganisationAdmin() || RBAC::isSuperAdmin()): 
    ?>
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h2>Administration</h2>
            <?php if ($organisationId && (RBAC::isOrganisationAdmin() || RBAC::isSuperAdmin())): ?>
                <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-primary">
                    <i class="fas fa-users"></i> Manage Staff
                </a>
            <?php endif; ?>
            <?php if (RBAC::isSuperAdmin()): ?>
                <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-primary" style="margin-left: 0.5rem;">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a>
                <a href="<?php echo url('admin/organisation-requests.php'); ?>" class="btn btn-primary" style="margin-left: 0.5rem;">
                    <i class="fas fa-building"></i> Organisation Requests
                </a>
            <?php endif; ?>
            <?php if (RBAC::isOrganisationAdmin() || RBAC::isSuperAdmin()): ?>
                <a href="<?php echo url('admin/api-keys.php'); ?>" class="btn btn-primary" style="margin-left: 0.5rem;">
                    <i class="fas fa-key"></i> API Keys
                </a>
            <?php endif; ?>
        </div>
    <?php 
        endif;
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
    }
    ?>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

