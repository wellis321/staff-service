<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Our Platform';
include INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cubes"></i> Our Platform</h1>
</div>

<style>
.services-intro { max-width: 640px; color: #6b7280; line-height: 1.7; margin-bottom: 2.5rem; font-size: 1.05rem; }

.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}
@media (max-width: 900px) { .services-grid { grid-template-columns: 1fr; } }

.service-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 8px rgba(0,0,0,0.07);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 2px solid transparent;
    transition: transform 0.15s, box-shadow 0.15s;
}
.service-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.service-card.current { border-color: #d97706; }

.sc-head {
    padding: 1.5rem 1.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.9rem;
}
.sc-icon {
    width: 3rem; height: 3rem; border-radius: 0.65rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0;
}
.sc-icon.staff  { background: #fef3c7; color: #d97706; }
.sc-icon.team   { background: #dbeafe; color: #2563eb; }
.sc-icon.people { background: #f3e8ff; color: #7c3aed; }

.sc-head h2 { font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0; }
.badge-current {
    font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
    background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 999px;
    margin-left: auto; white-space: nowrap;
}

.sc-body { padding: 0 1.5rem 1rem; flex: 1; }
.sc-body p { color: #4b5563; line-height: 1.6; font-size: 0.9rem; margin-bottom: 1rem; }

.sc-features { list-style: none; padding: 0; margin: 0; }
.sc-features li {
    display: flex; align-items: flex-start; gap: 0.5rem;
    color: #374151; font-size: 0.875rem; padding: 0.2rem 0;
}
.sc-features li i { color: #10b981; margin-top: 0.15rem; flex-shrink: 0; font-size: 0.8rem; }

.sc-foot { padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6; }
.sc-foot a, .sc-foot span {
    display: inline-flex; align-items: center; gap: 0.45rem;
    padding: 0.55rem 1.25rem; border-radius: 0.4rem; font-weight: 600;
    font-size: 0.9rem; text-decoration: none; transition: background 0.15s;
}
.sc-foot a.staff  { background: #d97706; color: white; }
.sc-foot a.staff:hover  { background: #b45309; }
.sc-foot a.team   { background: #2563eb; color: white; }
.sc-foot a.team:hover   { background: #1d4ed8; }
.sc-foot a.people { background: #7c3aed; color: white; }
.sc-foot a.people:hover { background: #6d28d9; }
.sc-foot span.disabled  { background: #e5e7eb; color: #9ca3af; cursor: default; }

.connects-card {
    background: white; border-radius: 0.75rem; padding: 2rem;
    box-shadow: 0 1px 8px rgba(0,0,0,0.07); text-align: center;
}
.connects-card h2 { font-size: 1.4rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; }
.connects-card > p { color: #6b7280; max-width: 560px; margin: 0 auto 2rem; line-height: 1.7; font-size: 0.95rem; }

.flow-diagram {
    display: flex; align-items: center; justify-content: center;
    flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;
}
.flow-node {
    display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
    background: #f9fafb; border-radius: 0.65rem; padding: 1rem 1.25rem;
    min-width: 8rem;
}
.flow-node i { font-size: 1.5rem; }
.flow-node.staff i  { color: #d97706; }
.flow-node.team i   { color: #2563eb; }
.flow-node.people i { color: #7c3aed; }
.flow-node span { font-size: 0.8rem; font-weight: 600; color: #374151; }
.flow-arrow { color: #d1d5db; font-size: 1.1rem; }
</style>

<p class="services-intro">Three connected services, each a specialist in its domain, working together to support your whole organisation. Use them individually or connect them via API for a unified view.</p>

<div class="services-grid">

    <!-- Staff Service (current) -->
    <div class="service-card current">
        <div class="sc-head">
            <div class="sc-icon staff"><i class="fas fa-id-card-clip"></i></div>
            <h2>Staff Service</h2>
            <span class="badge-current">You are here</span>
        </div>
        <div class="sc-body">
            <p>The single source of truth for your staff and employee data. Manage complete profiles, track training, run appraisals and supervisions, and connect with Microsoft 365.</p>
            <ul class="sc-features">
                <li><i class="fas fa-circle-check"></i> Complete staff profiles &amp; employment history</li>
                <li><i class="fas fa-circle-check"></i> Training &amp; learning records</li>
                <li><i class="fas fa-circle-check"></i> Appraisals &amp; supervisions</li>
                <li><i class="fas fa-circle-check"></i> Microsoft Entra / 365 integration</li>
                <li><i class="fas fa-circle-check"></i> Recruitment pipeline &amp; digital ID cards</li>
                <li><i class="fas fa-circle-check"></i> REST API for downstream systems</li>
            </ul>
        </div>
        <div class="sc-foot">
            <?php if (Auth::isLoggedIn()): ?>
            <a href="<?php echo url('index.php'); ?>" class="staff">
                <i class="fas fa-gauge"></i> Go to Dashboard
            </a>
            <?php else: ?>
            <a href="<?php echo url('landing.php'); ?>" class="staff">
                <i class="fas fa-home"></i> About This Service
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Team Service -->
    <div class="service-card">
        <div class="sc-head">
            <div class="sc-icon team"><i class="fas fa-people-group"></i></div>
            <h2>Team Service</h2>
        </div>
        <div class="sc-body">
            <p>Structure your whole organisation with flexible team hierarchies. Assign staff and people you support to teams, manage memberships, and share team data via API.</p>
            <ul class="sc-features">
                <li><i class="fas fa-circle-check"></i> Unlimited team hierarchy depth</li>
                <li><i class="fas fa-circle-check"></i> Flexible team types &amp; nesting</li>
                <li><i class="fas fa-circle-check"></i> Staff &amp; people membership management</li>
                <li><i class="fas fa-circle-check"></i> Named team roles</li>
                <li><i class="fas fa-circle-check"></i> Full membership history</li>
                <li><i class="fas fa-circle-check"></i> Open REST API</li>
            </ul>
        </div>
        <div class="sc-foot">
            <?php if (TEAM_SERVICE_URL): ?>
            <a href="<?php echo htmlspecialchars(TEAM_SERVICE_URL); ?>/landing.php" class="team" target="_blank" rel="noopener">
                <i class="fas fa-arrow-up-right-from-square"></i> Visit Team Service
            </a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-link-slash"></i> Not configured</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- People Service -->
    <div class="service-card">
        <div class="sc-head">
            <div class="sc-icon people"><i class="fas fa-heart-pulse"></i></div>
            <h2>People Service</h2>
        </div>
        <div class="sc-body">
            <p>Person-centred care management for the people you support. Complete profiles, care needs, key worker relationships, and emergency contacts in one secure place.</p>
            <ul class="sc-features">
                <li><i class="fas fa-circle-check"></i> Rich person profiles</li>
                <li><i class="fas fa-circle-check"></i> Categorised care needs tracking</li>
                <li><i class="fas fa-circle-check"></i> Key worker relationships</li>
                <li><i class="fas fa-circle-check"></i> Emergency contacts</li>
                <li><i class="fas fa-circle-check"></i> Staff Service integration</li>
                <li><i class="fas fa-circle-check"></i> Multi-organisation support</li>
            </ul>
        </div>
        <div class="sc-foot">
            <?php if (PEOPLE_SERVICE_URL): ?>
            <a href="<?php echo htmlspecialchars(PEOPLE_SERVICE_URL); ?>/landing.php" class="people" target="_blank" rel="noopener">
                <i class="fas fa-arrow-up-right-from-square"></i> Visit People Service
            </a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-link-slash"></i> Not configured</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="connects-card">
    <h2><i class="fas fa-plug" style="color:#6b7280;margin-right:.4rem"></i> How the Services Connect</h2>
    <p>Each service owns its domain of data and exposes a REST API. Connect them to enrich your views — staff records flow into team membership, team structure flows into care management.</p>
    <div class="flow-diagram">
        <div class="flow-node staff">
            <i class="fas fa-id-card-clip"></i>
            <span>Staff Service</span>
        </div>
        <div class="flow-arrow"><i class="fas fa-arrows-left-right"></i></div>
        <div class="flow-node team">
            <i class="fas fa-people-group"></i>
            <span>Team Service</span>
        </div>
        <div class="flow-arrow"><i class="fas fa-arrows-left-right"></i></div>
        <div class="flow-node people">
            <i class="fas fa-heart-pulse"></i>
            <span>People Service</span>
        </div>
    </div>
    <p style="font-size:.85rem;color:#9ca3af">Integration is optional — each service works standalone and connects per organisation via API keys.</p>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
