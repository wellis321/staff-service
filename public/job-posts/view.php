<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = $_GET['success'] ?? '';

// Get job post ID
$postId = $_GET['id'] ?? null;
if (!$postId) {
    header('Location: ' . url('job-posts/index.php?error=invalid_id'));
    exit;
}

// Get job post
$post = JobPost::findById($postId, $organisationId);
if (!$post || $post['organisation_id'] != $organisationId) {
    header('Location: ' . url('job-posts/index.php?error=not_found'));
    exit;
}

// Get staff members with this job post
$db = getDbConnection();
$stmt = $db->prepare("
    SELECT p.id, p.first_name, p.last_name, p.email, sp.job_title
    FROM people p
    JOIN staff_profiles sp ON p.id = sp.person_id
    WHERE sp.job_post_id = ? AND p.organisation_id = ? AND p.is_active = TRUE
    ORDER BY p.last_name, p.first_name
");
$stmt->execute([$postId, $organisationId]);
$staffMembers = $stmt->fetchAll();

$pageTitle = 'View Job Post';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('job-posts/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex: 1;">
            <h1><?php echo htmlspecialchars($post['title'] ?? $post['job_description_title']); ?></h1>
            <?php if ($post['code']): ?>
                <p style="color: #6b7280; margin-top: 0.5rem;">Code: <?php echo htmlspecialchars($post['code']); ?></p>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?php echo url('job-posts/history.php?id=' . $postId); ?>" class="btn btn-secondary">
                <i class="fas fa-history"></i> View History
            </a>
            <a href="<?php echo url('job-posts/edit.php?id=' . $postId); ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div>
            <h2>Job Description Template</h2>
            <?php if ($post['job_description_title']): ?>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 0; margin-bottom: 2rem;">
                    <p style="margin-bottom: 0.5rem;">
                        <strong>Based on:</strong> 
                        <a href="<?php echo url('job-descriptions/view.php?id=' . $post['job_description_id']); ?>" style="color: #2563eb;">
                            <?php echo htmlspecialchars($post['job_description_title']); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($post['job_description_text']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Description</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($post['job_description_text'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($post['job_description_responsibilities']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Key Responsibilities</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($post['job_description_responsibilities'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($post['job_description_requirements']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Requirements</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($post['job_description_requirements'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($post['additional_requirements']): ?>
                <div style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <h3>Additional Requirements (Post-Specific)</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($post['additional_requirements'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($post['specific_attributes']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Specific Attributes Required</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($post['specific_attributes'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h2>Position Details</h2>
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0; margin-bottom: 2rem;">
                <?php if ($post['location']): ?>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($post['location']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['place_of_work']): ?>
                    <p><strong>Place of Work:</strong> <?php echo htmlspecialchars($post['place_of_work']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['department']): ?>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($post['department']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['contract_type']): ?>
                    <p><strong>Contract Type:</strong> <?php echo htmlspecialchars($post['contract_type']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['hours_per_week']): ?>
                    <p><strong>Hours per Week:</strong> <?php echo htmlspecialchars($post['hours_per_week']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['reporting_to']): ?>
                    <p><strong>Reports To:</strong> <?php echo htmlspecialchars($post['reporting_to']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['manager_first_name']): ?>
                    <p><strong>Manager:</strong> <?php echo htmlspecialchars($post['manager_first_name'] . ' ' . $post['manager_last_name']); ?></p>
                <?php endif; ?>
                
                <?php if ($post['salary_range_min'] || $post['salary_range_max']): ?>
                    <p><strong>Salary Range:</strong> 
                        <?php 
                        if ($post['salary_range_min'] && $post['salary_range_max']) {
                            echo htmlspecialchars($post['salary_currency'] . ' ' . number_format($post['salary_range_min'], 2) . ' - ' . number_format($post['salary_range_max'], 2));
                        } elseif ($post['salary_range_min']) {
                            echo htmlspecialchars($post['salary_currency'] . ' ' . number_format($post['salary_range_min'], 2) . '+');
                        } elseif ($post['salary_range_max']) {
                            echo 'Up to ' . htmlspecialchars($post['salary_currency'] . ' ' . number_format($post['salary_range_max'], 2));
                        }
                        ?>
                    </p>
                <?php endif; ?>
                
                <p><strong>Status:</strong> 
                    <?php if ($post['is_active']): ?>
                        <span style="color: #10b981;">Active</span>
                        <?php if (!$post['is_open']): ?>
                            <span style="color: #f59e0b;">(Closed for applications)</span>
                        <?php else: ?>
                            <span style="color: #10b981;">(Open for applications)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #6b7280;">Inactive</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($post['external_system'] || $post['external_url']): ?>
                <h3>External System</h3>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 0; margin-bottom: 2rem;">
                    <?php if ($post['external_system']): ?>
                        <p><strong>System:</strong> <?php echo htmlspecialchars($post['external_system']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($post['external_id']): ?>
                        <p><strong>Reference ID:</strong> <?php echo htmlspecialchars($post['external_id']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($post['external_url']): ?>
                        <p>
                            <a href="<?php echo htmlspecialchars($post['external_url']); ?>" target="_blank" style="color: #2563eb;">
                                <i class="fas fa-external-link-alt"></i> View in External System
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($staffMembers)): ?>
                <h3>Staff in This Position</h3>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 0;">
                    <p style="margin-bottom: 0.5rem;"><strong><?php echo count($staffMembers); ?> staff member(s)</strong></p>
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($staffMembers as $staff): ?>
                            <li>
                                <a href="<?php echo url('staff/view.php?id=' . $staff['id']); ?>" style="color: #2563eb;">
                                    <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

