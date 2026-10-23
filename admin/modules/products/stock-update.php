<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/products/index.php');
}
csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid product ID.');
    redirect('admin/modules/products/index.php');
}

try {
    $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    flash_set('success', 'Status updated.');
} catch (PDOException $e) {
    error_log('[products.toggle] ' . $e->getMessage());
    flash_set('error', 'Could not update status.');
}

redirect('admin/modules/products/index.php');