<?php
/**
 * Public Data Fields Reference
 * Documents all data fields available in the Staff Service
 * No authentication required
 */

require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Data Fields Reference';
include INCLUDES_PATH . '/header.php';
?>

<style>
.fields-layout {
    display: flex;
    gap: 2rem;
    max-width: 1300px;
    margin: 0 auto;
    align-items: flex-start;
}
.fields-nav {
    width: 220px;
    flex-shrink: 0;
    position: sticky;
    top: 80px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
}
.fields-nav h3 {
    margin: 0 0 0.75rem;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
}
.fields-nav a {
    display: block;
    padding: 0.35rem 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
    border-radius: 0.25rem;
}
.fields-nav a:hover { background: #e5e7eb; }
.fields-content { flex: 1; min-width: 0; }
.field-section {
    margin-bottom: 2.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}
.field-section-header {
    background: #1f2937;
    color: white;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.field-section-header h2 {
    margin: 0;
    font-size: 1rem;
}
.field-section-header p {
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    opacity: 0.7;
}
.field-section-header i { font-size: 1.1rem; opacity: 0.8; }
.fields-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.fields-table th {
    background: #f9fafb;
    padding: 0.6rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.fields-table td {
    padding: 0.7rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}
.fields-table tr:last-child td { border-bottom: none; }
.fields-table tr:hover td { background: #fafafa; }
.field-name {
    font-family: monospace;
    font-size: 0.8rem;
    background: #f3f4f6;
    padding: 0.15rem 0.4rem;
    border-radius: 0.2rem;
    color: #1f2937;
    white-space: nowrap;
}
.badge {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}
.badge-api    { background: #d1fae5; color: #065f46; }
.badge-no-api { background: #f3f4f6; color: #6b7280; }
.badge-new    { background: #dbeafe; color: #1e40af; }
.badge-type   { background: #ede9fe; color: #5b21b6; }
</style>

<div style="max-width: 1300px; margin: 0 auto; padding: 2rem 1rem;">

    <div style="margin-bottom: 2rem;">
        <h1 style="margin: 0 0 0.5rem;">Data Fields Reference</h1>
        <p style="color: #6b7280; margin: 0; max-width: 700px;">
            A complete reference of all data fields the Staff Service can hold for each person.
            Fields marked <span class="badge badge-api">API</span> are available to connected applications via the REST API.
            Fields marked <span class="badge badge-new">New</span> have been recently added.
        </p>
    </div>

    <div class="fields-layout">

        <nav class="fields-nav">
            <h3>Sections</h3>
            <a href="#personal"><i class="fas fa-user fa-fw"></i> Personal</a>
            <a href="#employment"><i class="fas fa-briefcase fa-fw"></i> Employment</a>
            <a href="#registrations"><i class="fas fa-id-card fa-fw"></i> Registrations &amp; Compliance</a>
            <a href="#appraisals"><i class="fas fa-star fa-fw"></i> Appraisals</a>
            <a href="#supervisions"><i class="fas fa-comments fa-fw"></i> Supervisions</a>
            <a href="#org-units"><i class="fas fa-sitemap fa-fw"></i> Organisational Units</a>
            <a href="#working-time"><i class="fas fa-clock fa-fw"></i> Working Time</a>
            <a href="#leave"><i class="fas fa-calendar-alt fa-fw"></i> Leave</a>
            <a href="#media"><i class="fas fa-image fa-fw"></i> Photo &amp; Signature</a>
            <a href="#api-access" style="margin-top: 1rem; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                <i class="fas fa-plug fa-fw"></i> API Access
            </a>
        </nav>

        <div class="fields-content">

            <!-- PERSONAL -->
            <div class="field-section" id="personal">
                <div class="field-section-header">
                    <i class="fas fa-user"></i>
                    <div>
                        <h2>Personal Information</h2>
                        <p>Core identity fields for every person in the system</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>API</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code class="field-name">first_name</code></td><td>First / given name</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">last_name</code></td><td>Family / surname</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">email</code></td><td>Work email address</td><td><span class="badge badge-type">email</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">phone</code></td><td>Contact phone number</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">date_of_birth</code></td><td>Date of birth</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                        <tr><td><code class="field-name">employee_reference</code></td><td>Unique employee / payroll reference number</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_active</code></td><td>Whether the person is currently active</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">person_type</code></td><td>Role category: <em>staff</em> or <em>person_supported</em></td><td><span class="badge badge-type">enum</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">emergency_contact_name</code></td><td>Name of emergency contact</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                        <tr><td><code class="field-name">emergency_contact_phone</code></td><td>Emergency contact phone number</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- EMPLOYMENT -->
            <div class="field-section" id="employment">
                <div class="field-section-header">
                    <i class="fas fa-briefcase"></i>
                    <div>
                        <h2>Employment Details</h2>
                        <p>Role, contract, and employment history fields</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">job_title</code></td><td>Current job title</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">employment_start_date</code></td><td>Date employment began</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">employment_end_date</code></td><td>Date employment ended (if applicable)</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">contract_type</code></td><td>Employment contract type (permanent, fixed-term, casual, bank, zero-hours)</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_bank_staff</code></td><td>Whether the person is bank / agency staff</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">line_manager_id</code></td><td>Person ID of direct line manager</td><td><span class="badge badge-type">integer</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_apprentice</code></td><td>Whether on an apprenticeship programme</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">apprenticeship_start_date</code></td><td>Apprenticeship start date</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">apprenticeship_end_date</code></td><td>Apprenticeship end date</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">apprenticeship_level</code></td><td>Apprenticeship level (e.g. Level 3)</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">apprenticeship_provider</code></td><td>Training provider name</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_tupe</code></td><td>Whether transferred under TUPE regulations</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">tupe_transfer_date</code></td><td>Date of TUPE transfer</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">tupe_previous_organisation</code></td><td>Previous employer name (TUPE)</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">has_visa</code></td><td>Whether the person holds a work visa</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                        <tr><td><code class="field-name">visa_type</code></td><td>Visa category / type</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                        <tr><td><code class="field-name">visa_expiry_date</code></td><td>Date visa expires — triggers alerts before expiry</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- REGISTRATIONS -->
            <div class="field-section" id="registrations">
                <div class="field-section-header">
                    <i class="fas fa-id-card"></i>
                    <div>
                        <h2>Registrations &amp; Compliance</h2>
                        <p>Professional registrations, disclosure checks, and mandatory compliance records. Multiple entries per person supported.</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">registration_type</code></td><td>
                            Type of registration or check. Common values:
                            <br><strong>PVG</strong> — Protection of Vulnerable Groups (Scotland)
                            <br><strong>DBS Basic / Standard / Enhanced</strong> — Disclosure &amp; Barring Service (England &amp; Wales)
                            <br><strong>NMC</strong> — Nursing &amp; Midwifery Council
                            <br><strong>SSSC</strong> — Scottish Social Services Council
                            <br><strong>GMC</strong> — General Medical Council
                            <br><strong>HCPC</strong> — Health &amp; Care Professions Council
                            <br><strong>First Aid</strong>, <strong>Manual Handling</strong>, <strong>Food Hygiene</strong>, and any other certification
                        </td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">registration_number</code></td><td>Unique registration or certificate number</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">registration_body</code></td><td>Issuing body name</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">issue_date</code></td><td>Date registration was issued</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">expiry_date</code></td><td>Date registration expires — triggers alerts before expiry</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">renewal_date</code></td><td>Date renewal was or should be submitted</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_active</code></td><td>Whether the registration is currently valid</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_required_for_role</code></td><td>Whether this registration is mandatory for the person's role</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- APPRAISALS -->
            <div class="field-section" id="appraisals">
                <div class="field-section-header" style="background: #1e3a5f;">
                    <i class="fas fa-star"></i>
                    <div>
                        <h2>Appraisals <span class="badge badge-new" style="font-size: 0.65rem; vertical-align: middle;">New</span></h2>
                        <p>Annual and periodic performance reviews. Multiple entries per person supported.</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">appraisal_date</code></td><td>Date the appraisal took place</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">due_date</code></td><td>Date the appraisal was due</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">appraisal_type</code></td><td>Type: <em>annual</em>, <em>probationary</em>, <em>interim</em>, <em>return_to_work</em>, <em>other</em></td><td><span class="badge badge-type">enum</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">appraiser_name</code></td><td>Name of the person who conducted the appraisal</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">appraiser_person_id</code></td><td>Person ID of appraiser (if they are also in the system)</td><td><span class="badge badge-type">integer</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">outcome</code></td><td>Result: <em>outstanding</em>, <em>exceeds_expectations</em>, <em>meets_expectations</em>, <em>requires_improvement</em>, <em>unsatisfactory</em>, <em>not_completed</em>, <em>pending</em></td><td><span class="badge badge-type">enum</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">next_due_date</code></td><td>Date next appraisal is due — used for compliance alerts</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">notes</code></td><td>Internal notes (not shared via API)</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- SUPERVISIONS -->
            <div class="field-section" id="supervisions">
                <div class="field-section-header" style="background: #1e3a5f;">
                    <i class="fas fa-comments"></i>
                    <div>
                        <h2>Supervisions <span class="badge badge-new" style="font-size: 0.65rem; vertical-align: middle;">New</span></h2>
                        <p>Formal supervision records. Multiple entries per person supported.</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">supervision_date</code></td><td>Date the supervision took place</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">due_date</code></td><td>Date the supervision was due</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">supervision_type</code></td><td>Type: <em>individual</em>, <em>group</em>, <em>peer</em>, <em>other</em></td><td><span class="badge badge-type">enum</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">supervisor_name</code></td><td>Name of the supervising manager</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">supervisor_person_id</code></td><td>Person ID of supervisor (if they are also in the system)</td><td><span class="badge badge-type">integer</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">duration_minutes</code></td><td>Duration of the supervision session in minutes</td><td><span class="badge badge-type">integer</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">outcome</code></td><td>Summary of what was discussed and agreed</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">next_due_date</code></td><td>Date next supervision is due — used for compliance alerts</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">notes</code></td><td>Internal notes (not shared via API)</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-no-api">Private</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- ORG UNITS -->
            <div class="field-section" id="org-units">
                <div class="field-section-header">
                    <i class="fas fa-sitemap"></i>
                    <div>
                        <h2>Organisational Units</h2>
                        <p>Teams, departments, and services a person belongs to. Multiple units per person supported.</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">unit_name</code></td><td>Name of the team, service, or department</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">unit_code</code></td><td>Short reference code for the unit</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">role_in_unit</code></td><td>Person's role within the unit (e.g. member, lead, manager)</td><td><span class="badge badge-type">text</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">is_primary</code></td><td>Whether this is the person's primary unit</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">start_date</code></td><td>Date person joined this unit</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">end_date</code></td><td>Date person left this unit (if applicable)</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- WORKING TIME -->
            <div class="field-section" id="working-time">
                <div class="field-section-header">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h2>Working Time</h2>
                        <p>Working Time Directive (WTD) agreement and opt-out records</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">wtd_agreed</code></td><td>Whether the WTD agreement has been signed</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">wtd_agreement_date</code></td><td>Date WTD agreement was signed</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">wtd_opt_out</code></td><td>Whether the person has opted out of the 48-hour working week limit</td><td><span class="badge badge-type">boolean</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">wtd_opt_out_date</code></td><td>Date opt-out was signed</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">wtd_opt_out_expiry_date</code></td><td>Date opt-out expires (if applicable)</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- LEAVE -->
            <div class="field-section" id="leave">
                <div class="field-section-header">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <h2>Leave</h2>
                        <p>Annual leave entitlement and balances</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">annual_leave_allocation</code></td><td>Total annual leave entitlement in days</td><td><span class="badge badge-type">decimal</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">annual_leave_used</code></td><td>Days of annual leave used in current leave year</td><td><span class="badge badge-type">decimal</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">annual_leave_carry_over</code></td><td>Days carried over from previous leave year</td><td><span class="badge badge-type">decimal</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">time_in_lieu_hours</code></td><td>Time in lieu accrued (hours)</td><td><span class="badge badge-type">decimal</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">leave_year_start_date</code></td><td>Start of current leave year</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">leave_year_end_date</code></td><td>End of current leave year</td><td><span class="badge badge-type">date</span></td><td><span class="badge badge-api">API</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- MEDIA -->
            <div class="field-section" id="media">
                <div class="field-section-header">
                    <i class="fas fa-image"></i>
                    <div>
                        <h2>Photo &amp; Signature</h2>
                        <p>Profile photo and digital signature. Accessible by connected applications via authenticated URL.</p>
                    </div>
                </div>
                <table class="fields-table">
                    <thead><tr><th>Field</th><th>Description</th><th>Type</th><th>API</th></tr></thead>
                    <tbody>
                        <tr><td><code class="field-name">photo_url</code></td><td>Authenticated URL to the staff member's approved profile photo. Requires API key to fetch.</td><td><span class="badge badge-type">url</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">photo_approval_status</code></td><td>Photo status: <em>pending</em>, <em>approved</em>, <em>rejected</em>. Only approved photos are exposed via the API.</td><td><span class="badge badge-type">enum</span></td><td><span class="badge badge-api">API</span></td></tr>
                        <tr><td><code class="field-name">signature_url</code></td><td>Authenticated URL to the staff member's digital signature image. Requires API key to fetch.</td><td><span class="badge badge-type">url</span></td><td><span class="badge badge-api">API</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- API ACCESS -->
            <div class="field-section" id="api-access">
                <div class="field-section-header" style="background: #065f46;">
                    <i class="fas fa-plug"></i>
                    <div>
                        <h2>API Access</h2>
                        <p>Connecting your application to the Staff Service</p>
                    </div>
                </div>
                <div style="padding: 1.5rem;">
                    <p style="margin-top: 0;">Connected applications can access staff data programmatically using a REST API. Access is secured with API keys scoped to your organisation — you can only access your own staff data.</p>

                    <h3 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Endpoints</h3>
                    <table class="fields-table" style="margin-bottom: 1.5rem;">
                        <thead><tr><th>Endpoint</th><th>Method</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td><code class="field-name">/api/staff-data.php</code></td><td>GET</td><td>List staff or retrieve a single person by ID. Includes all API-accessible fields above plus registrations, appraisals, supervisions, and organisational units.</td></tr>
                            <tr><td><code class="field-name">/api/webhooks.php</code></td><td>POST</td><td>Subscribe to real-time events when staff data changes (person created, updated, deactivated).</td></tr>
                            <tr><td><code class="field-name">/api/verify-user.php</code></td><td>GET</td><td>Confirm a user is active in the Staff Service. Used by connected apps to gate access.</td></tr>
                            <tr><td><code class="field-name">/api/auth-token.php</code></td><td>POST</td><td>Verify staff credentials. Allows staff to log into connected apps using their Staff Service account.</td></tr>
                        </tbody>
                    </table>

                    <h3 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Authentication</h3>
                    <p style="font-size: 0.875rem; color: #374151;">Include your API key in the <code>Authorization</code> header:</p>
                    <pre style="background: #1f2937; color: #d1fae5; padding: 1rem; border-radius: 0.375rem; font-size: 0.8rem; overflow-x: auto;">Authorization: Bearer your-api-key-here</pre>

                    <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0;">API keys are created by organisation administrators in <strong>Admin → API Keys</strong>. Each key is scoped to a single organisation and shown only once at creation.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
