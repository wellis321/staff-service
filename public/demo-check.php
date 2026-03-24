<?php
// Temporary diagnostic — delete after use
if (($_GET['t'] ?? '') !== 'sunrise') { http_response_code(404); exit; }

require_once dirname(__DIR__) . '/config/config.php';
$db = getDbConnection();

$testEmail    = 'sarah.johnson@sunrisecare.demo';
$testPassword = 'Sunrise2024!';

echo '<pre>';
echo 'PHP: ' . phpversion() . "\n\n";

// 1. Raw DB check
$stmt = $db->prepare("SELECT id, email, is_active, email_verified, password_hash FROM users WHERE email = ?");
$stmt->execute([$testEmail]);
$u = $stmt->fetch();

if (!$u) {
    echo "USER NOT FOUND in DB for: $testEmail\n";
} else {
    echo "DB row found: id={$u['id']} active={$u['is_active']} verified={$u['email_verified']}\n";
    echo "Hash: {$u['password_hash']}\n";
    echo "password_verify: " . (password_verify($testPassword, $u['password_hash']) ? 'TRUE ✓' : 'FALSE ✗') . "\n\n";
}

// 2. Regenerate and store fresh hash
$fresh = password_hash($testPassword, PASSWORD_DEFAULT);
$db->prepare("UPDATE users SET password_hash = ? WHERE email LIKE '%sunrisecare.demo%'")->execute([$fresh]);
echo "Fresh hash written: $fresh\n";
echo "Fresh verify: " . (password_verify($testPassword, $fresh) ? 'TRUE ✓' : 'FALSE ✗') . "\n\n";

// 3. Re-read from DB immediately
$stmt->execute([$testEmail]);
$u2 = $stmt->fetch();
echo "Re-read from DB: " . (password_verify($testPassword, $u2['password_hash']) ? 'TRUE ✓' : 'FALSE ✗') . "\n";
echo "Stored hash after update: {$u2['password_hash']}\n\n";

// 4. Call Auth::login directly
echo "Auth::login result: ";
$result = Auth::login($testEmail, $testPassword);
var_dump($result);

echo '</pre>';
