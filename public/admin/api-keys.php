<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();

// Allow both organisation admins and super admins
if (!RBAC::isOrganisationAdmin() && !RBAC::isSuperAdmin()) {
    header('Location: ' . url('index.php'));
    exit;
}

$organisationId = Auth::getOrganisationId();
$userId = Auth::getUserId();
$isSuperAdmin = RBAC::isSuperAdmin();
$error = '';
$success = '';
$newApiKey = null;
$newApiKeyName = null;

$db = getDbConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $keyName = trim($_POST['key_name'] ?? '');
            $targetOrganisationId = $isSuperAdmin && isset($_POST['organisation_id']) ? (int)$_POST['organisation_id'] : $organisationId;
            
            if (empty($keyName)) {
                $error = 'API key name is required.';
            } elseif (!$targetOrganisationId && !$isSuperAdmin) {
                $error = 'Organisation not found. Please contact your administrator.';
            } else {
                // Generate API key
                $apiKey = bin2hex(random_bytes(32)); // 64-character hex string
                $apiKeyHash = hash('sha256', $apiKey);
                
                try {
                    $stmt = $db->prepare("
                        INSERT INTO api_keys (
                            user_id, organisation_id, name, api_key_hash, is_active
                        ) VALUES (?, ?, ?, ?, TRUE)
                    ");
                    $stmt->execute([$userId, $targetOrganisationId, $keyName, $apiKeyHash]);
                    
                    $newApiKey = $apiKey;
                    $newApiKeyName = $keyName;
                    $success = 'API key created successfully. Please copy it now - it won\'t be shown again!';
                } catch (Exception $e) {
                    $error = 'Failed to create API key: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle') {
            $keyId = (int)($_POST['key_id'] ?? 0);
            $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
            
            if ($keyId > 0) {
                // Super admins can toggle any key, organisation admins only their own
                if ($isSuperAdmin) {
                    $stmt = $db->prepare("
                        UPDATE api_keys 
                        SET is_active = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$isActive ? 1 : 0, $keyId]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE api_keys 
                        SET is_active = ? 
                        WHERE id = ? AND organisation_id = ?
                    ");
                    $stmt->execute([$isActive ? 1 : 0, $keyId, $organisationId]);
                }
                
                if ($stmt->rowCount() > 0) {
                    $success = 'API key ' . ($isActive ? 'activated' : 'deactivated') . ' successfully.';
                } else {
                    $error = 'API key not found or access denied.';
                }
            } else {
                $error = 'Invalid API key ID.';
            }
        } elseif ($action === 'delete') {
            $keyId = (int)($_POST['key_id'] ?? 0);
            
            if ($keyId > 0) {
                // Super admins can delete any key, organisation admins only their own
                if ($isSuperAdmin) {
                    $stmt = $db->prepare("
                        DELETE FROM api_keys 
                        WHERE id = ?
                    ");
                    $stmt->execute([$keyId]);
                } else {
                    $stmt = $db->prepare("
                        DELETE FROM api_keys 
                        WHERE id = ? AND organisation_id = ?
                    ");
                    $stmt->execute([$keyId, $organisationId]);
                }
                
                if ($stmt->rowCount() > 0) {
                    $success = 'API key deleted successfully.';
                } else {
                    $error = 'API key not found or access denied.';
                }
            } else {
                $error = 'Invalid API key ID.';
            }
        }
    }
}

// Get all API keys (for super admin, show all; for org admin, show only their organisation's)
if ($isSuperAdmin) {
    $stmt = $db->prepare("
        SELECT ak.id, ak.name, ak.is_active, ak.created_at, ak.last_used_at, ak.expires_at,
               u.email as user_email, o.name as organisation_name, o.domain as organisation_domain
        FROM api_keys ak
        JOIN users u ON ak.user_id = u.id
        LEFT JOIN organisations o ON ak.organisation_id = o.id
        ORDER BY ak.created_at DESC
    ");
    $stmt->execute();
} else {
    $stmt = $db->prepare("
        SELECT ak.id, ak.name, ak.is_active, ak.created_at, ak.last_used_at, ak.expires_at,
               u.email as user_email
        FROM api_keys ak
        JOIN users u ON ak.user_id = u.id
        WHERE ak.organisation_id = ?
        ORDER BY ak.created_at DESC
    ");
    $stmt->execute([$organisationId]);
}
$apiKeys = $stmt->fetchAll();

$pageTitle = 'API Keys';
include INCLUDES_PATH . '/header.php';
?>

<div class="card">
    <h1>API Keys Management</h1>
    
    <p>Create and manage API keys for integrating external systems (like Digital ID, recruitment systems, etc.) with Staff Service.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($newApiKey): ?>
        <div class="alert alert-warning" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1.5rem; margin: 2rem 0;">
            <h3 style="margin-top: 0; color: #92400e;">
                <i class="fas fa-exclamation-triangle"></i> Important: Save Your API Key Now
            </h3>
            <p style="color: #92400e; margin-bottom: 1rem;">
                This API key will <strong>only be shown once</strong>. Copy it now and store it securely. You won't be able to see it again.
            </p>
            <div style="background: white; padding: 1rem; border-radius: 0.25rem; border: 2px solid #f59e0b; margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #92400e;">API Key Name:</label>
                <p style="margin: 0; font-size: 1.1rem; color: #1f2937;"><?php echo htmlspecialchars($newApiKeyName); ?></p>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 0.25rem; border: 2px solid #f59e0b;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #92400e;">API Key:</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="text" 
                           id="new-api-key" 
                           value="<?php echo htmlspecialchars($newApiKey); ?>" 
                           readonly 
                           style="flex: 1; padding: 0.75rem; font-family: monospace; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.25rem; background: #f9fafb;">
                    <button onclick="copyApiKey()" class="btn btn-primary" style="white-space: nowrap;">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <div style="margin-top: 1rem; padding: 1rem; background-color: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0;">
                <h4 style="margin-top: 0; color: #1e40af; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> How to Use This API Key
                </h4>
                <p style="margin: 0.5rem 0 0; color: #1e40af; font-size: 0.875rem;">
                    <strong>For Digital ID Integration:</strong> Add this key to your Digital ID application's <code>.env</code> file:
                </p>
                <pre style="margin: 0.5rem 0 0; padding: 0.75rem; background: white; border-radius: 0.25rem; overflow-x: auto; font-size: 0.875rem; color: #1f2937;"><code>USE_STAFF_SERVICE=true
STAFF_SERVICE_URL=http://localhost:8000
STAFF_SERVICE_API_KEY=<?php echo htmlspecialchars($newApiKey); ?>
STAFF_SYNC_INTERVAL=3600</code></pre>
                <p style="margin: 0.5rem 0 0; color: #1e40af; font-size: 0.875rem;">
                    <strong>For API Requests:</strong> Use it in API requests with the header:
                    <code style="display: block; margin-top: 0.5rem; padding: 0.5rem; background: white; border-radius: 0.25rem; font-size: 0.875rem;">Authorization: Bearer <?php echo htmlspecialchars($newApiKey); ?></code>
                </p>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 2rem;">
        <h2>Create New API Key</h2>
        <form method="POST" action="" style="max-width: 600px;">
            <?php echo CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="create">
            
            <?php if ($isSuperAdmin): ?>
                <div class="form-group">
                    <label for="organisation_id">Organisation</label>
                    <select id="organisation_id" name="organisation_id" required>
                        <option value="">Select an organisation...</option>
                        <?php
                        $orgStmt = $db->query("SELECT id, name, domain FROM organisations ORDER BY name");
                        $organisations = $orgStmt->fetchAll();
                        foreach ($organisations as $org):
                        ?>
                            <option value="<?php echo $org['id']; ?>">
                                <?php echo htmlspecialchars($org['name'] . ' (' . $org['domain'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Select the organisation this API key will be associated with.</small>
                </div>
            <?php else: ?>
                <?php if (!$organisationId): ?>
                    <div class="alert alert-error">
                        Organisation not found. Please contact your administrator.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="key_name">API Key Name</label>
                <input type="text" 
                       id="key_name" 
                       name="key_name" 
                       required 
                       placeholder="e.g., Digital ID Integration, Recruitment System"
                       maxlength="255">
                <small>Give this API key a descriptive name to identify what it's used for.</small>
            </div>
            
            <button type="submit" class="btn btn-primary" <?php echo (!$organisationId && !$isSuperAdmin) ? 'disabled' : ''; ?>>
                <i class="fas fa-key"></i> Create API Key
            </button>
        </form>
    </div>
    
    <div style="margin-top: 3rem;">
        <h2>Existing API Keys</h2>
        
        <?php if (empty($apiKeys)): ?>
            <p style="color: #6b7280;">No API keys have been created yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                            <th style="padding: 0.75rem; text-align: left;">Name</th>
                            <?php if ($isSuperAdmin): ?>
                                <th style="padding: 0.75rem; text-align: left;">Organisation</th>
                            <?php endif; ?>
                            <th style="padding: 0.75rem; text-align: left;">Created By</th>
                            <th style="padding: 0.75rem; text-align: left;">Status</th>
                            <th style="padding: 0.75rem; text-align: left;">Created</th>
                            <th style="padding: 0.75rem; text-align: left;">Last Used</th>
                            <th style="padding: 0.75rem; text-align: left;">Expires</th>
                            <th style="padding: 0.75rem; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 0.75rem;">
                                    <strong><?php echo htmlspecialchars($key['name']); ?></strong>
                                </td>
                                <?php if ($isSuperAdmin): ?>
                                    <td style="padding: 0.75rem; color: #6b7280;">
                                        <?php 
                                        if (!empty($key['organisation_name'])) {
                                            echo htmlspecialchars($key['organisation_name']);
                                            if (!empty($key['organisation_domain'])) {
                                                echo ' <small style="color: #9ca3af;">(' . htmlspecialchars($key['organisation_domain']) . ')</small>';
                                            }
                                        } else {
                                            echo '<span style="color: #9ca3af;">No organisation</span>';
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                                <td style="padding: 0.75rem; color: #6b7280;">
                                    <?php echo htmlspecialchars($key['user_email']); ?>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <?php if ($key['is_active']): ?>
                                        <span style="color: #059669; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #dc2626; font-weight: 600;">
                                            <i class="fas fa-times-circle"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem; color: #6b7280; font-size: 0.875rem;">
                                    <?php echo date('d/m/Y H:i', strtotime($key['created_at'])); ?>
                                </td>
                                <td style="padding: 0.75rem; color: #6b7280; font-size: 0.875rem;">
                                    <?php if ($key['last_used_at']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($key['last_used_at'])); ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem; color: #6b7280; font-size: 0.875rem;">
                                    <?php if ($key['expires_at']): ?>
                                        <?php 
                                        $expiresAt = strtotime($key['expires_at']);
                                        $now = time();
                                        if ($expiresAt < $now): ?>
                                            <span style="color: #dc2626; font-weight: 600;">Expired</span>
                                        <?php else: ?>
                                            <?php echo date('d/m/Y', $expiresAt); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <?php
                                        // Only show actions if super admin or if key belongs to user's organisation
                                        $canManage = $isSuperAdmin;
                                        if (!$canManage && $organisationId) {
                                            // For org admins, check if key belongs to their org
                                            $keyOrgStmt = $db->prepare("SELECT organisation_id FROM api_keys WHERE id = ?");
                                            $keyOrgStmt->execute([$key['id']]);
                                            $keyOrg = $keyOrgStmt->fetch();
                                            $canManage = ($keyOrg && $keyOrg['organisation_id'] == $organisationId);
                                        }
                                        
                                        if ($canManage):
                                        ?>
                                        <form method="POST" action="" style="margin: 0;">
                                            <?php echo CSRF::tokenField(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $key['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit" 
                                                    class="btn <?php echo $key['is_active'] ? 'btn-secondary' : 'btn-primary'; ?>" 
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                                    onclick="return confirm('Are you sure you want to <?php echo $key['is_active'] ? 'deactivate' : 'activate'; ?> this API key?');">
                                                <i class="fas fa-<?php echo $key['is_active'] ? 'ban' : 'check'; ?>"></i> 
                                                <?php echo $key['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="" style="margin: 0;">
                                            <?php echo CSRF::tokenField(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-danger" 
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                                    onclick="return confirm('Are you sure you want to delete this API key? This action cannot be undone.');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 0.875rem;">No access</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 2rem; padding: 1rem; background-color: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0;">
        <h3 style="margin-top: 0; color: #1e40af;">
            <i class="fas fa-info-circle"></i> About API Keys
        </h3>
        <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: #1e40af;">
            <li>API keys allow external systems to authenticate with Staff Service</li>
            <li>Each key is associated with your organisation and user account</li>
            <li>Keys are shown only once when created - save them securely</li>
            <li>You can deactivate keys without deleting them (useful for temporary access)</li>
            <li>Deleting a key permanently removes it and cannot be undone</li>
            <li>Keys can be used in the <code>Authorization: Bearer</code> header for API requests</li>
        </ul>
    </div>
</div>

<script>
function copyApiKey() {
    const apiKeyInput = document.getElementById('new-api-key');
    apiKeyInput.select();
    apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show feedback
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.style.backgroundColor = '#059669';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    } catch (err) {
        alert('Failed to copy. Please select and copy manually.');
    }
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>

