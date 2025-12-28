<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Privacy Policy';

include INCLUDES_PATH . '/header.php';
?>

<style>
.policy-container {
    max-width: 900px;
    margin: 0 auto;
}

.policy-content {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    line-height: 1.8;
}

.policy-content h2 {
    color: #1f2937;
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.policy-content h2:first-child {
    margin-top: 0;
}

.policy-content h3 {
    color: #374151;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.policy-content p {
    margin-bottom: 1rem;
    color: #4b5563;
}

.policy-content ul, .policy-content ol {
    margin: 1rem 0;
    padding-left: 2rem;
    color: #4b5563;
}

.policy-content li {
    margin: 0.5rem 0;
}

.policy-content strong {
    color: #1f2937;
}

.last-updated {
    color: #6b7280;
    font-style: italic;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}
</style>

<div class="container">
    <div class="policy-container">
        <div class="policy-content">
            <h1>Privacy Policy</h1>
            <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>

            <h2>1. Introduction</h2>
            <p>This Privacy Policy explains how the Staff Service ("we", "our", or "us") collects, uses, and protects your personal information when you use our service. We are committed to protecting your privacy and ensuring the security of your personal data.</p>

            <h2>2. Information We Collect</h2>
            <h3>2.1 Information You Provide</h3>
            <p>We collect information that you provide directly to us, including:</p>
            <ul>
                <li>Organisation details (name, domain, contact information)</li>
                <li>User account information (name, email address, password)</li>
                <li>Staff profile data (personal details, employment information, qualifications, etc.)</li>
                <li>Communication data when you contact us for support</li>
            </ul>

            <h3>2.2 Information Collected Automatically</h3>
            <p>When you use our service, we may automatically collect:</p>
            <ul>
                <li>IP addresses and browser information</li>
                <li>Usage data and access logs</li>
                <li>Cookies and similar tracking technologies (see our Cookie Policy)</li>
            </ul>

            <h2>3. How We Use Your Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Provide and maintain the Staff Service</li>
                <li>Process your requests and transactions</li>
                <li>Send you important updates and notifications</li>
                <li>Improve our service and develop new features</li>
                <li>Ensure security and prevent fraud</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2>4. Data Sharing and Disclosure</h2>
            <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
            <ul>
                <li><strong>With Your Consent:</strong> When you explicitly consent to sharing</li>
                <li><strong>Service Providers:</strong> With trusted third-party service providers who assist in operating our service (under strict confidentiality agreements)</li>
                <li><strong>Legal Requirements:</strong> When required by law, court order, or government regulation</li>
                <li><strong>Protection of Rights:</strong> To protect our rights, property, or safety, or that of our users</li>
            </ul>

            <h2>5. Data Security</h2>
            <p>We implement appropriate technical and organisational measures to protect your personal information:</p>
            <ul>
                <li>Encryption of data in transit (HTTPS/TLS)</li>
                <li>Secure authentication and access controls</li>
                <li>Regular security assessments and updates</li>
                <li>Organisation-level data isolation</li>
                <li>Role-based access control</li>
            </ul>
            <p>However, no method of transmission over the internet or electronic storage is 100% secure, and we cannot guarantee absolute security.</p>

            <h2>6. Data Retention</h2>
            <p>We retain your personal information for as long as necessary to:</p>
            <ul>
                <li>Provide our services to you</li>
                <li>Comply with legal obligations</li>
                <li>Resolve disputes and enforce agreements</li>
            </ul>
            <p>When you request deletion of your account, we will delete or anonymise your personal information, except where we are required to retain it for legal or regulatory purposes.</p>

            <h2>7. Your Rights</h2>
            <p>Under data protection laws, you have the right to:</p>
            <ul>
                <li><strong>Access:</strong> Request a copy of your personal information</li>
                <li><strong>Rectification:</strong> Correct inaccurate or incomplete information</li>
                <li><strong>Erasure:</strong> Request deletion of your personal information</li>
                <li><strong>Restriction:</strong> Request restriction of processing</li>
                <li><strong>Portability:</strong> Receive your data in a structured, machine-readable format</li>
                <li><strong>Objection:</strong> Object to processing of your personal information</li>
            </ul>
            <p>To exercise these rights, please contact us at <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a></p>

            <h2>8. International Data Transfers</h2>
            <p>Your information may be transferred to and processed in countries other than your country of residence. We ensure that appropriate safeguards are in place to protect your data in accordance with this Privacy Policy and applicable data protection laws.</p>

            <h2>9. Children's Privacy</h2>
            <p>Our service is not intended for individuals under the age of 16. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.</p>

            <h2>10. Changes to This Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date. You are advised to review this Privacy Policy periodically for any changes.</p>

            <h2>11. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>
            <p>
                <strong>Email:</strong> <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a><br>
            </p>

            <h2>12. GDPR Compliance</h2>
            <p>If you are located in the European Economic Area (EEA), we process your personal information in accordance with the General Data Protection Regulation (GDPR). We act as a data controller for the personal information we collect through our service.</p>
            <p>Our legal basis for processing includes:</p>
            <ul>
                <li>Performance of a contract (providing the Staff Service)</li>
                <li>Legitimate interests (service improvement, security)</li>
                <li>Legal obligations (compliance with laws)</li>
                <li>Consent (where applicable)</li>
            </ul>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>


