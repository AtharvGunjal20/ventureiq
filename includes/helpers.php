<?php
// ============================================================
//  VentureIQ – Core Helpers
// ============================================================
if (!defined('ROOT_PATH')) require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/config/db.php';

// ── Session ───────────────────────────────────────────────────
function session_secure_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('VIQSESS');
        session_start();
    }
}

// ── CSRF ──────────────────────────────────────────────────────
function csrf_token(): string {
    session_secure_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── Auth ──────────────────────────────────────────────────────
function auth_user(): ?array {
    session_secure_start();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return auth_user() !== null;
}

function is_admin(): bool {
    return (auth_user()['role'] ?? '') === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('<h1>403 Access Denied</h1>');
    }
}

// ── Redirect ──────────────────────────────────────────────────
// Always pass root-relative path like '/login.php'
// BASE_PATH prefix is added automatically
function redirect(string $path, int $code = 302): never {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header("Location: $path", true, $code);
    } else {
        header('Location: ' . url($path), true, $code);
    }
    exit;
}

// ── Rate Limiting ─────────────────────────────────────────────
function rate_check(string $ip, string $action = 'login'): bool {
    try {
        DB::run('DELETE FROM rate_limit WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)', [LOGIN_WINDOW_SECS]);
        $row = DB::one('SELECT attempts FROM rate_limit WHERE ip_address=? AND action=?', [$ip, $action]);
        if (!$row) {
            DB::run('INSERT INTO rate_limit (ip_address, action, attempts) VALUES (?,?,1)', [$ip, $action]);
            return true;
        }
        if ($row['attempts'] >= LOGIN_MAX_ATTEMPTS) return false;
        DB::run('UPDATE rate_limit SET attempts=attempts+1 WHERE ip_address=? AND action=?', [$ip, $action]);
        return true;
    } catch (Exception $e) {
        return true; // fail open on DB error
    }
}

function rate_reset(string $ip, string $action = 'login'): void {
    try { DB::run('DELETE FROM rate_limit WHERE ip_address=? AND action=?', [$ip, $action]); } catch(Exception $e){}
}

// ── reCAPTCHA ─────────────────────────────────────────────────
function recaptcha_verify(string $token): bool {
    // Auto-bypass on localhost / dev
    if (RECAPTCHA_SECRET_KEY === 'YOUR_SECRET_KEY_HERE') return true;
    if (APP_ENV === 'development') return true;
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => client_ip(),
        ]),
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return ($response['success'] ?? false) && ($response['score'] ?? 0) >= RECAPTCHA_THRESHOLD;
}

// ── Sanitize ──────────────────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '127.0.0.1';
}

// ── Audit Log ─────────────────────────────────────────────────
function audit(string $action, $targetId = null, ?string $targetType = null, ?string $detail = null): void {
    try {
        $userId = auth_user()['id'] ?? null;
        DB::run(
            'INSERT INTO audit_log (user_id,action,target_type,target_id,detail,ip_address) VALUES (?,?,?,?,?,?)',
            [$userId, $action, $targetType, $targetId, $detail, client_ip()]
        );
    } catch (Exception $e) {}
}

// ── JSON Response ─────────────────────────────────────────────
function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Flash Messages ────────────────────────────────────────────
function flash_set(string $type, string $msg): void {
    session_secure_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
    session_secure_start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
