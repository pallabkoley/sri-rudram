<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/bookings/index.php');
}
csrf_verify(post('_csrf', ''));

$id          = (int) post('id', 0);
$status      = (string) post('status', '');
$muhuratTime = trim((string) post('muhurat_time', ''));

$valid = ['pending','confirmed','completed','cancelled'];

if ($id <= 0 || !in_array($status, $valid, true)) {
    flash_set('error', 'Invalid request.');
    redirect('admin/modules/bookings/index.php');
}

try {
    $sql = 'UPDATE puja_bookings SET status = ?';
    $params = [$status];

    if ($muhuratTime !== '') {
        $sql .= ', muhurat_time = ?';
        $params[] = $muhuratTime . ':00';
    }

    $sql .= ' WHERE id = ?';
    $params[] = $id;

    $pdo->prepare($sql)->execute($params);

    flash_set('success', 'Booking status updated to "' . $status . '".');
} catch (PDOException $e) {
    error_log('[bookings.update-status] ' . $e->getMessage());
    flash_set('error', 'Could not update status.');
}

redirect('admin/modules/bookings/view.php?id=' . $id);