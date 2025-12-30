<?php
/**
 * Create Super Admin User Script
 * Helper script to create a super admin user
 * 
 * WARNING: Delete this file after creating your super admin user!
 * 
 * Usage:
 *   Visit this page in your browser and fill in the form
 */

require_once dirname(__DIR__) . '/config/config.php';

// Get input
$email = $_GET['email'] ?? $_POST['email'] ?? null;
$password = $_GET['password'] ?? $_POST['password'] ?? null;
$firstName = $_GET['first_name'] ?? $_POST['first_name'] ?? null;
$lastName = $_GET['last_name'] ?? $_POST['last_name'] ?? null;

$db = getDbConnection();
$error = '';
$success = '';

// Show form if no data provided
if (!$email || !$password || !$firstName || !$lastName) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Super Admin</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f9fafb; }
            .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .form-group { margin-bottom: 1.5rem; }
            label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #374151; }
            input { width: 100%; padding: 0.75rem; font-size: 1rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
            input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
            button { background: #2563eb; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; width: 100%; }
            button:hover { background: #1d4ed8; }
            .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; color: #92400e; }
            .success { background: #d1fae5; border: 1px solid #10b981; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; color: #065f46; }
            .error { background: #fee2e2; border: 1px solid #ef4444; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; color: #991b1b; }
            a { color: #2563eb; text-decoration: none; }
            a:hover { text-decoration: underline; }
            h1 { color: #1f2937; margin-top: 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Create Super Admin User</h1>
            <div class="warning">
                <strong>⚠️ Security Warning:</strong> This script should only be used for initial setup. 
                <strong>Delete this file immediately after creating your super admin user!</strong>
            </div>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small style="color: #6b7280; font-size: 0.875rem;">Minimum 8 characters</small>
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
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Process form submission
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
    
    // Show success page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Super Admin Created</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f9fafb; }
            .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .success { background: #d1fae5; border: 1px solid #10b981; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; color: #065f46; }
            .error { background: #fee2e2; border: 1px solid #ef4444; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; color: #991b1b; }
            .info { background: #eff6ff; border: 1px solid #3b82f6; padding: 1rem; border-radius: 4px; margin-top: 1rem; color: #1e40af; }
            a { color: #2563eb; text-decoration: none; font-weight: 600; }
            a:hover { text-decoration: underline; }
            h1 { color: #1f2937; margin-top: 0; }
            p { color: #374151; line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Super Admin User</h1>
            <?php if ($error): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <p><a href="create-superadmin.php">Try Again</a></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <strong>✓ Success!</strong> <?php echo htmlspecialchars($success); ?>
                </div>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></p>
                <div class="info">
                    <strong>⚠️ Important:</strong> Please delete this file (create-superadmin.php) immediately for security!
                </div>
                <p style="margin-top: 1.5rem;">
                    <a href="<?php echo url('login.php'); ?>">Go to Login Page</a>
                </p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    $error = $e->getMessage();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f9fafb; }
            .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .error { background: #fee2e2; border: 1px solid #ef4444; padding: 1rem; border-radius: 4px; color: #991b1b; }
            a { color: #2563eb; text-decoration: none; font-weight: 600; }
            a:hover { text-decoration: underline; }
            h1 { color: #1f2937; margin-top: 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Error</h1>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <p style="margin-top: 1rem;"><a href="create-superadmin.php">Try Again</a></p>
        </div>
    </body>
    </html>
    <?php
}

