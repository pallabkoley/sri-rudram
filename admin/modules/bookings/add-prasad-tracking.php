<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/bookings/index.php');
}
csrf_verify(post('_csrf', ''));

$id       = (int) post('id', 0);
$tracking = trim((string) post('prasad_tracking', ''));

if ($id <= 0) {
    flash_set('error', 'Invalid booking ID.');
    redirect('admin/modules/bookings/index.php');
}

try {
    $pdo->prepare('UPDATE puja_bookings SET prasad_tracking = ? WHERE id = ?')
        ->execute([$tracking ?: null, $id]);

    flash_set('success', $tracking === ''
        ? 'Prasad tracking cleared.'
        : 'Prasad tracking saved: ' . $tracking);
} catch (PDOException $e) {
    error_log('[bookings.add-prasad] ' . $e->getMessage());
    flash_set('error', 'Could not save tracking.');
}

redirect('admin/modules/bookings/view.php?id=' . $id);