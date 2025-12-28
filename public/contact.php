<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Contact Us';
$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Rate limiting: 3 submissions per hour per IP
        $ip = RateLimiter::getClientIp();
        $rateLimit = RateLimiter::check($ip . ':contact', 3, 3600); // 1 hour = 3600 seconds
        
        if (!$rateLimit['allowed']) {
            $resetTime = date('H:i:s', $rateLimit['reset_at']);
            $error = "Too many contact form submissions. Please try again after $resetTime.";
        } else {
            $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($name)) {
            $error = 'Please enter your name.';
        } elseif (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($subject)) {
            $error = 'Please enter a subject.';
        } elseif (empty($message)) {
            $error = 'Please enter your message.';
        } else {
            // Send email
            $to = CONTACT_EMAIL;
            $emailSubject = 'Contact Form: ' . htmlspecialchars($subject);
            
            $emailMessage = "You have received a new contact form submission.\n\n";
            $emailMessage .= "From: " . htmlspecialchars($name) . "\n";
            $emailMessage .= "Email: " . htmlspecialchars($email) . "\n";
            $emailMessage .= "Subject: " . htmlspecialchars($subject) . "\n\n";
            $emailMessage .= "Message:\n";
            $emailMessage .= "--------\n";
            $emailMessage .= htmlspecialchars($message) . "\n\n";
            $emailMessage .= "---\n";
            $emailMessage .= "Submitted from: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n";
            $emailMessage .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $emailMessage .= "Date: " . date('Y-m-d H:i:s') . "\n";
            
            if (Auth::isLoggedIn()) {
                $user = Auth::getUser();
                $emailMessage .= "User ID: " . $user['id'] . "\n";
                $emailMessage .= "Organisation ID: " . ($user['organisation_id'] ?? 'N/A') . "\n";
            }
            
            $headers = "From: " . CONTACT_EMAIL . "\r\n";
            $headers .= "Reply-To: " . htmlspecialchars($email) . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // Send email (but don't fail if email fails)
            if (@mail($to, $emailSubject, $emailMessage, $headers)) {
                // Reset rate limit on successful submission
                RateLimiter::reset($ip . ':contact');
                $success = true;
            } else {
                $error = 'Failed to send your message. Please try again or contact us directly at ' . CONTACT_EMAIL;
            }
        }
        }
    }
}

include INCLUDES_PATH . '/header.php';
?>

<style>
.contact-container {
    max-width: 800px;
    margin: 0 auto;
}

.contact-info {
    background: #eff6ff;
    border: 1px solid #2563eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.contact-info h3 {
    color: #1e40af;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.contact-info p {
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 0.5rem;
}

.contact-info a {
    color: #2563eb;
    text-decoration: none;
}

.contact-info a:hover {
    text-decoration: underline;
}

.contact-form {
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
    color: #374151;
    font-weight: 500;
}

.form-group label .required {
    color: #dc2626;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group textarea {
    min-height: 150px;
    resize: vertical;
}

.form-group .help-text {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.btn-submit {
    background: #2563eb;
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-submit:hover {
    background: #1d4ed8;
}

.btn-submit:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
}
</style>

<div class="contact-container">
    <h1>Contact Us</h1>
    
    <div class="contact-info">
        <h3>Get in Touch</h3>
        <p>Have a question or need support? Fill out the form below and we'll get back to you as soon as possible.</p>
        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars(CONTACT_EMAIL); ?>"><?php echo htmlspecialchars(CONTACT_EMAIL); ?></a></p>
        <!-- Additional contact details can be added here later (address, Discord, etc.) -->
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Thank you!</strong> Your message has been sent successfully. We'll get back to you as soon as possible.
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$success): ?>
    <form method="POST" class="contact-form">
        <?php echo CSRF::tokenField(); ?>
        
        <div class="form-group">
            <label for="name">
                Name <span class="required">*</span>
            </label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                required 
                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                placeholder="Your full name"
            >
        </div>
        
        <div class="form-group">
            <label for="email">
                Email Address <span class="required">*</span>
            </label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                required 
                value="<?php echo htmlspecialchars($_POST['email'] ?? (Auth::isLoggedIn() ? Auth::getUser()['email'] : '')); ?>"
                placeholder="your.email@example.com"
            >
            <?php if (Auth::isLoggedIn()): ?>
                <div class="help-text">Your account email is pre-filled. You can change it if needed.</div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="subject">
                Subject <span class="required">*</span>
            </label>
            <input 
                type="text" 
                id="subject" 
                name="subject" 
                required 
                value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                placeholder="What is your message about?"
            >
        </div>
        
        <div class="form-group">
            <label for="message">
                Message <span class="required">*</span>
            </label>
            <textarea 
                id="message" 
                name="message" 
                required 
                placeholder="Please provide as much detail as possible..."
            ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            <div class="help-text">Please provide as much detail as possible so we can help you effectively.</div>
        </div>
        
        <button type="submit" class="btn-submit">Send Message</button>
    </form>
    <?php endif; ?>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

