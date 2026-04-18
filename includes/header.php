<?php
// ============================================================
//  VentureIQ – HTML Header Partial (BASE_PATH-aware)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
session_secure_start();

$flash     = flash_get();
$user      = auth_user();
$pageTitle = isset($pageTitle) ? $pageTitle . ' – ' . APP_NAME : APP_NAME . ' | ' . APP_TAGLINE;
$bodyClass = $bodyClass ?? '';
$csrf      = csrf_token();
$B         = BASE_PATH; // e.g. '/ventureiq/public' or '' on production
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf"      content="<?= $csrf ?>">
  <meta name="base-path" content="<?= $B ?>">
  <meta name="description" content="VentureIQ – AI-powered e-commerce startup planner. Find your ideal business model in 3 minutes.">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $B ?>/css/main.css">
  <?php if (!empty($loadCharts)): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <?php endif; ?>
  <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_SITE_KEY_HERE'): ?>
  <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>" defer></script>
  <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">

<script>window.BASE_PATH = <?= json_encode($B) ?>;</script>

<a href="#main" style="position:absolute;top:-9999px;left:-9999px">Skip to content</a>

<nav class="navbar" role="navigation" aria-label="Main navigation">
  <div class="container">
    <a href="<?= url('/') ?>" class="logo">⚡ VentureIQ</a>

    <button id="navToggle" onclick="document.getElementById('navMenu').classList.toggle('nav-open')"
      style="display:none;background:none;border:1px solid var(--border2);color:var(--text2);padding:6px 10px;border-radius:8px;cursor:pointer;font-size:1rem">☰</button>

    <div class="nav-links" id="navMenu" role="menubar">
      <a href="<?= url('/') ?>"            class="nav-link">Home</a>
      <a href="<?= url('/explore.php') ?>" class="nav-link">Explore</a>
      <a href="<?= url('/quiz.php') ?>"    class="nav-link">Quiz</a>
      <a href="<?= url('/how.php') ?>"     class="nav-link">How It Works</a>

      <?php if ($user): ?>
        <div style="position:relative;margin-left:8px">
          <div class="nav-avatar" data-dropdown-trigger="user-dd" role="button" tabindex="0">
            <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
          </div>
          <div class="nav-dropdown" id="user-dd" role="menu">
            <div style="padding:12px 14px;border-bottom:1px solid var(--border)">
              <div style="font-size:.85rem;font-weight:600;color:var(--text)"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
              <div style="font-size:.73rem;color:var(--text3)"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <a href="<?= url('/dashboard.php') ?>" class="dropdown-item">📊 My Dashboard</a>
            <a href="<?= url('/profile.php') ?>"   class="dropdown-item">👤 Profile</a>
            <?php if ($user['role']==='admin'): ?>
            <hr class="dropdown-divider">
            <a href="<?= url('/admin/') ?>" class="dropdown-item">⚙️ Admin Panel</a>
            <?php endif; ?>
            <hr class="dropdown-divider">
            <a href="<?= url('/logout.php') ?>" class="dropdown-item" style="color:var(--red)">🚪 Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= url('/login.php') ?>"    class="nav-link" style="margin-left:4px">Sign In</a>
        <a href="<?= url('/register.php') ?>" class="btn btn-primary btn-sm" style="margin-left:4px">Get Started</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<?php if ($flash): ?>
<div class="container" style="margin-top:16px">
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" data-auto-dismiss role="alert">
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
</div>
<?php endif; ?>

<main id="main">
