<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Safety net for image deletion */
if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
    }
}

if (!function_exists('fest_delete_image_file')) {
    function fest_delete_image_file(?string $p): void {
        if (!$p || !defined('ASSETS_PATH')) return;
        if (preg_match('#^https?://#i', $p)) return;
        $rel = ltrim($p, '/');
        if (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($rel, $m);
        if ($pos !== false) $rel = substr($rel, $pos + strlen($m));
        if (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);
        $rel = str_replace(['..', '\\'], ['', '/'], $rel);
        $rel = ltrim($rel, '/');
        if ($rel === '') return;
        $full = ASSETS_PATH . '/img/' . $rel;
        $real = @realpath($full); $root = @realpath(ASSETS_PATH . '/img');
        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) @unlink($real);
    }
}

/* POST only */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/festivals/index.php');
}

csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid festival ID.');
    redirect('admin/modules/festivals/index.php');
}

try {
    /* Grab image path before deleting */
    $stmt = $pdo->prepare('SELECT image_url FROM festivals WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $img = (string) $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM festivals WHERE id = ?')->execute([$id]);

    /* Clean up local image (skip external URLs) */
    if ($img !== '' && strpos($img, 'http') !== 0) {
        fest_delete_image_file($img);
    }

    flash_set('success', 'Festival deleted successfully.');
} catch (PDOException $e) {
    error_log('[festivals.delete] ' . $e->getMessage());
    flash_set('error', 'Delete failed.');
}

redirect('admin/modules/festivals/index.php');