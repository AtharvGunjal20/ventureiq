<?php
// ============================================================
//  VentureIQ – User Profile
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_login();

$user   = auth_user();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash_set('error','Invalid CSRF token'); redirect('/profile.php'); }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fn = clean($_POST['first_name'] ?? '');
        $ln = clean($_POST['last_name']  ?? '');
        $em = strtolower(trim($_POST['email'] ?? ''));
        if (!$fn) $errors['first_name'] = 'Required.';
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
        if (!$errors) {
            // Check email not taken by another user
            $exists = DB::one('SELECT id FROM users WHERE email=? AND id!=?', [$em, $user['id']]);
            if ($exists) { $errors['email'] = 'Email already in use.'; }
            else {
                DB::run('UPDATE users SET first_name=?,last_name=?,email=? WHERE id=?', [$fn,$ln,$em,$user['id']]);
                // Refresh session
                $_SESSION['user']['first_name'] = $fn;
                $_SESSION['user']['last_name']  = $ln;
                $_SESSION['user']['email']       = $em;
                audit('profile.update', $user['id'], 'user');
                $success = 'Profile updated successfully.';
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $dbUser  = DB::one('SELECT password_hash FROM users WHERE id=?', [$user['id']]);
        if (!password_verify($current, $dbUser['password_hash'])) $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($new) < 8)  $errors['new_password'] = 'Min 8 characters.';
        if ($new !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
        if (!$errors) {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>BCRYPT_COST]);
            DB::run('UPDATE users SET password_hash=? WHERE id=?', [$hash, $user['id']]);
            audit('profile.password_change', $user['id'], 'user');
            $success = 'Password changed successfully.';
        }
    }
}

$dbUser  = DB::one('SELECT * FROM users WHERE id=?', [$user['id']]);
$pageTitle = 'My Profile';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container-sm" style="padding-top:48px;padding-bottom:80px">

  <div style="margin-bottom:32px">
    <div class="label">Account Settings</div>
    <h1 style="font-size:1.6rem">My Profile</h1>
  </div>

  <?php if ($success): ?>
  <div class="alert alert-success" data-auto-dismiss>✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Avatar + Name -->
  <div style="display:flex;align-items:center;gap:20px;margin-bottom:32px;padding:24px;background:var(--glass);border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#7c6dfa,#4f9fff);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:800;color:white;flex-shrink:0">
      <?= strtoupper(substr($dbUser['first_name'],0,1).substr($dbUser['last_name'],0,1)) ?>
    </div>
    <div>
      <div style="font-size:1.1rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($dbUser['first_name'].' '.$dbUser['last_name']) ?></div>
      <div style="font-size:.84rem;color:var(--text3)"><?= htmlspecialchars($dbUser['email']) ?></div>
      <div style="font-size:.75rem;color:var(--text4);margin-top:3px">Member since <?= date('M Y', strtotime($dbUser['created_at'])) ?></div>
    </div>
  </div>

  <!-- Profile Form -->
  <div class="card-elevated" style="padding:28px;margin-bottom:20px">
    <h3 style="margin-bottom:20px;font-size:1rem">Personal Information</h3>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label class="form-label">First Name</label>
          <input class="form-control <?= isset($errors['first_name'])?'is-invalid':'' ?>" name="first_name" value="<?= htmlspecialchars($dbUser['first_name']) ?>" required>
          <?php if (isset($errors['first_name'])): ?><div class="form-error"><?= $errors['first_name'] ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name</label>
          <input class="form-control" name="last_name" value="<?= htmlspecialchars($dbUser['last_name']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">✉</span>
          <input class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>" type="email" name="email" value="<?= htmlspecialchars($dbUser['email']) ?>" required>
        </div>
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= $errors['email'] ?></div><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>

  <!-- Password Form -->
  <div class="card-elevated" style="padding:28px;margin-bottom:20px">
    <h3 style="margin-bottom:20px;font-size:1rem">Change Password</h3>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">🔒</span>
          <input class="form-control <?= isset($errors['current_password'])?'is-invalid':'' ?>" type="password" name="current_password" required>
          <button type="button" class="input-toggle" data-toggle-pass="current_password" id="current_password">👁</button>
        </div>
        <?php if (isset($errors['current_password'])): ?><div class="form-error"><?= $errors['current_password'] ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">🔒</span>
          <input class="form-control <?= isset($errors['new_password'])?'is-invalid':'' ?>" type="password" name="new_password" id="password" placeholder="Min 8 characters" required>
        </div>
        <div class="strength-bar"><div class="strength-seg"></div><div class="strength-seg"></div><div class="strength-seg"></div><div class="strength-seg"></div></div>
        <?php if (isset($errors['new_password'])): ?><div class="form-error"><?= $errors['new_password'] ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input class="form-control <?= isset($errors['confirm_password'])?'is-invalid':'' ?>" type="password" name="confirm_password" required>
        <?php if (isset($errors['confirm_password'])): ?><div class="form-error"><?= $errors['confirm_password'] ?></div><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </div>

  <!-- Danger Zone -->
  <div style="padding:20px;border:1px solid rgba(255,107,107,.2);border-radius:var(--radius);background:rgba(255,107,107,.04)">
    <h3 style="font-size:.95rem;color:var(--red);margin-bottom:8px">⚠ Danger Zone</h3>
    <p style="font-size:.84rem;margin-bottom:14px">Deleting your account is permanent and cannot be undone.</p>
    <form method="POST" onsubmit="return confirm('Are you sure? All your data will be permanently deleted.')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_account">
      <button class="btn btn-danger btn-sm" type="submit">Delete My Account</button>
    </form>
  </div>

  <div style="margin-top:20px">
    <a href="<?= url('/dashboard.php') ?>" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
