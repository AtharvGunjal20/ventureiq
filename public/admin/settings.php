<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

$flash = flash_get();
$pageTitle = 'Settings';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="admin-layout">
<aside class="sidebar">
  <div class="sidebar-section">Overview</div>
  <a href="<?= url('/admin/') ?>"              class="sidebar-link">📊 Dashboard</a>
  <a href="<?= url('/admin/analytics.php') ?>" class="sidebar-link">📈 Analytics</a>
  <div class="sidebar-section">Manage</div>
  <a href="<?= url('/admin/users.php') ?>"   class="sidebar-link">👥 Users</a>
  <a href="<?= url('/admin/results.php') ?>" class="sidebar-link">📋 Quiz Results</a>
  <a href="<?= url('/admin/models.php') ?>"  class="sidebar-link">🗂 Business Models</a>
  <div class="sidebar-section">System</div>
  <a href="<?= url('/admin/audit.php') ?>"    class="sidebar-link">🔍 Audit Log</a>
  <a href="<?= url('/admin/settings.php') ?>" class="sidebar-link active">⚙️ Settings</a>
  <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
  <a href="/" class="sidebar-link">← Back to Site</a>
  <a href="<?= url('/logout.php') ?>" class="sidebar-link" style="color:var(--red)">🚪 Sign Out</a>
</aside>

<div class="admin-content">
  <div style="margin-bottom:28px">
    <h1 style="font-size:1.4rem;margin-bottom:4px">Settings</h1>
    <p style="font-size:.83rem;color:var(--text3)">Platform configuration and environment info</p>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>" data-auto-dismiss><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- Environment Info -->
  <div class="card-elevated" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:1rem;margin-bottom:18px">⚙️ Environment</h3>
    <?php
    $checks = [
      ['PHP Version',     PHP_VERSION,                         version_compare(PHP_VERSION,'8.1.0','>=')],
      ['PDO MySQL',       extension_loaded('pdo_mysql')?'Enabled':'Missing', extension_loaded('pdo_mysql')],
      ['cURL',            extension_loaded('curl')?'Enabled':'Missing',      extension_loaded('curl')],
      ['OpenSSL',         extension_loaded('openssl')?'Enabled':'Missing',   extension_loaded('openssl')],
      ['App Environment', APP_ENV,                             APP_ENV==='production'],
      ['reCAPTCHA',       RECAPTCHA_SITE_KEY==='YOUR_SITE_KEY_HERE'?'Not configured':'Configured', RECAPTCHA_SITE_KEY!=='YOUR_SITE_KEY_HERE'],
      ['Secret Key',      SECRET_KEY==='CHANGE_THIS_TO_64_RANDOM_BYTES_IN_PRODUCTION'?'⚠ Default (change in production!)':'Custom', SECRET_KEY!=='CHANGE_THIS_TO_64_RANDOM_BYTES_IN_PRODUCTION'],
    ];
    foreach ($checks as [$label, $value, $ok]):
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
      <span style="font-size:.88rem;color:var(--text2)"><?= $label ?></span>
      <span style="font-size:.85rem;font-weight:500;color:<?= $ok?'var(--green)':'var(--amber)' ?>"><?= htmlspecialchars($value) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Config Reference -->
  <div class="card-elevated" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:1rem;margin-bottom:6px">📝 Configuration</h3>
    <p style="font-size:.83rem;color:var(--text3);margin-bottom:16px">Edit <code style="background:var(--bg3);padding:2px 6px;border-radius:4px;font-size:.8rem">/config/config.php</code> to update these settings.</p>
    <?php
    $config = [
      ['APP_URL',            APP_URL],
      ['APP_ENV',            APP_ENV],
      ['DB_HOST',            DB_HOST],
      ['DB_NAME',            DB_NAME],
      ['SESSION_LIFETIME',   SESSION_LIFETIME.'s ('.round(SESSION_LIFETIME/60).' min)'],
      ['LOGIN_MAX_ATTEMPTS', LOGIN_MAX_ATTEMPTS.' per '.round(LOGIN_WINDOW_SECS/60).' min'],
      ['BCRYPT_COST',        BCRYPT_COST],
      ['RECAPTCHA_THRESHOLD',RECAPTCHA_THRESHOLD],
    ];
    foreach ($config as [$key, $val]):
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border)">
      <code style="font-size:.8rem;color:var(--purple3)"><?= $key ?></code>
      <span style="font-size:.82rem;color:var(--text3)"><?= htmlspecialchars($val) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- DB Stats -->
  <div class="card-elevated" style="padding:24px">
    <h3 style="font-size:1rem;margin-bottom:18px">🗄️ Database Statistics</h3>
    <?php
    $dbStats = [
      ['Users',            DB::one('SELECT COUNT(*) as n FROM users')['n']],
      ['Quiz Results',     DB::one('SELECT COUNT(*) as n FROM quiz_results')['n']],
      ['Business Models',  DB::one('SELECT COUNT(*) as n FROM business_models')['n']],
      ['Audit Events',     DB::one('SELECT COUNT(*) as n FROM audit_log')['n']],
      ['Active Sessions',  DB::one('SELECT COUNT(*) as n FROM user_sessions WHERE expires_at > NOW()')['n']],
    ];
    foreach ($dbStats as [$label,$val]):
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border)">
      <span style="font-size:.88rem;color:var(--text2)"><?= $label ?></span>
      <span class="badge badge-purple"><?= number_format($val) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

</div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
