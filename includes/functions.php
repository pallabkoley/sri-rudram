<?php
// includes/functions.php
// DIVYA AASTHA - Helper Functions + Auth Helpers

/* ═══════════════ OUTPUT ═══════════════ */

/** HTML escape */
function e(?string $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** ₹ Indian currency format (digits follow the active language) */
function inr(float|int|string $n): string {
    $v = '₹' . number_format((float) $n, 0, '.', ',');
    return function_exists('localize_digits') ? localize_digits($v) : $v;
}

/* ═══════════════ URLS ═══════════════ */

/** Absolute URL */
function url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

/** Cache-busted asset URL */
function asset(string $path): string {
    $full = BASE_PATH . '/' . ltrim($path, '/');
    $ver  = is_file($full) ? filemtime($full) : time();
    return url($path) . '?v=' . $ver;
}

/**
 * Upload URL — images stored in assets/img/products/
 * Handles: full URLs, relative paths, filename-only values.
 */
function upload_url(?string $file, string $fallback = 'assets/img/placeholder.php'): string {
    if (!$file || trim($file) === '') {
        return url($fallback);
    }

    $file = trim($file);

    // 1. Full URL → as-is
    if (preg_match('#^https?://#i', $file)) {
        return $file;
    }

    // 2. Already has assets/ or uploads/ prefix
    if (str_starts_with($file, 'assets/') || str_starts_with($file, 'uploads/')) {
        return APP_URL . '/' . ltrim($file, '/');
    }

    // 3. Starts with "products/" → prefix with assets/img/
    if (str_starts_with($file, 'products/')) {
        return APP_URL . '/assets/img/' . ltrim($file, '/');
    }

    // 4. Just a filename (no slash) → assets/img/products/
    if (strpos($file, '/') === false) {
        return APP_URL . '/assets/img/products/' . ltrim($file, '/');
    }

    // 5. Any other relative path
    return APP_URL . '/' . ltrim($file, '/');
}

/**
 * <img> tag with onerror fallback.
 * Width/height optional. Does not use file_exists()
 * so external/CDN URLs always work.
 */
function img_tag(?string $src, string $alt = '', string $class = '', bool $lazy = true, int $width = 0, int $height = 0): string {
    $imageUrl    = upload_url($src);
    $placeholder = url('assets/img/placeholder.php');
    $lazyAttr    = $lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"';
    $clsAttr     = $class !== '' ? ' class="' . e($class) . '"' : '';
    $dimAttr     = '';
    if ($width  > 0) $dimAttr .= ' width="'  . $width  . '"';
    if ($height > 0) $dimAttr .= ' height="' . $height . '"';

    // If already the placeholder, skip onerror to avoid infinite loop
    if ($imageUrl === $placeholder) {
        return sprintf(
            '<img src="%s" alt="%s"%s%s%s>',
            e($imageUrl), e($alt), $clsAttr, $lazyAttr, $dimAttr
        );
    }

    return sprintf(
        '<img src="%s" alt="%s"%s%s%s onerror="this.onerror=null;this.src=\'%s\'">',
        e($imageUrl), e($alt), $clsAttr, $lazyAttr, $dimAttr, $placeholder
    );
}

/* ═══════════════ CSRF ═══════════════ */

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool {
    return is_string($token)
        && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

/* ═══════════════ Flash ═══════════════ */

function flash_set(string $type, string $msg): void {
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): array {
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* ═══════════════ Input ═══════════════ */

function post(string $key, mixed $default = null): mixed {
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = null): mixed {
    return $_GET[$key] ?? $default;
}

/** JSON response (status messages are localised for the active language) */
function json_out(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    if (is_array($data) && function_exists('__t')) {
        foreach (['message', 'error', 'success', 'warning', 'info'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = __t($data[$k]);
            }
        }
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Redirect */
function redirect(string $path): never {
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ═══════════════ STRINGS ═══════════════ */

/** Slug from title */
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** Short excerpt */
function excerpt(?string $text, int $len = 120): string {
    $text = trim(strip_tags((string) $text));
    return mb_strlen($text) <= $len ? $text : mb_substr($text, 0, $len) . '…';
}

/** Format date (month names + digits follow the active language) */
function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date)) return '';
    $ts = strtotime($date);
    if (!$ts) return '';
    $out = date($format, $ts);
    // only human readable formats are localised — machine formats (Y-m-d) stay as-is
    $human = preg_match('/[DFMl]/', $format) === 1;
    return ($human && function_exists('localize_date')) ? localize_date($out) : $out;
}

/* ═══════════════ PAGINATION ═══════════════ */

/** Pagination helper — returns [perPage, offset, page] */
function paginate(int $perPage = 20): array {
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;
    return [$perPage, $offset, $page];
}

/* ═══════════════ SETTINGS ═══════════════ */

/** Read site setting (cached per request) */
function setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        global $pdo;
        $cache = [];
        try {
            $rows = $pdo->query('SELECT key_name, value FROM settings')->fetchAll();
            foreach ($rows as $r) $cache[$r['key_name']] = $r['value'];
        } catch (Throwable) {}
    }
    return $cache[$key] ?? $default;
}

/* ═══════════════ PRODUCTS ═══════════════ */

/** Discount % for a product */
function off_percent(array $p): ?int {
    if (empty($p['mrp']) || $p['mrp'] <= $p['price']) return null;
    return (int) round((1 - $p['price'] / $p['mrp']) * 100);
}

/* ═══════════════ NAV ═══════════════ */

/** Current page filename (for nav) */
function current_page(): string {
    return basename($_SERVER['PHP_SELF'] ?? 'index.php');
}

/** Is a nav link active? */
function nav_active(string $file): string {
    return current_page() === $file ? ' active' : '';
}

/* ═══════════════ FLASH RENDER ═══════════════ */

/** Render a flash block */
function render_flash(): void {
    foreach (flash_get() as $f) {
        echo '<div class="flash flash-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

/* ═══════════════════════════════════════════════════════════
   AUTH HELPERS
   ═══════════════════════════════════════════════════════════ */

/** Check if a user is logged in */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/** Require login — redirect to login page if not logged in */
function require_login(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        flash_set('error', 'Please login to continue.');
        redirect($redirect_to);
    }
}

/** Current user ID (or null) */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/** Current user name */
function current_user_name(): string {
    return $_SESSION['user_name'] ?? 'Devotee';
}

/** Current user email */
function current_user_email(): string {
    return $_SESSION['user_email'] ?? '';
}

/** Log a user in (sets session) */
function login_user(int $userId, string $name, string $email, string $role = 'user'): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $role;
    $_SESSION['logged_in_at'] = time();
}

/** Log the current user out */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Check if current user is admin */
function is_admin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}