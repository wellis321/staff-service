<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Terms and Conditions';

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
            <h1>Terms and Conditions</h1>
            <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using the Staff Service, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

            <h2>2. Description of Service</h2>
            <p>The Staff Service is a web-based platform that allows organisations to manage and maintain staff information in a centralised database. The service provides tools for storing, managing, and synchronising staff data with other systems via API or MCP integration.</p>

            <h2>3. User Accounts and Registration</h2>
            <h3>3.1 Account Creation</h3>
            <p>To use the Staff Service, you must:</p>
            <ul>
                <li>Register an organisation account</li>
                <li>Provide accurate and complete information</li>
                <li>Maintain the security of your account credentials</li>
                <li>Be responsible for all activities under your account</li>
            </ul>

            <h3>3.2 Account Security</h3>
            <p>You are responsible for:</p>
            <ul>
                <li>Maintaining the confidentiality of your account password</li>
                <li>All activities that occur under your account</li>
                <li>Notifying us immediately of any unauthorised use</li>
            </ul>

            <h2>4. Acceptable Use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use the service for any illegal or unauthorised purpose</li>
                <li>Violate any laws in your jurisdiction</li>
                <li>Transmit any viruses, malware, or harmful code</li>
                <li>Attempt to gain unauthorised access to the service or related systems</li>
                <li>Interfere with or disrupt the service or servers</li>
                <li>Use automated systems to access the service without permission</li>
                <li>Share your account credentials with unauthorised parties</li>
            </ul>

            <h2>5. Data Ownership and Responsibility</h2>
            <h3>5.1 Your Data</h3>
            <p>You retain all ownership rights to the data you upload or create in the Staff Service. You are responsible for:</p>
            <ul>
                <li>The accuracy and completeness of your data</li>
                <li>Ensuring you have the right to store and process the data</li>
                <li>Complying with applicable data protection laws</li>
                <li>Maintaining appropriate backups of your data</li>
            </ul>

            <h3>5.2 Our Rights</h3>
            <p>We reserve the right to:</p>
            <ul>
                <li>Access your data for service provision, support, and security purposes</li>
                <li>Remove or suspend accounts that violate these terms</li>
                <li>Modify or discontinue the service with reasonable notice</li>
            </ul>

            <h2>6. Service Availability</h2>
            <p>We strive to provide reliable service but do not guarantee:</p>
            <ul>
                <li>Uninterrupted or error-free service</li>
                <li>That the service will meet all your requirements</li>
                <li>That defects will be corrected immediately</li>
            </ul>
            <p>We reserve the right to perform maintenance, updates, or modifications that may temporarily interrupt service.</p>

            <h2>7. Intellectual Property</h2>
            <p>The Staff Service, including its design, features, and functionality, is owned by us and protected by copyright, trademark, and other intellectual property laws. You may not:</p>
            <ul>
                <li>Copy, modify, or create derivative works</li>
                <li>Reverse engineer or attempt to extract source code</li>
                <li>Remove or alter any proprietary notices</li>
            </ul>

            <h2>8. Limitation of Liability</h2>
            <p>To the maximum extent permitted by law:</p>
            <ul>
                <li>The service is provided "as is" without warranties of any kind</li>
                <li>We are not liable for any indirect, incidental, or consequential damages</li>
                <li>Our total liability is limited to the amount you paid for the service in the past 12 months</li>
            </ul>

            <h2>9. Indemnification</h2>
            <p>You agree to indemnify and hold us harmless from any claims, damages, losses, or expenses arising from:</p>
            <ul>
                <li>Your use of the service</li>
                <li>Your violation of these terms</li>
                <li>Your violation of any rights of another party</li>
                <li>Any data you upload or process through the service</li>
            </ul>

            <h2>10. Termination</h2>
            <h3>10.1 By You</h3>
            <p>You may terminate your account at any time by contacting us or using account deletion features (where available).</p>

            <h3>10.2 By Us</h3>
            <p>We may suspend or terminate your account if:</p>
            <ul>
                <li>You violate these terms</li>
                <li>You engage in fraudulent or illegal activity</li>
                <li>Required by law or court order</li>
                <li>You fail to pay applicable fees (if any)</li>
            </ul>

            <h3>10.3 Effect of Termination</h3>
            <p>Upon termination:</p>
            <ul>
                <li>Your right to use the service immediately ceases</li>
                <li>We may delete your account and data (subject to legal retention requirements)</li>
                <li>You remain responsible for any obligations incurred before termination</li>
            </ul>

            <h2>11. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. We will notify you of significant changes by:</p>
            <ul>
                <li>Posting the updated terms on this page</li>
                <li>Updating the "Last Updated" date</li>
                <li>Sending email notifications for material changes (where applicable)</li>
            </ul>
            <p>Your continued use of the service after changes constitutes acceptance of the new terms.</p>

            <h2>12. Governing Law</h2>
            <p>These terms are governed by and construed in accordance with the laws of [Your Jurisdiction]. Any disputes arising from these terms or the service shall be subject to the exclusive jurisdiction of the courts of [Your Jurisdiction].</p>

            <h2>13. Severability</h2>
            <p>If any provision of these terms is found to be unenforceable or invalid, that provision shall be limited or eliminated to the minimum extent necessary, and the remaining provisions shall remain in full force and effect.</p>

            <h2>14. Entire Agreement</h2>
            <p>These terms constitute the entire agreement between you and us regarding the use of the Staff Service and supersede all prior agreements and understandings.</p>

            <h2>15. Contact Information</h2>
            <p>If you have any questions about these Terms and Conditions, please contact us:</p>
            <p>
                <strong>Email:</strong> <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a><br>
            </p>

            <h2>16. Acknowledgment</h2>
            <p>By using the Staff Service, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.</p>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>


