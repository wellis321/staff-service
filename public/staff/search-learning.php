<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$error = '';
$success = '';

// Search parameters
$employeeReference = $_GET['employee_reference'] ?? '';
$recordType = $_GET['record_type'] ?? '';
$status = $_GET['status'] ?? '';

$results = [];
$person = null;

if (!empty($employeeReference)) {
    // Get person by employee reference
    $person = Person::findByEmployeeReference($organisationId, $employeeReference);
    
    if ($person) {
        // Build filters
        $filters = [];
        if (!empty($recordType)) {
            $filters['record_type'] = $recordType;
        }
        if (!empty($status)) {
            $filters['status'] = $status;
        }
        
        // Get learning records
        $results = StaffLearningRecord::getByEmployeeReference($organisationId, $employeeReference, $filters);
    } else {
        $error = 'No person found with employee reference: ' . htmlspecialchars($employeeReference);
    }
}

$pageTitle = 'Search Learning Records by Employee Reference';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('staff/index.php'); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Search Learning Records</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Search Form -->
    <form method="GET" action="" style="margin-bottom: 2rem; padding: 1.5rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0;">
        <h2 style="margin-top: 0; margin-bottom: 1rem;">Search by Employee Reference</h2>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group">
                <label for="employee_reference">Employee Reference *</label>
                <input type="text" id="employee_reference" name="employee_reference" 
                       value="<?php echo htmlspecialchars($employeeReference); ?>" 
                       placeholder="Enter employee reference" required>
            </div>
            
            <div class="form-group">
                <label for="record_type">Record Type</label>
                <select id="record_type" name="record_type">
                    <option value="">All Types</option>
                    <option value="qualification" <?php echo $recordType === 'qualification' ? 'selected' : ''; ?>>Qualification</option>
                    <option value="course" <?php echo $recordType === 'course' ? 'selected' : ''; ?>>Course</option>
                    <option value="training" <?php echo $recordType === 'training' ? 'selected' : ''; ?>>Training</option>
                    <option value="certification" <?php echo $recordType === 'certification' ? 'selected' : ''; ?>>Certification</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
        
        <?php if (!empty($employeeReference)): ?>
            <div style="margin-top: 1rem;">
                <a href="<?php echo url('staff/search-learning.php'); ?>" class="btn btn-secondary" style="font-size: 0.875rem;">
                    <i class="fas fa-times"></i> Clear Search
                </a>
            </div>
        <?php endif; ?>
    </form>
    
    <!-- Results -->
    <?php if (!empty($employeeReference)): ?>
        <?php if ($person): ?>
            <div style="margin-bottom: 2rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0;">
                <h3 style="margin-top: 0;">Person Details</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>Name:</strong><br>
                        <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>
                    </div>
                    <div>
                        <strong>Employee Reference:</strong><br>
                        <?php echo htmlspecialchars($person['employee_reference']); ?>
                    </div>
                    <div>
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($person['email'] ?? '-'); ?>
                    </div>
                    <?php if ($person['job_title']): ?>
                        <div>
                            <strong>Job Title:</strong><br>
                            <?php echo htmlspecialchars($person['job_title']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="<?php echo url('staff/view.php?id=' . $person['id']); ?>" class="btn btn-secondary" style="font-size: 0.875rem;">
                        <i class="fas fa-user"></i> View Full Profile
                    </a>
                    <a href="<?php echo url('staff/link-records.php?id=' . $person['id']); ?>" class="btn btn-secondary" style="font-size: 0.875rem;">
                        <i class="fas fa-link"></i> Link Previous Records
                    </a>
                </div>
            </div>
            
            <?php if (!empty($results)): ?>
                <h2>Learning Records (<?php echo count($results); ?>)</h2>
                
                <!-- Group by record type -->
                <?php 
                $grouped = [];
                foreach ($results as $record) {
                    $type = $record['record_type'] ?? 'other';
                    if (!isset($grouped[$type])) {
                        $grouped[$type] = [];
                    }
                    $grouped[$type][] = $record;
                }
                ?>
                
                <?php foreach ($grouped as $type => $records): ?>
                    <h3 style="margin-top: 2rem; margin-bottom: 1rem; text-transform: capitalize;">
                        <?php echo ucfirst($type); ?>s (<?php echo count($records); ?>)
                    </h3>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                <th style="padding: 0.75rem; text-align: left;">Title</th>
                                <th style="padding: 0.75rem; text-align: left;">Provider</th>
                                <th style="padding: 0.75rem; text-align: left;">Completed</th>
                                <th style="padding: 0.75rem; text-align: left;">Expires</th>
                                <th style="padding: 0.75rem; text-align: left;">Status</th>
                                <?php if ($type === 'qualification'): ?>
                                    <th style="padding: 0.75rem; text-align: left;">Level</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem;">
                                        <strong><?php echo htmlspecialchars($record['title']); ?></strong>
                                        <?php if ($record['description']): ?>
                                            <br><small style="color: #6b7280;"><?php echo htmlspecialchars(substr($record['description'], 0, 100)); ?><?php echo strlen($record['description']) > 100 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                        <?php if ($record['is_mandatory']): ?>
                                            <br><span style="color: #dc2626; font-size: 0.75rem;"><i class="fas fa-exclamation-circle"></i> Mandatory</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($record['provider'] ?? '-'); ?></td>
                                    <td style="padding: 0.75rem;">
                                        <?php echo $record['completion_date'] ? date('d/m/Y', strtotime($record['completion_date'])) : '-'; ?>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <?php if ($record['expiry_date']): ?>
                                            <?php 
                                            $expiryDate = strtotime($record['expiry_date']);
                                            $daysUntilExpiry = floor(($expiryDate - time()) / 86400);
                                            $expiryClass = $daysUntilExpiry < 0 ? 'color: #dc2626;' : ($daysUntilExpiry < 90 ? 'color: #f59e0b;' : '');
                                            ?>
                                            <span style="<?php echo $expiryClass; ?>">
                                                <?php echo date('d/m/Y', $expiryDate); ?>
                                                <?php if ($daysUntilExpiry < 0): ?>
                                                    <br><small>(Expired)</small>
                                                <?php elseif ($daysUntilExpiry < 90): ?>
                                                    <br><small>(<?php echo $daysUntilExpiry; ?> days)</small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <?php 
                                        $statusColors = [
                                            'completed' => '#10b981',
                                            'in_progress' => '#3b82f6',
                                            'expired' => '#dc2626',
                                            'pending' => '#f59e0b'
                                        ];
                                        $statusColor = $statusColors[$record['status']] ?? '#6b7280';
                                        ?>
                                        <span style="color: <?php echo $statusColor; ?>; font-weight: 500;">
                                            <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                        </span>
                                    </td>
                                    <?php if ($type === 'qualification'): ?>
                                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($record['qualification_level'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No learning records found for this employee reference<?php echo !empty($recordType) || !empty($status) ? ' with the selected filters' : ''; ?>.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            Enter an employee reference above to search for learning records.
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

