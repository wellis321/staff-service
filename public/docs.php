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
            <a href="#digital-id-workflow" class="docs-sidebar-nav-link">
                <i class="fas fa-id-card"></i>
                <span>Digital ID Cards</span>
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
            <a href="#entra-integration" class="docs-sidebar-nav-link">
                <i class="fas fa-microsoft"></i>
                <span>Microsoft Entra/365 Integration</span>
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
        
        <section id="digital-id-workflow" class="docs-section">
            <h2>Getting Digital ID Cards for Staff Members</h2>
            <p>After creating a staff member in Staff Service, they need an employee record in Digital ID to get a digital ID card. The process depends on whether Digital ID is integrated with Staff Service.</p>
            
            <h3>Setting Up Staff Service Integration</h3>
            <p>Before you can sync staff from Staff Service to Digital ID, you need to configure the integration:</p>
            
            <h4>Step 1: Create API Key in Staff Service</h4>
            <p><strong>Web Interface (Recommended):</strong></p>
            <ol>
                <li>Log in to Staff Service as an organisation administrator</li>
                <li>Go to <strong>Admin</strong> → <strong>API Keys</strong> (in the Admin dropdown menu)</li>
                <li>Click <strong>"Create API Key"</strong></li>
                <li>Enter a descriptive name (e.g., "Digital ID Integration")</li>
                <li>Click <strong>"Create API Key"</strong></li>
                <li>Copy the API key immediately - it will only be shown once!</li>
            </ol>
            <p><strong>Command Line (Alternative):</strong></p>
            <ol>
                <li>Find your User ID and Organisation ID (check your profile URL or database)</li>
                <li>Run the API key creation script:
                    <pre><code>php scripts/create-api-key.php &lt;user_id&gt; &lt;organisation_id&gt; "Digital ID Integration"</code></pre>
                    Example: <code>php scripts/create-api-key.php 3 1 "Digital ID Integration"</code>
                </li>
                <li>Save the API key that's displayed - it won't be shown again!</li>
            </ol>
            
            <h4>Step 2: Configure Digital ID Settings</h4>
            <p><strong>Where to paste the API key:</strong> Copy the API key from Staff Service and configure it in Digital ID's web interface. Each organisation in Digital ID should use their own API key from Staff Service.</p>
            
            <div style="background-color: #f0fdf4; border-left: 4px solid #10b981; padding: 1rem; margin: 1rem 0; border-radius: 0;">
                <p style="margin: 0; color: #065f46;"><strong>Important:</strong> Each organisation in Digital ID should use their own unique API key from Staff Service. The API key you create in Staff Service is automatically scoped to your organisation, so when Digital ID uses it, it will only sync staff data from your organisation. This ensures complete data isolation between organisations.</p>
            </div>
            
            <p><strong>Web Interface (Recommended):</strong></p>
            <ol>
                <li>Log in to <strong>Digital ID</strong> as an organisation administrator</li>
                <li>Go to <strong>Admin</strong> → <strong>Organisation</strong> → <strong>Staff Service</strong></li>
                <li>Check "Enable Staff Service Integration"</li>
                <li>Enter the <strong>Staff Service URL</strong> (e.g., <code>http://localhost:8000</code>)</li>
                <li>Paste the <strong>API Key</strong> you copied from Staff Service (this key is unique to your organisation)</li>
                <li>Click <strong>"Save Settings"</strong></li>
            </ol>
            
            <p><strong>Alternative: .env File</strong> (for server administrators only - not recommended for multi-tenant setups):</p>
            <p style="color: #6b7280; font-size: 0.9rem;">The <code>.env</code> file approach is only suitable if you're running a single-tenant installation. For multi-tenant setups where multiple organisations use the same Digital ID instance, each organisation should configure their API key through the web interface, which stores it in the database per organisation.</p>
            <pre><code>USE_STAFF_SERVICE=true
STAFF_SERVICE_URL=http://localhost:8000
STAFF_SERVICE_API_KEY=your-api-key-from-staff-service-here
STAFF_SYNC_INTERVAL=3600</code></pre>
            <p><strong>Note:</strong> Settings configured via the web interface take precedence over <code>.env</code> file settings. For multi-tenant deployments, always use the web interface so each organisation can have their own API key.</p>
            
            <h3>With Staff Service Integration (Recommended)</h3>
            <p>When Digital ID is configured to use Staff Service as the source of truth:</p>
            <ol>
                <li><strong>Automatic Sync</strong>: Go to Digital ID admin panel → <strong>Manage Employees</strong> → Click <strong>"Sync from Staff Service"</strong> button. This will automatically create employee records for all staff members from Staff Service.</li>
                <li><strong>Manual Creation</strong> (if needed): Go to Digital ID admin panel → <strong>Manage Employees</strong> → Click <strong>"Create New Employee"</strong> → Select the user from dropdown → Enter employee number → System will automatically link to Staff Service if a matching person is found.</li>
                <li><strong>Upload Photo</strong> (optional): Staff member can upload through their profile, or admin can upload directly.</li>
                <li><strong>View ID Card</strong>: Staff member logs in and navigates to "Digital ID Card" page - card is automatically generated.</li>
            </ol>
            
            <h3>Without Staff Service Integration (Standalone)</h3>
            <p>If Digital ID is not integrated with Staff Service:</p>
            <ol>
                <li><strong>Create User Account</strong> (if not already created): Staff member registers at Digital ID registration page, or admin creates user account.</li>
                <li><strong>Create Employee Record</strong>: Go to Digital ID admin panel → <strong>Manage Employees</strong> → Click <strong>"Create New Employee"</strong> → Select user → Enter employee number and display reference → Click "Create Employee".</li>
                <li><strong>Upload Photo</strong> (optional): Staff member can upload through their profile, or admin can upload/approve photos.</li>
                <li><strong>View Digital ID Card</strong>: Staff member logs in and navigates to "Digital ID Card" page.</li>
            </ol>
            
            <h3>Important Notes</h3>
            <ul>
                <li><strong>Employee Number</strong>: Should match the employee reference from your HR/payroll system. It's used for integration and cannot be changed after creation.</li>
                <li><strong>User Account</strong>: Staff members need a user account in Digital ID to access their digital ID card. If they don't have one, they can register or an admin can create one.</li>
                <li><strong>Photo</strong>: Recommended but not required for the digital ID card to be generated.</li>
                <li><strong>Signature</strong>: If Staff Service integration is enabled and the staff member has a signature in Staff Service, it will automatically appear on their Digital ID card.</li>
            </ul>
            
            <p>For detailed step-by-step instructions, see the <a href="<?php echo url('docs/DIGITAL_ID_WORKFLOW.md'); ?>" target="_blank">Digital ID Workflow Guide</a>.</p>
        </section>
        
        <section id="api-integration" class="docs-section">
            <h2>API & MCP Integration</h2>
            <p>The Staff Service provides both REST API and MCP (Model Context Protocol) integration options for connecting with your existing systems.</p>
            
            <h3>API Key Management</h3>
            <p>API keys are used to authenticate external systems and applications that need to access Staff Service data. Organisation administrators can create and manage API keys through the web interface.</p>
            
            <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 1rem; margin: 1rem 0; border-radius: 0;">
                <h4 style="margin-top: 0; color: #1e40af; font-size: 1rem;">
                    <i class="fas fa-shield-alt"></i> Organisation-Scoped Security
                </h4>
                <p style="margin: 0.5rem 0 0; color: #1e40af;">
                    <strong>Each organisation has separate API keys that are automatically scoped to their organisation.</strong> When you create an API key in Staff Service, it is automatically linked to your organisation. When that API key is used, it can <strong>only</strong> access data belonging to your organisation - it cannot access data from other organisations. This ensures complete data isolation and security. Multiple organisations can each have their own unique API keys, and each key will only work for that specific organisation's data.
                </p>
            </div>
            
            <h4>Creating an API Key</h4>
            <ol>
                <li>Log in to Staff Service as an organisation administrator</li>
                <li>Navigate to <strong>Admin</strong> → <strong>API Keys</strong> (in the Admin dropdown menu)</li>
                <li>Click <strong>"Create API Key"</strong></li>
                <li>Enter a descriptive name (e.g., "Digital ID Integration", "HR System API")</li>
                <li>Click <strong>"Create API Key"</strong></li>
                <li><strong>Copy the API key immediately</strong> - it will only be shown once!</li>
            </ol>
            
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin: 1rem 0; border-radius: 0;">
                <p style="margin: 0; color: #92400e;"><strong>Important:</strong> API keys are only displayed once when created. Make sure to copy and store them securely. If you lose an API key, you'll need to create a new one and update all systems using it.</p>
            </div>
            
            <h4>Managing API Keys</h4>
            <p>From the API Keys management page, you can:</p>
            <ul>
                <li>View all API keys for your organisation (you can only see keys created by your organisation)</li>
                <li>See when each key was created and last used</li>
                <li>Activate or deactivate keys (deactivated keys cannot be used for authentication)</li>
                <li>Delete keys that are no longer needed</li>
            </ul>
            
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin: 1rem 0; border-radius: 0;">
                <p style="margin: 0; color: #92400e;"><strong>Security Note:</strong> API keys are automatically scoped to your organisation. You can only create and manage API keys for your own organisation, and each key can only access your organisation's data. Other organisations cannot see or use your API keys, and your keys cannot access their data.</p>
            </div>
            
            <h4>Using API Keys</h4>
            <p>Include the API key in API requests using the <code>Authorization</code> header:</p>
            <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
            
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
        
        <section id="entra-integration" class="docs-section">
            <h2>Microsoft Entra/365 Integration</h2>
            <p>The Staff Service can integrate with Microsoft Entra ID (formerly Azure AD) and Microsoft 365 to synchronise user accounts and staff data, making Staff Service the central hub for identity management across your Microsoft ecosystem.</p>
            
            <h3>Setting Up Entra Integration</h3>
            <p>Organisation administrators can configure Entra integration through the web interface:</p>
            
            <h4>Step 1: Register Your Application in Azure AD</h4>
            <ol>
                <li>Go to the <a href="https://portal.azure.com" target="_blank">Azure Portal</a></li>
                <li>Navigate to <strong>Azure Active Directory</strong> → <strong>App registrations</strong></li>
                <li>Click <strong>"New registration"</strong></li>
                <li>Enter a name for your application (e.g., "Staff Service Integration")</li>
                <li>Select supported account types (typically "Accounts in this organizational directory only")</li>
                <li>Click <strong>"Register"</strong></li>
            </ol>
            
            <h4>Step 2: Configure API Permissions</h4>
            <p>For staff synchronisation, you need the following permission:</p>
            <ul>
                <li><strong>User.Read.All</strong> - Application permission (not delegated) - requires admin consent</li>
            </ul>
            <ol>
                <li>In your app registration, go to <strong>"API permissions"</strong></li>
                <li>Click <strong>"Add a permission"</strong></li>
                <li>Select <strong>"Microsoft Graph"</strong></li>
                <li>Choose <strong>"Application permissions"</strong></li>
                <li>Search for and select <strong>"User.Read.All"</strong></li>
                <li>Click <strong>"Add permissions"</strong></li>
                <li>Click <strong>"Grant admin consent"</strong> (this requires an Azure AD administrator)</li>
            </ol>
            
            <h4>Step 3: Create a Client Secret</h4>
            <ol>
                <li>In your app registration, go to <strong>"Certificates & secrets"</strong></li>
                <li>Click <strong>"New client secret"</strong></li>
                <li>Enter a description and choose an expiration period</li>
                <li>Click <strong>"Add"</strong></li>
                <li><strong>Copy the secret value immediately</strong> - it won't be shown again!</li>
            </ol>
            
            <h4>Step 4: Get Your Tenant ID and Client ID</h4>
            <ul>
                <li><strong>Tenant ID</strong>: Found in the "Overview" page of your app registration, or in Azure AD → Overview</li>
                <li><strong>Client ID (Application ID)</strong>: Found in the "Overview" page of your app registration</li>
            </ul>
            
            <h4>Step 5: Configure in Staff Service</h4>
            <ol>
                <li>Log in to Staff Service as an organisation administrator</li>
                <li>Navigate to <strong>Admin</strong> → <strong>Entra/365 Settings</strong></li>
                <li>Enter your <strong>Tenant ID</strong> and <strong>Client ID</strong></li>
                <li>Set the <code>ENTRA_CLIENT_SECRET</code> environment variable with the client secret you created</li>
                <li>Click <strong>"Enable Entra Integration"</strong></li>
            </ol>
            
            <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 1rem; margin: 1rem 0; border-radius: 0;">
                <p style="margin: 0; color: #1e40af;"><strong>Note:</strong> The client secret must be set as an environment variable (<code>ENTRA_CLIENT_SECRET</code>) on your server. Contact your system administrator if you need help with this.</p>
            </div>
            
            <h3>Synchronising Staff from Entra</h3>
            <p>Once Entra integration is enabled, you can synchronise staff from Microsoft Entra ID:</p>
            <ol>
                <li>Go to <strong>Admin</strong> → <strong>Entra/365 Settings</strong></li>
                <li>Click <strong>"Sync Staff from Microsoft Entra ID"</strong></li>
                <li>The system will:
                    <ul>
                        <li>Fetch all active users from Microsoft Entra ID</li>
                        <li>Match users by email address</li>
                        <li>Create new staff records or update existing ones</li>
                        <li>Map employee IDs from Entra to Staff Service</li>
                    </ul>
                </li>
            </ol>
            
            <h3>Integration with Other Applications</h3>
            <p>When Entra integration is enabled in Staff Service, other applications (like Digital ID) can use Staff Service as the source of truth for Entra-synced staff data. This ensures consistent data across all applications without duplication.</p>
            
            <h3>Super Administrator Access</h3>
            <p>Super administrators can configure Entra integration for any organisation when requested. This is useful for organisations that need assistance with setup or troubleshooting.</p>
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
            
            <h4>Managing User Roles</h4>
            <p>Super administrators can manage user roles and assign organisation administrator privileges:</p>
            <ol>
                <li>Log in as a super administrator</li>
                <li>Navigate to <strong>Admin</strong> → <strong>Users</strong></li>
                <li>Select an organisation from the dropdown (optional - leave blank to see all users)</li>
                <li>For each user, you can:
                    <ul>
                        <li><strong>Make Admin</strong> - Assign organisation administrator role to a user</li>
                        <li><strong>Remove Admin</strong> - Remove organisation administrator role from a user</li>
                    </ul>
                </li>
            </ol>
            
            <div style="background-color: #f0f9ff; border-left: 4px solid #3b82f6; padding: 1rem; margin: 1rem 0; border-radius: 0;">
                <p style="margin: 0; color: #1e40af;"><strong>Note:</strong> Organisation administrators have full access to manage staff, job descriptions, and organisational units within their organisation. They cannot access other organisations' data. Super administrators can access all organisations and manage organisation admins.</p>
            </div>
            
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

