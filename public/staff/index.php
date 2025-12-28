<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Search/filter
$search = $_GET['search'] ?? '';
$activeOnly = !isset($_GET['show_inactive']) || $_GET['show_inactive'] !== '1';

// Get staff
if (!empty($search)) {
    $staff = Person::searchStaff($organisationId, $search, $activeOnly);
    $totalCount = count($staff);
    $staff = array_slice($staff, $offset, $perPage);
} else {
    $staff = Person::getStaffByOrganisation($organisationId, $activeOnly, $perPage, $offset);
    $totalCount = Person::countStaff($organisationId, $activeOnly);
}

$totalPages = ceil($totalCount / $perPage);

$pageTitle = 'Manage Staff';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="margin: 0;">Manage Staff</h1>
        <a href="<?php echo url('staff/create.php'); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Staff Member
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Search and filters -->
    <form method="GET" action="" style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
        <div class="form-group" style="flex: 1; min-width: 200px;">
            <label for="search">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, employee reference...">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="show_inactive" value="1" <?php echo !$activeOnly ? 'checked' : ''; ?>>
                Show inactive
            </label>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-search"></i> Search
        </button>
        <a href="<?php echo url('staff/search-learning.php'); ?>" class="btn btn-secondary">
            <i class="fas fa-graduation-cap"></i> Search Learning Records
        </a>
        <?php if (!empty($search) || !$activeOnly): ?>
            <a href="<?php echo url('staff/index.php'); ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        <?php endif; ?>
    </form>
    
    <!-- Staff list -->
    <?php if (empty($staff)): ?>
        <div class="alert alert-info">
            <?php if (!empty($search)): ?>
                No staff members found matching your search.
            <?php else: ?>
                No staff members found. <a href="<?php echo url('staff/create.php'); ?>">Add your first staff member</a>.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left;">Name</th>
                        <th style="padding: 0.75rem; text-align: left;">Employee Reference</th>
                        <th style="padding: 0.75rem; text-align: left;">Email</th>
                        <th style="padding: 0.75rem; text-align: left;">Job Title</th>
                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                        <th style="padding: 0.75rem; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;">
                                <a href="<?php echo url('staff/view.php?id=' . $member['id']); ?>" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                </a>
                            </td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($member['employee_reference'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($member['user_email'] ?? $member['email'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($member['job_title'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($member['is_active']): ?>
                                    <span style="color: #10b981; font-weight: 500;">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">
                                        <i class="fas fa-times-circle"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <a href="<?php echo url('staff/view.php?id=' . $member['id']); ?>" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?php echo url('staff/edit.php?id=' . $member['id']); ?>" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 1rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !$activeOnly ? '&show_inactive=1' : ''; ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !$activeOnly ? '&show_inactive=1' : ''; ?>" class="btn btn-secondary">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Export -->
    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
        <a href="<?php echo url('api/export-staff.php?format=csv'); ?>" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export CSV
        </a>
        <a href="<?php echo url('api/export-staff.php?format=json'); ?>" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export JSON
        </a>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

