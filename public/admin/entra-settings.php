<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
// Allow both organisation admins and super admins
if (!RBAC::isOrganisationAdmin() && !RBAC::isSuperAdmin()) {
    header('Location: ' . url('index.php?error=access_denied'));
    exit;
}

// For super admins, allow selecting organisation; for org admins, use their organisation
$isSuperAdmin = RBAC::isSuperAdmin();
$selectedOrganisationId = isset($_GET['organisation_id']) ? (int)$_GET['organisation_id'] : null;

if ($isSuperAdmin) {
    // Super admin can select organisation
    $organisationId = $selectedOrganisationId ?: Auth::getOrganisationId();
} else {
    // Organisation admin uses their own organisation
    $organisationId = Auth::getOrganisationId();
}

$error = '';
$success = '';

require_once SRC_PATH . '/classes/EntraIntegration.php';

$config = EntraIntegration::getConfig($organisationId);

$syncResult = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'enable') {
            $tenantId = $_POST['tenant_id'] ?? '';
            $clientId = $_POST['client_id'] ?? '';
            
            if (empty($tenantId) || empty($clientId)) {
                $error = 'Tenant ID and Client ID are required.';
            } else {
                $result = EntraIntegration::enable($organisationId, $tenantId, $clientId);
                if ($result['success']) {
                    $success = 'Entra integration enabled successfully.';
                    $config = EntraIntegration::getConfig($organisationId);
                } else {
                    $error = 'Failed to enable Entra integration.';
                }
            }
        } elseif ($action === 'disable') {
            $result = EntraIntegration::disable($organisationId);
            if ($result['success']) {
                $success = 'Entra integration disabled.';
                $config = EntraIntegration::getConfig($organisationId);
            } else {
                $error = 'Failed to disable Entra integration.';
            }
        } elseif ($action === 'sync_users') {
            // For super admins, get organisation from POST if provided
            if ($isSuperAdmin && isset($_POST['organisation_id'])) {
                $organisationId = (int)$_POST['organisation_id'];
            }
            
            if (!$organisationId) {
                $error = 'Organisation ID is required.';
            } else {
                $syncResult = EntraIntegration::syncUsersFromEntra($organisationId);
                
                if ($syncResult['success']) {
                    $successMessage = "Sync complete! {$syncResult['people_created']} people created";
                    if ($syncResult['people_updated'] > 0) {
                        $successMessage .= ", {$syncResult['people_updated']} updated";
                    }
                    if ($syncResult['people_skipped'] > 0) {
                        $successMessage .= ", {$syncResult['people_skipped']} skipped";
                    }
                    $success = $successMessage;
                } else {
                    $error = 'Sync failed: ' . ($syncResult['message'] ?? 'Unknown error');
                }
            }
        }
    }
}

$pageTitle = 'Entra/365 Settings';
include INCLUDES_PATH . '/header.php';

// Get organisation name if we have an ID
$organisationName = null;
if ($organisationId) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT name FROM organisations WHERE id = ?");
    $stmt->execute([$organisationId]);
    $org = $stmt->fetch();
    $organisationName = $org ? $org['name'] : null;
}

// Get all organisations for super admin dropdown
$allOrganisations = [];
if ($isSuperAdmin) {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, name FROM organisations ORDER BY name");
    $allOrganisations = $stmt->fetchAll();
}
?>

<div class="card">
    <h1>Microsoft Entra/365 Integration Settings</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($isSuperAdmin && !empty($allOrganisations)): ?>
        <!-- Organisation selector for super admins -->
        <div style="margin-bottom: 2rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0;">
            <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label for="organisation_id">Select Organisation</label>
                    <select id="organisation_id" name="organisation_id" onchange="this.form.submit()" required>
                        <option value="">-- Select Organisation --</option>
                        <?php foreach ($allOrganisations as $org): ?>
                            <option value="<?php echo $org['id']; ?>" <?php echo $organisationId == $org['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <p style="margin: 0.5rem 0 0; color: #6b7280; font-size: 0.875rem;">
                <i class="fas fa-info-circle"></i> As a super administrator, you can configure Entra integration for any organisation when requested by that organisation.
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (!$organisationId): ?>
        <?php if ($isSuperAdmin): ?>
            <div class="alert alert-info">
                Please select an organisation above to configure Entra integration.
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                Organisation ID not found. Please contact support.
            </div>
        <?php endif; ?>
    <?php else: ?>
    
    <p>Configure Microsoft Entra ID (Azure AD) integration for staff synchronisation. Staff Service acts as the central hub for Entra integration, syncing staff data to Microsoft 365.</p>
    
    <?php if ($organisationName): ?>
        <p><strong>Organisation:</strong> <?php echo htmlspecialchars($organisationName); ?></p>
    <?php endif; ?>
    
    <?php if (!$isSuperAdmin): ?>
        <div class="alert alert-info" style="background-color: #eff6ff; border-left: 4px solid #3b82f6; margin-top: 1rem;">
            <strong><i class="fas fa-info-circle"></i> Organisation Admin Responsibility</strong>
            <p style="margin: 0.5rem 0 0; color: #1e40af;">
                As an organisation administrator, you are responsible for configuring Entra integration for your organisation. 
                This includes setting up the connection to Microsoft 365 and managing staff synchronisation. 
                If you need assistance, you can contact a super administrator.
            </p>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; margin-top: 1rem;">
            <strong><i class="fas fa-exclamation-triangle"></i> Super Administrator Access</strong>
            <p style="margin: 0.5rem 0 0; color: #92400e;">
                You are configuring Entra integration on behalf of this organisation. 
                Organisation administrators are normally responsible for this configuration, but you can assist when requested.
            </p>
        </div>
    <?php endif; ?>
    
    <?php if ($config && $config['entra_enabled']): ?>
        <div class="alert alert-success">
            <strong>Entra integration is enabled</strong>
            <p>Tenant ID: <?php echo htmlspecialchars($config['entra_tenant_id']); ?></p>
            <p>Client ID: <?php echo htmlspecialchars($config['entra_client_id']); ?></p>
        </div>
        
        <!-- User Synchronisation Section -->
        <div class="card" style="margin-top: 2rem; padding: 1.5rem; background: #f9fafb; border: 1px solid #e5e7eb;">
            <h2 style="margin-top: 0;">Staff Synchronisation</h2>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">
                Synchronise staff from Microsoft Entra ID (Azure AD) to Staff Service. This will fetch all active users from your Microsoft 365 organisation and create/update staff records.
            </p>
            
            <?php if ($syncResult && !empty($syncResult['warnings'])): ?>
                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 1rem; border-radius: 0;">
                    <h4 style="margin-top: 0; color: #92400e; font-size: 1rem;">Sync Warnings</h4>
                    <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: #92400e; font-size: 0.875rem;">
                        <?php foreach ($syncResult['warnings'] as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo CSRF::tokenField(); ?>
                <input type="hidden" name="action" value="sync_users">
                <?php if ($isSuperAdmin): ?>
                    <input type="hidden" name="organisation_id" value="<?php echo $organisationId; ?>">
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Sync Staff from Microsoft Entra ID
                </button>
            </form>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background-color: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0;">
                <h4 style="margin-top: 0; color: #1e40af; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> How It Works
                </h4>
                <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: #1e40af; font-size: 0.875rem;">
                    <li>Fetches all active users from Microsoft Entra ID</li>
                    <li>Matches users by email address</li>
                    <li>Creates new staff records or updates existing ones</li>
                    <li>Maps employee IDs from Entra to Staff Service</li>
                    <li>Other applications (like Digital ID) can then sync from Staff Service</li>
                </ul>
            </div>
            
            <div style="margin-top: 1rem; padding: 1rem; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0;">
                <h4 style="margin-top: 0; color: #92400e; font-size: 0.875rem;">
                    <i class="fas fa-exclamation-triangle"></i> Required Permissions
                </h4>
                <p style="margin: 0.5rem 0 0; color: #92400e; font-size: 0.875rem;">
                    For staff synchronisation to work, your Azure AD app registration needs <strong>User.Read.All</strong> application permission (not delegated). 
                    Admin consent is required for this permission.
                </p>
            </div>
        </div>
        
        <form method="POST" action="" style="margin-top: 2rem;">
            <?php echo CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="disable">
            <?php if ($isSuperAdmin): ?>
                <input type="hidden" name="organisation_id" value="<?php echo $organisationId; ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-danger">Disable Entra Integration</button>
        </form>
    <?php else: ?>
        <form method="POST" action="">
            <?php echo CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="enable">
            <?php if ($isSuperAdmin): ?>
                <input type="hidden" name="organisation_id" value="<?php echo $organisationId; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="tenant_id">Tenant ID</label>
                <input type="text" id="tenant_id" name="tenant_id" required>
                <small>Your Azure AD Tenant ID</small>
            </div>
            
            <div class="form-group">
                <label for="client_id">Client ID (Application ID)</label>
                <input type="text" id="client_id" name="client_id" required>
                <small>Your Azure AD Application (Client) ID</small>
            </div>
            
            <div class="alert alert-info">
                <strong>Note:</strong> You also need to set the <code>ENTRA_CLIENT_SECRET</code> environment variable 
                with your Azure AD Application secret.
            </div>
            
            <button type="submit" class="btn btn-primary">Enable Entra Integration</button>
        </form>
    <?php endif; ?>
    
    <div style="margin-top: 2rem; padding: 1rem; background-color: #f0f0f0; border-radius: 0;">
        <h3>Setup Instructions</h3>
        <ol style="margin-left: 1.5rem;">
            <li>Register your application in Azure AD</li>
            <li>Grant API permissions:
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                    <li><strong>For staff synchronisation:</strong> <code>User.Read.All</code> (application permission, requires admin consent)</li>
                </ul>
            </li>
            <li>Copy your Tenant ID and Client ID</li>
            <li>Set the Client Secret as an environment variable: <code>ENTRA_CLIENT_SECRET</code></li>
            <li>Enter the details above and enable integration</li>
        </ol>
        
        <div style="margin-top: 1rem; padding: 1rem; background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 0;">
            <h4 style="margin-top: 0; color: #1e40af; font-size: 0.875rem;">
                <i class="fas fa-info-circle"></i> Integration with Other Apps
            </h4>
            <p style="margin: 0.5rem 0 0; color: #1e40af; font-size: 0.875rem;">
                When Entra integration is enabled in Staff Service, other applications (like Digital ID) can use Staff Service as the source of truth for Entra-synced staff data. 
                This ensures consistent data across all applications.
            </p>
        </div>
    </div>
    <?php endif; // End organisationId check ?>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

