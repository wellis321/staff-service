<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireSuperAdmin();

$pageTitle = 'Organisation Access Requests';
$userId = Auth::getUserId();
$db = getDbConnection();

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validatePost()) {
    $action = $_POST['action'] ?? '';
    $requestId = intval($_POST['request_id'] ?? 0);
    
    if ($action === 'approve' && $requestId > 0) {
        // Get request details
        $stmt = $db->prepare("SELECT * FROM organisation_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if ($request && $request['status'] === 'pending') {
            try {
                $db->beginTransaction();
                
                // Check if organisation already exists
                $checkStmt = $db->prepare("SELECT id FROM organisations WHERE domain = ?");
                $checkStmt->execute([$request['organisation_domain']]);
                $existingOrg = $checkStmt->fetch();
                
                if ($existingOrg) {
                    // Update existing organisation
                    $updateStmt = $db->prepare("UPDATE organisations SET seats_allocated = ? WHERE id = ?");
                    $updateStmt->execute([$request['seats_requested'], $existingOrg['id']]);
                    $organisationId = $existingOrg['id'];
                } else {
                    // Create new organisation
                    $insertStmt = $db->prepare("INSERT INTO organisations (name, domain, seats_allocated) VALUES (?, ?, ?)");
                    $insertStmt->execute([
                        $request['organisation_name'],
                        $request['organisation_domain'],
                        $request['seats_requested']
                    ]);
                    $organisationId = $db->lastInsertId();
                }
                
                // Update request status
                $updateRequestStmt = $db->prepare("
                    UPDATE organisation_requests 
                    SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                    WHERE id = ?
                ");
                $reviewNotes = $_POST['review_notes'] ?? 'Approved and organisation created.';
                $updateRequestStmt->execute([$userId, $reviewNotes, $requestId]);
                
                $db->commit();
                
                // Send email to requester
                $to = $request['contact_email'];
                $subject = 'Organisation Access Approved - Staff Service';
                $message = "Dear " . htmlspecialchars($request['contact_name']) . ",\n\n";
                $message .= "Your organisation access request has been approved!\n\n";
                $message .= "Organisation: " . htmlspecialchars($request['organisation_name']) . "\n";
                $message .= "Domain: " . htmlspecialchars($request['organisation_domain']) . "\n";
                $message .= "Seats Allocated: " . $request['seats_requested'] . "\n\n";
                $message .= "You can now register user accounts at: " . url('register.php') . "\n\n";
                $message .= "When registering, use an email address with the domain: " . htmlspecialchars($request['organisation_domain']) . "\n\n";
                $message .= "If you have any questions, please contact us at " . CONTACT_EMAIL . "\n\n";
                $message .= "Best regards,\nStaff Service Team";
                
                $headers = "From: " . CONTACT_EMAIL . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                @mail($to, $subject, $message, $headers);
                
                $success = 'Organisation request approved and organisation created successfully.';
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Error approving organisation request: " . $e->getMessage());
                $error = 'Failed to approve request: ' . $e->getMessage();
            }
        } else {
            $error = 'Request not found or already processed.';
        }
    } elseif ($action === 'reject' && $requestId > 0) {
        $reviewNotes = trim($_POST['review_notes'] ?? '');
        if (empty($reviewNotes)) {
            $error = 'Please provide a reason for rejection.';
        } else {
            try {
                $stmt = $db->prepare("
                    UPDATE organisation_requests 
                    SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                    WHERE id = ? AND status = 'pending'
                ");
                $stmt->execute([$userId, $reviewNotes, $requestId]);
                
                if ($stmt->rowCount() > 0) {
                    // Get request for email
                    $getStmt = $db->prepare("SELECT * FROM organisation_requests WHERE id = ?");
                    $getStmt->execute([$requestId]);
                    $request = $getStmt->fetch();
                    
                    if ($request) {
                        // Send email to requester
                        $to = $request['contact_email'];
                        $subject = 'Organisation Access Request - Update';
                        $message = "Dear " . htmlspecialchars($request['contact_name']) . ",\n\n";
                        $message .= "Thank you for your interest in the Staff Service.\n\n";
                        $message .= "Unfortunately, we are unable to approve your organisation access request at this time.\n\n";
                        $message .= "Reason: " . htmlspecialchars($reviewNotes) . "\n\n";
                        $message .= "If you have any questions or would like to discuss this further, please contact us at " . CONTACT_EMAIL . "\n\n";
                        $message .= "Best regards,\nStaff Service Team";
                        
                        $headers = "From: " . CONTACT_EMAIL . "\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        @mail($to, $subject, $message, $headers);
                    }
                    
                    $success = 'Request rejected and requester notified.';
                } else {
                    $error = 'Request not found or already processed.';
                }
            } catch (Exception $e) {
                error_log("Error rejecting organisation request: " . $e->getMessage());
                $error = 'Failed to reject request: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'mark_contacted' && $requestId > 0) {
        try {
            $stmt = $db->prepare("
                UPDATE organisation_requests 
                SET status = 'contacted', reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$userId, $requestId]);
            $success = 'Request marked as contacted.';
        } catch (Exception $e) {
            error_log("Error updating request status: " . $e->getMessage());
            $error = 'Failed to update request status.';
        }
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'pending', 'approved', 'rejected', 'contacted']) ? $statusFilter : 'all';

// Get requests
$query = "SELECT r.*, u.email as reviewed_by_email, u.first_name as reviewer_first_name, u.last_name as reviewer_last_name
          FROM organisation_requests r
          LEFT JOIN users u ON r.reviewed_by = u.id
          WHERE 1=1";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Count by status
$countStmt = $db->query("SELECT status, COUNT(*) as count FROM organisation_requests GROUP BY status");
$statusCounts = [];
while ($row = $countStmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.requests-filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.filter-badge.active {
    background: #2563eb;
    color: white;
}

.filter-badge:not(.active) {
    background: #f3f4f6;
    color: #374151;
}

.filter-badge:not(.active):hover {
    background: #e5e7eb;
}

.filter-badge .count {
    background: rgba(255,255,255,0.2);
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.filter-badge:not(.active) .count {
    background: #d1d5db;
    color: #374151;
}

.requests-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.requests-table th {
    background: #f9fafb;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.requests-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    color: #4b5563;
}

.requests-table tr:hover {
    background: #f9fafb;
}

.requests-table tr:last-child td {
    border-bottom: none;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.approved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.contacted {
    background: #dbeafe;
    color: #1e40af;
}

.request-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.request-details {
    background: #f9fafb;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.request-details h3 {
    margin-top: 0;
    color: #1f2937;
}

.request-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.request-details-item {
    display: flex;
    flex-direction: column;
}

.request-details-item label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.request-details-item .value {
    color: #4b5563;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content h2 {
    margin-top: 0;
    color: #1f2937;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    min-height: 100px;
    font-family: inherit;
    box-sizing: border-box;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    justify-content: flex-end;
}
</style>

<div class="container">
    <h1>Organisation Access Requests</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="requests-filters">
        <a href="?status=all" class="filter-badge <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            All <span class="count"><?php echo array_sum($statusCounts); ?></span>
        </a>
        <a href="?status=pending" class="filter-badge <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
            Pending <span class="count"><?php echo $statusCounts['pending'] ?? 0; ?></span>
        </a>
        <a href="?status=contacted" class="filter-badge <?php echo $statusFilter === 'contacted' ? 'active' : ''; ?>">
            Contacted <span class="count"><?php echo $statusCounts['contacted'] ?? 0; ?></span>
        </a>
        <a href="?status=approved" class="filter-badge <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
            Approved <span class="count"><?php echo $statusCounts['approved'] ?? 0; ?></span>
        </a>
        <a href="?status=rejected" class="filter-badge <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
            Rejected <span class="count"><?php echo $statusCounts['rejected'] ?? 0; ?></span>
        </a>
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="card">
            <p>No requests found.</p>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Organisation</th>
                        <th>Domain</th>
                        <th>Contact</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>#<?php echo $request['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['organisation_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($request['organisation_domain']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($request['contact_name']); ?></div>
                                <small style="color: #6b7280;"><?php echo htmlspecialchars($request['contact_email']); ?></small>
                            </td>
                            <td><?php echo $request['seats_requested']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                            </td>
                            <td>
                                <div class="request-actions">
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <button onclick="showApproveModal(<?php echo $request['id']; ?>)" class="btn btn-primary btn-sm">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button onclick="showRejectModal(<?php echo $request['id']; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                        <form method="POST" action="" style="display: inline;">
                                            <?php echo CSRF::tokenField(); ?>
                                            <input type="hidden" name="action" value="mark_contacted">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-envelope"></i> Mark Contacted
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button onclick="showDetails(<?php echo $request['id']; ?>)" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <h2>Approve Organisation Request</h2>
        <p>This will create a new organisation (or update an existing one) and allow users with this domain to register.</p>
        <form method="POST" action="" id="approveForm">
            <?php echo CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="request_id" id="approve_request_id">
            <div class="form-group">
                <label for="approve_notes">Review Notes (optional)</label>
                <textarea id="approve_notes" name="review_notes" placeholder="Add any notes about this approval..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeModal('approveModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Approve & Create Organisation</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h2>Reject Organisation Request</h2>
        <p>Please provide a reason for rejection. This will be sent to the requester.</p>
        <form method="POST" action="" id="rejectForm">
            <?php echo CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="request_id" id="reject_request_id">
            <div class="form-group">
                <label for="reject_notes">Reason for Rejection <span style="color: #dc2626;">*</span></label>
                <textarea id="reject_notes" name="review_notes" required placeholder="Please explain why this request cannot be approved..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeModal('rejectModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <h2>Request Details</h2>
        <div id="detailsContent"></div>
        <div class="modal-actions">
            <button type="button" onclick="closeModal('detailsModal')" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<script>
const requestsData = <?php echo json_encode($requests); ?>;

function showApproveModal(requestId) {
    document.getElementById('approve_request_id').value = requestId;
    document.getElementById('approveModal').classList.add('active');
}

function showRejectModal(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    document.getElementById('rejectModal').classList.add('active');
}

function showDetails(requestId) {
    const request = requestsData.find(r => r.id == requestId);
    if (!request) return;
    
    const content = `
        <div class="request-details">
            <div class="request-details-grid">
                <div class="request-details-item">
                    <label>Organisation Name</label>
                    <div class="value">${escapeHtml(request.organisation_name)}</div>
                </div>
                <div class="request-details-item">
                    <label>Domain</label>
                    <div class="value">${escapeHtml(request.organisation_domain)}</div>
                </div>
                <div class="request-details-item">
                    <label>Seats Requested</label>
                    <div class="value">${request.seats_requested}</div>
                </div>
                <div class="request-details-item">
                    <label>Status</label>
                    <div class="value"><span class="status-badge ${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span></div>
                </div>
                <div class="request-details-item">
                    <label>Contact Name</label>
                    <div class="value">${escapeHtml(request.contact_name)}</div>
                </div>
                <div class="request-details-item">
                    <label>Contact Email</label>
                    <div class="value"><a href="mailto:${escapeHtml(request.contact_email)}">${escapeHtml(request.contact_email)}</a></div>
                </div>
                ${request.contact_phone ? `
                <div class="request-details-item">
                    <label>Contact Phone</label>
                    <div class="value">${escapeHtml(request.contact_phone)}</div>
                </div>
                ` : ''}
                <div class="request-details-item">
                    <label>Submitted</label>
                    <div class="value">${new Date(request.created_at).toLocaleString()}</div>
                </div>
                ${request.reviewed_at ? `
                <div class="request-details-item">
                    <label>Reviewed</label>
                    <div class="value">${new Date(request.reviewed_at).toLocaleString()}</div>
                </div>
                <div class="request-details-item">
                    <label>Reviewed By</label>
                    <div class="value">${request.reviewer_first_name ? escapeHtml(request.reviewer_first_name + ' ' + request.reviewer_last_name) : 'N/A'}</div>
                </div>
                ` : ''}
            </div>
            ${request.description ? `
            <div style="margin-top: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">About the Organisation</label>
                <div style="background: white; padding: 1rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; white-space: pre-wrap;">${escapeHtml(request.description)}</div>
            </div>
            ` : ''}
            ${request.use_case ? `
            <div style="margin-top: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Intended Use Case</label>
                <div style="background: white; padding: 1rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; white-space: pre-wrap;">${escapeHtml(request.use_case)}</div>
            </div>
            ` : ''}
            ${request.review_notes ? `
            <div style="margin-top: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Review Notes</label>
                <div style="background: white; padding: 1rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; white-space: pre-wrap;">${escapeHtml(request.review_notes)}</div>
            </div>
            ` : ''}
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                <div style="font-size: 0.875rem; color: #6b7280;">
                    <div>IP Address: ${request.ip_address || 'Unknown'}</div>
                    <div>Submitted from: ${request.submitted_from || 'Unknown'}</div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detailsContent').innerHTML = content;
    document.getElementById('detailsModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>


