<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('NAVTOOL_ADMIN');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax'
    ]);
    session_start();
}

function setting(string $key, string $default=''): string {
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['setting_value'] : $default;
}
function set_setting(string $key, string $value): void {
    $stmt = db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $stmt->execute([$key,$value]);
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); exit('CSRF token invalid');
    }
}
function admin_required(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php'); exit;
    }
}
function admin_log(string $action, string $details=''): void {
    $stmt=db()->prepare('INSERT INTO admin_log(admin_id,action,details) VALUES(?,?,?)');
    $stmt->execute([$_SESSION['admin_id'] ?? null,$action,$details]);
}
function ua_device(string $ua): string {
    $u=strtolower($ua);
    if (preg_match('/ipad|tablet|android(?!.*mobile)/i',$u)) return 'Tablet';
    if (preg_match('/iphone|ipod|android.*mobile|mobile/i',$u)) return 'Mobile';
    if (preg_match('/macintosh|mac os x/i',$u)) return 'Mac';
    if (preg_match('/windows/i',$u)) return 'Windows';
    if (preg_match('/linux/i',$u)) return 'Linux';
    return 'Other';
}
function ua_browser(string $ua): string {
    $u=strtolower($ua);
    if (str_contains($u,'edg/')) return 'Edge';
    if (str_contains($u,'chrome/') && !str_contains($u,'edg/')) return 'Chrome';
    if (str_contains($u,'firefox/')) return 'Firefox';
    if (str_contains($u,'safari/') && !str_contains($u,'chrome/')) return 'Safari';
    if (str_contains($u,'opr/')) return 'Opera';
    return 'Other';
}
function clean_referrer(?string $ref): ?string {
    if (!$ref) return null;
    $p=parse_url($ref);
    if (!$p || empty($p['host'])) return null;
    $scheme=isset($p['scheme']) ? $p['scheme'].'://' : '';
    return substr($scheme.$p['host'],0,500);
}
function visitor_hash(): string {
    $ip=$_SERVER['REMOTE_ADDR'] ?? '';
    return hash('sha256',$ip.'|'.ANALYTICS_SALT.'|'.gmdate('Y-m-d'));
}
function session_hash(): string {
    $sid=session_id();
    if (!$sid) $sid=bin2hex(random_bytes(16));
    return hash('sha256',$sid.'|'.ANALYTICS_SALT);
}
function is_online(): bool { return setting('site_online','1')==='1'; }
