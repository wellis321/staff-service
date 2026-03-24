<?php
// Temporary diagnostic — delete after use
if (($_GET['t'] ?? '') !== 'sunrise') { http_response_code(404); exit; }

require_once dirname(__DIR__) . '/config/config.php';
$db = getDbConnection();

$stmt = $db->prepare("SELECT id, email, is_active, email_verified, password_hash FROM users WHERE email LIKE '%sunrisecare.demo%' ORDER BY id");
$stmt->execute();
$users = $stmt->fetchAll();

$testPassword = 'Sunrise2024!';

echo '<pre>';
echo 'PHP version: ' . phpversion() . "\n\n";
foreach ($users as $u) {
    $match = password_verify($testPassword, $u['password_hash']);
    echo $u['email'] . "\n";
    echo '  id=' . $u['id'] . ' active=' . $u['is_active'] . ' verified=' . $u['email_verified'] . "\n";
    echo '  hash=' . $u['password_hash'] . "\n";
    echo '  password_verify("' . $testPassword . '"): ' . ($match ? 'TRUE ✓' : 'FALSE ✗') . "\n\n";
}
echo '</pre>';
