<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/orders/index.php');
}
csrf_verify(post('_csrf', ''));

$id     = (int) post('id', 0);
$status = (string) post('status', '');

$valid = ['pending','paid','packed','shipped','delivered','cancelled'];

if ($id <= 0 || !in_array($status, $valid, true)) {
    flash_set('error', 'Invalid request.');
    redirect('admin/modules/orders/index.php');
}

try {
    $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Order status updated to "' . $status . '".');
} catch (PDOException $e) {
    error_log('[orders.update-status] ' . $e->getMessage());
    flash_set('error', 'Could not update status.');
}

redirect('admin/modules/orders/view.php?id=' . $id);