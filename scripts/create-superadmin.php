<?php
/**
 * Create Super Admin User Script
 * Helper script to create a super admin user
 * 
 * Usage (command line):
 *   php scripts/create-superadmin.php <email> <password> <first_name> <last_name>
 * 
 * Usage (browser):
 *   Visit: /scripts/create-superadmin.php?email=admin@example.com&password=SecurePass123&first_name=Admin&last_name=User
 *   Note: Browser usage is for initial setup only - remove or secure this file after use
 */

require_once dirname(__DIR__) . '/config/config.php';

$isCli = php_sapi_name() === 'cli';

// Get input
if ($isCli) {
    // Command line usage
    $email = $argv[1] ?? null;
    $password = $argv[2] ?? null;
    $firstName = $argv[3] ?? null;
    $lastName = $argv[4] ?? null;
    
    if (!$email || !$password || !$firstName || !$lastName) {
        echo "Usage: php scripts/create-superadmin.php <email> <password> <first_name> <last_name>\n";
        echo "\n";
        echo "Example:\n";
        echo "  php scripts/create-superadmin.php admin@example.com SecurePass123 Admin User\n";
        echo "\n";
        exit(1);
    }
} else {
    // Browser usage (for initial setup)
    $email = $_GET['email'] ?? $_POST['email'] ?? null;
    $password = $_GET['password'] ?? $_POST['password'] ?? null;
    $firstName = $_GET['first_name'] ?? $_POST['first_name'] ?? null;
    $lastName = $_GET['last_name'] ?? $_POST['last_name'] ?? null;
    
    // Show form if no data provided
    if (!$email || !$password || !$firstName || !$lastName) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Create Super Admin</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .form-group { margin-bottom: 1rem; }
                label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
                input { width: 100%; padding: 0.5rem; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; }
                button { background: #2563eb; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
                button:hover { background: #1d4ed8; }
                .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
            </style>
        </head>
        <body>
            <h1>Create Super Admin User</h1>
            <div class="warning">
                <strong>Warning:</strong> This script should only be used for initial setup. 
                Remove or secure this file after creating your super admin user.
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <button type="submit">Create Super Admin</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

$db = getDbConnection();
$error = '';
$success = '';

try {
    // Validate password
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.");
    }
    
    // Check if user already exists
    $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // User exists - check if they already have superadmin role
        $stmt = $db->prepare("
            SELECT ur.id 
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ? AND r.name = 'superadmin'
        ");
        $stmt->execute([$existingUser['id']]);
        $hasSuperAdmin = $stmt->fetch();
        
        if ($hasSuperAdmin) {
            throw new Exception("User {$email} already has superadmin role.");
        } else {
            // Assign superadmin role to existing user
            $result = RBAC::assignRole($existingUser['id'], 'superadmin');
            if ($result['success']) {
                $success = "Superadmin role assigned to existing user: {$email}";
            } else {
                throw new Exception("Failed to assign superadmin role: " . $result['message']);
            }
        }
    } else {
        // Create new user
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Create user (superadmin doesn't need an organisation_id)
        $stmt = $db->prepare("
            INSERT INTO users (
                organisation_id, email, password_hash, first_name, last_name, 
                is_active, email_verified
            ) VALUES (NULL, ?, ?, ?, ?, TRUE, TRUE)
        ");
        $stmt->execute([$email, $passwordHash, $firstName, $lastName]);
        $userId = $db->lastInsertId();
        
        if (!$userId) {
            throw new Exception("Failed to create user.");
        }
        
        // Get superadmin role ID
        $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'superadmin'");
        $stmt->execute();
        $role = $stmt->fetch();
        
        if (!$role) {
            throw new Exception("Superadmin role not found in database. Please run the schema migration.");
        }
        
        // Assign superadmin role
        $stmt = $db->prepare("
            INSERT INTO user_roles (user_id, role_id, assigned_by)
            VALUES (?, ?, NULL)
        ");
        $stmt->execute([$userId, $role['id']]);
        
        $success = "Super admin user created successfully!";
    }
    
    // Output results
    if ($isCli) {
        echo "=== Super Admin User Created ===\n\n";
        if ($success) {
            echo "✓ {$success}\n";
        }
        echo "\nEmail: {$email}\n";
        echo "Name: {$firstName} {$lastName}\n";
        if (!$existingUser) {
            echo "User ID: {$userId}\n";
        }
        echo "\nYou can now log in with this account.\n";
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Super Admin Created</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .success { background: #d1fae5; border: 1px solid #10b981; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
                .error { background: #fee2e2; border: 1px solid #ef4444; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
                a { color: #2563eb; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <h1>Super Admin User</h1>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                </div>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></p>
                <p><a href="<?php echo url('login.php'); ?>">Go to Login Page</a></p>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    
    if ($isCli) {
        echo "Error: {$error}\n";
        exit(1);
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .error { background: #fee2e2; border: 1px solid #ef4444; padding: 1rem; border-radius: 4px; }
            </style>
        </head>
        <body>
            <h1>Error</h1>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <p><a href="create-superadmin.php">Try Again</a></p>
        </body>
        </html>
        <?php
    }
}

