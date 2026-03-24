<?php
// Temporary diagnostic + password reset — delete after use
if (($_GET['t'] ?? '') !== 'sunrise') { http_response_code(404); exit; }

require_once dirname(__DIR__) . '/config/config.php';
$db = getDbConnection();

$testPassword = 'Sunrise2024!';
$newHash = password_hash($testPassword, PASSWORD_DEFAULT);

// Reset all demo passwords using a server-generated hash
$db->prepare("UPDATE users SET password_hash = ? WHERE email LIKE '%sunrisecare.demo%'")
   ->execute([$newHash]);

$stmt = $db->prepare("SELECT id, email, is_active, email_verified, password_hash FROM users WHERE email LIKE '%sunrisecare.demo%' ORDER BY id");
$stmt->execute();
$users = $stmt->fetchAll();

echo '<pre>';
echo 'PHP version: ' . phpversion() . "\n";
echo 'New hash generated on this server: ' . $newHash . "\n\n";
foreach ($users as $u) {
    $match = password_verify($testPassword, $u['password_hash']);
    echo $u['email'] . "\n";
    echo '  password_verify: ' . ($match ? 'TRUE ✓' : 'FALSE ✗') . "\n\n";
}
echo '</pre>';
