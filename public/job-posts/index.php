<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Get all job posts
$jobPosts = JobPost::getAllByOrganisation($organisationId, false);

$pageTitle = 'Job Posts';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="margin: 0;">Job Posts</h1>
        <a href="<?php echo url('job-posts/create.php'); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Job Post
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <p style="color: #6b7280; margin-bottom: 2rem;">
        Job posts are specific positions based on job description templates. They include location, hours, manager, and other position-specific details.
    </p>
    
    <?php if (empty($jobPosts)): ?>
        <div class="alert alert-info">
            No job posts found. <a href="<?php echo url('job-posts/create.php'); ?>">Create your first job post</a>.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left;">Title</th>
                        <th style="padding: 0.75rem; text-align: left;">Job Description</th>
                        <th style="padding: 0.75rem; text-align: left;">Location</th>
                        <th style="padding: 0.75rem; text-align: left;">Hours</th>
                        <th style="padding: 0.75rem; text-align: left;">Contract</th>
                        <th style="padding: 0.75rem; text-align: left;">Staff Count</th>
                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                        <th style="padding: 0.75rem; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobPosts as $post): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;">
                                <a href="<?php echo url('job-posts/view.php?id=' . $post['id']); ?>" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                    <?php echo htmlspecialchars($post['title'] ?? $post['job_description_title']); ?>
                                </a>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($post['job_description_title']): ?>
                                    <a href="<?php echo url('job-descriptions/view.php?id=' . $post['job_description_id']); ?>" style="color: #6b7280;">
                                        <?php echo htmlspecialchars($post['job_description_title']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($post['location'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo $post['hours_per_week'] ? htmlspecialchars($post['hours_per_week']) : '-'; ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($post['contract_type'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($post['staff_count'] ?? 0); ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($post['is_active']): ?>
                                    <span style="color: #10b981; font-weight: 500;">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                    <?php if (!$post['is_open']): ?>
                                        <br><small style="color: #6b7280;">(Closed)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #6b7280;">
                                        <i class="fas fa-times-circle"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <a href="<?php echo url('job-posts/view.php?id=' . $post['id']); ?>" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?php echo url('job-posts/edit.php?id=' . $post['id']); ?>" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

