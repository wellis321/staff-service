<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';

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

// Get history
$history = JobPost::getHistory($postId, 100);

$pageTitle = 'Job Post History';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('job-posts/view.php?id=' . $postId); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex: 1;">
            <h1>History: <?php echo htmlspecialchars($post['title'] ?? $post['job_description_title']); ?></h1>
            <p style="color: #6b7280; margin-top: 0.5rem;">Track changes to this job post over time</p>
        </div>
    </div>
    
    <?php if (empty($history)): ?>
        <div class="alert alert-info">
            No history available for this job post.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left;">Date</th>
                        <th style="padding: 0.75rem; text-align: left;">Changed By</th>
                        <th style="padding: 0.75rem; text-align: left;">Type</th>
                        <th style="padding: 0.75rem; text-align: left;">Salary Range</th>
                        <th style="padding: 0.75rem; text-align: left;">Hours/Week</th>
                        <th style="padding: 0.75rem; text-align: left;">Contract</th>
                        <th style="padding: 0.75rem; text-align: left;">Location</th>
                        <th style="padding: 0.75rem; text-align: left;">Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;">
                                <?php echo date('d/m/Y H:i', strtotime($entry['created_at'])); ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($entry['changed_by_first_name']): ?>
                                    <?php echo htmlspecialchars($entry['changed_by_first_name'] . ' ' . $entry['changed_by_last_name']); ?>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">System</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <span style="text-transform: capitalize;"><?php echo htmlspecialchars($entry['change_type']); ?></span>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($entry['salary_range_min'] || $entry['salary_range_max']): ?>
                                    <?php 
                                    if ($entry['salary_range_min'] && $entry['salary_range_max']) {
                                        echo htmlspecialchars($entry['salary_currency'] . ' ' . number_format($entry['salary_range_min'], 0) . ' - ' . number_format($entry['salary_range_max'], 0));
                                    } elseif ($entry['salary_range_min']) {
                                        echo htmlspecialchars($entry['salary_currency'] . ' ' . number_format($entry['salary_range_min'], 0) . '+');
                                    } elseif ($entry['salary_range_max']) {
                                        echo 'Up to ' . htmlspecialchars($entry['salary_currency'] . ' ' . number_format($entry['salary_range_max'], 0));
                                    }
                                    ?>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php echo $entry['hours_per_week'] ? htmlspecialchars($entry['hours_per_week']) : '<span style="color: #9ca3af;">-</span>'; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php echo $entry['contract_type'] ? htmlspecialchars($entry['contract_type']) : '<span style="color: #9ca3af;">-</span>'; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php echo $entry['location'] ? htmlspecialchars($entry['location']) : '<span style="color: #9ca3af;">-</span>'; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php 
                                if ($entry['changed_fields']) {
                                    $changedFields = json_decode($entry['changed_fields'], true);
                                    if (is_array($changedFields) && !empty($changedFields)) {
                                        echo '<small style="color: #6b7280;">';
                                        echo htmlspecialchars(implode(', ', array_map(function($f) {
                                            return str_replace('_', ' ', ucwords($f, '_'));
                                        }, $changedFields)));
                                        echo '</small>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h3>Salary History Chart</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                Showing salary range changes over time for this position:
            </p>
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0;">
                <?php
                $salaryHistory = array_filter($history, function($h) {
                    return $h['salary_range_min'] || $h['salary_range_max'];
                });
                
                if (empty($salaryHistory)) {
                    echo '<p style="color: #6b7280;">No salary information in history.</p>';
                } else {
                    echo '<table style="width: 100%;">';
                    echo '<tr><th style="text-align: left; padding: 0.5rem;">Date</th><th style="text-align: left; padding: 0.5rem;">Salary Range</th></tr>';
                    foreach (array_reverse($salaryHistory) as $entry) {
                        echo '<tr>';
                        echo '<td style="padding: 0.5rem;">' . date('d/m/Y', strtotime($entry['created_at'])) . '</td>';
                        echo '<td style="padding: 0.5rem;">';
                        if ($entry['salary_range_min'] && $entry['salary_range_max']) {
                            echo htmlspecialchars($entry['salary_currency'] . ' ' . number_format($entry['salary_range_min'], 0) . ' - ' . number_format($entry['salary_range_max'], 0));
                        } elseif ($entry['salary_range_min']) {
                            echo htmlspecialchars($entry['salary_currency'] . ' ' . number_format($entry['salary_range_min'], 0) . '+');
                        } elseif ($entry['salary_range_max']) {
                            echo 'Up to ' . htmlspecialchars($entry['salary_currency'] . ' ' . number_format($entry['salary_range_max'], 0));
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

