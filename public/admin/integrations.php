<?php
/**
 * PMS — Admin: Integrations
 *
 * Configures per-organisation connections to:
 *   • Team Service
 * Settings are stored in `organisation_settings` table so each organisation
 * can connect to a different instance with its own API key.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
if (!RBAC::isOrganisationAdmin() && !RBAC::isSuperAdmin()) {
    header('Location: ' . url('index.php'));
    exit;
}

$organisationId = (int) Auth::getOrganisationId();
$success = '';
$error   = '';
$testResult = null;

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_team_service') {
            $url    = trim($_POST['team_service_url']     ?? '');
            $apiKey = trim($_POST['team_service_api_key'] ?? '');

            // If API key field left blank, keep existing key
            $existing = OrgSettings::get($organisationId, 'team_service_api_key');
            if ($apiKey === '' && $existing !== '') {
                $apiKey = $existing;
            }

            OrgSettings::setMany($organisationId, [
                'team_service_url'     => $url,
                'team_service_api_key' => $apiKey,
            ]);
            $success = 'Team Service settings saved.';
        }

        if ($action === 'test_team_service') {
            $url    = trim($_POST['team_service_url']     ?? '');
            $apiKey = trim($_POST['team_service_api_key'] ?? '');
            if ($url && $apiKey) {
                $testResult = TeamServiceClient::testConnection($url, $apiKey)
                    ? 'success'
                    : 'fail';
            } else {
                $testResult = 'missing';
            }
        }

        if ($action === 'clear_team_service') {
            OrgSettings::setMany($organisationId, [
                'team_service_url'     => '',
                'team_service_api_key' => '',
            ]);
            $success = 'Team Service disconnected.';
        }
    }
}

// ── Load current settings ─────────────────────────────────────────────────────
$teamUrl    = OrgSettings::get($organisationId, 'team_service_url',     getenv('TEAM_SERVICE_URL')     ?: '');
$teamKeySet = OrgSettings::get($organisationId, 'team_service_api_key', getenv('TEAM_SERVICE_API_KEY') ?: '') !== '';

$pageTitle = 'Integrations';
include INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-plug"></i> Integrations</h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($testResult === 'success'): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Connection successful — Team Service is reachable.</div>
<?php elseif ($testResult === 'fail'): ?>
<div class="alert alert-error"><i class="fas fa-times-circle"></i> Connection failed — check the URL and API key.</div>
<?php elseif ($testResult === 'missing'): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Please enter both a URL and API key to test.</div>
<?php endif; ?>

<!-- Team Service -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-people-group"></i> Team Service</h2>
        <?php if ($teamUrl && $teamKeySet): ?>
            <span class="badge badge-green"><i class="fas fa-circle" style="font-size:.6rem"></i> Connected</span>
        <?php else: ?>
            <span class="badge badge-grey">Not configured</span>
        <?php endif; ?>
    </div>

    <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1.5rem">
        Connect to the Team Service so staff members can be added to and removed from teams directly from their profiles.
        Settings are stored per organisation — different organisations can connect to different Team Service instances.
    </p>

    <form method="POST" action="">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="save_team_service">

        <div class="form-group">
            <label for="team_service_url">Team Service URL</label>
            <input type="url" id="team_service_url" name="team_service_url" class="form-control"
                   placeholder="https://your-team-service.hostingersite.com"
                   value="<?php echo htmlspecialchars($teamUrl); ?>">
            <div class="form-hint">The base URL of the Team Service (no trailing slash).</div>
        </div>

        <div class="form-group">
            <label for="team_service_api_key">API Key</label>
            <input type="password" id="team_service_api_key" name="team_service_api_key" class="form-control"
                   placeholder="<?php echo $teamKeySet ? '(key saved — leave blank to keep)' : 'Paste API key from Team Service → Settings'; ?>">
            <div class="form-hint">
                Generated in <strong>Team Service → Admin → Settings → API Keys</strong>.
                <?php if ($teamKeySet): ?>Leave blank to keep the existing key.<?php endif; ?>
            </div>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="submit" form="test-form" class="btn btn-secondary">
                <i class="fas fa-plug"></i> Test Connection
            </button>
            <?php if ($teamUrl || $teamKeySet): ?>
            <button type="submit" form="clear-form" class="btn btn-danger"
                    onclick="return confirm('Disconnect the Team Service for this organisation?')">
                <i class="fas fa-unlink"></i> Disconnect
            </button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Separate forms for test and clear so they don't interfere with save -->
    <form id="test-form" method="POST" action="" style="display:none">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="test_team_service">
        <input type="hidden" name="team_service_url"
               id="test_url" value="<?php echo htmlspecialchars($teamUrl); ?>">
        <input type="hidden" name="team_service_api_key" id="test_key" value="">
    </form>

    <form id="clear-form" method="POST" action="" style="display:none">
        <?php echo CSRF::tokenField(); ?>
        <input type="hidden" name="action" value="clear_team_service">
    </form>
</div>

<script>
// When "Test Connection" is clicked, copy current field values into the hidden test form
document.querySelector('[form="test-form"]').addEventListener('click', function() {
    document.getElementById('test_url').value =
        document.getElementById('team_service_url').value;
    document.getElementById('test_key').value =
        document.getElementById('team_service_api_key').value;
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
