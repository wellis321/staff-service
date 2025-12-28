<?php
require_once dirname(__DIR__) . '/config/config.php';

// Docs page is publicly accessible - no authentication required
$pageTitle = 'Documentation';

include INCLUDES_PATH . '/header.php';
?>

<style>
.docs-container {
    display: flex;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 20px;
    align-items: flex-start;
}

.docs-sidebar {
    width: 250px;
    max-width: 250px;
    flex-shrink: 0;
    position: sticky;
    top: 80px;
    align-self: flex-start;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    z-index: 10;
}

.docs-sidebar-nav {
    display: flex;
    flex-direction: column;
    width: 100%;
    background: white;
    border-right: 2px solid #e5e7eb;
    padding: 1rem 0;
}

.docs-sidebar-nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.2s;
    text-align: left;
    justify-content: flex-start;
    border-left: 3px solid transparent;
}

.docs-sidebar-nav-link:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.docs-sidebar-nav-link.active {
    background: #2563eb;
    color: white;
    border-left-color: #1e40af;
}

.docs-sidebar-nav-link i {
    width: 18px;
    text-align: center;
    flex-shrink: 0;
}

.docs-content {
    flex: 1;
    min-width: 0;
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.docs-section {
    margin-bottom: 3rem;
    scroll-margin-top: 100px;
}

.docs-section h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #1f2937;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.docs-section h3 {
    font-size: 1.5rem;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #374151;
}

.docs-section p {
    line-height: 1.8;
    color: #4b5563;
    margin-bottom: 1rem;
}

.docs-section ul, .docs-section ol {
    margin-left: 2rem;
    margin-bottom: 1rem;
    line-height: 1.8;
    color: #4b5563;
}

.docs-section code {
    background: #f3f4f6;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    color: #dc2626;
}

.docs-section pre {
    background: #1f2937;
    color: #f9fafb;
    padding: 1.5rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1rem 0;
}

.docs-section pre code {
    background: transparent;
    color: #f9fafb;
    padding: 0;
}

@media (max-width: 968px) {
    .docs-container {
        flex-direction: column;
    }
    
    .docs-sidebar {
        width: 100%;
        max-width: 100%;
        position: relative;
        top: 0;
        max-height: none;
    }
    
    .docs-sidebar-nav {
        border-right: none;
        border-bottom: 2px solid #e5e7eb;
    }
}
</style>

<div class="docs-container">
    <nav class="docs-sidebar">
        <div class="docs-sidebar-nav">
            <a href="#overview" class="docs-sidebar-nav-link active">
                <i class="fas fa-info-circle"></i>
                <span>Overview</span>
            </a>
            <a href="#getting-started" class="docs-sidebar-nav-link">
                <i class="fas fa-rocket"></i>
                <span>Getting Started</span>
            </a>
            <a href="#api-integration" class="docs-sidebar-nav-link">
                <i class="fas fa-plug"></i>
                <span>API & MCP Integration</span>
            </a>
            <a href="#staff-profiles" class="docs-sidebar-nav-link">
                <i class="fas fa-user"></i>
                <span>Staff Profiles</span>
            </a>
            <a href="#learning-history" class="docs-sidebar-nav-link">
                <i class="fas fa-link"></i>
                <span>Learning History & Record Linking</span>
            </a>
            <a href="#data-sync" class="docs-sidebar-nav-link">
                <i class="fas fa-sync"></i>
                <span>Data Synchronisation</span>
            </a>
            <a href="#security" class="docs-sidebar-nav-link">
                <i class="fas fa-shield-alt"></i>
                <span>Security & Privacy</span>
            </a>
            <a href="<?php echo url('security.php'); ?>" class="docs-sidebar-nav-link" target="_blank">
                <i class="fas fa-lock"></i>
                <span>Security Features <i class="fas fa-external-link-alt" style="font-size: 0.75rem; margin-left: 0.25rem;"></i></span>
            </a>
        </div>
    </nav>
    
    <div class="docs-content">
        <section id="overview" class="docs-section">
            <h2>Overview</h2>
            <p>The Staff Service is a centralised staff management system designed for organisations where data ownership is critical. It serves as your <strong>single source of truth</strong> for all staff information, allowing you to maintain complete control over your data while feeding other systems without duplication or vendor lock-in.</p>
            
            <h3>Key Features</h3>
            <ul>
                <li><strong>Centralised Staff Database</strong> - One central database you own and control</li>
                <li><strong>API & MCP Integration</strong> - Connect with existing systems without duplication</li>
                <li><strong>Complete Data Ownership</strong> - Your data stays yours, no vendor lock-in</li>
                <li><strong>Bidirectional Sync</strong> - Keep all systems in sync automatically</li>
                <li><strong>Staff Self-Service</strong> - Staff can update their own details with verification workflows</li>
                <li><strong>Digital Signatures</strong> - Capture and store staff signatures digitally</li>
                <li><strong>Compliance Alerts</strong> - Automatic monitoring of registrations and qualifications</li>
                <li><strong>Persistent Learning History</strong> - Link staff records to preserve training and skills across role changes</li>
                <li><strong>Microsoft Integration</strong> - Seamless integration with Microsoft Entra and 365</li>
            </ul>
        </section>
        
        <section id="getting-started" class="docs-section">
            <h2>Getting Started</h2>
            <p>To get started with the Staff Service, your organisation needs to be registered. If you're an organisation administrator, you can request access through the <a href="<?php echo url('request-access.php'); ?>">Request Organisation Access</a> page.</p>
            
            <h3>For Staff Members</h3>
            <p>Once your organisation has access:</p>
            <ol>
                <li>Register for an account using your organisation email address</li>
                <li>Log in to access your profile</li>
                <li>Update your personal details, contact information, and emergency contacts</li>
                <li>Upload your profile photo (subject to admin approval)</li>
                <li>Add your digital signature for contracts and agreements</li>
            </ol>
            
            <h3>For Administrators</h3>
            <p>As an organisation administrator, you can:</p>
            <ul>
                <li>Create and manage staff profiles</li>
                <li>Link staff to user accounts</li>
                <li>Assign staff to organisational units</li>
                <li>Review and approve staff profile updates</li>
                <li>Manage job descriptions and job posts</li>
                <li>Export staff data for reporting</li>
            </ul>
        </section>
        
        <section id="api-integration" class="docs-section">
            <h2>API & MCP Integration</h2>
            <p>The Staff Service provides both REST API and MCP (Model Context Protocol) integration options for connecting with your existing systems.</p>
            
            <h3>REST API</h3>
            <p>Our REST API provides programmatic access to staff data. All API endpoints require authentication using either:</p>
            <ul>
                <li><strong>API Key</strong> - For external systems and automated integrations</li>
                <li><strong>Session Authentication</strong> - For web applications on the same domain</li>
            </ul>
            
            <h4>Base URL</h4>
            <pre><code><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/api/</code></pre>
            
            <h4>Example: Get Staff Member</h4>
            <pre><code>GET /api/staff-data.php?id=123
Authorization: Bearer YOUR_API_KEY</code></pre>
            
            <h3>MCP Integration</h3>
            <p>For applications using Model Context Protocol (MCP), the Staff Service provides MCP-compatible endpoints that allow seamless integration with MCP-enabled systems.</p>
            
            <h3>Webhooks</h3>
            <p>Subscribe to webhooks for real-time notifications when staff data changes. Webhooks are sent for:</p>
            <ul>
                <li>New staff member creation</li>
                <li>Profile updates</li>
                <li>Status changes (active/inactive)</li>
                <li>Organisational unit assignments</li>
            </ul>
        </section>
        
        <section id="staff-profiles" class="docs-section">
            <h2>Staff Profiles</h2>
            <p>Each staff member has a comprehensive profile containing:</p>
            
            <h3>Personal Information</h3>
            <ul>
                <li>Name, date of birth, contact details</li>
                <li>Emergency contacts</li>
                <li>Profile photo</li>
                <li>Digital signature</li>
            </ul>
            
            <h3>Employment Details</h3>
            <ul>
                <li>Job title and job post</li>
                <li>Employment start date</li>
                <li>Line manager</li>
                <li>Organisational unit assignments</li>
                <li>Contract type and status</li>
            </ul>
            
            <h3>Financial & Identification</h3>
            <ul>
                <li>National Insurance number</li>
                <li>Bank account details</li>
                <li>Address information</li>
            </ul>
            
            <h3>Compliance & Qualifications</h3>
            <ul>
                <li>Professional registrations</li>
                <li>Qualifications and certifications</li>
                <li>Learning records</li>
                <li>Role history</li>
            </ul>
            
            <h3>Leave Management</h3>
            <ul>
                <li>Annual leave allocation and usage</li>
                <li>Time in lieu</li>
                <li>Leave year dates</li>
            </ul>
        </section>
        
        <section id="learning-history" class="docs-section">
            <h2>Learning History & Record Linking</h2>
            <p>The Staff Service includes powerful features for maintaining complete learning and skills history, even when staff change roles or return to your organisation with different employee numbers.</p>
            
            <h3>Linking Person Records</h3>
            <p>When a staff member changes post, leaves and rejoins, or receives a new employee number, you can link their old and new person records. This preserves their learning history, qualifications, and skills without creating duplicate data.</p>
            
            <h4>How to Link Records</h4>
            <ol>
                <li>Navigate to the staff member's profile in the <strong>Staff</strong> section</li>
                <li>Click on <strong>"Link Staff Records"</strong> (available in both view and edit modes)</li>
                <li>Use the search form to find potential matches based on:
                    <ul>
                        <li>Email address</li>
                        <li>First and last name</li>
                        <li>Date of birth</li>
                    </ul>
                </li>
                <li>Review the search results and click <strong>"Link Records"</strong> for the correct match</li>
                <li>The system will create a bidirectional relationship between the records</li>
            </ol>
            
            <h4>What Gets Linked</h4>
            <p>When person records are linked, the following data becomes accessible across both records:</p>
            <ul>
                <li><strong>Learning Records</strong> - All training, qualifications, and certifications</li>
                <li><strong>Skills History</strong> - Professional skills and competencies</li>
            </ul>
            <p><strong>Note:</strong> Annual leave records, disciplinary records, and other employment-specific data are not transferred, as they relate to specific employment periods.</p>
            
            <h3>Searching Learning Records by Employee Reference</h3>
            <p>You can search for learning records using an employee reference number, which is useful when:</p>
            <ul>
                <li>A staff member has changed employee numbers</li>
                <li>You need to find historical training records</li>
                <li>You're verifying qualifications from previous employment periods</li>
            </ul>
            
            <h4>How to Search</h4>
            <ol>
                <li>Go to the <strong>Staff</strong> section</li>
                <li>Click on <strong>"Search Learning Records by Employee Ref"</strong></li>
                <li>Enter the employee reference number</li>
                <li>Review the results, which will show all learning records associated with that employee reference</li>
            </ol>
            
            <h3>Viewing Linked Records</h3>
            <p>Linked records are visible in several places:</p>
            <ul>
                <li><strong>Staff Profile View/Edit</strong> - Shows all linked person records with relationship types</li>
                <li><strong>Learning & Qualifications Section</strong> - Displays learning records from both current and linked records, clearly marked</li>
                <li><strong>My Profile</strong> - Staff members can see their complete learning history, including records from linked profiles</li>
            </ul>
            
            <h3>Best Practices</h3>
            <ul>
                <li>Link records as soon as you identify a match to preserve complete history</li>
                <li>Use the search functionality to find potential matches before creating new records</li>
                <li>Review linked records periodically to ensure accuracy</li>
                <li>Document the reason for linking in the relationship notes field</li>
            </ul>
            
            <h3>Unlinking Records</h3>
            <p>If records were linked incorrectly, administrators can unlink them from the "Link Srtaff Records" page. This removes the relationship but does not delete any data from either record.</p>
        </section>
        
        <section id="data-sync" class="docs-section">
            <h2>Data Synchronisation</h2>
            <p>The Staff Service supports bidirectional data synchronisation with external systems, ensuring all your systems stay in sync without manual duplication.</p>
            
            <h3>Outbound Sync (Staff Service → Other Systems)</h3>
            <p>When staff data is updated in the Staff Service, changes can be automatically pushed to:</p>
            <ul>
                <li>Microsoft Entra / Azure AD</li>
                <li>Microsoft 365</li>
                <li>HR systems</li>
                <li>Rota systems</li>
                <li>Recruitment platforms</li>
                <li>Finance systems</li>
                <li>LMS systems</li>
            </ul>
            
            <h3>Inbound Sync (Other Systems → Staff Service)</h3>
            <p>The Staff Service can receive initial data from:</p>
            <ul>
                <li>Recruitment systems (new hires)</li>
                <li>HR systems (employment updates)</li>
            </ul>
            
            <h3>Sync Methods</h3>
            <ul>
                <li><strong>API Polling</strong> - External systems can poll the API for updates</li>
                <li><strong>Webhooks</strong> - Real-time notifications sent to subscribed endpoints</li>
                <li><strong>Scheduled Sync</strong> - Automated sync jobs run at regular intervals</li>
            </ul>
        </section>
        
        <section id="security" class="docs-section">
            <h2>Security & Privacy</h2>
            <p>The Staff Service is built with security and privacy as top priorities.</p>
            
            <h3>Data Ownership</h3>
            <p>You maintain complete ownership of your data. There's no vendor lock-in, and your data stays in your control. You can export all your data at any time.</p>
            
            <h3>Multi-Tenant Isolation</h3>
            <p>All data is isolated by organisation. Staff from one organisation cannot access data from another organisation, even administrators.</p>
            
            <h3>Role-Based Access Control</h3>
            <p>Access is controlled through role-based permissions:</p>
            <ul>
                <li><strong>Staff Members</strong> - Can view and edit their own profile</li>
                <li><strong>Organisation Administrators</strong> - Can manage all staff in their organisation</li>
                <li><strong>Super Administrators</strong> - Can manage organisations and system-wide settings</li>
            </ul>
            
            <h3>Data Protection</h3>
            <p>The Staff Service is designed to comply with GDPR and UK data protection regulations:</p>
            <ul>
                <li>Secure data storage and transmission</li>
                <li>Access logging and audit trails</li>
                <li>Data export capabilities</li>
                <li>Right to deletion</li>
            </ul>
            
            <h3>API Security</h3>
            <p>All API endpoints require authentication. API keys can be generated and revoked by administrators, and all API access is logged for security auditing.</p>
        </section>
    </div>
</div>

<script>
// Smooth scrolling and active state management
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.docs-sidebar-nav-link');
    const sections = document.querySelectorAll('.docs-section');
    
    // Handle click events
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Only handle anchor links (starting with #), allow external links to work normally
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const targetId = href.substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    // Update active state
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Smooth scroll
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            // If it's an external link (doesn't start with #), let it work normally
        });
    });
    
    // Update active state on scroll
    function updateActiveSection() {
        const scrollPos = window.scrollY + 150;
        
        sections.forEach(section => {
            const top = section.offsetTop;
            const bottom = top + section.offsetHeight;
            const id = section.getAttribute('id');
            
            if (scrollPos >= top && scrollPos < bottom) {
                navLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    // Only update active state for anchor links, not external links
                    if (href && href.startsWith('#')) {
                        link.classList.remove('active');
                        if (href === '#' + id) {
                            link.classList.add('active');
                        }
                    }
                });
            }
        });
    }
    
    window.addEventListener('scroll', updateActiveSection);
    updateActiveSection();
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>

