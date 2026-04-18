<?php
// ============================================================
//  VentureIQ – Configuration
//  For localhost: BASE_PATH = '/ventureiq/public'
//  For live domain: BASE_PATH = ''
// ============================================================

define('APP_NAME',    'VentureIQ');
define('APP_TAGLINE', 'AI-Powered E-Commerce Startup Planner');
define('APP_ENV',     'development');

// ── SET YOUR BASE PATH HERE ──────────────────────────────────
// localhost with XAMPP:  '/ventureiq/public'
// Live domain root:      ''
define('BASE_PATH', '/ventureiq/public');
define('APP_URL',   'http://localhost' . BASE_PATH);  // ← ADD THIS LINE HERE

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'ventureiq');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');

// ── Paths (filesystem) ───────────────────────────────────────
if (!defined('ROOT_PATH'))   define('ROOT_PATH',   dirname(__DIR__));
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', __DIR__);
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . '/public');
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// ── Security ─────────────────────────────────────────────────
define('SECRET_KEY',       'ventureiq_dev_secret_key_change_in_production');
define('SESSION_LIFETIME',  7200);
define('BCRYPT_COST',       12);
define('CSRF_TOKEN_NAME',   '_csrf');

// ── Rate Limiting ─────────────────────────────────────────────
define('LOGIN_MAX_ATTEMPTS',  5);
define('LOGIN_WINDOW_SECS',   900);
define('LOGIN_LOCKOUT_SECS',  1800);

// ── reCAPTCHA (leave as-is for localhost — auto bypassed) ────
define('RECAPTCHA_SITE_KEY',   'YOUR_SITE_KEY_HERE');
define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY_HERE');
define('RECAPTCHA_THRESHOLD',  0.5);

// ── Mail ─────────────────────────────────────────────────────
define('MAIL_FROM',      'noreply@ventureiq.com');
define('MAIL_FROM_NAME', 'VentureIQ');

// ── URL Helper ───────────────────────────────────────────────
// Use url('/page.php') everywhere instead of hardcoded paths
function url(string $path = ''): string {
    return BASE_PATH . '/' . ltrim($path, '/');
}

// ── PHP Error Display ────────────────────────────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');
