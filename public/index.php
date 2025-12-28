<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Home';

// Require login
Auth::requireLogin();

$organisationId = Auth::getOrganisationId();
$userId = Auth::getUserId();

// Get current user data
$user = Auth::getUser();

// Handle profile creation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_profile']) && CSRF::validatePost()) {
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
}

// Get current user's person record
$person = Person::findByUserId($userId, $organisationId);

include INCLUDES_PATH . '/header.php';
?>

<div class="card">
    <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></h1>
    
    <?php if ($person): ?>
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
            <form method="POST" action="" style="margin-top: 1rem;">
                <?php echo CSRF::tokenField(); ?>
                <input type="hidden" name="create_profile" value="1">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create My Profile
                </button>
            </form>
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
    
    <?php if (RBAC::isOrganisationAdmin() || RBAC::isSuperAdmin()): ?>
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h2>Administration</h2>
            <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-primary">
                <i class="fas fa-users"></i> Manage Staff
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

