<?php
require_once dirname(__DIR__) . '/config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Rate limiting: 5 attempts per 15 minutes per IP
        $ip = RateLimiter::getClientIp();
        $rateLimit = RateLimiter::check($ip . ':login', 5, 900); // 15 minutes = 900 seconds
        
        if (!$rateLimit['allowed']) {
            $resetTime = date('H:i:s', $rateLimit['reset_at']);
            $error = "Too many login attempts. Please try again after $resetTime.";
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $result = Auth::login($email, $password);
        
            if ($result === true) {
                // Reset rate limit on successful login
                RateLimiter::reset($ip . ':login');
                
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);
                
                header('Location: ' . url('index.php'));
                exit;
            } elseif (is_array($result) && isset($result['error'])) {
                $error = $result['message'];
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login';
include INCLUDES_PATH . '/header.php';
?>

<div class="card">
    <h1>Login</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error" role="alert" aria-live="assertive"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success" role="alert" aria-live="polite"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" autocomplete="off">
        <?php echo CSRF::tokenField(); ?>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <p style="margin-top: 1.5rem; text-align: center;">
        <a href="<?php echo url('register.php'); ?>">Don't have an account? Register</a>
    </p>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

