<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Request Organisation Access';

$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validatePost()) {
    $organisationName = trim($_POST['organisation_name'] ?? '');
    $organisationDomain = trim($_POST['organisation_domain'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $seatsRequested = intval($_POST['seats_requested'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $useCase = trim($_POST['use_case'] ?? '');
    
    // Validation
    if (empty($organisationName)) {
        $error = 'Please provide your organisation name.';
    } elseif (empty($organisationDomain)) {
        $error = 'Please provide your organisation domain.';
    } elseif (empty($contactName)) {
        $error = 'Please provide your contact name.';
    } elseif (empty($contactEmail) || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid contact email address.';
    } elseif ($seatsRequested < 1) {
        $error = 'Please specify how many seats you need (minimum 1).';
    } else {
        // Store request in database
        $db = getDbConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO organisation_requests (
                    organisation_name, organisation_domain, contact_name, contact_email, 
                    contact_phone, seats_requested, description, use_case, 
                    ip_address, submitted_from, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $organisationName,
                $organisationDomain,
                $contactName,
                $contactEmail,
                !empty($contactPhone) ? $contactPhone : null,
                $seatsRequested,
                !empty($description) ? $description : null,
                !empty($useCase) ? $useCase : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_HOST'] ?? null
            ]);
            
            $requestId = $db->lastInsertId();
            
            // Send email notification to superadmin
            $to = 'williamjamesellis@outlook.com';
            $subject = 'New Organisation Access Request - ' . htmlspecialchars($organisationName);
            
            $message = "A new organisation has requested access to the Staff Service.\n\n";
            $message .= "Request ID: #" . $requestId . "\n";
            $message .= "View in admin panel: " . url('admin/organisation-requests.php') . "\n\n";
            $message .= "Organisation Details:\n";
            $message .= "-------------------\n";
            $message .= "Name: " . htmlspecialchars($organisationName) . "\n";
            $message .= "Domain: " . htmlspecialchars($organisationDomain) . "\n";
            $message .= "Seats Requested: " . $seatsRequested . "\n\n";
            $message .= "Contact Information:\n";
            $message .= "-------------------\n";
            $message .= "Name: " . htmlspecialchars($contactName) . "\n";
            $message .= "Email: " . htmlspecialchars($contactEmail) . "\n";
            $message .= "Phone: " . htmlspecialchars($contactPhone) . "\n\n";
            
            if (!empty($description)) {
                $message .= "About the Organisation:\n";
                $message .= "-------------------\n";
                $message .= htmlspecialchars($description) . "\n\n";
            }
            
            if (!empty($useCase)) {
                $message .= "Intended Use Case:\n";
                $message .= "-------------------\n";
                $message .= htmlspecialchars($useCase) . "\n\n";
            }
            
            $message .= "---\n";
            $message .= "This request was submitted from: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n";
            $message .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
            
            $headers = "From: " . CONTACT_EMAIL . "\r\n";
            $headers .= "Reply-To: " . htmlspecialchars($contactEmail) . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // Send email (but don't fail if email fails - request is already saved)
            @mail($to, $subject, $message, $headers);
            
            $success = true;
            
        } catch (Exception $e) {
            error_log("Error saving organisation request: " . $e->getMessage());
            $error = 'Failed to save your request. Please try again or contact us directly at ' . CONTACT_EMAIL;
        }
    }
}

include INCLUDES_PATH . '/header.php';
?>

<style>
.request-access-container {
    max-width: 800px;
    margin: 0 auto;
}

.info-banner {
    background: #eff6ff;
    border: 1px solid #2563eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-banner h3 {
    color: #1e40af;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.info-banner p {
    color: #1e40af;
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.info-banner ul {
    margin: 0.75rem 0 0 1.5rem;
    color: #1e40af;
}

.info-banner li {
    margin: 0.5rem 0;
    line-height: 1.6;
}

.request-form {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

.form-group label .required {
    color: #dc2626;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
}

.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.form-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
}

.success-message {
    background: #f0fdf4;
    border: 1px solid #16a34a;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    color: #166534;
}

.success-message h3 {
    color: #166534;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.success-message p {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}
</style>

<div class="container">
    <div class="request-access-container">
        <h1>Request Organisation Access</h1>
        
        <?php if ($success): ?>
            <div class="success-message">
                <h3><i class="fas fa-check-circle"></i> Request Submitted Successfully</h3>
                <p>Thank you for your interest in the Staff Service. We have received your organisation access request and will review it shortly.</p>
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Our team will review your request and the information you provided</li>
                    <li>We may contact you at <strong><?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?></strong> to discuss your requirements</li>
                    <li>Once approved, you'll receive instructions on how to set up your organisation account</li>
                    <li>After your organisation is set up, you and your staff will be able to register user accounts</li>
                </ul>
                <p style="margin-top: 1rem;">If you have any questions, please contact us at <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a></p>
            </div>
        <?php else: ?>
            <div class="info-banner">
                <h3><i class="fas fa-info-circle"></i> Before You Register</h3>
                <p><strong>Organisation access is required before users can register.</strong></p>
                <p>To use the Staff Service, your organisation must first be approved by our administrators. This ensures:</p>
                <ul>
                    <li>Proper setup of your organisation account with the right number of seats</li>
                    <li>Security and data isolation between organisations</li>
                    <li>We can provide the best support for your specific needs</li>
                </ul>
                <p style="margin-top: 1rem;"><strong>After approval:</strong> Once your organisation is set up, you and your staff will be able to register user accounts and start using the service.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="request-form">
                <form method="POST" action="">
                    <?php echo CSRF::tokenField(); ?>
                    
                    <h2 style="margin-bottom: 1.5rem; color: #1f2937;">Organisation Information</h2>
                    
                    <div class="form-group">
                        <label for="organisation_name">Organisation Name <span class="required">*</span></label>
                        <input type="text" id="organisation_name" name="organisation_name" required 
                               value="<?php echo htmlspecialchars($_POST['organisation_name'] ?? ''); ?>">
                        <small>The legal or trading name of your organisation</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="organisation_domain">Organisation Domain <span class="required">*</span></label>
                        <input type="text" id="organisation_domain" name="organisation_domain" required 
                               placeholder="example.com" 
                               value="<?php echo htmlspecialchars($_POST['organisation_domain'] ?? ''); ?>">
                        <small>Your organisation's email domain (e.g., example.com). This will be used for user registration.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seats_requested">Number of Seats Required <span class="required">*</span></label>
                        <input type="number" id="seats_requested" name="seats_requested" required 
                               min="1" step="1" 
                               value="<?php echo htmlspecialchars($_POST['seats_requested'] ?? '10'); ?>">
                        <small>How many user accounts do you need? This can be adjusted later if needed.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">About Your Organisation</label>
                        <textarea id="description" name="description" 
                                  placeholder="Tell us about your organisation, what you do, and how many staff you have..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <small>Help us understand your organisation and its needs</small>
                    </div>
                    
                    <h2 style="margin-top: 2rem; margin-bottom: 1.5rem; color: #1f2937;">Contact Information</h2>
                    
                    <div class="form-group">
                        <label for="contact_name">Your Name <span class="required">*</span></label>
                        <input type="text" id="contact_name" name="contact_name" required 
                               value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                        <small>The name of the person we should contact about this request</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Your Email <span class="required">*</span></label>
                        <input type="email" id="contact_email" name="contact_email" required 
                               value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>">
                        <small>We'll use this email to contact you about your request</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Phone Number</label>
                        <input type="tel" id="contact_phone" name="contact_phone" 
                               value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                        <small>Optional - helpful if we need to discuss your requirements</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="use_case">Intended Use Case</label>
                        <textarea id="use_case" name="use_case" 
                                  placeholder="How do you plan to use the Staff Service? What systems will you integrate with?"><?php echo htmlspecialchars($_POST['use_case'] ?? ''); ?></textarea>
                        <small>Tell us about your plans for using the service and any integrations you need</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                        <a href="<?php echo url('landing.php'); ?>" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

