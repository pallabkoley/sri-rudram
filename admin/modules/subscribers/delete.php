<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/subscribers/index.php');
}
csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid subscriber ID.');
    redirect('admin/modules/subscribers/index.php');
}

try {
    /* Get email for the flash message */
    $stmt = $pdo->prepare('SELECT email FROM subscribers WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $email = (string) $stmt->fetchColumn();

    if ($email === '') {
        flash_set('error', 'Subscriber not found.');
        redirect('admin/modules/subscribers/index.php');
    }

    $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
    flash_set('success', 'Subscriber "' . $email . '" deleted.');
} catch (PDOException $e) {
    error_log('[subscribers.delete] ' . $e->getMessage());
    flash_set('error', 'Delete failed.');
}

redirect('admin/modules/subscribers/index.php');