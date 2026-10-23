<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Safety net */
if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
    }
}

if (!function_exists('pj_delete_image_file')) {
    function pj_delete_image_file(?string $p): void {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/pujas/index.php');
}

csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid puja ID.');
    redirect('admin/modules/pujas/index.php');
}

try {
    /* Refuse hard-delete if bookings reference this puja */
    $b = $pdo->prepare('SELECT COUNT(*) FROM puja_bookings WHERE puja_id = ?');
    $b->execute([$id]);
    $bookings = (int) $b->fetchColumn();

    if ($bookings > 0) {
        $pdo->prepare('UPDATE pujas SET is_active = 0 WHERE id = ?')->execute([$id]);
        flash_set('info', "Puja has {$bookings} booking(s) — deactivated instead of deleted.");
    } else {
        $imgStmt = $pdo->prepare('SELECT image_url FROM pujas WHERE id = ?');
        $imgStmt->execute([$id]);
        $img = (string) $imgStmt->fetchColumn();

        $pdo->prepare('DELETE FROM pujas WHERE id = ?')->execute([$id]);

        if ($img !== '' && strpos($img, 'http') !== 0) {
            pj_delete_image_file($img);
        }

        flash_set('success', 'Puja deleted.');
    }
} catch (PDOException $e) {
    error_log('[pujas.delete] ' . $e->getMessage());
    flash_set('error', 'Delete failed.');
}

redirect('admin/modules/pujas/index.php');