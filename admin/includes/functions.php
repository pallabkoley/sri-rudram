<?php
// ============================================================
// DIVYA AASTHA — Admin helper functions
// Loaded by admin/includes/auth.php on every admin request
// ============================================================

/* ==================================================================
 |  URLs & REDIRECTS
 * ================================================================== */

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = defined('APP_URL') ? APP_URL : '';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string {
        $base = defined('APP_URL') ? APP_URL : '';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void {
        header('Location: ' . url($path));
        exit;
    }
}

/* ==================================================================
 |  OUTPUT ESCAPING
 * ================================================================== */

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/* ==================================================================
 |  REQUEST INPUT
 * ================================================================== */

if (!function_exists('post')) {
    function post(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('get')) {
    function get(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }
}

/* ==================================================================
 |  TEXT & SLUG
 * ================================================================== */

if (!function_exists('slugify')) {
    function slugify(string $s): string {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?? '';
        return trim($s, '-');
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $s, int $len = 120): string {
        $s = trim(strip_tags((string) $s));
        return mb_strlen($s) <= $len ? $s : mb_substr($s, 0, $len) . '…';
    }
}

/* ==================================================================
 |  CURRENCY
 * ================================================================== */

if (!function_exists('inr')) {
    function inr($amount): string {
        return '₹' . number_format((float) $amount, 0, '.', ',');
    }
}

/* ==================================================================
 |  FLASH MESSAGES
 * ================================================================== */

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void {
        $_SESSION['_flash'][] = ['type' => $type, 'msg' => $message];
    }
}

if (!function_exists('flash_get')) {
    function flash_get(): array {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }
}

if (!function_exists('flash_render')) {
    function flash_render(): string {
        $out = '';
        foreach (flash_get() as $f) {
            $icon = ['success' => 'check-circle', 'error' => 'exclamation-triangle',
                     'info' => 'info-circle'][$f['type']] ?? 'info-circle';
            $out .= '<div class="flash flash-' . e($f['type']) . '">'
                  . '<i class="bi bi-' . $icon . '"></i> ' . e($f['msg']) . '</div>';
        }
        return $out;
    }
}

/* ==================================================================
 |  CSRF  — accepts 0 or 1 argument
 * ================================================================== */

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Verify CSRF token.
     * Can be called as csrf_verify()            — reads $_POST['_csrf']
     * or              csrf_verify($token)       — validates the passed token.
     */
    function csrf_verify(?string $token = null): void {
        $sent = $token ?? ($_POST['_csrf'] ?? '');
        if (!$sent || !hash_equals($_SESSION['_csrf'] ?? '', (string) $sent)) {
            http_response_code(419);
            exit('Session expired — please refresh and try again.');
        }
    }
}

/* ==================================================================
 |  AUTH SHORTCUTS
 * ================================================================== */

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return !empty($_SESSION['admin_id']) || !empty($_SESSION['admin_user']);
    }
}

if (!function_exists('current_admin')) {
    function current_admin(): ?array {
        return $_SESSION['admin_user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int {
        return is_logged_in() ? (int) $_SESSION['user_id'] : null;
    }
}

/* ==================================================================
 |  SIDEBAR ACTIVE-STATE
 * ================================================================== */

if (!function_exists('sb_active')) {
    function sb_active(string $slug): string {
        $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $dir     = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        return ($current === $slug || $dir === $slug) ? ' active' : '';
    }
}

/* ==================================================================
 |  IMAGE — resolve stored path → public URL
 * ================================================================== */

if (!function_exists('img_url')) {
    function img_url(?string $stored): string {
        if (!$stored) return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;

        // Legacy /uploads/… paths
        if (strpos($stored, '/uploads/') === 0 || strpos($stored, 'uploads/') === 0) {
            return APP_URL . '/' . ltrim($stored, '/');
        }

        // Modern /assets/img/ paths (stored as "temples/foo.jpg")
        return APP_URL . '/assets/img/' . ltrim($stored, '/');
    }
}

/* ==================================================================
 |  IMAGE — render <img> with placeholder fallback
 * ================================================================== */

if (!function_exists('img_tag')) {
    function img_tag(?string $stored, string $alt = '', string $class = 'thumb'): string {
        $src = img_url($stored);
        if (!$src) {
            return '<div class="' . e($class) . '" style="background:#F3E8D8;'
                 . 'display:grid;place-items:center;color:#9A8B7C;font-size:18px">'
                 . '<i class="bi bi-image"></i></div>';
        }
        return '<img src="' . e($src) . '" alt="' . e($alt) . '" class="' . e($class) . '" loading="lazy">';
    }
}

/* ==================================================================
 |  IMAGE — safe delete (only files under /assets/img/)
 * ================================================================== */

if (!function_exists('admin_delete_image')) {
    function admin_delete_image(?string $pathOrUrl): void {
        if (!$pathOrUrl) return;

        // If external URL, don't touch it unless it's OUR url
        if (preg_match('#^https?://#i', $pathOrUrl)) {
            $prefix = APP_URL . '/assets/img/';
            if (strpos($pathOrUrl, $prefix) !== 0) return;
            $pathOrUrl = substr($pathOrUrl, strlen(APP_URL . '/assets/'));
        }

        // Strip leading /assets/ or assets/
        $rel = $pathOrUrl;
        if (strpos($rel, '/assets/img/') === 0) $rel = substr($rel, strlen('/assets/'));
        elseif (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/'));
        elseif (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);

        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
        $path = ASSETS_PATH . '/img/' . $rel;

        $real = realpath($path);
        $root = realpath(ASSETS_PATH . '/img');

        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}

/* ==================================================================
 |  IMAGE — upload (3-tier MIME detection, no finfo dependency)
 * ================================================================== */

if (!function_exists('admin_upload_image')) {
    function admin_upload_image(string $field, string $subfolder, ?string $existing = null): ?string
    {
        // No file sent → keep existing
        if (empty($_FILES[$field]) ||
            ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing;
        }

        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'Upload failed (code ' . (int) $file['error'] . ').');
            return $existing;
        }

        if ($file['size'] > 3 * 1024 * 1024) {
            flash_set('error', 'Image must be under 3 MB.');
            return $existing;
        }

        /* ---- MIME detection: try 3 methods ---- */
        $mime = null;

        // Method 1: fileinfo
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = @finfo_file($fi, $file['tmp_name']);
                @finfo_close($fi);
            }
        }

        // Method 2: mime_content_type
        if (!$mime && function_exists('mime_content_type')) {
            $mime = @mime_content_type($file['tmp_name']);
        }

        // Method 3: getimagesize
        if (!$mime) {
            $info = @getimagesize($file['tmp_name']);
            if ($info && !empty($info['mime'])) $mime = $info['mime'];
        }

        $allowed = [
            'image/jpeg'  => 'jpg',
            'image/jpg'   => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png'   => 'png',
            'image/webp'  => 'webp',
            'image/gif'   => 'gif',
        ];

        if (!$mime || !isset($allowed[$mime])) {
            flash_set('error', 'Only JPG, PNG, WEBP or GIF images are allowed.');
            return $existing;
        }

        if (@getimagesize($file['tmp_name']) === false) {
            flash_set('error', 'File is not a valid image.');
            return $existing;
        }

        /* ---- Target directory ---- */
        $subfolder = trim($subfolder, '/');
        $dir = ASSETS_PATH . '/img' . ($subfolder !== '' ? '/' . $subfolder : '');

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            flash_set('error', 'Cannot create upload folder.');
            return $existing;
        }
        if (!is_writable($dir)) {
            flash_set('error', 'Upload folder is not writable: ' . $dir);
            return $existing;
        }

        /* ---- Save ---- */
        $ext  = $allowed[$mime];
        $name = date('Y-m-d') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            flash_set('error', 'Could not save the file. Please try again.');
            return $existing;
        }

        @chmod($dest, 0644);

        // Delete replaced local file
        if ($existing && $existing !== $subfolder . '/' . $name) {
            admin_delete_image($existing);
        }

        return $subfolder . '/' . $name;
    }
}

/* ==================================================================
 |  IMAGE — compatibility alias
 * ================================================================== */

if (!function_exists('handle_image_upload')) {
    function handle_image_upload(string $field, string $subdir = ''): array {
        $path = admin_upload_image($field, $subdir);
        return [
            'ok'    => (bool) $path,
            'path'  => $path,
            'error' => null,
            'empty' => empty($_FILES[$field]['name']),
        ];
    }
}

/* ==================================================================
 |  TABLE HELPERS
 * ================================================================== */

if (!function_exists('th')) {
    function th(string $label, string $width = ''): void {
        echo '<th' . ($width ? ' style="width:' . e($width) . '"' : '') . '>'
           . e($label) . '</th>';
    }
}

/* ==================================================================
 |  BADGES & DOTS
 * ================================================================== */

if (!function_exists('active_dot')) {
    function active_dot(int $active): string {
        return $active
            ? '<span class="dot-active" title="Active" aria-label="Active"></span>'
            : '<span class="dot-inactive" title="Inactive" aria-label="Inactive"></span>';
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status, ?string $label = null): string {
        $status = strtolower(trim($status));
        static $known = [
            'pending','paid','packed','shipped','delivered','cancelled',
            'confirmed','completed','active','inactive',
            'unpaid','refunded','failed',
        ];
        $safe = in_array($status, $known, true) ? $status : 'active';
        $text = $label ?? ucfirst($status);
        return '<span class="status-badge status-' . e($safe) . '">' . e($text) . '</span>';
    }
}

/* ==================================================================
 |  PAGINATION  (Bootstrap 5)
 * ================================================================== */

if (!function_exists('admin_pagination')) {
    function admin_pagination(int $total, int $perPage, int $currentPage, string $baseUrl): void
    {
        $pages = (int) ceil($total / $perPage);
        if ($pages <= 1) return;

        $currentPage = max(1, min($currentPage, $pages));
        $sep = str_contains($baseUrl, '?') ? '&' : '?';

        $buildUrl = function (int $n) use ($baseUrl, $sep) {
            $clean = preg_replace('/([?&])page=\d+&?/', '$1', $baseUrl);
            $clean = rtrim($clean, '?&');
            return $clean . $sep . 'page=' . $n;
        };

        echo '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">';

        // Previous
        if ($currentPage > 1) {
            echo '<li class="page-item"><a class="page-link" href="'
               . e($buildUrl($currentPage - 1)) . '" aria-label="Previous">'
               . '<i class="bi bi-chevron-left"></i></a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">'
               . '<i class="bi bi-chevron-left"></i></span></li>';
        }

        $range = 2;
        $start = max(1, $currentPage - $range);
        $end   = min($pages, $currentPage + $range);

        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="'
               . e($buildUrl(1)) . '">1</a></li>';
            if ($start > 2) {
                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $currentPage) {
                echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                echo '<li class="page-item"><a class="page-link" href="'
                   . e($buildUrl($i)) . '">' . $i . '</a></li>';
            }
        }

        if ($end < $pages) {
            if ($end < $pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="'
               . e($buildUrl($pages)) . '">' . $pages . '</a></li>';
        }

        // Next
        if ($currentPage < $pages) {
            echo '<li class="page-item"><a class="page-link" href="'
               . e($buildUrl($currentPage + 1)) . '" aria-label="Next">'
               . '<i class="bi bi-chevron-right"></i></a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">'
               . '<i class="bi bi-chevron-right"></i></span></li>';
        }

        echo '</ul></nav>';
    }
}