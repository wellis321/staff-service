<?php
// Temporary — delete after use
if (($_GET['t'] ?? '') !== 'sunrise') { http_response_code(404); exit; }

require_once dirname(__DIR__) . '/config/config.php';
$db = getDbConnection();

// Set a simple password with no special characters
$newPassword = 'SunriseCare1';
$newHash     = password_hash($newPassword, PASSWORD_DEFAULT);

$db->prepare("UPDATE users SET password_hash = ? WHERE email LIKE '%sunrisecare.demo%'")
   ->execute([$newHash]);

// Verify it stuck
$stmt = $db->prepare("SELECT email, password_hash FROM users WHERE email = 'sarah.johnson@sunrisecare.demo'");
$stmt->execute();
$u = $stmt->fetch();

echo '<pre>';
echo "Password set to: $newPassword\n";
echo "Verify: " . (password_verify($newPassword, $u['password_hash']) ? 'TRUE ✓' : 'FALSE ✗') . "\n";
echo "Auth::login test: ";
var_dump(Auth::login('sarah.johnson@sunrisecare.demo', $newPassword));
echo '</pre>';
echo '<p>Now try logging in with: sarah.johnson@sunrisecare.demo / SunriseCare1</p>';
