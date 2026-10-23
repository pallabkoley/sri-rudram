<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Safety net — image deletion helper (self-contained)
 * ================================================================== */
if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
    }
}

if (!function_exists('prod_delete_image_file')) {
    function prod_delete_image_file(?string $pathOrUrl): void {
        if (!$pathOrUrl) return;
        if (!defined('ASSETS_PATH')) return;

        // Skip external URLs (Unsplash etc.)
        if (preg_match('#^https?://#i', $pathOrUrl)) return;

        // Normalize relative path
        $rel = ltrim($pathOrUrl, '/');
        if (strpos($rel, 'assets/img/') === 0) {
            $rel = substr($rel, strlen('assets/img/'));
        }
        $marker = 'assets/img/';
        $pos = strpos($rel, $marker);
        if ($pos !== false) {
            $rel = substr($rel, $pos + strlen($marker));
        }
        if (strpos($rel, 'img/') === 0) {
            $rel = substr($rel, 4);
        }

        // Sanitize — no traversal
        $rel = str_replace(['..', '\\'], ['', '/'], $rel);
        $rel = ltrim($rel, '/');

        if ($rel === '') return;

        $full = ASSETS_PATH . '/img/' . $rel;
        $real = @realpath($full);
        $root = @realpath(ASSETS_PATH . '/img');

        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}

/* ==================================================================
 |  POST-only, CSRF-protected deletion
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('error', 'Invalid request method.');
    redirect('admin/modules/products/index.php');
}

csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid product ID.');
    redirect('admin/modules/products/index.php');
}

try {
    /* 1) Fetch the image path (before we lose the row) */
    $stmt = $pdo->prepare('SELECT name, image_url FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        flash_set('error', 'Product not found.');
        redirect('admin/modules/products/index.php');
    }

    $productName = (string) $product['name'];
    $imagePath   = (string) ($product['image_url'] ?? '');

    /* 2) Delete the row.
          cart_items  → CASCADE (auto cleaned)
          wishlists   → CASCADE (auto cleaned)
          order_items → SET NULL (order history preserved) */
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);

    /* 3) Delete the local image file (external URLs skipped) */
    prod_delete_image_file($imagePath);

    /* 4) Success flash */
    flash_set('success', 'Product "' . $productName . '" deleted successfully.');

} catch (PDOException $e) {
    error_log('[products.delete] ' . $e->getMessage());
    flash_set('error', 'Could not delete product: ' . $e->getMessage());
}

redirect('admin/modules/products/index.php');