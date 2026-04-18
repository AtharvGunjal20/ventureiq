<?php
// ============================================================
//  VentureIQ – Forgot Password
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();

$sent   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors['general'] = 'Invalid form token.'; goto render; }
    if (!rate_check(client_ip(), 'forgot')) { $errors['general'] = 'Too many requests. Wait 15 minutes.'; goto render; }

    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email.'; goto render; }

    $user = DB::one('SELECT id FROM users WHERE email=? AND is_active=1', [$email]);
    if ($user) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        DB::run('UPDATE users SET reset_token=?,reset_expires=? WHERE id=?', [$token, $expires, $user['id']]);
        // In production: send email with reset link
        // mail($email, 'Reset your VentureIQ password', APP_URL.'/reset.php?token='.$token);
        audit('password.reset_request', $user['id'], 'user');
    }
    // Always show success to prevent user enumeration
    $sent = true;
}

render:
$pageTitle = 'Forgot Password';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card fade-in">
    <div class="auth-logo">
      <a href="<?= url('/') ?>" style="font-family:Poppins,sans-serif;font-size:1.4rem;font-weight:800;background:linear-gradient(135deg,#7c6dfa,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:inline-flex;align-items:center;gap:8px">⚡ VentureIQ</a>
    </div>
    <h1 class="auth-title">Reset password</h1>
    <p class="auth-subtitle">Enter your email and we'll send a reset link</p>

    <?php if ($sent): ?>
    <div class="alert alert-success">
      ✓ If that email is registered, a reset link has been sent. Check your inbox.
    </div>
    <div style="text-align:center;margin-top:16px">
      <a href="<?= url('/login.php') ?>" class="btn btn-ghost" style="width:100%;justify-content:center">← Back to Sign In</a>
    </div>
    <?php else: ?>

    <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">✉</span>
          <input class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>" type="email" name="email" placeholder="you@example.com" required autofocus>
        </div>
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
      </div>
      <button class="btn btn-primary" style="width:100%" type="submit">Send Reset Link</button>
    </form>
    <div class="auth-switch" style="margin-top:20px">
      <a href="<?= url('/login.php') ?>">← Back to Sign In</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
