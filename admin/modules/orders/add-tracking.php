<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/orders/index.php');
}
csrf_verify(post('_csrf', ''));

$id  = (int) post('id', 0);
$ref = trim((string) post('payment_ref', ''));

if ($id <= 0) {
    flash_set('error', 'Invalid order ID.');
    redirect('admin/modules/orders/index.php');
}

try {
    /* Store tracking in payment_ref field */
    $pdo->prepare('UPDATE orders SET payment_ref = ? WHERE id = ?')->execute([$ref ?: null, $id]);

    /* Also try to save prasad_tracking to puja_bookings if this is a puja-linked order */
    /* This is optional — orders table doesn't have a tracking field, so we use payment_ref */

    flash_set('success', 'Tracking information saved.');
} catch (PDOException $e) {
    error_log('[orders.add-tracking] ' . $e->getMessage());
    flash_set('error', 'Could not save tracking.');
}

redirect('admin/modules/orders/view.php?id=' . $id);