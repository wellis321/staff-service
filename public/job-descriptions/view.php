<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = $_GET['success'] ?? '';

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


$pageTitle = 'View Job Description';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('job-descriptions/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex: 1;">
            <h1><?php echo htmlspecialchars($jd['title']); ?></h1>
            <?php if ($jd['code']): ?>
                <p style="color: #6b7280; margin-top: 0.5rem;">Code: <?php echo htmlspecialchars($jd['code']); ?></p>
            <?php endif; ?>
        </div>
        <a href="<?php echo url('job-descriptions/edit.php?id=' . $jdId); ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 1rem; margin-bottom: 2rem; border-radius: 0;">
        <p style="margin: 0; color: #1e40af;">
            <strong><i class="fas fa-info-circle"></i> Job Description Template</strong><br>
            This is a generic job description template. To create a specific position, create a <a href="<?php echo url('job-posts/create.php?job_description_id=' . $jdId); ?>" style="color: #2563eb; font-weight: 600;">Job Post</a> based on this description.
        </p>
    </div>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div>
            <h2>Role Description</h2>
            
            <?php if ($jd['description']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Description</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($jd['description'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($jd['responsibilities']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Key Responsibilities</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($jd['responsibilities'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($jd['requirements']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3>Requirements</h3>
                    <div style="white-space: pre-wrap; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($jd['requirements'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h2>Template Information</h2>
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0; margin-bottom: 2rem;">
                <p><strong>Status:</strong> 
                    <?php if ($jd['is_active']): ?>
                        <span style="color: #10b981;">Active</span>
                    <?php else: ?>
                        <span style="color: #6b7280;">Inactive</span>
                    <?php endif; ?>
                </p>
                
                <?php if ($jd['code']): ?>
                    <p><strong>Code:</strong> <?php echo htmlspecialchars($jd['code']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php
            // Get job posts based on this description
            $jobPosts = JobPost::getByJobDescription($jdId, $organisationId);
            ?>
            
            <?php if (!empty($jobPosts)): ?>
                <h3>Job Posts Based on This Description</h3>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 0; margin-bottom: 2rem;">
                    <p style="margin-bottom: 0.5rem;"><strong><?php echo count($jobPosts); ?> post(s)</strong></p>
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($jobPosts as $post): ?>
                            <li>
                                <a href="<?php echo url('job-posts/view.php?id=' . $post['id']); ?>" style="color: #2563eb;">
                                    <?php echo htmlspecialchars($post['title'] ?? $jd['title']); ?>
                                    <?php if ($post['location']): ?>
                                        - <?php echo htmlspecialchars($post['location']); ?>
                                    <?php endif; ?>
                                    <?php if ($post['contract_type']): ?>
                                        (<?php echo htmlspecialchars($post['contract_type']); ?>)
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($jd['external_system'] || $jd['external_url']): ?>
                <h3>External System</h3>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 0; margin-bottom: 2rem;">
                    <?php if ($jd['external_system']): ?>
                        <p><strong>System:</strong> <?php echo htmlspecialchars($jd['external_system']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($jd['external_id']): ?>
                        <p><strong>Reference ID:</strong> <?php echo htmlspecialchars($jd['external_id']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($jd['external_url']): ?>
                        <p>
                            <a href="<?php echo htmlspecialchars($jd['external_url']); ?>" target="_blank" style="color: #2563eb;">
                                <i class="fas fa-external-link-alt"></i> View in External System
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

