<?php
/**
 * Main Configuration File
 * People Management Service
 */

// Error reporting (environment-based)
$isProduction = getenv('APP_ENV') === 'production' || getenv('APP_ENV') === 'prod';
if ($isProduction) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone
date_default_timezone_set('Europe/London');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Use secure cookies in production (requires HTTPS)
ini_set('session.cookie_secure', $isProduction ? 1 : 0);

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Application settings (from .env or defaults)
define('APP_NAME', getenv('APP_NAME') ?: 'People Management');
define('APP_VERSION', '1.0.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// Contact email - Main super admin and point of contact
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: 'digital-ids@outlook.com');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('VERIFICATION_TOKEN_EXPIRY_HOURS', 24);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Date format (UK format)
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// Photo upload settings
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('PHOTO_UPLOAD_PATH', UPLOADS_PATH . '/people/photos');

// Signature upload settings
define('MAX_SIGNATURE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_SIGNATURE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('SIGNATURE_UPLOAD_PATH', UPLOADS_PATH . '/people/signatures');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    // Prevent caching of pages with forms to avoid CSRF token mismatches
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed for your CDN/external resources)
    $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com; connect-src 'self';";
    header("Content-Security-Policy: $csp");
    
    // HSTS header (only in production with HTTPS)
    if ($isProduction && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    session_start();
}

// Determine shared-auth path (can be symlink, submodule, or relative path)
$sharedAuthPath = ROOT_PATH . '/shared-auth';
if (!file_exists($sharedAuthPath)) {
    // Try relative to digital-id if this is a sibling project
    $digitalIdSharedAuth = dirname(ROOT_PATH) . '/digital-id/shared-auth';
    if (file_exists($digitalIdSharedAuth)) {
        $sharedAuthPath = $digitalIdSharedAuth;
    } else {
        die('Error: shared-auth package not found. Please set up shared-auth package.');
    }
}

// Include shared authentication package
require_once $sharedAuthPath . '/src/Database.php';
require_once $sharedAuthPath . '/src/Auth.php';
require_once $sharedAuthPath . '/src/RBAC.php';
require_once $sharedAuthPath . '/src/CSRF.php';
require_once $sharedAuthPath . '/src/Email.php';
require_once $sharedAuthPath . '/src/OrganisationalUnits.php';

// Include database configuration
require_once CONFIG_PATH . '/database.php';

// Load API authentication class if it exists
if (file_exists(SRC_PATH . '/classes/ApiAuth.php')) {
    require_once SRC_PATH . '/classes/ApiAuth.php';
}

// Load rate limiter class if it exists
if (file_exists(SRC_PATH . '/classes/RateLimiter.php')) {
    require_once SRC_PATH . '/classes/RateLimiter.php';
}

// Autoload classes (simple autoloader)
spl_autoload_register(function ($class) {
    $paths = [
        SRC_PATH . '/models/' . $class . '.php',
        SRC_PATH . '/controllers/' . $class . '.php',
        SRC_PATH . '/classes/' . $class . '.php',
        SRC_PATH . '/services/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load Person model explicitly if not autoloaded
if (!class_exists('Person') && file_exists(SRC_PATH . '/models/Person.php')) {
    require_once SRC_PATH . '/models/Person.php';
}

// Load JobDescription model explicitly if not autoloaded
if (!class_exists('JobDescription') && file_exists(SRC_PATH . '/models/JobDescription.php')) {
    require_once SRC_PATH . '/models/JobDescription.php';
}

// Load JobPost model explicitly if not autoloaded
if (!class_exists('JobPost') && file_exists(SRC_PATH . '/models/JobPost.php')) {
    require_once SRC_PATH . '/models/JobPost.php';
}

// Load StaffRegistration model explicitly if not autoloaded
if (!class_exists('StaffRegistration') && file_exists(SRC_PATH . '/models/StaffRegistration.php')) {
    require_once SRC_PATH . '/models/StaffRegistration.php';
}

// Load StaffRoleHistory model explicitly if not autoloaded
if (!class_exists('StaffRoleHistory') && file_exists(SRC_PATH . '/models/StaffRoleHistory.php')) {
    require_once SRC_PATH . '/models/StaffRoleHistory.php';
}

// Load StaffLearningRecord model explicitly if not autoloaded
if (!class_exists('StaffLearningRecord') && file_exists(SRC_PATH . '/models/StaffLearningRecord.php')) {
    require_once SRC_PATH . '/models/StaffLearningRecord.php';
}

// Calculate base URL dynamically based on document root
// Handles Hostinger case where document root is project root but URLs include /public/
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        static $baseUrl = null;
        if ($baseUrl === null) {
            // Allow override via environment variable (for Hostinger configurations)
            $forcePublicPrefix = getenv('FORCE_PUBLIC_PREFIX') === '1' || getenv('FORCE_PUBLIC_PREFIX') === 'true';
            
            if ($forcePublicPrefix) {
                $baseUrl = '/public';
                return $baseUrl;
            }
            
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            
            // Check if current script path includes /public/
            // If script name is /public/index.php, then document root is project root
            // If script name is /index.php, then document root is public folder
            $hasPublicInPath = (strpos($scriptName, '/public/') !== false || $scriptName === '/public' || strpos($scriptName, '/public/index') === 0);
            
            if ($hasPublicInPath) {
                // Document root is project root, URLs need /public/ prefix
                $baseUrl = '/public';
            } else {
                // Document root is public folder - no prefix needed
                $baseUrl = '';
            }
        }
        return $baseUrl;
    }
}

// Helper function to generate URLs with proper base path
if (!function_exists('url')) {
    function url($path = '') {
        $appUrl = rtrim(APP_URL, '/');
        $basePath = getBaseUrl();
        
        // Remove leading slash from path if present
        $path = ltrim($path, '/');
        // Remove 'public/' prefix from path if present (we'll add it via basePath if needed)
        $path = preg_replace('#^public/#', '', $path);
        
        // Build URL: APP_URL + basePath + path
        $fullPath = $basePath ? $basePath . '/' . $path : $path;
        // Ensure fullPath starts with / but don't double it
        $fullPath = '/' . ltrim($fullPath, '/');
        return $appUrl . $fullPath;
    }
}

