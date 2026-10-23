<?php
// ============================================================
// DIVYA AASTHA — Config Bootstrap
// ============================================================
declare(strict_types=1);

if (defined('DIVYA_AASTHA_LOADED')) {
    return;
}
define('DIVYA_AASTHA_LOADED', true);
define('DIVYA_AASTHA', true);

// ---------------- Environment ----------------
$env = getenv('APP_ENV') ?: 'production';
define('APP_ENV',   $env);
define('APP_DEBUG', $env !== 'production');

// ---------------- Database ----------------
// ✅ XAMPP এর জন্য সঠিক সেটিংস
// ⚠️ লাইভে যাওয়ার সময় DB_USER / DB_PASS পরিবর্তন করবেন

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'xinkpayr_divyaaastha');
define('DB_USER', 'root');                   // XAMPP: root | লাইভ: আপনার ইউজার
define('DB_PASS', '');                       // XAMPP: খালি | লাইভ: আপনার পাসওয়ার্ড
define('DB_CHARSET', 'utf8mb4');

// ---------------- Site ----------------
define('APP_NAME', 'Divya Aastha');

// ✅ ডাইনামিক APP_URL — লোকালহোস্ট এবং লাইভ দুটোতেই কাজ করবে
$detectedUrl = getenv('APP_URL');

if (!$detectedUrl) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    $scheme = $isHttps ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // প্রকল্পের ফোল্ডার পাথ বের করা
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDir   = rtrim($scriptDir, '/');

    // config.php নিজে config/ ফোল্ডারে আছে — সেই ফোল্ডারটি সরিয়ে ফেলতে হবে
    if (basename($baseDir) === 'config') {
        $baseDir = dirname($baseDir);
    }
    if ($baseDir === '/' || $baseDir === '.' || $baseDir === '\\') {
        $baseDir = '';
    }

    $detectedUrl = $scheme . '://' . $host . $baseDir;
}

define('APP_URL', rtrim($detectedUrl, '/'));

// ---------------- Paths ----------------
define('BASE_PATH',      dirname(__DIR__));
define('CONFIG_PATH',    BASE_PATH . '/config');
define('INCLUDES_PATH',  BASE_PATH . '/includes');
define('ASSETS_PATH',    BASE_PATH . '/assets');
define('UPLOADS_PATH',   BASE_PATH . '/uploads');
define('STORAGE_PATH',   BASE_PATH . '/storage');
define('UPLOAD_URL',     APP_URL . '/uploads');

// ---------------- Session ----------------
define('SESSION_NAME',     'divyaaastha_sess');
define('SESSION_LIFETIME', 86400);
define('SESSION_REGEN',    900);

// ---------------- Errors ----------------
error_reporting(E_ALL);
ini_set('log_errors', '1');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

$logDir = STORAGE_PATH . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
ini_set('error_log', $logDir . '/php-error.log');

// ---------------- Timezone ----------------
date_default_timezone_set('Asia/Kolkata');

// ---------------- Force HTTPS in production ----------------
// ⚠️ লোকালহোস্টে HTTPS রিডাইরেক্ট করবে না
if (APP_ENV === 'production'
    && PHP_SAPI !== 'cli'
    && empty($_SERVER['HTTPS'])
    && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https'
    && strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false
    && strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') === false) {
    $host = $_SERVER['HTTP_HOST'] ?? parse_url(APP_URL, PHP_URL_HOST);
    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// ---------------- Session start ----------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS'])
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    @session_start();

    if (empty($_SESSION['_last_regen']) || (time() - $_SESSION['_last_regen']) > SESSION_REGEN) {
        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
        $_SESSION['_last_regen'] = time();
    }
}

// ---------------- Language System ----------------
if (PHP_SAPI !== 'cli') {
    require_once INCLUDES_PATH . '/language.php';
}

// ---------------- Load modules ----------------
require_once CONFIG_PATH   . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/db-queries.php';