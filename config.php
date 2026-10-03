<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('APP_ROOT', __DIR__);

function load_app_config(): array
{
    $defaultRuntimePath = APP_ROOT . DIRECTORY_SEPARATOR . 'storage';
    $config = [
        'db_host' => getenv('HOSPITAL_DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com',
        'db_port' => (int) (getenv('HOSPITAL_DB_PORT') ?: 4000),
        'db_name' => getenv('HOSPITAL_DB_NAME') ?: 'benhvien_support',
        'db_user' => getenv('HOSPITAL_DB_USER') ?: '7mi6REnua6JsV4r.root',
        'db_password' => getenv('HOSPITAL_DB_PASSWORD') ?: 'IaD5avauzefIXvEs',
        'db_ssl' => filter_var(getenv('HOSPITAL_DB_SSL') ?: true, FILTER_VALIDATE_BOOL),
        'db_ssl_ca' => getenv('HOSPITAL_DB_SSL_CA') ?: '',
        'runtime_path' => getenv('HOSPITAL_RUNTIME_PATH') ?: $defaultRuntimePath,
        'backup_path' => getenv('HOSPITAL_BACKUP_PATH') ?: (APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups'),
        'extra_backup_paths' => getenv('HOSPITAL_EXTRA_BACKUP_PATHS') ?: '',
        'trusted_proxies' => [],
        'force_https' => filter_var(getenv('HOSPITAL_FORCE_HTTPS') ?: true, FILTER_VALIDATE_BOOL),
        'https_port' => (int) (getenv('HOSPITAL_HTTPS_PORT') ?: 443),
        'session_lifetime' => 420,
        'smtp_host' => getenv('HOSPITAL_SMTP_HOST') ?: 'smtp.gmail.com',
        'smtp_port' => (int) (getenv('HOSPITAL_SMTP_PORT') ?: 465),
        'smtp_secure' => getenv('HOSPITAL_SMTP_SECURE') ?: 'ssl',
        'smtp_username' => getenv('HOSPITAL_SMTP_USERNAME') ?: 'pcnttphongkhamdakhoaphuthai@gmail.com',
        'smtp_password' => getenv('HOSPITAL_SMTP_PASSWORD') ?: 'chtmizpukofpnqix',
        'smtp_from_email' => getenv('HOSPITAL_SMTP_FROM_EMAIL') ?: 'pcnttphongkhamdakhoaphuthai@gmail.com',
        'smtp_from_name' => getenv('HOSPITAL_SMTP_FROM_NAME') ?: 'Phòng khám đa khoa Phú Thái Support',
        'sms_webhook' => getenv('HOSPITAL_SMS_WEBHOOK') ?: '',
        'sms_webhook_bearer' => getenv('HOSPITAL_SMS_WEBHOOK_BEARER') ?: '',
        'turnstile_site_key' => getenv('HOSPITAL_TURNSTILE_SITE_KEY') ?: '0x4AAAAAADHrpLXjP19BAGlZ',
        'turnstile_secret_key' => getenv('HOSPITAL_TURNSTILE_SECRET_KEY') ?: '0x4AAAAAADHrpGFJR8lS_WWL0h-V54_tvOY',
        'recaptcha_site_key' => getenv('HOSPITAL_RECAPTCHA_SITE_KEY') ?: '',
        'recaptcha_secret_key' => getenv('HOSPITAL_RECAPTCHA_SECRET_KEY') ?: '',
        'otp_request_cooldown' => (int) (getenv('HOSPITAL_OTP_REQUEST_COOLDOWN') ?: 60),
        'otp_request_limit' => (int) (getenv('HOSPITAL_OTP_REQUEST_LIMIT') ?: 5),
        'otp_request_window' => (int) (getenv('HOSPITAL_OTP_REQUEST_WINDOW') ?: 3600),
        'gemini_api_key' => getenv('HOSPITAL_GEMINI_API_KEY') ?: 'AIzaSyC5Oi03Bj5HDq98zZxav7TQugk3sHAxKAI',
    ];

    $rawConfigPath = getenv('HOSPITAL_APP_CONFIG');
    $configPath = is_string($rawConfigPath) ? trim($rawConfigPath) : '';
    if ($configPath === '') {
        $candidates = [
            __DIR__ . DIRECTORY_SEPARATOR . 'hospital_hosting.secrets.php',
            __DIR__ . DIRECTORY_SEPARATOR . 'hospital_full_ALL.secrets.php',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'hospital_full_ALL.secrets.php',
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'hospital_full_ALL.secrets.php',
        ];
        foreach ($candidates as $cand) {
            if (is_file($cand)) {
                $configPath = $cand;
                break;
            }
        }
    }

    if ($configPath !== '' && is_file($configPath)) {
        $fileConfig = require $configPath;
        if (is_array($fileConfig)) {
            $config = array_merge($config, $fileConfig);
        }
    }

    $proxyList = $config['trusted_proxies'] ?? [];
    if (is_string($proxyList)) {
        $proxyList = array_filter(array_map('trim', explode(',', $proxyList)));
    }
    $envProxies = getenv('HOSPITAL_TRUSTED_PROXIES');
    if (is_string($envProxies) && trim($envProxies) !== '') {
        $proxyList = array_merge((array) $proxyList, array_filter(array_map('trim', explode(',', $envProxies))));
    }
    if (getenv('RENDER') !== false || !empty($_SERVER['HTTP_CF_RAY']) || !empty($_SERVER['HTTP_X_RENDER_ORIGIN_SERVER'])) {
        $proxyList[] = 'private';
        $proxyList[] = '*';
    }

    $config['trusted_proxies'] = array_values(array_unique(array_filter((array) $proxyList, static function ($value): bool {
        return is_string($value) && ($value === '*' || $value === 'private' || filter_var($value, FILTER_VALIDATE_IP) !== false);
    })));

    return $config;
}

$appConfig = load_app_config();

if (($appConfig['db_user'] ?? '') === '') {
    http_response_code(500);
    echo "<!DOCTYPE html><html lang=\"vi\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>L\u{1ED7}i c\u{1EA5}u h\u{00EC}nh b\u{1EA3}o m\u{1EAD}t</title>";
    echo '<style>body{font-family:Segoe UI,Tahoma,sans-serif;background:#f8fafc;color:#0f172a;margin:0;padding:32px}.box{max-width:720px;margin:0 auto;background:#fff;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(15,23,42,.08)}code{background:#e2e8f0;padding:2px 6px;border-radius:6px}</style>';
    echo "</head><body><div class=\"box\"><h1>Thi\u{1EBF}u c\u{1EA5}u h\u{00EC}nh k\u{1EBF}t n\u{1ED1}i an to\u{00E0}n</h1><p>H\u{00E3}y t\u{1EA1}o file <code>C:\xampp\hospital_full_ALL.secrets.php</code> ho\u{1EB7}c \u{0111}\u{1EB7}t c\u{00E1}c bi\u{1EBF}n m\u{00F4}i tr\u{01B0}\u{1EDD}ng <code>HOSPITAL_DB_HOST</code>, <code>HOSPITAL_DB_NAME</code>, <code>HOSPITAL_DB_USER</code>, <code>HOSPITAL_DB_PASSWORD</code>.</p></div></body></html>";
    exit;
}

define('APP_RUNTIME_ROOT', rtrim((string) $appConfig['runtime_path'], "\\/"));
define('APP_RUNTIME_PATH', APP_RUNTIME_ROOT);
define('APP_RESULTS_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'results');
define('APP_RATE_LIMIT_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'rate_limits');
define('APP_SECURITY_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'security');
define('APP_SESSION_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'sessions');
define('APP_AUDIT_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'audit');
define('APP_RESET_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'password_resets');
define('APP_ADMIN_NOTICE_ROOT', APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'admin_notices');
define('APP_PUBLIC_ASSETS_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'assets');
define('APP_DOCTOR_PHOTO_ROOT', APP_PUBLIC_ASSETS_ROOT . DIRECTORY_SEPARATOR . 'doctor_photos');
define('APP_BRANDING_ROOT', APP_PUBLIC_ASSETS_ROOT . DIRECTORY_SEPARATOR . 'branding');
define('APP_NEWS_MEDIA_ROOT', APP_PUBLIC_ASSETS_ROOT . DIRECTORY_SEPARATOR . 'news_media');
define('APP_BACKUP_ROOT', rtrim((string) ($appConfig['backup_path'] ?? (APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups')), "\\/"));
define('APP_EXTRA_BACKUP_PATHS', array_filter(array_map('trim', explode(',', (string) ($appConfig['extra_backup_paths'] ?? '')))));
define('APP_TRUSTED_PROXIES', $appConfig['trusted_proxies']);
define('APP_FORCE_HTTPS', (bool) ($appConfig['force_https'] ?? false));
define('APP_HTTPS_PORT', max(1, (int) ($appConfig['https_port'] ?? 443)));
define('APP_SESSION_LIFETIME', max(300, (int) ($appConfig['session_lifetime'] ?? 420)));
define('APP_SESSION_REGENERATE_INTERVAL', min(APP_SESSION_LIFETIME, 300));
define('APP_SMTP_HOST', trim((string) ($appConfig['smtp_host'] ?? '')));
define('APP_SMTP_PORT', max(1, (int) ($appConfig['smtp_port'] ?? 465)));
define('APP_SMTP_SECURE', strtolower(trim((string) ($appConfig['smtp_secure'] ?? 'ssl'))));
define('APP_SMTP_USERNAME', trim((string) ($appConfig['smtp_username'] ?? '')));
define('APP_SMTP_PASSWORD', (string) ($appConfig['smtp_password'] ?? ''));
define('APP_SMTP_FROM_EMAIL', trim((string) ($appConfig['smtp_from_email'] ?? '')));
define('APP_SMTP_FROM_NAME', trim((string) ($appConfig['smtp_from_name'] ?? '')));
define('APP_SMS_WEBHOOK', trim((string) ($appConfig['sms_webhook'] ?? '')));
define('APP_SMS_WEBHOOK_BEARER', trim((string) ($appConfig['sms_webhook_bearer'] ?? '')));
define('APP_TURNSTILE_SITE_KEY', trim((string) ($appConfig['turnstile_site_key'] ?? '')));
define('APP_TURNSTILE_SECRET_KEY', trim((string) ($appConfig['turnstile_secret_key'] ?? '')));
define('APP_RECAPTCHA_SITE_KEY', trim((string) ($appConfig['recaptcha_site_key'] ?? '')));
define('APP_RECAPTCHA_SECRET_KEY', trim((string) ($appConfig['recaptcha_secret_key'] ?? '')));
define('APP_OTP_REQUEST_COOLDOWN', max(10, (int) ($appConfig['otp_request_cooldown'] ?? 60)));
define('APP_OTP_REQUEST_LIMIT', max(1, (int) ($appConfig['otp_request_limit'] ?? 5)));
define('APP_OTP_REQUEST_WINDOW', max(60, (int) ($appConfig['otp_request_window'] ?? 3600)));
define('APP_GEMINI_API_KEY', trim((string) ($appConfig['gemini_api_key'] ?? '')));
const RECAPTCHA_TEST_SITE_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
const RECAPTCHA_TEST_SECRET_KEY = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

try {
    $conn = mysqli_init();
    $dbHost = (string) ($appConfig['db_host'] ?? 'localhost');
    $dbPort = (int) ($appConfig['db_port'] ?? 0);
    if (str_contains($dbHost, ':')) {
        $parts = explode(':', $dbHost, 2);
        $dbHost = $parts[0];
        $dbPort = (int) $parts[1];
    }
    if ($dbPort <= 0) {
        $dbPort = str_contains($dbHost, 'tidbcloud.com') ? 4000 : 3306;
    }
    if ($dbHost === 'localhost' && $dbPort !== 3306) {
        $dbHost = '127.0.0.1';
    }

    $isTiDB = str_contains($dbHost, 'tidbcloud.com');
    $useSSL = (bool) ($appConfig['db_ssl'] ?? false) || $isTiDB;
    $flags = 0;

    if ($useSSL) {
        $flags |= MYSQLI_CLIENT_SSL;
        $sslCa = (string) ($appConfig['db_ssl_ca'] ?? '');
        if ($sslCa === '' || !is_file($sslCa)) {
            $systemCas = [
                '/etc/ssl/certs/ca-certificates.crt',
                '/etc/pki/tls/certs/ca-bundle.crt',
                '/etc/ssl/ca-bundle.pem',
            ];
            foreach ($systemCas as $caCandidate) {
                if (is_file($caCandidate)) {
                    $sslCa = $caCandidate;
                    break;
                }
            }
        }
        if ($sslCa !== '' && is_file($sslCa)) {
            mysqli_ssl_set($conn, null, null, $sslCa, null, null);
        }
    }

    mysqli_real_connect(
        $conn,
        $dbHost,
        (string) $appConfig['db_user'],
        (string) $appConfig['db_password'],
        (string) $appConfig['db_name'],
        $dbPort,
        null,
        $flags
    );
    $conn->set_charset('utf8mb4');
} catch (Throwable $exception) {
    error_log('[hospital_full_ALL] database_connect_failed: ' . $exception->getMessage());
    http_response_code(500);
    echo "<!DOCTYPE html><html lang=\"vi\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>L\u{1ED7}i k\u{1EBF}t n\u{1ED1}i c\u{01A1} s\u{1EDF} d\u{1EEF} li\u{1EC7}u</title>";
    echo '<style>body{font-family:Segoe UI,Tahoma,sans-serif;background:#f8fafc;color:#0f172a;margin:0;padding:32px}.box{max-width:720px;margin:0 auto;background:#fff;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(15,23,42,.08)}code{background:#e2e8f0;padding:2px 6px;border-radius:6px}</style>';
    echo "</head><body><div class=\"box\"><h1>Kh\u{00F4}ng th\u{1EC3} k\u{1EBF}t n\u{1ED1}i c\u{01A1} s\u{1EDF} d\u{1EEF} li\u{1EC7}u</h1><p>H\u{00E3}y ch\u{1EAF}c r\u{1EB1}ng MySQL \u{0111}ang ch\u{1EA1}y v\u{00E0} database <code>benhvien_support</code> \u{0111}\u{00E3} \u{0111}\u{01B0}\u{1EE3}c import t\u{1EEB} file <code>database.sql</code>.</p><p>Chi ti\u{1EBF}t k\u{1EF9} thu\u{1EAD}t \u{0111}\u{00E3} \u{0111}\u{01B0}\u{1EE3}c ghi log n\u{1ED9}i b\u{1ED9}.</p></div></body></html>";
    exit;
}

if (!defined('APP_DIRS_INITIALIZED')) {
    define('APP_DIRS_INITIALIZED', true);
    foreach ([APP_SESSION_ROOT, APP_RATE_LIMIT_ROOT, APP_SECURITY_ROOT, APP_AUDIT_ROOT, APP_RESET_ROOT, APP_ADMIN_NOTICE_ROOT, APP_RESULTS_ROOT, APP_PUBLIC_ASSETS_ROOT, APP_DOCTOR_PHOTO_ROOT, APP_BRANDING_ROOT, APP_NEWS_MEDIA_ROOT] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}

function is_trusted_proxy_ip(string $ip): bool
{
    if (in_array('*', APP_TRUSTED_PROXIES, true)) {
        return true;
    }
    if (in_array($ip, APP_TRUSTED_PROXIES, true)) {
        return true;
    }
    if (in_array('private', APP_TRUSTED_PROXIES, true)) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
    }
    return false;
}

function forwarded_proto_is_https(): bool
{
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!is_trusted_proxy_ip($remoteAddr)) {
        return false;
    }

    foreach (['HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_SSL'] as $key) {
        $value = strtolower(trim((string) ($_SERVER[$key] ?? '')));
        if ($value === '') {
            continue;
        }

        if (str_contains($value, ',')) {
            $value = trim((string) explode(',', $value)[0]);
        }

        if ($value === 'https' || $value === 'on') {
            return true;
        }
    }

    return false;
}

function request_is_https(): bool
{
    return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || forwarded_proto_is_https());
}

function force_https_if_needed(): void
{
    if (!APP_FORCE_HTTPS || request_is_https()) {
        return;
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return;
    }

    $host = preg_replace('/:\d+$/', '', $host);
    if (!is_string($host) || $host === '') {
        return;
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $httpsHost = $host;
    if (APP_HTTPS_PORT !== 443) {
        $httpsHost .= ':' . APP_HTTPS_PORT;
    }

    header('Location: https://' . $httpsHost . $requestUri, true, 307);
    exit;
}

function running_in_cli(): bool
{
    return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
}

function handle_cors_headers(): void
{
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin === '') {
        return;
    }

    $allowedExactOrigins = [
        'https://conghotrophongkhamphuthai.io.vn',
        'http://conghotrophongkhamphuthai.io.vn',
        'https://web-iewr.onrender.com',
    ];

    $isAllowed = in_array($origin, $allowedExactOrigins, true);
    if (!$isAllowed && preg_match('#^https://[a-z0-9\-]+\.pages\.dev$#i', $origin)) {
        $isAllowed = true;
    }
    if (!$isAllowed && preg_match('#^https://[a-z0-9\-\.]+\.workers\.dev$#i', $origin)) {
        $isAllowed = true;
    }

    if ($isAllowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');
        header('Vary: Origin');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

if (!running_in_cli()) {
    handle_cors_headers();
    force_https_if_needed();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    $isHttps = request_is_https();
    session_name('hospital_support_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $sessionPath = APP_SESSION_ROOT;
    if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
        $tempSessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hospital_sessions';
        if (!is_dir($tempSessionPath)) {
            @mkdir($tempSessionPath, 0777, true);
        }
        if (is_dir($tempSessionPath) && is_writable($tempSessionPath)) {
            $sessionPath = $tempSessionPath;
        } else {
            $sessionPath = sys_get_temp_dir();
        }
    }

    try {
        if ($sessionPath !== '') {
            @session_save_path($sessionPath);
        }
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    } catch (Throwable $sessionErr) {
        error_log('[hospital_session] session_start_failed: ' . $sessionErr->getMessage());
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
    }
} else {
    $isHttps = false;
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        $_SESSION = [];
    }
}

function current_request_path(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? basename($path) : '';
}

function current_session_account_type(): ?string
{
    if (isset($_SESSION['admin_id'])) {
        return 'admin';
    }

    if (isset($_SESSION['user_id'])) {
        return 'patient';
    }

    return null;
}

function session_login_path(?string $accountType = null): string
{
    return $accountType === 'admin' ? 'admin_login.php' : 'login.php';
}

function session_client_fingerprint(): string
{
    $userAgent = normalize_single_line_input($_SERVER['HTTP_USER_AGENT'] ?? '');
    $acceptLanguage = normalize_single_line_input($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    return hash('sha256', $userAgent . '|' . $acceptLanguage);
}

function session_start_fresh(): void
{
    session_start();
    $_SESSION['session_initialized_at'] = time();
    $_SESSION['session_last_regenerated_at'] = time();
    $_SESSION['session_fingerprint'] = session_client_fingerprint();
}

function clear_authenticated_session(array $extraSessionData = []): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start_fresh();

    foreach ($extraSessionData as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

function mark_session_authenticated(): void
{
    $_SESSION['last_activity'] = time();
    $_SESSION['session_last_regenerated_at'] = time();
    $_SESSION['session_fingerprint'] = session_client_fingerprint();
}

function enforce_session_security(): void
{
    $now = time();

    if (!isset($_SESSION['session_initialized_at'])) {
        $_SESSION['session_initialized_at'] = $now;
    }

    if (!isset($_SESSION['session_last_regenerated_at'])) {
        $_SESSION['session_last_regenerated_at'] = $now;
    }

    $expectedFingerprint = session_client_fingerprint();
    $accountType = current_session_account_type();

    if (!isset($_SESSION['session_fingerprint'])) {
        $_SESSION['session_fingerprint'] = $expectedFingerprint;
    } elseif (!hash_equals((string) $_SESSION['session_fingerprint'], $expectedFingerprint)) {
        if ($accountType === null) {
            clear_authenticated_session();
        }

        security_log('session_fingerprint_mismatch', [
            'account_type' => $accountType,
            'request_path' => current_request_path(),
        ]);
        clear_authenticated_session([
            'flash' => [
                'type' => 'error',
                'message' => "\u{0050}hi\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}. Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p l\u{1EA1}i.",
            ],
        ]);
        redirect(session_login_path($accountType));
    }

    if ($accountType !== null && isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > APP_SESSION_LIFETIME) {
        $loginPath = session_login_path($accountType);
        security_log('session_idle_timeout', [
            'account_type' => $accountType,
            'request_path' => current_request_path(),
            'idle_seconds' => $now - (int) $_SESSION['last_activity'],
        ]);
        clear_authenticated_session([
            'flash' => [
                'type' => 'error',
                'message' => "Phi\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p \u{0111}\u{00E3} h\u{1EBF}t h\u{1EA1}n sau 7 ph\u{00FA}t kh\u{00F4}ng thao t\u{00E1}c. Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p l\u{1EA1}i.",
            ],
            'expired_login_path' => $loginPath,
            'expired_request_path' => current_request_path(),
        ]);
        redirect($loginPath);
    }

    if (($now - (int) $_SESSION['session_last_regenerated_at']) >= APP_SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['session_last_regenerated_at'] = $now;
        $_SESSION['session_fingerprint'] = $expectedFingerprint;
    }

    $_SESSION['last_activity'] = $now;
}

if (!running_in_cli()) {
    enforce_session_security();

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://challenges.cloudflare.com; frame-src https://www.google.com https://recaptcha.google.com https://challenges.cloudflare.com; connect-src 'self' https://www.google.com https://www.gstatic.com https://challenges.cloudflare.com");
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

const PASSWORD_ALGO = 'pbkdf2_sha256';
const PASSWORD_ITERATIONS = 120000;
const PASSWORD_BYTES = 32;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function get_client_ip(): string
{
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($remoteAddr, FILTER_VALIDATE_IP) === false) {
        $remoteAddr = 'unknown';
    }

    if (!is_trusted_proxy_ip($remoteAddr)) {
        return $remoteAddr;
    }

    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }

        foreach (array_map('trim', explode(',', (string) $_SERVER[$key])) as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP)) {
                return $part;
            }
        }
    }

    return $remoteAddr;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function log_internal_error(string $event, Throwable $exception, array $context = []): void
{
    security_log($event, array_merge($context, [
        'exception' => get_class($exception),
        'message' => $exception->getMessage(),
    ]));
}

function with_transaction(mysqli $conn, callable $callback)
{
    $conn->begin_transaction();

    try {
        $result = $callback();
        $conn->commit();
        return $result;
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function contains_control_chars(string $value): bool
{
    return preg_match('/[\x00-\x1F\x7F]/u', $value) === 1;
}

function normalize_single_line_input(?string $value): string
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
}

function validate_cccd(string $cccd): bool
{
    return preg_match('/^\d{12}$/', $cccd) === 1;
}

function validate_phone_number(string $phone): bool
{
    return preg_match('/^\d{9,15}$/', $phone) === 1;
}

function validate_email_address(string $email): bool
{
    return strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_username_format(string $username): bool
{
    return preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) === 1;
}

function validate_person_name(string $name): bool
{
    if ($name === '' || contains_control_chars($name)) {
        return false;
    }

    if (function_exists('mb_strlen') && mb_strlen($name, 'UTF-8') > 120) {
        return false;
    }

    return preg_match('/^[\p{L}\p{M}0-9 .\'-]{2,120}$/u', $name) === 1;
}

function validate_generic_label(string $value, int $maxLength = 120): bool
{
    if ($value === '' || contains_control_chars($value)) {
        return false;
    }

    if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $maxLength) {
        return false;
    }

    return true;
}

function validate_multiline_text(string $value, int $maxLength): bool
{
    if ($value === '' || contains_control_chars($value)) {
        return false;
    }

    if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $maxLength) {
        return false;
    }

    return true;
}

function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Mật khẩu phải có ít nhất 8 ký tự.';
    }

    if (strlen($password) > 72) {
        return 'Mật khẩu không được vượt quá 72 ký tự.';
    }

    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Mật khẩu phải gồm chữ hoa, chữ thường, số và ký tự đặc biệt.';
    }

    return null;
}

function validate_visit_date(string $value): ?string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date->format('Y-m-d');
}

function is_duplicate_key_exception(Throwable $exception): bool
{
    return $exception instanceof mysqli_sql_exception && (int) $exception->getCode() === 1062;
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function rate_limit_path(string $key): string
{
    return APP_RATE_LIMIT_ROOT . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
}

function rate_limit_hit(string $context, int $limit, int $windowSeconds): bool
{
    $path = rate_limit_path($context . '|' . get_client_ip());
    $now = time();
    $hits = [];

    if (is_file($path)) {
        $stored = json_decode((string) file_get_contents($path), true);
        if (is_array($stored)) {
            $hits = array_values(array_filter($stored, static function ($timestamp) use ($now, $windowSeconds): bool {
                return is_int($timestamp) && $timestamp > ($now - $windowSeconds);
            }));
        }
    }

    $hits[] = $now;
    file_put_contents($path, json_encode($hits), LOCK_EX);

    return count($hits) > $limit;
}

function security_log(string $event, array $context = []): void
{
    $record = [
        'time' => date('c'),
        'ip' => get_client_ip(),
        'event' => $event,
        'context' => $context,
    ];
    file_put_contents(
        APP_SECURITY_ROOT . DIRECTORY_SEPARATOR . 'security.log',
        json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function audit_log(string $event, array $context = []): void
{
    $actor = [
        'type' => 'guest',
        'admin_id' => 0,
        'username' => '',
        'full_name' => '',
        'patient_id' => 0,
        'cccd' => '',
        'is_root' => false,
    ];

    if (isset($_SESSION['admin_id'])) {
        $actor['type'] = 'admin';
        $actor['admin_id'] = (int) ($_SESSION['admin_id'] ?? 0);
        $actor['username'] = (string) ($_SESSION['admin_username'] ?? '');
        $actor['full_name'] = (string) ($_SESSION['admin_full_name'] ?? '');
        $actor['is_root'] = !empty($_SESSION['admin_is_root']);
    } elseif (isset($_SESSION['user_id'])) {
        $actor['type'] = 'patient';
        $actor['patient_id'] = (int) ($_SESSION['user_id'] ?? 0);
        $actor['full_name'] = (string) ($_SESSION['name'] ?? '');
        $actor['cccd'] = (string) ($_SESSION['cccd'] ?? '');
    }

    $record = [
        'time' => date('c'),
        'ip' => get_client_ip(),
        'event' => $event,
        'actor' => $actor,
        'context' => $context,
    ];

    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        return;
    }

    file_put_contents(
        APP_AUDIT_ROOT . DIRECTORY_SEPARATOR . 'audit.log',
        $line . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function read_json_log_tail(string $path, int $limit = 100): array
{
    if (!is_file($path)) {
        return [];
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines) || $lines === []) {
        return [];
    }

    $lines = array_slice($lines, -max(1, $limit));
    $rows = [];
    foreach (array_reverse($lines) as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $rows[] = $decoded;
        }
    }

    return $rows;
}

function recent_audit_logs(int $limit = 100): array
{
    return read_json_log_tail(APP_AUDIT_ROOT . DIRECTORY_SEPARATOR . 'audit.log', $limit);
}

function recent_security_logs(int $limit = 100): array
{
    return read_json_log_tail(APP_SECURITY_ROOT . DIRECTORY_SEPARATOR . 'security.log', $limit);
}

function log_matches_account_filter(array $log, string $filter): bool
{
    $filter = trim($filter);
    if ($filter === '') {
        return true;
    }

    $needles = [
        (string) ($log['actor']['username'] ?? ''),
        (string) ($log['actor']['full_name'] ?? ''),
        (string) ($log['actor']['cccd'] ?? ''),
        (string) ($log['context']['username'] ?? ''),
        (string) ($log['context']['full_name'] ?? ''),
        (string) ($log['context']['cccd'] ?? ''),
    ];

    foreach ($needles as $needle) {
        if ($needle !== '' && stripos($needle, $filter) !== false) {
            return true;
        }
    }

    return false;
}

function filter_logs_by_account(array $logs, string $filter): array
{
    if (trim($filter) === '') {
        return $logs;
    }

    return array_values(array_filter($logs, static function (array $log) use ($filter): bool {
        return log_matches_account_filter($log, $filter);
    }));
}

function log_matches_event_filter(array $log, string $filter): bool
{
    $filter = trim($filter);
    if ($filter === '') {
        return true;
    }

    return stripos((string) ($log['event'] ?? ''), $filter) !== false;
}

function log_matches_actor_type_filter(array $log, string $filter): bool
{
    $filter = trim($filter);
    if ($filter === '' || $filter === 'all') {
        return true;
    }

    return (string) ($log['actor']['type'] ?? '') === $filter;
}

function log_matches_date_range_filter(array $log, string $dateFrom, string $dateTo): bool
{
    $timestamp = strtotime((string) ($log['time'] ?? ''));
    if ($timestamp === false) {
        return false;
    }

    $dateFrom = trim($dateFrom);
    if ($dateFrom !== '') {
        $fromTs = strtotime($dateFrom . ' 00:00:00');
        if ($fromTs !== false && $timestamp < $fromTs) {
            return false;
        }
    }

    $dateTo = trim($dateTo);
    if ($dateTo !== '') {
        $toTs = strtotime($dateTo . ' 23:59:59');
        if ($toTs !== false && $timestamp > $toTs) {
            return false;
        }
    }

    return true;
}

function filter_logs(array $logs, array $filters): array
{
    $account = (string) ($filters['account'] ?? '');
    $event = (string) ($filters['event'] ?? '');
    $actorType = (string) ($filters['actor_type'] ?? 'all');
    $dateFrom = (string) ($filters['date_from'] ?? '');
    $dateTo = (string) ($filters['date_to'] ?? '');

    return array_values(array_filter($logs, static function (array $log) use ($account, $event, $actorType, $dateFrom, $dateTo): bool {
        return log_matches_account_filter($log, $account)
            && log_matches_event_filter($log, $event)
            && log_matches_actor_type_filter($log, $actorType)
            && log_matches_date_range_filter($log, $dateFrom, $dateTo);
    }));
}

function backup_copy_tree(string $source, string $destination, array $excludePaths = []): void
{
    if (!is_dir($source)) {
        return;
    }

    // Chuẩn hóa đường dẫn loại trừ
    $normalizedExcludes = array_map(static fn(string $p) => rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p), DIRECTORY_SEPARATOR), $excludePaths);
    $normalizedSource = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $source), DIRECTORY_SEPARATOR);

    // Bỏ qua nếu thư mục nguồn nằm trong danh sách loại trừ
    foreach ($normalizedExcludes as $exclude) {
        if ($normalizedSource === $exclude || str_starts_with($normalizedSource, $exclude . DIRECTORY_SEPARATOR)) {
            return;
        }
    }

    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }

    $items = scandir($source);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $from = $source . DIRECTORY_SEPARATOR . $item;
        $to = $destination . DIRECTORY_SEPARATOR . $item;

        if (is_dir($from)) {
            backup_copy_tree($from, $to, $excludePaths);
            continue;
        }

        if (!is_readable($from)) {
            continue;
        }

        @copy($from, $to);
    }
}

function backup_cleanup_old(int $keepCount = 7): void
{
    $backupRoot = rtrim(APP_BACKUP_ROOT, "\\/");
    $dirs = glob($backupRoot . DIRECTORY_SEPARATOR . 'backup_*', GLOB_ONLYDIR);
    if (!is_array($dirs) || count($dirs) <= $keepCount) {
        return;
    }

    // Sắp xếp theo tên (tên chứa timestamp nên thứ tự bảng chữ cái = thứ tự thời gian)
    sort($dirs);
    $toDelete = array_slice($dirs, 0, count($dirs) - $keepCount);

    foreach ($toDelete as $dir) {
        // Xóa đệ quy thư mục backup cũ
        $innerItems = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($innerItems as $innerItem) {
            $innerItem->isDir() ? @rmdir($innerItem->getRealPath()) : @unlink($innerItem->getRealPath());
        }
        @rmdir($dir);
    }
}

function export_database_snapshot(mysqli $conn, string $destinationPath): void
{
    $tables = [];
    $result = $conn->query('SHOW TABLES');
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        if (!empty($row[0])) {
            $tables[] = (string) $row[0];
        }
    }
    $result->close();

    $sql = [];
    $sql[] = '-- Hospital backup generated at ' . date('c');
    $sql[] = 'SET NAMES utf8mb4;';
    $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';
    $sql[] = '';

    foreach ($tables as $table) {
        $safeTable = sql_quote_identifier($table);
        $createResult = $conn->query('SHOW CREATE TABLE ' . $safeTable);
        $createRow = $createResult->fetch_assoc();
        $createResult->close();

        $sql[] = '-- Structure for table ' . $table;
        $sql[] = 'DROP TABLE IF EXISTS ' . $safeTable . ';';
        $sql[] = (string) ($createRow['Create Table'] ?? '') . ';';
        $sql[] = '';

        $dataResult = $conn->query('SELECT * FROM ' . $safeTable);
        if ($dataResult instanceof mysqli_result && $dataResult->num_rows > 0) {
            $fields = [];
            foreach ($dataResult->fetch_fields() as $field) {
                $fields[] = '`' . str_replace('`', '``', $field->name) . '`';
            }
            $columnList = implode(', ', $fields);

            while ($row = $dataResult->fetch_assoc()) {
                $values = [];
                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : "'" . $conn->real_escape_string((string) $value) . "'";
                }
                $sql[] = 'INSERT INTO ' . $safeTable . ' (' . $columnList . ') VALUES (' . implode(', ', $values) . ');';
            }
        }
        if ($dataResult instanceof mysqli_result) {
            $dataResult->close();
        }
        $sql[] = '';
    }

    $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
    file_put_contents($destinationPath, implode(PHP_EOL, $sql) . PHP_EOL, LOCK_EX);
}

function backup_compress_directory_to_zip(string $sourceDir, string $destinationZip): bool
{
    $sourceDir = realpath($sourceDir);
    if ($sourceDir === false) {
        return false;
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($destinationZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceDir) + 1);
                    $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
                }
            }
            $zip->close();
            return is_file($destinationZip);
        }
    }

    // Fallback trên Windows nếu môi trường chưa cài ZipArchive
    if (DIRECTORY_SEPARATOR === '\\') {
        $cmd = sprintf(
            'powershell -Command "Compress-Archive -Path %s -DestinationPath %s -Force"',
            escapeshellarg($sourceDir . DIRECTORY_SEPARATOR . '*'),
            escapeshellarg($destinationZip)
        );
        $output = [];
        $returnVar = 0;
        @exec($cmd, $output, $returnVar);
        return $returnVar === 0 && is_file($destinationZip);
    }

    return false;
}

function create_system_backup(mysqli $conn, string $trigger = 'manual'): array
{
    $timestamp = date('Ymd_His');
    $folderName = 'backup_' . $timestamp;
    $backupDir = rtrim(APP_BACKUP_ROOT, "\\/") . DIRECTORY_SEPARATOR . $folderName;

    if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Không thể tạo thư mục backup.');
    }

    $dbPath = $backupDir . DIRECTORY_SEPARATOR . 'database_snapshot.sql';
    export_database_snapshot($conn, $dbPath);

    // Loại trừ thư mục backups khỏi quá trình copy để tránh đệ quy vô hạn
    $excludePaths = [rtrim(APP_BACKUP_ROOT, "\\/")];

    foreach (backup_runtime_paths() as $runtimeName => $runtimePath) {
        if (!is_dir($runtimePath)) {
            continue;
        }

        backup_copy_tree($runtimePath, $backupDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . $runtimeName, $excludePaths);
    }
    backup_copy_tree(APP_PUBLIC_ASSETS_ROOT, $backupDir . DIRECTORY_SEPARATOR . 'assets', $excludePaths);

    // Xóa các bản backup cũ, chỉ giữ 7 bản gần nhất
    backup_cleanup_old(7);

    $manifest = [
        'created_at' => date('c'),
        'trigger' => $trigger,
        'backup_root' => APP_BACKUP_ROOT,
        'database_snapshot' => basename($dbPath),
        'runtime_path' => APP_RUNTIME_ROOT,
        'assets_path' => APP_PUBLIC_ASSETS_ROOT,
        'actor' => [
            'admin_id' => (int) ($_SESSION['admin_id'] ?? 0),
            'username' => (string) ($_SESSION['admin_username'] ?? ''),
            'full_name' => (string) ($_SESSION['admin_full_name'] ?? ''),
        ],
    ];
    file_put_contents(
        $backupDir . DIRECTORY_SEPARATOR . 'manifest.json',
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    $latestMeta = [
        'folder' => $folderName,
        'path' => $backupDir,
        'created_at' => $manifest['created_at'],
        'trigger' => $trigger,
    ];
    file_put_contents(
        rtrim(APP_BACKUP_ROOT, "\\/") . DIRECTORY_SEPARATOR . 'latest_backup.json',
        json_encode($latestMeta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    audit_log('backup_created', [
        'trigger' => $trigger,
        'backup_dir' => $backupDir,
    ]);

    // Tạo file ZIP nén để lưu trữ và tải về
    $zipName = 'backup_web_' . $timestamp . '.zip';
    $zipPath = rtrim(APP_BACKUP_ROOT, "\\/") . DIRECTORY_SEPARATOR . $zipName;

    if (backup_compress_directory_to_zip($backupDir, $zipPath)) {
        // Sao lưu sang các vị trí bổ sung (ví dụ: ổ F, thư mục Google Drive)
        foreach (APP_EXTRA_BACKUP_PATHS as $extraPath) {
            $extraPath = rtrim($extraPath, "\\/");
            if (is_dir($extraPath)) {
                @copy($zipPath, $extraPath . DIRECTORY_SEPARATOR . $zipName);
            }
        }
        $latestMeta['zip_path'] = $zipPath;
        $latestMeta['zip_name'] = $zipName;
    }

    return $latestMeta;
}

function backup_runtime_paths(): array
{
    return [
        'audit' => APP_AUDIT_ROOT,
        'admin_notices' => APP_ADMIN_NOTICE_ROOT,
        'results' => APP_RESULTS_ROOT,
    ];
}

function latest_backup_info(): ?array
{
    $path = rtrim(APP_BACKUP_ROOT, "\\/") . DIRECTORY_SEPARATOR . 'latest_backup.json';
    if (!is_file($path)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : null;
}


function table_has_column(string $table, string $column): bool
{
    static $cache = [];

    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    global $conn;

    $databaseName = (string) $conn->query('SELECT DATABASE()')->fetch_row()[0];
    $stmt = $conn->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1'
    );
    $stmt->bind_param('sss', $databaseName, $table, $column);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc() !== null;
    $stmt->close();

    $cache[$key] = $exists;
    return $exists;
}

function table_exists(string $table): bool
{
    global $conn;

    $databaseName = (string) $conn->query('SELECT DATABASE()')->fetch_row()[0];
    $stmt = $conn->prepare(
        'SELECT 1
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
         LIMIT 1'
    );
    $stmt->bind_param('ss', $databaseName, $table);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc() !== null;
    $stmt->close();

    return $exists;
}

function admin_permission_definitions(): array
{
    return [
        'manage_accounts' => ['column' => 'can_manage_accounts', 'label' => 'Quản lý tài khoản nhân viên'],
        'manage_records' => ['column' => 'can_manage_records', 'label' => 'Trả kết quả khám bệnh'],
        'manage_all_records' => ['column' => 'can_manage_all_records', 'label' => 'Xử lý tất cả bộ phận'],
        'manage_clinic_content' => ['column' => 'can_manage_clinic_content', 'label' => 'Sửa nội dung phòng khám'],
        'manage_doctors' => ['column' => 'can_manage_doctors', 'label' => 'Quản lý hồ sơ bác sĩ'],
        'manage_patients' => ['column' => 'can_manage_patients', 'label' => 'Quản lý tài khoản bệnh nhân'],
        'manage_chatbot' => ['column' => 'can_manage_chatbot', 'label' => 'Quản lý câu trả lời chatbot'],
        'manage_support_chat' => ['column' => 'can_manage_support_chat', 'label' => 'Trả lời chat hỗ trợ'],
        'publish_announcements' => ['column' => 'can_publish_announcements', 'label' => 'Gửi thông báo cho bệnh nhân'],
        'create_backup' => ['column' => 'can_create_backup', 'label' => 'Tạo bản sao lưu dữ liệu'],
        'view_logs' => ['column' => 'can_view_logs', 'label' => 'Xem nhật ký hệ thống'],
    ];
}

function ensure_admin_permission_schema(): void
{
    global $conn;

    if (!table_exists('admins')) {
        return;
    }

    foreach (admin_permission_definitions() as $definition) {
        $column = (string) $definition['column'];
        if (table_has_column('admins', $column)) {
            continue;
        }

        try {
            $conn->query('ALTER TABLE admins ADD COLUMN ' . sql_quote_identifier($column) . ' TINYINT(1) NOT NULL DEFAULT 0');
        } catch (Throwable $exception) {
            log_internal_error('admin_permission_schema_update_failed', $exception, ['column' => $column]);
            return;
        }
    }
}

function ensure_news_media_schema(): void
{
    global $conn;

    if (!table_exists('news_posts')) {
        return;
    }

    $columns = [
        'media_path' => 'VARCHAR(255) NULL',
        'media_type' => 'VARCHAR(20) NULL',
    ];

    foreach ($columns as $column => $definition) {
        if (table_has_column('news_posts', $column)) {
            continue;
        }

        try {
            $conn->query('ALTER TABLE news_posts ADD COLUMN ' . sql_quote_identifier($column) . ' ' . $definition);
        } catch (Throwable $exception) {
            log_internal_error('news_media_schema_update_failed', $exception, ['column' => $column]);
            return;
        }
    }
}

function run_schema_migrations_if_needed(): void
{
    $lockFile = APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'schema_migrated.lock';
    if (is_file($lockFile)) {
        return;
    }

    ensure_admin_permission_schema();
    ensure_news_media_schema();

    @file_put_contents($lockFile, date('c'));
}

run_schema_migrations_if_needed();

function patient_email_enabled(): bool
{
    static $enabled = null;
    if ($enabled !== null) {
        return $enabled;
    }
    $enabled = table_has_column('patients', 'email');
    return $enabled;
}

function sql_quote_identifier(string $identifier): string
{
    if ($identifier === '' || !preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException('SQL identifier không hợp lệ.');
    }

    return '`' . $identifier . '`';
}

function patient_select_fields(bool $includeId = false, bool $includeEmail = false, bool $includeCreatedAt = false): string
{
    $fields = [];

    if ($includeId) {
        $fields[] = 'id';
    }

    $fields[] = 'cccd';
    $fields[] = 'full_name';
    $fields[] = 'phone';

    if ($includeEmail && patient_email_enabled()) {
        $fields[] = 'email';
    }

    if ($includeCreatedAt) {
        $fields[] = 'created_at';
    }

    return implode(', ', $fields);
}

function patient_select_sql(
    string $suffix,
    bool $includeId = false,
    bool $includeEmail = false,
    bool $includeCreatedAt = false
): string {
    return 'SELECT '
        . patient_select_fields($includeId, $includeEmail, $includeCreatedAt)
        . ' FROM patients '
        . ltrim($suffix);
}

function password_reset_path(string $cccd): string
{
    return APP_RESET_ROOT . DIRECTORY_SEPARATOR . hash('sha256', strtolower(trim($cccd))) . '.json';
}

function mask_contact(string $value): string
{
    $value = trim($value);
    $length = strlen($value);
    if ($length <= 4) {
        return str_repeat('*', $length);
    }

    return substr($value, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($value, -2);
}

function smtp_server_target(): string
{
    if (APP_SMTP_HOST === '') {
        throw new RuntimeException('Chưa cấu hình HOSPITAL_SMTP_HOST.');
    }

    if (APP_SMTP_SECURE === 'ssl') {
        return 'ssl://' . APP_SMTP_HOST;
    }

    return APP_SMTP_HOST;
}

function smtp_read_response($socket): array
{
    $lines = [];
    $code = 0;

    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }

        $lines[] = rtrim($line, "\r\n");
        if (preg_match('/^(\d{3})([ -])/', $line, $matches) === 1) {
            $code = (int) $matches[1];
            if ($matches[2] === ' ') {
                break;
            }
        } else {
            break;
        }
    }

    return [$code, implode("\n", $lines)];
}

function smtp_expect_codes($socket, array $expectedCodes, string $step): void
{
    [$code, $response] = smtp_read_response($socket);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP lỗi ở bước ' . $step . ': ' . ($response !== '' ? $response : 'không nhận được phản hồi.'));
    }
}

function smtp_write_line($socket, string $line): void
{
    $written = fwrite($socket, $line . "\r\n");
    if ($written === false) {
        throw new RuntimeException('Không thể gửi dữ liệu đến máy chủ SMTP.');
    }
}

function smtp_send_mail(string $to, string $subject, string $message, ?string $attachmentPath = null, ?string $attachmentName = null): bool
{
    if (APP_SMTP_HOST === '' || APP_SMTP_USERNAME === '' || APP_SMTP_PASSWORD === '') {
        return false;
    }

    $fromEmail = APP_SMTP_FROM_EMAIL !== '' ? APP_SMTP_FROM_EMAIL : APP_SMTP_USERNAME;
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $socket = @stream_socket_client(
        smtp_server_target() . ':' . APP_SMTP_PORT,
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT
    );

    if (!is_resource($socket)) {
        throw new RuntimeException('Không kết nối được SMTP: ' . $errstr . ' (' . $errno . ').');
    }

    stream_set_timeout($socket, 15);

    try {
        smtp_expect_codes($socket, [220], 'connect');

        $hostName = gethostname();
        if (!is_string($hostName) || trim($hostName) === '') {
            $hostName = 'localhost';
        }

        smtp_write_line($socket, 'EHLO ' . $hostName);
        smtp_expect_codes($socket, [250], 'ehlo');

        if (APP_SMTP_SECURE === 'tls') {
            smtp_write_line($socket, 'STARTTLS');
            smtp_expect_codes($socket, [220], 'starttls');

            $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoEnabled !== true) {
                throw new RuntimeException('Không thể bật mã hóa TLS cho SMTP.');
            }

            smtp_write_line($socket, 'EHLO ' . $hostName);
            smtp_expect_codes($socket, [250], 'ehlo_tls');
        }

        smtp_write_line($socket, 'AUTH LOGIN');
        smtp_expect_codes($socket, [334], 'auth_login');

        smtp_write_line($socket, base64_encode(APP_SMTP_USERNAME));
        smtp_expect_codes($socket, [334], 'auth_username');

        smtp_write_line($socket, base64_encode(APP_SMTP_PASSWORD));
        smtp_expect_codes($socket, [235], 'auth_password');

        smtp_write_line($socket, 'MAIL FROM:<' . $fromEmail . '>');
        smtp_expect_codes($socket, [250], 'mail_from');

        smtp_write_line($socket, 'RCPT TO:<' . $to . '>');
        smtp_expect_codes($socket, [250, 251], 'rcpt_to');

        smtp_write_line($socket, 'DATA');
        smtp_expect_codes($socket, [354], 'data');

        $fromName = APP_SMTP_FROM_NAME !== '' ? APP_SMTP_FROM_NAME : site_setting('clinic_name', 'Phòng khám');
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $body = str_replace(["\r\n", "\r"], "\n", $message);
        $body = str_replace("\n.", "\n..", $body);
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.";

        smtp_write_line($socket, $payload);
        smtp_expect_codes($socket, [250], 'message_body');

        smtp_write_line($socket, 'QUIT');
        return true;
    } finally {
        fclose($socket);
    }
}

function send_password_reset_email(string $to, string $otp): bool
{
    $subject = 'Mã OTP đặt lại mật khẩu';
    $message = "Mã OTP của bạn là: {$otp}\nMã có hiệu lực trong 10 phút.\nNếu bạn không yêu cầu, vui lòng bỏ qua email này.";

    return smtp_send_mail($to, $subject, $message);
}

function send_patient_announcement_email(string $to, string $title, string $message): bool
{
    $clinicName = site_setting('clinic_name', 'Phòng khám');
    $subject = $title;
    $body = $title . "\n\n"
        . $message . "\n\n"
        . "Trân trọng,\n"
        . $clinicName;

    return smtp_send_mail($to, $subject, $body);
}

function send_patient_result_email(string $to, string $patientName, string $diagnosis, ?string $prescription, ?string $attachmentPath = null): bool
{
    $clinicName = site_setting('clinic_name', 'Phòng khám');
    $subject = 'Kết quả khám bệnh - ' . $clinicName;
    
    $body = "Kính gửi " . $patientName . ",\n\n"
        . "Chúng tôi xin gửi bạn kết quả khám bệnh tại " . $clinicName . ".\n\n"
        . "Kết quả chẩn đoán:\n" . $diagnosis . "\n\n";
        
    if (!empty($prescription)) {
        $body .= "Đơn thuốc / Ghi chú:\n" . $prescription . "\n\n";
    }
    
    if ($attachmentPath !== null && is_file($attachmentPath)) {
        $body .= "Vui lòng xem tệp đính kèm (PDF) để biết thêm chi tiết kết quả khám của bạn.\n\n";
    }
    
    $body .= "Trân trọng,\n" . $clinicName;

    return smtp_send_mail($to, $subject, $body, $attachmentPath, 'Ket_qua_kham.pdf');
}

function send_patient_announcement_emails(mysqli $conn, string $title, string $message): array
{
    $result = [
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
    ];

    if (!patient_email_enabled()) {
        return $result;
    }

    $query = $conn->query("SELECT DISTINCT email FROM patients WHERE email IS NOT NULL AND email <> '' ORDER BY email ASC");
    while ($row = $query->fetch_assoc()) {
        $email = trim((string) ($row['email'] ?? ''));
        if (!validate_email_address($email)) {
            continue;
        }

        $result['total']++;
        try {
            if (send_patient_announcement_email($email, $title, $message)) {
                $result['sent']++;
            } else {
                $result['failed']++;
            }
        } catch (Throwable $exception) {
            $result['failed']++;
            log_internal_error('patient_announcement_email_failed', $exception, ['email' => mask_contact($email)]);
        }
    }
    $query->close();

    return $result;
}

function send_password_reset_sms(string $phone, string $otp): bool
{
    if (APP_SMS_WEBHOOK === '') {
        return false;
    }

    $payload = json_encode([
        'phone' => $phone,
        'message' => 'Mã OTP đặt lại mật khẩu cua ban la: ' . $otp,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n"
                . (APP_SMS_WEBHOOK_BEARER !== '' ? 'Authorization: Bearer ' . APP_SMS_WEBHOOK_BEARER . "\r\n" : ''),
            'content' => $payload,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);

    $result = @file_get_contents(APP_SMS_WEBHOOK, false, $context);
    if ($result === false) {
        return false;
    }

    $statusLine = $http_response_header[0] ?? '';
    return preg_match('/\s2\d\d\s/', $statusLine) === 1;
}

function issue_password_reset_otp(array $patient, string $channel): array
{
    $cccd = (string) ($patient['cccd'] ?? '');
    if ($cccd === '') {
        throw new RuntimeException('Không tìm thấy tài khoản cần khôi phục.');
    }

    $path = password_reset_path($cccd);
    $existing = null;
    if (is_file($path)) {
        $stored = json_decode((string) file_get_contents($path), true);
        if (is_array($stored)) {
            $existing = $stored;
        }
    }

    $now = time();
    $lastRequestedAt = (int) ($existing['requested_at'] ?? 0);
    if ($lastRequestedAt > 0) {
        $remaining = ($lastRequestedAt + APP_OTP_REQUEST_COOLDOWN) - $now;
        if ($remaining > 0) {
            throw new RuntimeException('Bạn vừa yêu cầu OTP. Vui lòng chờ ' . $remaining . ' giây rồi thử lại.');
        }
    }

    $requestHistory = [];
    if (is_array($existing['request_history'] ?? null)) {
        $requestHistory = array_values(array_filter($existing['request_history'], static function ($timestamp) use ($now): bool {
            return is_int($timestamp) && $timestamp > ($now - APP_OTP_REQUEST_WINDOW);
        }));
    }
    if (count($requestHistory) >= APP_OTP_REQUEST_LIMIT) {
        throw new RuntimeException('Bạn đã yêu cầu OTP quá nhiều lần. Vui lòng thử lại sau.');
    }

    $otp = (string) random_int(100000, 999999);
    $record = [
        'patient_id' => (int) ($patient['id'] ?? 0),
        'cccd' => $cccd,
        'channel' => $channel,
        'otp_hash' => hash('sha256', $otp),
        'expires_at' => $now + 600,
        'attempts_left' => 5,
        'email' => (string) ($patient['email'] ?? ''),
        'phone' => (string) ($patient['phone'] ?? ''),
        'requested_at' => $now,
        'request_history' => array_merge($requestHistory, [$now]),
    ];

    $sent = false;
    $maskedDestination = '';
    if ($channel === 'email') {
        $email = trim((string) ($patient['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException('Tài khoản này chưa có email để nhận OTP.');
        }
        $sent = send_password_reset_email($email, $otp);
        $maskedDestination = mask_contact($email);
    } elseif ($channel === 'phone') {
        $phone = trim((string) ($patient['phone'] ?? ''));
        if ($phone === '') {
            throw new RuntimeException('Tài khoản này chưa có số điện thoại để nhận OTP.');
        }
        $sent = send_password_reset_sms($phone, $otp);
        $maskedDestination = mask_contact($phone);
    } else {
        throw new RuntimeException('Kênh gửi OTP không hợp lệ.');
    }

    if (!$sent) {
        security_log('password_reset_otp_delivery_failed', [
            'cccd' => $cccd,
            'channel' => $channel,
        ]);
        throw new RuntimeException($channel === 'email'
            ? 'Kênh email chưa được cấu hình. Hãy khai báo SMTP Gmail trong file secrets.'
            : 'Kênh SMS chưa được cấu hình. Hãy khai báo webhook SMS của nhà cung cấp.');
    }

    file_put_contents($path, json_encode($record), LOCK_EX);
    audit_log('password_reset_otp_issued', [
        'cccd' => $cccd,
        'channel' => $channel,
        'destination' => $maskedDestination,
    ]);

    return [
        'channel' => $channel,
        'destination' => $maskedDestination,
        'expires_in' => 600,
    ];
}

function verify_password_reset_otp(string $cccd, string $otp): array
{
    $path = password_reset_path($cccd);
    if (!is_file($path)) {
        throw new RuntimeException('Không tìm thấy yêu cầu OTP. Vui lòng gửi lại mã mới.');
    }

    $record = json_decode((string) file_get_contents($path), true);
    if (!is_array($record)) {
        @unlink($path);
        throw new RuntimeException('Dữ liệu OTP không hợp lệ.');
    }

    if (($record['expires_at'] ?? 0) < time()) {
        @unlink($path);
        throw new RuntimeException('Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.');
    }

    $attemptsLeft = (int) ($record['attempts_left'] ?? 0);
    if ($attemptsLeft <= 0) {
        @unlink($path);
        throw new RuntimeException('Bạn đã nhập sai OTP quá số lần cho phép.');
    }

    if (hash('sha256', $otp) !== (string) ($record['otp_hash'] ?? '')) {
        $record['attempts_left'] = $attemptsLeft - 1;
        file_put_contents($path, json_encode($record), LOCK_EX);
        throw new RuntimeException('Mã OTP không đúng.');
    }

    return $record;
}

function consume_password_reset_otp(string $cccd): void
{
    $path = password_reset_path($cccd);
    if (is_file($path)) {
        @unlink($path);
    }
}

function temporary_patient_password_path(string $cccd): string
{
    return APP_RESET_ROOT . DIRECTORY_SEPARATOR . 'temp_' . hash('sha256', strtolower(trim($cccd))) . '.json';
}

function clear_temporary_patient_password(string $cccd): void
{
    $path = temporary_patient_password_path($cccd);
    if (is_file($path)) {
        @unlink($path);
    }
}

function generate_temporary_patient_password(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    $maxIndex = strlen($alphabet) - 1;
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $maxIndex)];
    }

    return $password;
}

function issue_temporary_patient_password(array $patient, int $adminId): array
{
    $cccd = (string) ($patient['cccd'] ?? '');
    $patientId = (int) ($patient['id'] ?? 0);
    if ($cccd === '' || $patientId <= 0) {
        throw new RuntimeException('Không thể tạo mật khẩu tạm cho bệnh nhân này.');
    }

    $plainPassword = generate_temporary_patient_password();
    $record = [
        'patient_id' => $patientId,
        'cccd' => $cccd,
        'password_hash' => hash_password($plainPassword),
        'expires_at' => time() + 300,
        'issued_at' => time(),
        'issued_by_admin_id' => $adminId,
    ];

    if (file_put_contents(temporary_patient_password_path($cccd), json_encode($record), LOCK_EX) === false) {
        throw new RuntimeException('Không thể lưu mật khẩu tạm lúc này.');
    }

    audit_log('patient_temporary_password_issued', [
        'patient_id' => $patientId,
        'cccd' => $cccd,
        'admin_id' => $adminId,
        'expires_at' => (int) $record['expires_at'],
    ]);

    return [
        'password' => $plainPassword,
        'expires_at' => (int) $record['expires_at'],
        'expires_in' => 300,
    ];
}

function temporary_admin_password_path(string $username): string
{
    return APP_RESET_ROOT . DIRECTORY_SEPARATOR . 'admin_temp_' . hash('sha256', strtolower(trim($username))) . '.json';
}

function clear_temporary_admin_password(string $username): void
{
    $path = temporary_admin_password_path($username);
    if (is_file($path)) {
        @unlink($path);
    }
}

function issue_temporary_admin_password(array $admin, int $issuedByAdminId): array
{
    $adminId = (int) ($admin['id'] ?? 0);
    $username = (string) ($admin['username'] ?? '');
    if ($adminId <= 0 || $username === '') {
        throw new RuntimeException('Không thể tạo mật khẩu tạm cho nhân viên này.');
    }

    $plainPassword = generate_temporary_patient_password();
    $record = [
        'admin_id' => $adminId,
        'username' => $username,
        'password_hash' => hash_password($plainPassword),
        'expires_at' => time() + 300,
        'issued_at' => time(),
        'issued_by_admin_id' => $issuedByAdminId,
    ];

    if (file_put_contents(temporary_admin_password_path($username), json_encode($record), LOCK_EX) === false) {
        throw new RuntimeException('Không thể lưu mật khẩu tạm nhân viên lúc này.');
    }

    audit_log('admin_temporary_password_issued', [
        'admin_id' => $adminId,
        'username' => $username,
        'issued_by_admin_id' => $issuedByAdminId,
        'expires_at' => (int) $record['expires_at'],
    ]);

    return [
        'password' => $plainPassword,
        'expires_at' => (int) $record['expires_at'],
        'expires_in' => 300,
    ];
}

function active_temporary_admin_password_record(string $username): ?array
{
    $path = temporary_admin_password_path($username);
    if (!is_file($path)) {
        return null;
    }

    $record = json_decode((string) file_get_contents($path), true);
    if (!is_array($record)) {
        @unlink($path);
        return null;
    }

    if ((int) ($record['expires_at'] ?? 0) < time()) {
        @unlink($path);
        return null;
    }

    return $record;
}

function verify_temporary_admin_password(string $username, string $password): ?array
{
    $record = active_temporary_admin_password_record($username);
    if ($record === null) {
        return null;
    }

    return verify_password($password, (string) ($record['password_hash'] ?? '')) ? $record : null;
}

function active_temporary_patient_password_record(string $cccd): ?array
{
    $path = temporary_patient_password_path($cccd);
    if (!is_file($path)) {
        return null;
    }

    $record = json_decode((string) file_get_contents($path), true);
    if (!is_array($record)) {
        @unlink($path);
        return null;
    }

    if ((int) ($record['expires_at'] ?? 0) < time()) {
        @unlink($path);
        return null;
    }

    return $record;
}

function verify_temporary_patient_password(string $cccd, string $password): ?array
{
    $record = active_temporary_patient_password_record($cccd);
    if ($record === null) {
        return null;
    }

    return verify_password($password, (string) ($record['password_hash'] ?? '')) ? $record : null;
}

function mark_temporary_patient_password_session(string $password, array $record): void
{
    $_SESSION['require_password_change'] = true;
    $_SESSION['temporary_password_hash'] = hash('sha256', $password);
    $_SESSION['temporary_password_patient_id'] = (int) ($record['patient_id'] ?? 0);
    $_SESSION['temporary_password_cccd'] = (string) ($record['cccd'] ?? '');
}

function patient_requires_password_change(): bool
{
    return !empty($_SESSION['require_password_change']);
}

function clear_patient_password_change_requirement(): void
{
    unset(
        $_SESSION['require_password_change'],
        $_SESSION['temporary_password_hash'],
        $_SESSION['temporary_password_patient_id'],
        $_SESSION['temporary_password_cccd']
    );
}

function enforce_patient_password_change(): void
{
    if (!patient_requires_password_change()) {
        return;
    }

    $currentPath = current_request_path();
    if (in_array($currentPath, ['account.php', 'logout.php'], true)) {
        return;
    }

    set_flash('error', 'Bạn đang dùng mật khẩu tạm thời. Vui lòng đổi mật khẩu mới ngay bây giờ.');
    redirect('account.php');
}

function login_attempt_path(string $scope, string $identity): string
{
    return APP_SECURITY_ROOT . DIRECTORY_SEPARATOR . hash('sha256', $scope . '|' . strtolower($identity)) . '.json';
}

function get_login_lockout(string $scope, string $identity): ?int
{
    $path = login_attempt_path($scope, $identity);
    if (!is_file($path)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return null;
    }

    $lockedUntil = (int) ($data['locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        return $lockedUntil;
    }

    if ($lockedUntil !== 0) {
        @unlink($path);
    }

    return null;
}

function record_login_failure(string $scope, string $identity, int $maxAttempts = 5, int $lockoutSeconds = 900): int
{
    $path = login_attempt_path($scope, $identity);
    $data = [
        'failures' => 0,
        'first_failure_at' => time(),
        'locked_until' => 0,
    ];

    if (is_file($path)) {
        $stored = json_decode((string) file_get_contents($path), true);
        if (is_array($stored)) {
            $data = array_merge($data, $stored);
        }
    }

    $now = time();
    if (($now - (int) $data['first_failure_at']) > $lockoutSeconds) {
        $data['failures'] = 0;
        $data['first_failure_at'] = $now;
        $data['locked_until'] = 0;
    }

    $data['failures'] = (int) $data['failures'] + 1;

    if ($data['failures'] >= $maxAttempts) {
        $data['locked_until'] = $now + $lockoutSeconds;
        $data['failures'] = 0;
        $data['first_failure_at'] = $now;
    }

    file_put_contents($path, json_encode($data), LOCK_EX);

    return (int) $data['locked_until'];
}

function clear_login_failures(string $scope, string $identity): void
{
    $path = login_attempt_path($scope, $identity);
    if (is_file($path)) {
        @unlink($path);
    }
}

function submit_cooldown_path(string $scope, string $identity): string
{
    return APP_SECURITY_ROOT . DIRECTORY_SEPARATOR . 'cooldown_' . hash('sha256', $scope . '|' . strtolower($identity) . '|' . get_client_ip()) . '.json';
}

function login_submit_cooldown_remaining(string $scope, string $identity, int $threshold = 2, int $cooldownSeconds = 10): int
{
    $path = submit_cooldown_path($scope, $identity);
    $now = time();
    $hits = [];

    if (is_file($path)) {
        $stored = json_decode((string) file_get_contents($path), true);
        if (is_array($stored)) {
            $hits = array_values(array_filter($stored, static function ($timestamp) use ($now, $cooldownSeconds): bool {
                return is_int($timestamp) && $timestamp > ($now - $cooldownSeconds);
            }));
        }
    }

    if (count($hits) >= $threshold) {
        $last = max($hits);
        $remaining = ($last + $cooldownSeconds) - $now;
        if ($remaining > 0) {
            return $remaining;
        }
    }

    return 0;
}

function register_login_submit_attempt(string $scope, string $identity, int $cooldownSeconds = 10): void
{
    $path = submit_cooldown_path($scope, $identity);
    $now = time();
    $hits = [];

    if (is_file($path)) {
        $stored = json_decode((string) file_get_contents($path), true);
        if (is_array($stored)) {
            $hits = array_values(array_filter($stored, static function ($timestamp) use ($now, $cooldownSeconds): bool {
                return is_int($timestamp) && $timestamp > ($now - $cooldownSeconds);
            }));
        }
    }

    $hits[] = $now;
    file_put_contents($path, json_encode($hits), LOCK_EX);
}

function clear_login_submit_attempts(string $scope, string $identity): void
{
    $path = submit_cooldown_path($scope, $identity);
    if (is_file($path)) {
        @unlink($path);
    }
}

function csrf_token(string $context): string
{
    if (!isset($_SESSION['csrf_tokens'][$context])) {
        $_SESSION['csrf_tokens'][$context] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$context];
}

function verify_csrf_token(string $context, ?string $token): bool
{
    if (!isset($_SESSION['csrf_tokens'][$context]) || $token === null) {
        return false;
    }

    return hash_equals($_SESSION['csrf_tokens'][$context], $token);
}

function render_form_guard(string $context): void
{
    $_SESSION['form_started_at'][$context] = time();
    echo '<input type="hidden" name="_csrf" value="' . e(csrf_token($context)) . '">';
    echo '<input type="hidden" name="_form_started_at" value="' . (int) $_SESSION['form_started_at'][$context] . '">';
}

function running_on_localhost(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function recaptcha_site_key(): string
{
    if (APP_RECAPTCHA_SITE_KEY !== '') {
        return APP_RECAPTCHA_SITE_KEY;
    }

    return running_on_localhost() ? RECAPTCHA_TEST_SITE_KEY : '';
}

function recaptcha_secret_key(): string
{
    if (APP_RECAPTCHA_SECRET_KEY !== '') {
        return APP_RECAPTCHA_SECRET_KEY;
    }

    return running_on_localhost() ? RECAPTCHA_TEST_SECRET_KEY : '';
}

function recaptcha_configured(): bool
{
    return recaptcha_site_key() !== '' && recaptcha_secret_key() !== '';
}

function turnstile_site_key(): string
{
    return APP_TURNSTILE_SITE_KEY;
}

function turnstile_secret_key(): string
{
    return APP_TURNSTILE_SECRET_KEY;
}

function turnstile_configured(): bool
{
    return turnstile_site_key() !== '' && turnstile_secret_key() !== '';
}

function visual_captcha_code(string $context): string
{
    if (!isset($_SESSION['visual_captcha'][$context]) || !is_array($_SESSION['visual_captcha'][$context])) {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $_SESSION['visual_captcha'][$context] = [
            'code' => $code,
            'created_at' => time(),
        ];
    }

    return (string) $_SESSION['visual_captcha'][$context]['code'];
}

function visual_captcha_image_src(string $code): string
{
    $chars = preg_split('//u', $code, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $colors = ['#c026d3', '#dc2626', '#2563eb', '#16a34a', '#ea580c'];
    $letters = '';
    foreach ($chars as $index => $char) {
        $x = 22 + ($index * 30);
        $y = 35 + (($index % 2) * 4);
        $rotate = [-9, 6, -5, 8, -7][$index] ?? 0;
        $color = $colors[$index % count($colors)];
        $letters .= '<text x="' . $x . '" y="' . $y . '" fill="' . $color . '" font-size="28" font-weight="800" font-family="Arial, sans-serif" transform="rotate(' . $rotate . ' ' . $x . ' ' . $y . ')">' . e($char) . '</text>';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="216" height="58" viewBox="0 0 216 58">'
        . '<rect width="216" height="58" fill="#fff"/>'
        . '<path d="M10 42 C44 16, 82 58, 126 24 S180 34, 206 18" fill="none" stroke="#86efac" stroke-width="1.4"/>'
        . '<path d="M16 18 C54 30, 85 10, 136 38 S184 42, 204 26" fill="none" stroke="#93c5fd" stroke-width="1.2"/>'
        . $letters
        . '<circle cx="28" cy="14" r="1.8" fill="#bae6fd"/><circle cx="164" cy="44" r="2" fill="#fecdd3"/><circle cx="104" cy="17" r="1.5" fill="#ddd6fe"/>'
        . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function visual_captcha_required(string $context): bool
{
    return in_array($context, [
        'admin_login',
        'admin_bootstrap',
        'patient_login',
        'patient_register',
        'forgot_password_request',
    ], true);
}

function render_captcha(string $context): void
{
    echo '<div class="captcha-block" style="margin-top:12px;">';
    echo '<input type="text" name="contact_website" tabindex="-1" autocomplete="off" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">';
    $turnstileSiteKey = turnstile_site_key();
    if ($turnstileSiteKey !== '') {
        echo '<div style="margin-bottom:8px;"><label>Xác minh an toàn</label><div class="cf-turnstile" data-sitekey="' . e($turnstileSiteKey) . '" data-theme="light"></div></div>';
        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }
    $siteKey = $turnstileSiteKey === '' ? recaptcha_site_key() : '';
    if ($siteKey !== '') {
        echo '<div style="margin-bottom:8px;"><label>Xác minh an toàn</label><div class="g-recaptcha" data-sitekey="' . e($siteKey) . '" data-theme="light"></div></div>';
        echo '<script src="https://www.google.com/recaptcha/api.js?hl=vi" async defer></script>';
    }
    if (visual_captcha_required($context)) {
        $visualCode = visual_captcha_code($context);
        $inputId = 'captcha_' . preg_replace('/[^a-zA-Z0-9_]/', '', $context);
        echo '<div>';
        echo '<label for="' . $inputId . '">Mã xác thực hình ảnh <span style="font-size:12px;font-weight:normal;color:#64748b">(phân biệt chữ hoa/thường)</span></label>';
        echo '<div class="visual-captcha" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
        echo '<img id="img_' . $inputId . '" src="' . e(visual_captcha_image_src($visualCode)) . '" alt="Hình ảnh chứa 6 ký tự mã xác nhận" width="200" height="52" style="border:1.5px solid var(--border);border-radius:8px;background:#fff;display:block;">';
        echo '<input id="' . $inputId . '" name="visual_captcha_answer" autocomplete="off" inputmode="text" maxlength="8" placeholder="Nhập 6 ký tự" style="flex:1;min-width:140px;height:50px" aria-label="Mã xác thực gồm 6 ký tự trong ảnh" required>';
        echo '</div>';
        echo '<div style="margin-top:6px;font-size:12px;color:#64748b;">Mã khó đọc? <a href="javascript:location.reload()" style="color:var(--primary);text-decoration:underline;">Tải lại trang để lấy mã mới</a></div>';
        echo '</div>';
    }
    echo '</div>';
}

function verify_recaptcha(?string $token): bool
{
    if (!recaptcha_configured() || trim((string) $token) === '') {
        return false;
    }

    $payload = http_build_query([
        'secret' => recaptcha_secret_key(),
        'response' => trim((string) $token),
        'remoteip' => get_client_ip(),
    ]);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ],
    ]);

    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($response === false) {
        security_log('recaptcha_verify_failed', ['reason' => 'request_failed']);
        return false;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || !isset($decoded['success'])) {
        security_log('recaptcha_verify_failed', ['reason' => 'invalid_response']);
        return false;
    }

    if ((bool) $decoded['success'] !== true) {
        security_log('recaptcha_verify_failed', [
            'reason' => 'rejected',
            'errors' => $decoded['error-codes'] ?? [],
        ]);
        return false;
    }

    return true;
}

function verify_turnstile(?string $token): bool
{
    if (!turnstile_configured() || trim((string) $token) === '') {
        return false;
    }

    $payload = http_build_query([
        'secret' => turnstile_secret_key(),
        'response' => trim((string) $token),
        'remoteip' => get_client_ip(),
    ]);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ],
    ]);

    $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    if ($response === false) {
        security_log('turnstile_verify_failed', ['reason' => 'request_failed']);
        return false;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || !isset($decoded['success'])) {
        security_log('turnstile_verify_failed', ['reason' => 'invalid_response']);
        return false;
    }

    if ((bool) $decoded['success'] !== true) {
        security_log('turnstile_verify_failed', [
            'reason' => 'rejected',
            'errors' => $decoded['error-codes'] ?? [],
        ]);
        return false;
    }

    return true;
}

function verify_visual_captcha(string $context, ?string $answer): bool
{
    $record = $_SESSION['visual_captcha'][$context] ?? null;
    unset($_SESSION['visual_captcha'][$context]);

    if (!is_array($record) || !isset($record['code'], $record['created_at'])) {
        return false;
    }

    if ((time() - (int) $record['created_at']) > 600) {
        return false;
    }

    $expected = strtoupper((string) $record['code']);
    $actual = strtoupper(preg_replace('/\s+/', '', (string) $answer) ?? '');

    return hash_equals($expected, $actual);
}

function validate_form_guard(string $context, int $limit, int $windowSeconds, int $minSubmitSeconds = 2, bool $requireCaptcha = true): ?string
{
    if (!verify_csrf_token($context, $_POST['_csrf'] ?? null)) {
        return "Phi\u{00EA}n bi\u{1EC3}u m\u{1EAB}u kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}. Vui l\u{00F2}ng t\u{1EA3}i l\u{1EA1}i trang.";
    }

    if (trim((string) ($_POST['contact_website'] ?? '')) !== '') {
        return "Kh\u00F4ng th\u1EC3 x\u00E1c minh y\u00EAu c\u1EA7u. Vui l\u00F2ng th\u1EED l\u1EA1i.";
    }

    $startedAt = (int) ($_POST['_form_started_at'] ?? 0);
    if ($startedAt <= 0 || (time() - $startedAt) < $minSubmitSeconds) {
        return "Thao t\u{00E1}c qu\u{00E1} nhanh. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i.";
    }

    if (rate_limit_hit($context, $limit, $windowSeconds)) {
        return "B\u{1EA1}n thao t\u{00E1}c qu\u{00E1} nhi\u{1EC1}u l\u{1EA7}n. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i sau \u{00ED}t ph\u{00FA}t.";
    }

    if ($requireCaptcha && turnstile_configured() && !verify_turnstile($_POST['cf-turnstile-response'] ?? null)) {
        return "Kh\u{00F4}ng th\u{1EC3} x\u{00E1}c minh Turnstile. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i.";
    }

    if ($requireCaptcha && !turnstile_configured() && recaptcha_configured() && !verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
        return "Không thể xác minh Google reCAPTCHA. Vui lòng thử lại.";
    }

    if ($requireCaptcha && visual_captcha_required($context) && !verify_visual_captcha($context, $_POST['visual_captcha_answer'] ?? null)) {
        return "Mã captcha trong ảnh không đúng.";
    }

    return null;
}

function hash_password(string $password): string
{
    $salt = random_bytes(16);
    $hash = hash_pbkdf2('sha256', $password, $salt, PASSWORD_ITERATIONS, PASSWORD_BYTES, true);

    return sprintf(
        '%s$%d$%s$%s',
        PASSWORD_ALGO,
        PASSWORD_ITERATIONS,
        base64_encode($salt),
        base64_encode($hash)
    );
}

function verify_password(string $password, string $stored): bool
{
    if (strpos($stored, PASSWORD_ALGO . '$') === 0) {
        $parts = explode('$', $stored, 4);
        if (count($parts) !== 4) {
            return false;
        }

        [, $iterations, $encodedSalt, $encodedHash] = $parts;
        $salt = base64_decode($encodedSalt, true);
        $hash = base64_decode($encodedHash, true);

        if ($salt === false || $hash === false) {
            return false;
        }

        $calculated = hash_pbkdf2('sha256', $password, $salt, (int) $iterations, strlen($hash), true);

        return hash_equals($hash, $calculated);
    }

    return password_verify($password, $stored);
}

function result_storage_path(string $filename): string
{
    return APP_RESULTS_ROOT . DIRECTORY_SEPARATOR . ltrim($filename, "\\/");
}

function store_result_upload(array $file, int $patientId, int $doctorId): string
{
    $filename = sprintf(
        'result_%d_%d_%s.pdf',
        $patientId,
        $doctorId,
        bin2hex(random_bytes(12))
    );

    if (!move_uploaded_file($file['tmp_name'], result_storage_path($filename))) {
        throw new RuntimeException('Không thể lưu tệp kết quả.');
    }

    return $filename;
}

function resolve_result_file_path(?string $stored): ?string
{
    if ($stored === null || $stored === '') {
        return null;
    }

    $candidates = [
        result_storage_path(basename($stored)),
        APP_ROOT . DIRECTORY_SEPARATOR . ltrim($stored, "\\/"),
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function delete_result_file(?string $stored): void
{
    $path = resolve_result_file_path($stored);
    if ($path !== null) {
        @unlink($path);
    }
}

function result_download_url(int $recordId): string
{
    return 'download_result.php?id=' . $recordId;
}

function require_patient_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        set_flash('error', "Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p \u{0111}\u{1EC3} ti\u{1EBF}p t\u{1EE5}c.");
        redirect('login.php');
    }

    enforce_patient_password_change();
}

function require_admin_login(): void
{
    if (!isset($_SESSION['admin_id'])) {
        set_flash('error', "Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p qu\u{1EA3}n tr\u{1ECB}.");
        redirect('admin_login.php');
    }

    refresh_admin_session();
}

function admin_account_count(): int
{
    global $conn;

    $result = $conn->query('SELECT COUNT(*) AS total FROM admins');
    $row = $result->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function has_any_admin_account(): bool
{
    return admin_account_count() > 0;
}

function create_initial_root_admin(string $username, string $fullName, string $password, string $department = ''): array
{
    global $conn;

    if (has_any_admin_account()) {
        throw new RuntimeException('Hệ thống đã có tài khoản admin.');
    }

    $passwordHash = hash_password($password);
    $role = 'root_admin';
    $isRoot = 1;
    $isActive = 1;
    $canManageAccounts = 1;
    $canManageRecords = 1;
    $canManageAllRecords = 1;

    $stmt = $conn->prepare(
        'INSERT INTO admins (username, full_name, department, role, is_root, is_active, can_manage_accounts, can_manage_records, can_manage_all_records, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'ssssiiiiis',
        $username,
        $fullName,
        $department,
        $role,
        $isRoot,
        $isActive,
        $canManageAccounts,
        $canManageRecords,
        $canManageAllRecords,
        $passwordHash
    );
    $stmt->execute();
    $adminId = (int) $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT *
         FROM admins
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin) {
        throw new RuntimeException('Không thể tải lại tài khoản admin vừa tạo.');
    }

    audit_log('initial_root_admin_created', [
        'admin_id' => $adminId,
        'username' => $username,
    ]);

    return $admin;
}

function load_admin_session(array $admin): void
{
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = (string) $admin['username'];
    $_SESSION['admin_full_name'] = (string) ($admin['full_name'] ?? $admin['username']);
    $_SESSION['admin_role'] = (string) ($admin['role'] ?? 'staff');
    $_SESSION['admin_department'] = $admin['department'] ?? null;
    $_SESSION['admin_is_root'] = (int) ($admin['is_root'] ?? 0) === 1;
    $permissions = [];
    foreach (admin_permission_definitions() as $permission => $definition) {
        $permissions[$permission] = (int) ($admin[$definition['column']] ?? 0) === 1;
    }
    $_SESSION['admin_permissions'] = $permissions;
}

function refresh_admin_session(): void
{
    global $conn;

    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('error', "Phi\u{00EA}n qu\u{1EA3}n tr\u{1ECB} kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}. Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p l\u{1EA1}i.");
        redirect('admin_login.php');
    }

    $stmt = $conn->prepare(
        'SELECT *
         FROM admins
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin || (int) ($admin['is_active'] ?? 0) !== 1) {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('error', "Phi\u{00EA}n qu\u{1EA3}n tr\u{1ECB} \u{0111}\u{00E3} h\u{1EBF}t hi\u{1EC7}u l\u{1EF1}c. Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p l\u{1EA1}i.");
        redirect('admin_login.php');
    }

    load_admin_session($admin);
}

function admin_home_path(): string
{
    if (!isset($_SESSION['admin_id'])) {
        return 'admin_login.php';
    }

    if (is_root_admin() || admin_can('manage_records')) {
        return 'admin_add_record.php';
    }

    if (admin_can_any(array_keys(admin_permission_definitions()))) {
        return 'admin_accounts.php';
    }

    return 'admin_login.php';
}

function asset_url(string $path): string
{
    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    $absolutePath = APP_PUBLIC_ASSETS_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

    return 'assets/' . $normalized . '?v=' . rawurlencode($version);
}

function is_root_admin(): bool
{
    return !empty($_SESSION['admin_is_root']);
}

function admin_can(string $permission): bool
{
    if (is_root_admin()) {
        return true;
    }

    return !empty($_SESSION['admin_permissions'][$permission]);
}

function admin_can_any(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (admin_can((string) $permission)) {
            return true;
        }
    }

    return false;
}

function require_admin_permission(string $permission): void
{
    require_admin_login();
    if (!admin_can($permission)) {
        set_flash('error', "T\u{00E0}i kho\u{1EA3}n c\u{1EE7}a b\u{1EA1}n kh\u{00F4}ng c\u{00F3} quy\u{1EC1}n thao t\u{00E1}c ch\u{1EE9}c n\u{0103}ng n\u{00E0}y.");
        redirect(admin_home_path());
    }
}

function require_root_admin(): void
{
    require_admin_login();
    if (!is_root_admin()) {
        set_flash('error', "Ch\u{1EC9} admin g\u{1ED1}c m\u{1EDB}i c\u{00F3} quy\u{1EC1}n qu\u{1EA3}n l\u{00FD} khu v\u{1EF1}c n\u{00E0}y.");
        redirect(admin_home_path());
    }
}

function get_all_site_settings_cached(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cacheFile = APP_RUNTIME_ROOT . '/site_settings_cache.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 600)) {
        $json = @file_get_contents($cacheFile);
        if ($json !== false) {
            $data = json_decode($json, true);
            if (is_array($data) && !empty($data)) {
                $cache = $data;
                return $cache;
            }
        }
    }

    global $conn;
    $cache = [];
    try {
        $result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cache[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
            $result->free();
        }
        if (!empty($cache)) {
            @file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
    } catch (Throwable $exception) {
        log_internal_error('fetch_site_settings_failed', $exception);
    }

    return $cache;
}

function site_settings(array $defaults, bool $useDefaultIfEmpty = false): array
{
    $all = get_all_site_settings_cached();
    $settings = $defaults;
    foreach ($defaults as $key => $defaultVal) {
        if (array_key_exists($key, $all)) {
            $val = $all[$key];
            if ($useDefaultIfEmpty && $val === '') {
                continue;
            }
            $settings[$key] = $val;
        }
    }

    return $settings;
}

function site_setting(string $key, string $default = ''): string
{
    $settings = site_settings([$key => $default]);
    return (string) ($settings[$key] ?? $default);
}

function site_setting_bool(string $key, bool $default = false): bool
{
    $value = site_setting($key, $default ? '1' : '0');
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

function save_site_settings(array $settings): void
{
    global $conn;

    $stmt = $conn->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    foreach ($settings as $key => $value) {
        $settingKey = (string) $key;
        $settingValue = trim((string) $value);
        $stmt->bind_param('ss', $settingKey, $settingValue);
        $stmt->execute();
    }

    $stmt->close();
    @unlink(APP_RUNTIME_ROOT . '/site_settings_cache.json');
}

function get_active_quick_replies(int $limit = 12): array
{
    global $conn;

    $stmt = $conn->prepare(
        'SELECT id, question, answer, sort_order
         FROM chat_quick_replies
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC
         LIMIT ?'
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function find_quick_reply(?int $quickReplyId, string $message = ''): ?array
{
    global $conn;

    if ($quickReplyId !== null && $quickReplyId > 0) {
        $stmt = $conn->prepare(
            'SELECT id, question, answer
             FROM chat_quick_replies
             WHERE id = ? AND is_active = 1
             LIMIT 1'
        );
        $stmt->bind_param('i', $quickReplyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    $normalized = mb_strtolower(trim($message), 'UTF-8');
    if ($normalized === '') {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, question, answer
         FROM chat_quick_replies
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        if (mb_strtolower(trim((string) $row['question']), 'UTF-8') === $normalized) {
            $stmt->close();
            return $row;
        }
    }
    $stmt->close();

    return null;
}

function chatbot_default_reply(): string
{
    return site_setting(
        'chatbot_fallback',
        "Ch\u{00FA}ng t\u{00F4}i \u{0111}\u{00E3} nh\u{1EAD}n \u{0111}\u{01B0}\u{1EE3}c c\u{00E2}u h\u{1ECF}i c\u{1EE7}a b\u{1EA1}n. B\u{1ED9} ph\u{1EAD}n h\u{1ED7} tr\u{1EE3} s\u{1EBD} ph\u{1EA3}n h\u{1ED3}i s\u{1EDB}m ho\u{1EB7}c b\u{1EA1}n c\u{00F3} th\u{1EC3} ch\u{1ECD}n m\u{1ED9}t c\u{00E2}u h\u{1ECF}i nhanh b\u{00EA}n d\u{01B0}\u{1EDB}i \u{0111}\u{1EC3} xem h\u{01B0}\u{1EDB}ng d\u{1EAB}n ngay."
    );
}

function appointments_enabled(): bool
{
    return site_setting_bool('appointments_enabled', true);
}

/**
 * Trả về lịch nhận lịch theo từng ngày trong tuần.
 * Format JSON: {"1":{"open":true,"from":"07:00","to":"17:00","break_open":true,"break_from":"12:00","break_to":"13:00"}, ...}
 * Key 1=T2, 2=T3, ..., 7=CN (theo date('N'))
 */
function get_appointment_schedule(): array
{
    $default = [
        '1' => ['open' => true,  'from' => '07:00', 'to' => '17:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // T2
        '2' => ['open' => true,  'from' => '07:00', 'to' => '17:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // T3
        '3' => ['open' => true,  'from' => '07:00', 'to' => '17:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // T4
        '4' => ['open' => true,  'from' => '07:00', 'to' => '17:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // T5
        '5' => ['open' => true,  'from' => '07:00', 'to' => '17:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // T6
        '6' => ['open' => true,  'from' => '07:00', 'to' => '12:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // T7
        '7' => ['open' => false, 'from' => '07:00', 'to' => '11:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'], // CN
    ];

    $raw = site_setting('appointment_schedule', '');
    if ($raw === '') {
        return $default;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $default;
    }

    // Merge với default để đảm bảo đủ 7 ngày và đủ key
    foreach ($default as $day => $cfg) {
        if (!isset($decoded[$day])) {
            $decoded[$day] = $cfg;
        } else {
            $decoded[$day] = array_merge($cfg, $decoded[$day]);
        }
    }

    return $decoded;
}

/**
 * Kiểm tra thời điểm hiện tại có nằm trong giờ nhận lịch không.
 * Trả về ['ok' => bool, 'reason' => string]
 */
function check_appointment_schedule(): array
{
    if (!appointments_enabled()) {
        return ['ok' => false, 'reason' => 'Tính năng đặt lịch hiện đang tạm ngưng.'];
    }

    $schedule = get_appointment_schedule();
    $now = new DateTime('now');
    $dayKey = $now->format('N'); // 1=T2 ... 7=CN
    $currentTime = $now->format('H:i');

    $dayCfg = $schedule[$dayKey] ?? null;
    if ($dayCfg === null || empty($dayCfg['open'])) {
        $dayNames = ['1'=>'Thứ Hai','2'=>'Thứ Ba','3'=>'Thứ Tư','4'=>'Thứ Năm','5'=>'Thứ Sáu','6'=>'Thứ Bảy','7'=>'Chủ Nhật'];
        return ['ok' => false, 'reason' => ($dayNames[$dayKey] ?? 'Hôm nay') . ' không nhận lịch hẹn.'];
    }

    $from = $dayCfg['from'] ?? '07:00';
    $to   = $dayCfg['to']   ?? '17:00';

    if ($currentTime < $from || $currentTime > $to) {
        return ['ok' => false, 'reason' => "Ngoài giờ nhận lịch ({$from} – {$to})."];
    }

    // Kiểm tra giờ nghỉ trưa
    if (!empty($dayCfg['break_open'])) {
        $bFrom = $dayCfg['break_from'] ?? '12:00';
        $bTo   = $dayCfg['break_to']   ?? '13:00';
        if ($currentTime >= $bFrom && $currentTime <= $bTo) {
            return ['ok' => false, 'reason' => "Đang trong giờ nghỉ trưa ({$bFrom} – {$bTo}). Vui lòng đặt lịch sau."];
        }
    }

    return ['ok' => true, 'reason' => ''];
}

function validate_appointment_slot(mysqli $conn, int $doctorId, int $patientId, string $appointmentDate, int $excludeAppointmentId = 0): ?string
{
    $ts = strtotime($appointmentDate);
    if ($ts === false) {
        return 'Thời gian hẹn không hợp lệ.';
    }

    if (($ts - time()) < 1800) {
        return 'Lịch khám phải được đặt trước ít nhất 30 phút so với thời điểm hiện tại.';
    }

    $schedule = get_appointment_schedule();
    $dayKey = date('N', $ts);
    $timeStr = date('H:i', $ts);
    $dayCfg = $schedule[$dayKey] ?? null;

    if ($dayCfg === null || empty($dayCfg['open'])) {
        $dayNames = ['1'=>'Thứ Hai','2'=>'Thứ Ba','3'=>'Thứ Tư','4'=>'Thứ Năm','5'=>'Thứ Sáu','6'=>'Thứ Bảy','7'=>'Chủ Nhật'];
        return ($dayNames[$dayKey] ?? 'Ngày này') . ' phòng khám không làm việc. Vui lòng chọn ngày khác.';
    }

    $from = $dayCfg['from'] ?? '07:00';
    $to   = $dayCfg['to']   ?? '17:00';
    if ($timeStr < $from || $timeStr > $to) {
        return "Thời gian khám nằm ngoài giờ làm việc ({$from} – {$to}).";
    }

    if (!empty($dayCfg['break_open'])) {
        $bFrom = $dayCfg['break_from'] ?? '12:00';
        $bTo   = $dayCfg['break_to']   ?? '13:00';
        if ($timeStr >= $bFrom && $timeStr < $bTo) {
            return "Thời gian khám rơi vào giờ nghỉ trưa ({$bFrom} – {$bTo}). Vui lòng chọn giờ khác.";
        }
    }

    $sqlDoctor = 'SELECT id, appointment_date FROM appointments 
                  WHERE doctor_id = ? 
                  AND status NOT IN ("Đã hủy", "Đã huỷ") 
                  AND ABS(TIMESTAMPDIFF(MINUTE, appointment_date, ?)) < 30';
    if ($excludeAppointmentId > 0) {
        $sqlDoctor .= ' AND id != ' . (int) $excludeAppointmentId;
    }
    $sqlDoctor .= ' LIMIT 1';

    $stmtDoc = $conn->prepare($sqlDoctor);
    $stmtDoc->bind_param('is', $doctorId, $appointmentDate);
    $stmtDoc->execute();
    $resDoc = $stmtDoc->get_result();
    $conflictDoc = $resDoc->fetch_assoc();
    $stmtDoc->close();

    if ($conflictDoc) {
        $conflictTime = date('H:i d/m/Y', strtotime((string) $conflictDoc['appointment_date']));
        return "Bác sĩ đã có lịch hẹn vào lúc {$conflictTime}. Mỗi ca khám cách nhau tối thiểu 30 phút. Vui lòng chọn giờ khác.";
    }

    $sqlPatient = 'SELECT id, appointment_date FROM appointments 
                   WHERE patient_id = ? 
                   AND status NOT IN ("Đã hủy", "Đã huỷ") 
                   AND ABS(TIMESTAMPDIFF(MINUTE, appointment_date, ?)) < 30';
    if ($excludeAppointmentId > 0) {
        $sqlPatient .= ' AND id != ' . (int) $excludeAppointmentId;
    }
    $sqlPatient .= ' LIMIT 1';

    $stmtPat = $conn->prepare($sqlPatient);
    $stmtPat->bind_param('is', $patientId, $appointmentDate);
    $stmtPat->execute();
    $resPat = $stmtPat->get_result();
    $conflictPat = $resPat->fetch_assoc();
    $stmtPat->close();

    if ($conflictPat) {
        return "Bạn đã có một lịch khám khác gần khung giờ này. Vui lòng kiểm tra lại danh sách lịch khám.";
    }

    return null;
}

function normalize_appointment_datetime(string $dateValue): ?string
{
    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function appointment_is_editable(array $appointment): bool
{
    $status = trim((string) ($appointment['status'] ?? ''));
    return !in_array($status, ['Đã hủy', 'Đã huỷ', 'Đã khám', 'Hoàn tất'], true);
}

function find_doctor_by_id(mysqli $conn, int $doctorId): ?array
{
    $stmt = $conn->prepare('SELECT id, name, department FROM doctors WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $doctorId);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $doctor ?: null;
}

function find_patient_appointment(mysqli $conn, int $appointmentId, int $patientId): ?array
{
    $stmt = $conn->prepare(
        'SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, a.reason, a.status, d.department
         FROM appointments a
         INNER JOIN doctors d ON d.id = a.doctor_id
         WHERE a.id = ? AND a.patient_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $appointmentId, $patientId);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $appointment ?: null;
}

function find_admin_appointment(mysqli $conn, int $appointmentId): ?array
{
    $stmt = $conn->prepare(
        'SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, a.reason, a.status, d.department
         FROM appointments a
         INNER JOIN doctors d ON d.id = a.doctor_id
         WHERE a.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $appointmentId);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $appointment ?: null;
}

function can_admin_manage_appointment(array $appointment, bool $canManageAllRecords, string $adminDepartment): bool
{
    if ($canManageAllRecords || $adminDepartment === '') {
        return true;
    }

    return (string) ($appointment['department'] ?? '') === $adminDepartment;
}

function create_patient_appointment(mysqli $conn, int $patientId, int $doctorId, string $appointmentDate, string $reason): void
{
    $status = 'Chờ khám';
    $stmt = $conn->prepare(
        'INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iisss', $patientId, $doctorId, $appointmentDate, $reason, $status);
    $stmt->execute();
    $stmt->close();
}

function update_appointment(mysqli $conn, int $appointmentId, int $doctorId, string $appointmentDate, string $reason): void
{
    $stmt = $conn->prepare(
        'UPDATE appointments
         SET doctor_id = ?, appointment_date = ?, reason = ?
         WHERE id = ?'
    );
    $stmt->bind_param('issi', $doctorId, $appointmentDate, $reason, $appointmentId);
    $stmt->execute();
    $stmt->close();
}

function delete_appointment_by_id(mysqli $conn, int $appointmentId): void
{
    $stmt = $conn->prepare('DELETE FROM appointments WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $appointmentId);
    $stmt->execute();
    $stmt->close();
}

function doctor_photo_url(?string $stored): string
{
    if ($stored === null || trim($stored) === '') {
        return asset_url('doctor-placeholder.svg');
    }

    return asset_url('doctor_photos/' . basename($stored));
}

function clinic_logo_url(): string
{
    $stored = site_setting('clinic_logo_path', '');
    if ($stored === '') {
        return asset_url('clinic-logo.svg');
    }

    return asset_url('branding/' . basename($stored));
}

function resolve_clinic_logo_path(?string $stored): ?string
{
    if ($stored === null || trim($stored) === '') {
        return null;
    }

    $path = APP_BRANDING_ROOT . DIRECTORY_SEPARATOR . basename($stored);
    return is_file($path) ? $path : null;
}

function delete_clinic_logo(?string $stored): void
{
    $path = resolve_clinic_logo_path($stored);
    if ($path !== null) {
        @unlink($path);
    }
}

function store_clinic_logo(array $file, ?string $currentLogo = null): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải logo thất bại.');
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('Logo không được vượt quá 3MB.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['svg', 'png', 'jpg', 'jpeg', 'webp'], true)) {
        throw new RuntimeException('Chỉ chấp nhận logo SVG, PNG, JPG hoặc WEBP.');
    }

    if ($extension !== 'svg' && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Tệp logo không phải ảnh hợp lệ.');
        }
    }

    $normalizedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
    $filename = 'clinic_logo_' . bin2hex(random_bytes(10)) . '.' . $normalizedExtension;
    $destination = APP_BRANDING_ROOT . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Không thể lưu logo phòng khám.');
    }

    if ($currentLogo !== null && $currentLogo !== '') {
        delete_clinic_logo($currentLogo);
    }

    return $filename;
}

function resolve_doctor_photo_path(?string $stored): ?string
{
    if ($stored === null || trim($stored) === '') {
        return null;
    }

    $path = APP_DOCTOR_PHOTO_ROOT . DIRECTORY_SEPARATOR . basename($stored);
    return is_file($path) ? $path : null;
}

function delete_doctor_photo(?string $stored): void
{
    $path = resolve_doctor_photo_path($stored);
    if ($path !== null) {
        @unlink($path);
    }
}

function store_doctor_photo(array $file, ?string $currentPhoto = null): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải ảnh bác sĩ thất bại.');
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('Ảnh bác sĩ không được vượt quá 3MB.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Tệp tải lên không phải ảnh hợp lệ.');
        }
    }

    $filename = 'doctor_' . bin2hex(random_bytes(12)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
    $destination = APP_DOCTOR_PHOTO_ROOT . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Không thể lưu ảnh bác sĩ.');
    }

    if ($currentPhoto !== null && $currentPhoto !== '') {
        delete_doctor_photo($currentPhoto);
    }

    return $filename;
}

function resolve_news_media_path(?string $stored): ?string
{
    if ($stored === null || trim($stored) === '') {
        return null;
    }

    $path = APP_NEWS_MEDIA_ROOT . DIRECTORY_SEPARATOR . basename($stored);
    return is_file($path) ? $path : null;
}

function delete_news_media(?string $stored): void
{
    $path = resolve_news_media_path($stored);
    if ($path !== null) {
        @unlink($path);
    }
}

function news_media_url(?string $stored): string
{
    if ($stored === null || trim($stored) === '') {
        return '';
    }

    return asset_url('news_media/' . basename($stored));
}

function store_news_media(array $file, ?string $currentMedia = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải media tin tức thất bại.');
    }

    if (($file['size'] ?? 0) > 50 * 1024 * 1024) {
        throw new RuntimeException('Ảnh hoặc video tin tức không được vượt quá 50MB.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $videoExtensions = ['mp4', 'webm', 'mov'];
    if (!in_array($extension, array_merge($imageExtensions, $videoExtensions), true)) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG, WEBP, GIF hoặc video MP4, WEBM, MOV.');
    }

    $mediaType = in_array($extension, $imageExtensions, true) ? 'image' : 'video';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'video/mp4', 'video/webm', 'video/quicktime',
        ];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new RuntimeException('Tệp tải lên không phải ảnh hoặc video hợp lệ.');
        }
    }

    $normalizedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
    $filename = 'news_' . bin2hex(random_bytes(12)) . '.' . $normalizedExtension;
    $destination = APP_NEWS_MEDIA_ROOT . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Không thể lưu ảnh hoặc video tin tức.');
    }

    if ($currentMedia !== null && $currentMedia !== '') {
        delete_news_media($currentMedia);
    }

    return ['path' => $filename, 'type' => $mediaType];
}

function render_news_media(array $post): void
{
    $mediaPath = (string) ($post['media_path'] ?? '');
    if ($mediaPath === '') {
        return;
    }

    $mediaUrl = news_media_url($mediaPath);
    if ($mediaUrl === '') {
        return;
    }

    $mediaType = (string) ($post['media_type'] ?? '');
    if ($mediaType === 'video') {
        echo '<video controls preload="metadata" style="width:100%;max-height:360px;border-radius:14px;margin-bottom:12px;background:#000;"><source src="' . e($mediaUrl) . '"></video>';
        return;
    }

    echo '<img src="' . e($mediaUrl) . '" alt="' . e((string) ($post['title'] ?? 'Tin tức')) . '" style="width:100%;max-height:320px;object-fit:cover;border-radius:14px;margin-bottom:12px;">';
}

function save_chat_message(int $patientId, string $sender, string $message): void
{
    global $conn;

    $stmt = $conn->prepare('INSERT INTO chats (patient_id, sender, message) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $patientId, $sender, $message);
    $stmt->execute();
    $stmt->close();
}

function get_patient_chat_messages(int $patientId, int $limit = 30): array
{
    global $conn;

    $stmt = $conn->prepare(
        'SELECT id, sender, message, created_at
         FROM chats
         WHERE patient_id = ?
         ORDER BY id DESC
         LIMIT ?'
    );
    $stmt->bind_param('ii', $patientId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_reverse($rows);
}

function get_patient_chat_poll_payload(int $patientId, int $limit = 30): array
{
    $messages = get_patient_chat_messages($patientId, $limit);
    $latestChatId = 0;

    if ($messages !== []) {
        $lastMessage = $messages[count($messages) - 1];
        $latestChatId = (int) ($lastMessage['id'] ?? 0);
    }

    return [
        'ok' => true,
        'latest_chat_id' => $latestChatId,
        'message_count' => count($messages),
        'messages' => $messages,
    ];
}

function create_patient_announcement(
    mysqli $conn,
    string $title,
    string $message,
    int $createdByAdminId,
    bool $pushToChat = true
): int {
    $stmt = $conn->prepare(
        'INSERT INTO patient_announcements (title, message, created_by_admin_id, push_to_chat)
         VALUES (?, ?, ?, ?)'
    );
    $pushFlag = $pushToChat ? 1 : 0;
    $stmt->bind_param('ssii', $title, $message, $createdByAdminId, $pushFlag);
    $stmt->execute();
    $announcementId = (int) $stmt->insert_id;
    $stmt->close();

    if ($pushToChat) {
        $chatMessage = "Thông báo mới: {$title}\n{$message}";
        $result = $conn->query('SELECT id FROM patients ORDER BY id ASC');
        while ($row = $result->fetch_assoc()) {
            save_chat_message((int) $row['id'], 'bot', $chatMessage);
        }
        $result->close();
    }

    audit_log('patient_announcement_created', [
        'announcement_id' => $announcementId,
        'title' => $title,
        'push_to_chat' => $pushToChat,
    ]);

    return $announcementId;
}

function get_recent_patient_announcements(mysqli $conn, int $limit = 5): array
{
    $stmt = $conn->prepare(
        'SELECT pa.id, pa.title, pa.message, pa.created_at, pa.push_to_chat, a.full_name AS admin_name
         FROM patient_announcements pa
         LEFT JOIN admins a ON a.id = pa.created_by_admin_id
         ORDER BY pa.id DESC
         LIMIT ?'
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function get_patient_announcements(mysqli $conn, int $limit = 10): array
{
    $stmt = $conn->prepare(
        'SELECT id, title, message, created_at
         FROM patient_announcements
         ORDER BY id DESC
         LIMIT ?'
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function save_news_post(int $postId, string $title, string $excerpt, string $body, bool $isPublished, int $adminId, ?string $mediaPath = null, ?string $mediaType = null): int
{
    global $conn;

    $published = $isPublished ? 1 : 0;
    if ($postId > 0) {
        $stmt = $conn->prepare(
            'UPDATE news_posts
             SET title = ?, excerpt = ?, body = ?, is_published = ?, media_path = ?, media_type = ?
             WHERE id = ?'
        );
        $stmt->bind_param('sssissi', $title, $excerpt, $body, $published, $mediaPath, $mediaType, $postId);
        $stmt->execute();
        $stmt->close();
        return $postId;
    }

    $stmt = $conn->prepare(
        'INSERT INTO news_posts (title, excerpt, body, is_published, created_by_admin_id, media_path, media_type)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssiiss', $title, $excerpt, $body, $published, $adminId, $mediaPath, $mediaType);
    $stmt->execute();
    $newId = (int) $stmt->insert_id;
    $stmt->close();

    return $newId;
}

function delete_news_post(int $postId): void
{
    global $conn;

    $stmt = $conn->prepare('SELECT media_path FROM news_posts WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM news_posts WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $stmt->close();

    delete_news_media((string) ($post['media_path'] ?? ''));
}

function get_recent_news_posts(int $limit = 10, bool $publishedOnly = false): array
{
    global $conn;

    if ($publishedOnly) {
        $stmt = $conn->prepare(
            'SELECT id, title, excerpt, body, media_path, media_type, is_published, created_at, updated_at
             FROM news_posts
             WHERE is_published = 1
             ORDER BY created_at DESC, id DESC
             LIMIT ?'
        );
    } else {
        $stmt = $conn->prepare(
            'SELECT id, title, excerpt, body, media_path, media_type, is_published, created_at, updated_at
             FROM news_posts
             ORDER BY created_at DESC, id DESC
             LIMIT ?'
        );
    }
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function save_customer_resource(int $resourceId, string $title, string $description, string $resourceUrl, int $sortOrder, bool $isPublished, int $adminId): int
{
    global $conn;

    $published = $isPublished ? 1 : 0;
    if ($resourceId > 0) {
        $stmt = $conn->prepare(
            'UPDATE customer_resources
             SET title = ?, description = ?, resource_url = ?, sort_order = ?, is_published = ?
             WHERE id = ?'
        );
        $stmt->bind_param('sssiii', $title, $description, $resourceUrl, $sortOrder, $published, $resourceId);
        $stmt->execute();
        $stmt->close();
        return $resourceId;
    }

    $stmt = $conn->prepare(
        'INSERT INTO customer_resources (title, description, resource_url, sort_order, is_published, created_by_admin_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssiii', $title, $description, $resourceUrl, $sortOrder, $published, $adminId);
    $stmt->execute();
    $newId = (int) $stmt->insert_id;
    $stmt->close();

    return $newId;
}

function delete_customer_resource(int $resourceId): void
{
    global $conn;

    $stmt = $conn->prepare('DELETE FROM customer_resources WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $resourceId);
    $stmt->execute();
    $stmt->close();
}

function get_customer_resources(int $limit = 12, bool $publishedOnly = false): array
{
    global $conn;

    if ($publishedOnly) {
        $stmt = $conn->prepare(
            'SELECT id, title, description, resource_url, sort_order, is_published, created_at, updated_at
             FROM customer_resources
             WHERE is_published = 1
             ORDER BY sort_order ASC, id DESC
             LIMIT ?'
        );
    } else {
        $stmt = $conn->prepare(
            'SELECT id, title, description, resource_url, sort_order, is_published, created_at, updated_at
             FROM customer_resources
             ORDER BY sort_order ASC, id DESC
             LIMIT ?'
        );
    }
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function admin_support_notice_path(int $adminId): string
{
    return APP_ADMIN_NOTICE_ROOT . DIRECTORY_SEPARATOR . 'admin_' . max(0, $adminId) . '_support_notice.json';
}

function get_admin_support_notice_state(int $adminId): array
{
    $path = admin_support_notice_path($adminId);
    if (!is_file($path)) {
        return ['last_seen_chat_id' => 0];
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return ['last_seen_chat_id' => 0];
    }

    return [
        'last_seen_chat_id' => (int) ($data['last_seen_chat_id'] ?? 0),
    ];
}

function mark_admin_support_notice_seen(int $adminId, int $chatId): void
{
    $path = admin_support_notice_path($adminId);
    $state = [
        'last_seen_chat_id' => max(0, $chatId),
        'updated_at' => date('c'),
    ];
    @file_put_contents($path, json_encode($state), LOCK_EX);
}

function get_admin_support_chat_notice(mysqli $conn, int $adminId, int $previewLimit = 3): array
{
    $state = get_admin_support_notice_state($adminId);
    $lastSeenChatId = (int) ($state['last_seen_chat_id'] ?? 0);

    $latestRow = $conn->query("SELECT MAX(id) AS latest_id FROM chats WHERE sender = 'patient'")->fetch_assoc();
    $latestPatientChatId = (int) ($latestRow['latest_id'] ?? 0);
    if ($lastSeenChatId > $latestPatientChatId) {
        $lastSeenChatId = 0;
        mark_admin_support_notice_seen($adminId, 0);
    }

    if ($latestPatientChatId <= $lastSeenChatId) {
        return [
            'has_new' => false,
            'unread_count' => 0,
            'latest_chat_id' => $latestPatientChatId,
            'messages' => [],
        ];
    }

    $countStmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM chats WHERE sender = 'patient' AND id > ?");
    $countStmt->bind_param('i', $lastSeenChatId);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();

    $previewStmt = $conn->prepare(
        "SELECT c.id, c.patient_id, c.message, c.created_at, p.full_name, p.cccd, p.phone
         FROM chats c
         INNER JOIN patients p ON p.id = c.patient_id
         WHERE c.sender = 'patient' AND c.id > ?
         ORDER BY c.id DESC
         LIMIT ?"
    );
    $previewStmt->bind_param('ii', $lastSeenChatId, $previewLimit);
    $previewStmt->execute();
    $messages = $previewStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $previewStmt->close();

    return [
        'has_new' => true,
        'unread_count' => (int) ($countRow['unread_count'] ?? 0),
        'latest_chat_id' => $latestPatientChatId,
        'messages' => $messages,
    ];
}

function render_header(string $title, string $activeNav = ''): void
{
    $isPatient = isset($_SESSION['user_id']);
    $isAdmin = isset($_SESSION['admin_id']);
    $clinicName = site_setting('clinic_name', "Phòng khám đa khoa Phú Thái");

    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($title) . '</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">';
    echo '<style>
        :root {
            --primary: #0077b6;
            --primary-hover: #005f92;
            --secondary: #00b4d8;
            --surface: #ffffff;
            --soft: #f0f7fb;
            --bg: #f8fafc;
            --bg2: #f1f5f9;
            --ink: #1e293b;
            --tx: #1e293b;
            --tx2: #64748b;
            --muted: #64748b;
            --border: #dbe4ec;
            --ct: #e2e8f0;
            --shadow: 0 10px 25px rgba(15,35,55,.06);
            --r: 12px;
            --radius: 12px;
            --danger: #b91c1c;
            --err-tx: #b91c1c;
            color-scheme: light;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { 
            margin: 0; 
            font-family: "Be Vietnam Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f8fafc; 
            color: var(--tx); 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            line-height: 1.6;
        }
        main { flex: 1; }
        a { color: inherit; text-decoration: none; }
        
        /* SITE HEADER */
        .site-header { position: sticky; top: 0; z-index: 50; background: rgba(255,255,255,.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); box-shadow: 0 2px 12px rgba(0,0,0,.03); }
        .site-header-inner { max-width: 1180px; margin: 0 auto; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; min-height: 64px; }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--primary); }
        .logo-mark { height: 46px; width: auto; max-width: 140px; object-fit: contain; display: block; }
        .logo-text { font-size: 17px; font-weight: 800; line-height: 1.15; color: #0f3d61; letter-spacing: -0.3px; display: block; }
        .logo-sub { font-size: 11px; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px; display: block; }
        
        .nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .nav-link { padding: 8px 12px; border-radius: 8px; font-size: 14px; font-weight: 500; color: #334155; transition: all .15s; }
        .nav-link:hover { color: var(--primary); background: var(--soft); }
        .nav-link.active { color: var(--primary); font-weight: 700; background: var(--soft); box-shadow: inset 0 -2px 0 var(--primary); }
        .nav-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 999px; font-size: 13.5px; font-weight: 600; transition: all .15s; }
        .nav-btn-primary { background: linear-gradient(135deg, #0077b6, #0096c7); color: #fff!important; box-shadow: 0 4px 12px rgba(0,119,182,.2); }
        .nav-btn-primary:hover { background: #005f92; transform: translateY(-1px); }
        .nav-btn-outline { border: 1.5px solid var(--border); color: #334155; background: #fff; }
        .nav-btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .nav-pill { padding: 8px 14px; background: #e0f2fe; color: #0284c7!important; border-radius: 999px; font-size: 13.5px; font-weight: 600; }

        /* CONTAINERS & SECTIONS */
        .wrap { max-width: 1180px; margin: 0 auto; padding: 24px 20px 48px; }
        .section { margin-bottom: 36px; }
        .section-header { margin-bottom: 20px; }
        .section-title { font-size: 26px; font-weight: 700; color: #0f2942; margin: 0 0 6px; }
        .section-lead { color: var(--muted); font-size: 15px; margin: 0; }
        
        .card { background: var(--surface); border-radius: 16px; padding: 24px; box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px; }
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }

        /* HERO SECTION */
        .hero { background: linear-gradient(135deg, #0077b6 0%, #0096c7 60%, #48cae4 100%); color: #fff; padding: 48px 20px 56px; }
        .hero-inner { max-width: 1180px; margin: 0 auto; display: grid; grid-template-columns: minmax(0,1.3fr) minmax(300px,.9fr); gap: 32px; align-items: center; }
        .hero h1 { font-size: clamp(28px, 4vw, 40px); line-height: 1.2; margin: 0 0 14px; font-weight: 800; }
        .hero p { font-size: 16px; line-height: 1.7; opacity: .95; margin: 0 0 24px; max-width: 600px; }
        .hero-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3); font-size: 13px; font-weight: 600; color: #fff; }
        .hero-panel { background: rgba(255,255,255,.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.25); border-radius: 20px; padding: 24px; }
        .hero-panel h3 { margin: 0 0 16px; font-size: 18px; font-weight: 700; color: #fff; }
        .hero-list { display: grid; gap: 10px; }
        .hero-item { padding: 12px 16px; border-radius: 12px; background: rgba(255,255,255,.12); font-size: 14px; line-height: 1.5; color: #f8fafc; }

        /* BUTTONS & FORMS */
        button, .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; border-radius: 10px; background: var(--primary); color: #fff; padding: 11px 18px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .15s; text-decoration: none; }
        button:hover, .btn:hover { background: var(--primary-hover); }
        .btn-secondary { background: #475569; }
        .btn-secondary:hover { background: #334155; }
        .btn-light { background: var(--soft); color: var(--primary); }
        .btn-light:hover { background: #e0f2fe; }
        .btn-outline { background: transparent; border: 1.5px solid var(--border); color: #334155; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: #fff; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #991b1b; }
        
        label { display: block; font-weight: 600; font-size: 13.5px; color: #334155; margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 11px 14px; border: 1.5px solid var(--border); border-radius: 10px; font: inherit; font-size: 14.5px; background: #fff; color: var(--tx); transition: border-color .15s, box-shadow .15s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,119,182,.12); }
        
        /* FOOTER */
        .site-footer { background: #0c2333; color: #cbd5e1; margin-top: auto; font-size: 14px; border-top: 1px solid rgba(255,255,255,.08); }
        .footer-main { max-width: 1180px; margin: 0 auto; padding: 40px 20px 32px; display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 36px; }
        .footer-col h4 { color: #fff; font-size: 15px; font-weight: 700; margin: 0 0 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .footer-col p { margin: 0 0 10px; line-height: 1.6; }
        .footer-col a { color: #94a3b8; transition: color .15s; }
        .footer-col a:hover { color: #38bdf8; }
        .footer-contact-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start; }
        .footer-bottom { max-width: 1180px; margin: 0 auto; padding: 18px 20px; border-top: 1px solid rgba(255,255,255,.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 12.5px; color: #94a3b8; }
        .footer-bottom a { color: #cbd5e1; }
        .footer-bottom a:hover { color: #38bdf8; }
        .staff-link { color: #94a3b8; font-weight: 500; }
        .staff-link:hover { color: #f8fafc; }

        /* FLASH MESSAGES */
        .flash { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* FLOATING SUPPORT WIDGET (UNIFIED FAB) */
        .fab-support-container { position: fixed; right: 24px; bottom: 24px; z-index: 9995; }
        .fab-support-btn { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #0077b6, #0096c7); color: #fff; border: 3px solid #fff; box-shadow: 0 8px 24px rgba(0,119,182,.35); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform .2s, box-shadow .2s; }
        .fab-support-btn:hover { transform: scale(1.06); box-shadow: 0 10px 28px rgba(0,119,182,.45); }
        .fab-support-btn svg { width: 26px; height: 26px; fill: currentColor; }
        .fab-support-menu { position: absolute; bottom: 68px; right: 0; width: 260px; background: #fff; border-radius: 16px; box-shadow: 0 14px 36px rgba(15,35,55,.18); border: 1px solid var(--border); padding: 10px; display: none; flex-direction: column; gap: 6px; animation: fabFadeIn .2s ease-out; }
        .fab-support-menu.active { display: flex; }
        @keyframes fabFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .fab-support-header { padding: 8px 12px 6px; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
        .fab-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; text-decoration: none; color: #1e293b; font-size: 13.5px; font-weight: 600; transition: background .15s; }
        .fab-item:hover { background: var(--soft); color: var(--primary); }
        .fab-item-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
        .fab-item-icon svg { width: 18px; height: 18px; fill: currentColor; }
        .fab-icon-call { background: #10b981; }
        .fab-icon-zalo { background: #0068ff; }
        .fab-icon-msg { background: linear-gradient(135deg, #00b2ff, #9b30ff); }
        .fab-icon-guide { background: #0284c7; }

        @media (max-width: 900px) {
            .site-header-inner { padding: 8px 16px; }
            .hero-inner { grid-template-columns: 1fr; gap: 24px; }
            .footer-main { grid-template-columns: 1fr; gap: 24px; padding: 32px 16px 24px; }
        }
        @media (max-width: 640px) {
            .logo-text { font-size: 15px; }
            .logo-mark { height: 38px; }
            .nav { gap: 4px; }
            .nav-link { padding: 6px 8px; font-size: 13px; }
            .nav-btn { padding: 6px 12px; font-size: 12.5px; }
            .hero { padding: 36px 16px 44px; }
            .wrap { padding: 16px 16px 36px; }
            .footer-bottom { flex-direction: column; align-items: flex-start; gap: 8px; }
            .fab-support-container { right: 16px; bottom: 16px; }
        }
    </style></head><body>';
    
    echo '<header class="site-header"><div class="site-header-inner">';
    echo '<a class="logo" href="index.php">';
    echo '<img class="logo-mark" src="' . e(clinic_logo_url()) . '" alt="Logo Phòng khám đa khoa Phú Thái">';
    echo '<div><span class="logo-text">' . e($clinicName) . '</span><span class="logo-sub">Cổng người bệnh</span></div>';
    echo '</a>';
    
    echo '<nav class="nav">';
    echo '<a class="nav-link' . ($activeNav === 'home' ? ' active' : '') . '" href="index.php">Trang chủ</a>';
    echo '<a class="nav-link' . ($activeNav === 'services' ? ' active' : '') . '" href="index.php#services">Dịch vụ</a>';
    echo '<a class="nav-link' . ($activeNav === 'doctors' ? ' active' : '') . '" href="index.php#doctors">Bác sĩ</a>';
    echo '<a class="nav-link' . ($activeNav === 'guide' ? ' active' : '') . '" href="resources.php">Hướng dẫn</a>';
    echo '<a class="nav-link' . ($activeNav === 'support' ? ' active' : '') . '" href="support.php">Hỗ trợ</a>';

    if ($isPatient) {
        echo '<a class="nav-btn nav-btn-primary" href="dashboard.php#records">Tra cứu kết quả</a>';
        echo '<a class="nav-pill" href="dashboard.php">' . e((string) ($_SESSION['name'] ?? "Bệnh nhân")) . '</a>';
        echo '<a class="nav-link" href="logout.php">Đăng xuất</a>';
    } elseif ($isAdmin) {
        echo '<a class="nav-pill" href="admin_add_record.php">Quản trị</a>';
        echo '<a class="nav-link" href="logout.php">Đăng xuất</a>';
    } else {
        echo '<a class="nav-btn nav-btn-primary" href="login.php?redirect=records">Tra cứu kết quả</a>';
        echo '<a class="nav-btn nav-btn-outline" href="login.php">Đăng nhập</a>';
    }
    echo '</nav></div></header><main>';

    if ($isAdmin) {
        $adminChatLink = (is_root_admin() || admin_can('manage_support_chat')) ? 'admin_accounts.php#recent-chats' : '';
        echo '<div data-admin-support-endpoint="admin_support_notice.php" data-admin-support-csrf="' . e(csrf_token('admin_support_api')) . '" data-admin-chat-link="' . e($adminChatLink) . '" hidden></div>';
    }
}

function render_footer(): void
{
    $clinicName = site_setting('clinic_name', "Phòng khám đa khoa Phú Thái");
    $hotline = site_setting('support_hotline', '0208 628 9888');
    $hotlineCskh = '0963 485 651';
    $email = site_setting('support_email', 'pcnttphongkhamdakhoaphuthai@gmail.com');
    $address = site_setting('clinic_address', 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên');
    $googleMapUrl = site_setting('google_maps_url', 'https://maps.google.com/?q=' . urlencode($clinicName . ' ' . $address));
    $zaloUrl = site_setting('zalo_url', '');
    $messengerUrl = site_setting('messenger_url', '');

    echo '</main><footer class="site-footer"><div class="footer-main">';
    
    // Cột 1: Thông tin phòng khám
    echo '<div class="footer-col">';
    echo '<h4>' . e($clinicName) . '</h4>';
    echo '<p>Cổng hỗ trợ và chăm sóc sức khỏe người bệnh trực tuyến. Đồng hành cùng người bệnh trong việc tra cứu hồ sơ và kết quả khám chữa bệnh nhanh chóng, an toàn.</p>';
    echo '<p style="color:#94a3b8;font-size:13px">Giờ tiếp đón: <strong>7:00 – 17:00</strong> (Tất cả các ngày trong tuần)</p>';
    echo '</div>';

    // Cột 2: Liên hệ
    echo '<div class="footer-col">';
    echo '<h4>Liên hệ phòng khám</h4>';
    echo '<div class="footer-contact-item"><span>📞</span><div><span>Hotline tư vấn:</span><a href="tel:' . preg_replace('/[^0-9]/', '', $hotline) . '"><strong>' . e($hotline) . '</strong></a></div></div>';
    echo '<div class="footer-contact-item"><span>🚑</span><div><span>Cấp cứu & CSKH:</span><a href="tel:' . preg_replace('/[^0-9]/', '', $hotlineCskh) . '">' . e($hotlineCskh) . '</a></div></div>';
    echo '<div class="footer-contact-item"><span>✉️</span><div><span>Email:</span><a href="mailto:' . e($email) . '">' . e($email) . '</a></div></div>';
    echo '</div>';

    // Cột 3: Địa chỉ & Bản đồ
    echo '<div class="footer-col">';
    echo '<h4>Địa chỉ & Chỉ đường</h4>';
    echo '<p>' . e($address) . '</p>';
    echo '<div style="margin-top:14px;">';
    echo '<a class="btn btn-outline" style="border-color:#334155;color:#e2e8f0;background:rgba(255,255,255,.05);" href="' . e($googleMapUrl) . '" target="_blank" rel="noopener">📍 Chỉ đường trên Google Maps ↗</a>';
    echo '</div>';
    echo '</div>';

    echo '</div>';

    // Dòng đáy
    echo '<div class="footer-bottom">';
    echo '<span>© ' . date('Y') . ' ' . e($clinicName) . ' · Cổng dịch vụ người bệnh</span>';
    echo '<div>';
    echo '<a href="resources.php">Hướng dẫn người bệnh</a> · ';
    echo '<a href="support.php">Liên hệ hỗ trợ</a> · ';
    echo '<a class="staff-link" href="login.php?role=staff">Dành cho nhân viên ↗</a>';
    echo '</div>';
    echo '</div>';
    echo '</footer>';

    // Nút Hỗ trợ nổi gộp (FAB)
    echo '<div class="fab-support-container" id="fabSupportWidget">';
    echo '<div class="fab-support-menu" id="fabSupportMenu" role="menu" aria-label="Menu hỗ trợ">';
    echo '<div class="fab-support-header">Hỗ trợ & Liên hệ</div>';
    echo '<a class="fab-item" href="tel:' . preg_replace('/[^0-9]/', '', $hotline) . '">';
    echo '<span class="fab-item-icon fab-icon-call"><svg viewBox="0 0 24 24"><path d="m7 3 3 5-3 3a15 15 0 0 0 6 6l3-3 5 3v3c-9 3-20-8-17-17z"/></svg></span>';
    echo '<div><div>Gọi hotline tư vấn</div><small style="color:#64748b;font-weight:normal">' . e($hotline) . '</small></div>';
    echo '</a>';

    if ($zaloUrl !== '') {
        echo '<a class="fab-item" href="' . e($zaloUrl) . '" target="_blank" rel="noopener">';
        echo '<span class="fab-item-icon fab-icon-zalo"><span style="font-size:11px;font-weight:900">Zalo</span></span>';
        echo '<div><div>Chat qua Zalo</div><small style="color:#64748b;font-weight:normal">Hỗ trợ trực tuyến</small></div>';
        echo '</a>';
    }

    if ($messengerUrl !== '') {
        echo '<a class="fab-item" href="' . e($messengerUrl) . '" target="_blank" rel="noopener">';
        echo '<span class="fab-item-icon fab-icon-msg"><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.1 2 11.2c0 2.9 1.4 5.5 3.6 7.2v3.6l3.5-1.9c.9.3 1.9.4 2.9.4 5.5 0 10-4.1 10-9.2S17.5 2 12 2zm1 12.4-2.6-2.8-5.1 2.8 5.6-6 2.7 2.8 5-2.8-5.6 6z"/></svg></span>';
        echo '<div><div>Chat Facebook Messenger</div><small style="color:#64748b;font-weight:normal">Fanpage phòng khám</small></div>';
        echo '</a>';
    }

    echo '<a class="fab-item" href="support.php">';
    echo '<span class="fab-item-icon fab-icon-guide"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></span>';
    echo '<div><div>Hướng dẫn & Báo sự cố</div><small style="color:#64748b;font-weight:normal">Gửi yêu cầu hỗ trợ</small></div>';
    echo '</a>';
    echo '</div>';

    echo '<button type="button" class="fab-support-btn" id="fabSupportToggle" aria-expanded="false" aria-label="Mở menu hỗ trợ" title="Hỗ trợ & Liên hệ">';
    echo '<svg viewBox="0 0 24 24" id="fabIconChat"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>';
    echo '<svg viewBox="0 0 24 24" id="fabIconClose" style="display:none"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
    echo '</button>';
    echo '</div>';

    echo '<script>
    (function(){
        var btn = document.getElementById("fabSupportToggle");
        var menu = document.getElementById("fabSupportMenu");
        var iconChat = document.getElementById("fabIconChat");
        var iconClose = document.getElementById("fabIconClose");
        if (!btn || !menu) return;
        function toggleMenu(open) {
            var isOpen = open !== undefined ? open : !menu.classList.contains("active");
            if (isOpen) {
                menu.classList.add("active");
                btn.setAttribute("aria-expanded", "true");
                if (iconChat && iconClose) { iconChat.style.display = "none"; iconClose.style.display = "block"; }
            } else {
                menu.classList.remove("active");
                btn.setAttribute("aria-expanded", "false");
                if (iconChat && iconClose) { iconChat.style.display = "block"; iconClose.style.display = "none"; }
            }
        }
        btn.addEventListener("click", function(e){ e.stopPropagation(); toggleMenu(); });
        document.addEventListener("click", function(e){
            if (!document.getElementById("fabSupportWidget").contains(e.target)) { toggleMenu(false); }
        });
        document.addEventListener("keydown", function(e){ if (e.key === "Escape") { toggleMenu(false); } });
    })();
    </script>';

    echo '<script src="' . e(asset_url('app.js')) . '"></script></body></html>';
}

function render_flash(): void
{
    $flash = get_flash();
    if ($flash === null) {
        return;
    }

    $class = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
    echo '<div class="flash ' . $class . '">' . e($flash['message']) . '</div>';
}

function render_hero(string $title, string $subtitle): void
{
    echo '<section class="hero"><div class="hero-inner">';
    echo '<div>';
    echo '<h1>' . e($title) . '</h1>';
    echo '<p>' . e($subtitle) . '</p>';
    echo '<div class="hero-badges">';
    echo '<span class="hero-badge">✓ Tra cứu hồ sơ & đơn thuốc</span>';
    echo '<span class="hero-badge">✓ Xem kết quả xét nghiệm</span>';
    echo '<span class="hero-badge">✓ Hỗ trợ người bệnh 24/7</span>';
    echo '</div>';
    echo '</div>';
    echo '<div class="hero-panel">';
    echo '<h3>Dịch vụ trực tuyến</h3>';
    echo '<div class="hero-list">';
    echo '<div class="hero-item">Đăng nhập bằng số CCCD đã đăng ký để tra cứu nhanh hồ sơ và kết quả khám.</div>';
    echo '<div class="hero-item">Xem kết quả chẩn đoán, đơn thuốc và tệp PDF trực tiếp trên điện thoại hoặc máy tính.</div>';
    echo '<div class="hero-item">Liên hệ bộ phận chăm sóc khách hàng để được hướng dẫn sử dụng và cập nhật thông tin.</div>';
    echo '</div>';
    echo '</div>';
    echo '</div></section>';
}
?>
