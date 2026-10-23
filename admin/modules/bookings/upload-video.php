<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/bookings/index.php');
}
csrf_verify(post('_csrf', ''));

$id       = (int) post('id', 0);
$videoUrl = trim((string) post('video_url', ''));

if ($id <= 0) {
    flash_set('error', 'Invalid booking ID.');
    redirect('admin/modules/bookings/index.php');
}

/* Validate URL (optional) */
if ($videoUrl !== '' && !preg_match('#^https?://#i', $videoUrl)) {
    flash_set('error', 'Please enter a valid http(s) URL.');
    redirect('admin/modules/bookings/view.php?id=' . $id);
}

try {
    $pdo->prepare('UPDATE puja_bookings SET video_url = ? WHERE id = ?')
        ->execute([$videoUrl ?: null, $id]);

    flash_set('success', $videoUrl === ''
        ? 'Video link removed.'
        : 'Video proof saved successfully.');
} catch (PDOException $e) {
    error_log('[bookings.upload-video] ' . $e->getMessage());
    flash_set('error', 'Could not save video link.');
}

redirect('admin/modules/bookings/view.php?id=' . $id);