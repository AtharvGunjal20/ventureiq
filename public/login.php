<?php
// ============================================================
//  VentureIQ – Login Page
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();

if (is_logged_in()) redirect('/dashboard.php');

$errors   = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $errors['general'] = 'Invalid session token. Please refresh.';
        goto render;
    }

    $ip    = client_ip();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $oldEmail = htmlspecialchars($email);

    if (!rate_check($ip, 'login')) {
        $errors['general'] = 'Too many login attempts. Please wait 15 minutes.';
        goto render;
    }

    if (!$email || !$pass) {
        $errors['general'] = 'Email and password are required.';
        goto render;
    }

    $user = DB::one('SELECT * FROM users WHERE email=?', [$email]);

    $validHash = $user ? $user['password_hash'] : '$2y$12$invalidhashpadding000000000000000000000000000000000000000';
    $valid     = password_verify($pass, $validHash) && $user;

    if (!$valid) {
        $errors['general'] = 'Invalid email or password.';
        audit('login.fail', null, 'user', "email=$email");
        goto render;
    }

    if (!$user['is_active']) {
        $errors['general'] = 'Your account is suspended. Contact support.';
        goto render;
    }

    // Success
    session_regenerate_id(true);
    rate_reset($ip, 'login');

    $_SESSION['user'] = [
        'id'         => $user['id'],
        'uuid'       => $user['uuid'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
    ];

    DB::run('UPDATE users SET last_login=NOW(), login_attempts=0 WHERE id=?', [$user['id']]);
    audit('login.success', $user['id'], 'user');

    // Redirect based on role
    if ($user['role'] === 'admin') {
        redirect('/admin/index.php');
    } else {
        redirect('/dashboard.php');
    }
}

render:
$pageTitle = 'Sign In';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card fade-in">

    <div class="auth-logo">
      <a href="<?= url('/') ?>" style="font-family:Poppins,sans-serif;font-size:1.4rem;font-weight:800;
         background:linear-gradient(135deg,#7c6dfa,#00d4aa);-webkit-background-clip:text;
         -webkit-text-fill-color:transparent;display:inline-flex;align-items:center;gap:8px">
        ⚡ VentureIQ
      </a>
    </div>

    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your account</p>

    <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/login.php') ?>">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label">Email address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">✉</span>
          <input class="form-control" type="email" name="email"
                 value="<?= $oldEmail ?>"
                 placeholder="you@example.com" required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
          <label class="form-label" style="margin:0">Password</label>
          <a href="<?= url('/forgot.php') ?>" style="font-size:.78rem;color:var(--text3)">Forgot password?</a>
        </div>
        <div class="input-icon-wrap">
          <span class="input-icon">🔒</span>
          <input class="form-control" type="password" id="password" name="password"
                 placeholder="Your password" required autocomplete="current-password">
          <button type="button" class="input-toggle" data-toggle-pass="password">👁</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;font-size:.95rem;margin-top:4px">
        Sign In →
      </button>
    </form>

    <div class="auth-switch" style="margin-top:20px">
      Don't have an account? <a href="<?= url('/register.php') ?>">Create one free</a>
    </div>

    <div style="margin-top:20px;padding:12px;background:rgba(124,109,250,.08);
        border:1px solid rgba(124,109,250,.2);border-radius:10px;font-size:.75rem;
        color:var(--text3);text-align:center">
      <strong style="color:var(--purple3)">Demo Admin:</strong>
      admin@ventureiq.com / Admin@123
    </div>

  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
