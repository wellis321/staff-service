<?php
/**
 * Debug version of register.php with error display enabled
 * Use this to identify the 500 error
 */

// Enable error display temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!-- Starting register.php debug -->\n";

try {
    echo "<!-- Step 1: Loading config -->\n";
    require_once dirname(__DIR__) . '/config/config.php';
    echo "<!-- Step 2: Config loaded -->\n";
    
    // Redirect if already logged in
    if (Auth::isLoggedIn()) {
        echo "<!-- User is logged in, redirecting -->\n";
        header('Location: ' . url('index.php'));
        exit;
    }
    echo "<!-- Step 3: User not logged in -->\n";
    
    $error = '';
    $success = '';
    
    echo "<!-- Step 4: Checking request method -->\n";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<!-- Step 5: POST request detected -->\n";
        if (!CSRF::validatePost()) {
            echo "<!-- Step 6: CSRF validation failed -->\n";
            $error = 'Invalid security token. Please try again.';
        } else {
            echo "<!-- Step 7: CSRF validation passed -->\n";
            // Rate limiting: 3 attempts per hour per IP (if RateLimiter is available)
            $rateLimitAllowed = true;
            if (class_exists('RateLimiter')) {
                try {
                    echo "<!-- Step 8: RateLimiter exists, checking rate limit -->\n";
                    $ip = RateLimiter::getClientIp();
                    $rateLimit = RateLimiter::check($ip . ':register', 3, 3600); // 1 hour = 3600 seconds
                    $rateLimitAllowed = $rateLimit['allowed'] ?? true;
                    
                    if (!$rateLimitAllowed) {
                        $resetTime = date('H:i:s', $rateLimit['reset_at'] ?? time() + 3600);
                        $error = "Too many registration attempts. Please try again after $resetTime.";
                    }
                } catch (Exception $e) {
                    // If rate limiting fails, allow the request but log the error
                    error_log("Rate limiting error: " . $e->getMessage());
                    $rateLimitAllowed = true;
                }
            }
            
            if ($rateLimitAllowed) {
                echo "<!-- Step 9: Rate limit check passed -->\n";
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $organisationDomain = trim($_POST['organisation_domain'] ?? '');
                
                // Validation
                if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
                    $error = 'All fields are required.';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
                    $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
                } else {
                    echo "<!-- Step 10: Validation passed, processing registration -->\n";
                    // Find or create organisation by domain
                    $db = getDbConnection();
                    $stmt = $db->prepare("SELECT id, seats_allocated FROM organisations WHERE domain = ?");
                    $stmt->execute([$organisationDomain]);
                    $organisation = $stmt->fetch();
                    
                    if (!$organisation) {
                        // Create organisation with default seats for local development
                        // Set a high number (1000) to avoid seat limits during development
                        $defaultSeats = 1000;
                        $stmt = $db->prepare("INSERT INTO organisations (name, domain, seats_allocated) VALUES (?, ?, ?)");
                        $stmt->execute([$organisationDomain, $organisationDomain, $defaultSeats]);
                        $organisationId = $db->lastInsertId();
                    } else {
                        $organisationId = $organisation['id'];
                        // If organisation exists but has 0 seats, update it to allow registration
                        if ($organisation['seats_allocated'] == 0) {
                            $defaultSeats = 1000;
                            $stmt = $db->prepare("UPDATE organisations SET seats_allocated = ? WHERE id = ?");
                            $stmt->execute([$defaultSeats, $organisationId]);
                        }
                    }
                    
                    // Register user - Auth::register expects domain string, not organisation ID
                    $result = Auth::register($email, $password, $firstName, $lastName, $organisationDomain);
                    
                    if (is_array($result) && isset($result['success']) && $result['success'] === true) {
                        // After successful registration, auto-create a staff profile for local development
                        try {
                            // Get the newly created user ID
                            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND organisation_id = ?");
                            $stmt->execute([$email, $organisationId]);
                            $newUser = $stmt->fetch();
                            
                            if ($newUser) {
                                // Check if staff profile already exists
                                $stmt = $db->prepare("SELECT id FROM people WHERE user_id = ?");
                                $stmt->execute([$newUser['id']]);
                                $existingProfile = $stmt->fetch();
                                
                                if (!$existingProfile) {
                                    // Create a basic staff profile
                                    $staffData = [
                                        'organisation_id' => $organisationId,
                                        'user_id' => $newUser['id'],
                                        'first_name' => $firstName,
                                        'last_name' => $lastName,
                                        'email' => $email,
                                        'is_active' => true
                                    ];
                                    
                                    $person = Person::createStaff($staffData);
                                    if (!$person) {
                                        error_log("Failed to auto-create staff profile for user: $email");
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // Log error but don't fail registration - profile can be created later
                            error_log("Error auto-creating staff profile: " . $e->getMessage());
                        }
                        
                        // Reset rate limit on successful registration (if RateLimiter is available)
                        if (class_exists('RateLimiter') && isset($ip)) {
                            try {
                                RateLimiter::reset($ip . ':register');
                            } catch (Exception $e) {
                                error_log("Rate limit reset error: " . $e->getMessage());
                            }
                        }
                        
                        // Regenerate session ID to prevent session fixation attacks
                        session_regenerate_id(true);
                        
                        $success = $result['message'] ?? 'Registration successful! Please check your email to verify your account.';
                    } elseif (is_array($result) && isset($result['success']) && $result['success'] === false) {
                        $error = $result['message'] ?? 'Registration failed. Please try again.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    }
    
    echo "<!-- Step 11: Loading page template -->\n";
    $pageTitle = 'Register';
    include INCLUDES_PATH . '/header.php';
    echo "<!-- Step 12: Header loaded -->\n";
    ?>
    
    <div class="card">
        <h1>Register</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert" aria-live="assertive"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert" aria-live="polite"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" autocomplete="off">
            <?php echo CSRF::tokenField(); ?>
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="organisation_domain">Organisation Domain</label>
                <input type="text" id="organisation_domain" name="organisation_domain" value="<?php echo htmlspecialchars($_POST['organisation_domain'] ?? ''); ?>" placeholder="example.com" required>
                <small>The domain for your organisation (e.g. example.com)</small>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        
        <p style="margin-top: 1.5rem; text-align: center;">
            <a href="<?php echo url('login.php'); ?>">Already have an account? Login</a>
        </p>
    </div>
    
    <?php 
    echo "<!-- Step 13: Loading footer -->\n";
    include INCLUDES_PATH . '/footer.php';
    echo "<!-- Step 14: Footer loaded, page complete -->\n";
    ?>
    
<?php
} catch (Throwable $e) {
    echo "<h1>Error Detected</h1>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

