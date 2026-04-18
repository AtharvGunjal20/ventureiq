<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/db.php';

// Delete old admin and create fresh one
DB::run("DELETE FROM users WHERE email = 'admin@ventureiq.com'");

$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);

DB::run("INSERT INTO users (uuid, first_name, last_name, email, password_hash, role, is_active, is_verified) VALUES (UUID(), 'Admin', 'VentureIQ', 'admin@ventureiq.com', '$hash', 'admin', 1, 1)");

$user = DB::one("SELECT * FROM users WHERE email = 'admin@ventureiq.com'");

echo "<h2>Admin Reset Done!</h2>";
echo "Email: " . $user['email'] . "<br>";
echo "Role: " . $user['role'] . "<br>";
echo "Active: " . $user['is_active'] . "<br>";
echo "Hash: " . $user['password_hash'] . "<br><br>";

$test = password_verify('admin123', $user['password_hash']);
echo $test ? "<b style='color:green'>Password verify: OK ✅</b>" : "<b style='color:red'>Password verify: FAILED ❌</b>";
echo "<br><br>";
echo "<b>Now login with:</b><br>";
echo "Email: admin@ventureiq.com<br>";
echo "Password: admin123";
?>