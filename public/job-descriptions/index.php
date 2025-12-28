<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Get all job descriptions
$jobDescriptions = JobDescription::getAllByOrganisation($organisationId, false);

$pageTitle = 'Job Descriptions';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="margin: 0;">Job Descriptions</h1>
        <a href="<?php echo url('job-descriptions/create.php'); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Job Description
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (empty($jobDescriptions)): ?>
        <div class="alert alert-info">
            No job descriptions found. <a href="<?php echo url('job-descriptions/create.php'); ?>">Create your first job description</a>.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left;">Title</th>
                        <th style="padding: 0.75rem; text-align: left;">Code</th>
                        <th style="padding: 0.75rem; text-align: left;">Department</th>
                        <th style="padding: 0.75rem; text-align: left;">Location</th>
                        <th style="padding: 0.75rem; text-align: left;">Hours/Week</th>
                        <th style="padding: 0.75rem; text-align: left;">Staff Count</th>
                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                        <th style="padding: 0.75rem; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobDescriptions as $jd): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;">
                                <a href="<?php echo url('job-descriptions/view.php?id=' . $jd['id']); ?>" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                    <?php echo htmlspecialchars($jd['title']); ?>
                                </a>
                            </td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($jd['code'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($jd['department'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($jd['location'] ?? '-'); ?></td>
                            <td style="padding: 0.75rem;"><?php echo $jd['hours_per_week'] ? htmlspecialchars($jd['hours_per_week']) : '-'; ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($jd['staff_count'] ?? 0); ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($jd['is_active']): ?>
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
                                <a href="<?php echo url('job-descriptions/view.php?id=' . $jd['id']); ?>" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?php echo url('job-descriptions/edit.php?id=' . $jd['id']); ?>" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
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

