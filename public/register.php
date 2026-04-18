<?php
// ============================================================
//  VentureIQ – Registration Page
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();

if (is_logged_in()) redirect('/dashboard.php');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!csrf_verify()) { $errors['csrf'] = 'Invalid form token. Please refresh.'; goto render; }

    // Rate limit by IP
    if (!rate_check(client_ip(), 'register')) {
        $errors['general'] = 'Too many registration attempts. Please wait 15 minutes.';
        goto render;
    }

    // reCAPTCHA
    $captchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (!recaptcha_verify($captchaToken)) {
        $errors['general'] = 'CAPTCHA verification failed. Please try again.';
        goto render;
    }

    // Sanitize & validate inputs
    $firstName = clean($_POST['first_name'] ?? '');
    $lastName  = clean($_POST['last_name']  ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password']         ?? '';
    $confirm   = $_POST['password_confirm'] ?? '';
    $old       = compact('firstName','lastName','email');

    if (!$firstName)          $errors['first_name'] = 'First name is required.';
    if (!$lastName)           $errors['last_name']  = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (strlen($password) < 8)  $errors['password'] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) $errors['password'] = 'Password must contain an uppercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors['password'] = 'Password must contain a number.';
    if ($password !== $confirm) $errors['password_confirm'] = 'Passwords do not match.';

    if (!$errors) {
        // Check duplicate email
        $existing = DB::one('SELECT id FROM users WHERE email=?', [$email]);
        if ($existing) { $errors['email'] = 'This email is already registered.'; goto render; }

        $hash       = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $verifyTok  = bin2hex(random_bytes(32));

        DB::insert(
            'INSERT INTO users (first_name,last_name,email,password_hash,verify_token,is_active,is_verified) VALUES (?,?,?,?,?,1,1)',
            [$firstName, $lastName, $email, $hash, $verifyTok]
        );
        rate_reset(client_ip(), 'register');
        audit('user.register', null, 'user', "email=$email");
        flash_set('success', 'Account created! Please sign in.');
        redirect('/login.php');
    }
}

render:
$pageTitle = 'Create Account';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card fade-in">
    <div class="auth-logo">
      <a href="<?= url('/') ?>" style="font-family:Poppins,sans-serif;font-size:1.4rem;font-weight:800;background:linear-gradient(135deg,#7c6dfa,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:inline-flex;align-items:center;gap:8px">⚡ VentureIQ</a>
    </div>
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-subtitle">Start your free assessment today</p>

    <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['csrf'])): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($errors['csrf']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/register.php') ?>" novalidate autocomplete="off" id="registerForm">
      <?= csrf_field() ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label class="form-label" for="first_name">First Name</label>
          <input class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                 type="text" id="first_name" name="first_name"
                 value="<?= htmlspecialchars($old['firstName'] ?? '') ?>"
                 placeholder="Aarav" required autocomplete="given-name">
          <?php if (isset($errors['first_name'])): ?>
          <div class="form-error">⚠ <?= htmlspecialchars($errors['first_name']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="last_name">Last Name</label>
          <input class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                 type="text" id="last_name" name="last_name"
                 value="<?= htmlspecialchars($old['lastName'] ?? '') ?>"
                 placeholder="Mehta" required autocomplete="family-name">
          <?php if (isset($errors['last_name'])): ?>
          <div class="form-error">⚠ <?= htmlspecialchars($errors['last_name']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">✉</span>
          <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                 type="email" id="email" name="email"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                 placeholder="you@example.com" required autocomplete="email">
        </div>
        <?php if (isset($errors['email'])): ?>
        <div class="form-error">⚠ <?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">🔒</span>
          <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                 type="password" id="password" name="password"
                 placeholder="Min 8 chars, 1 uppercase, 1 number" required autocomplete="new-password">
          <button type="button" class="input-toggle" data-toggle-pass="password">👁</button>
        </div>
        <div class="strength-bar" aria-label="Password strength indicator">
          <div class="strength-seg" id="s1"></div>
          <div class="strength-seg" id="s2"></div>
          <div class="strength-seg" id="s3"></div>
          <div class="strength-seg" id="s4"></div>
        </div>
        <?php if (isset($errors['password'])): ?>
        <div class="form-error">⚠ <?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirm">Confirm Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">🔒</span>
          <input class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                 type="password" id="password_confirm" name="password_confirm"
                 placeholder="Repeat your password" required autocomplete="new-password">
          <button type="button" class="input-toggle" data-toggle-pass="password_confirm">👁</button>
        </div>
        <?php if (isset($errors['password_confirm'])): ?>
        <div class="form-error">⚠ <?= htmlspecialchars($errors['password_confirm']) ?></div>
        <?php endif; ?>
      </div>

      <!-- reCAPTCHA v3 hidden token -->
      <input type="hidden" name="g-recaptcha-response" id="recaptchaToken">

      <div style="font-size:.78rem;color:var(--text3);margin-bottom:16px">
        By creating an account you agree to our <a href="<?= url('/terms.php') ?>">Terms of Service</a> and <a href="<?= url('/privacy.php') ?>">Privacy Policy</a>.
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;font-size:.95rem" id="submitBtn">
        Create Account →
      </button>
    </form>

    <div class="auth-switch">
      Already have an account? <a href="<?= url('/login.php') ?>">Sign in</a>
    </div>
  </div>
</div>

<script>
// Fire reCAPTCHA v3 on submit
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
  const siteKey = '<?= RECAPTCHA_SITE_KEY ?>';
  if (siteKey && siteKey !== 'YOUR_SITE_KEY_HERE') {
    e.preventDefault();
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = 'Verifying…';
    try {
      const token = await grecaptcha.execute(siteKey, { action: 'register' });
      document.getElementById('recaptchaToken').value = token;
      this.submit();
    } catch(err) {
      document.getElementById('submitBtn').disabled = false;
      document.getElementById('submitBtn').textContent = 'Create Account →';
    }
  }
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
