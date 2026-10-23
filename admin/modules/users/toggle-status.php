<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/users/index.php');
}
csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid user ID.');
    redirect('admin/modules/users/index.php');
}

/* Prevent admin from disabling themselves */
$currentAdminId = current_user_id();
if ($id === (int) $currentAdminId) {
    flash_set('error', 'You cannot disable your own account.');
    redirect('admin/modules/users/index.php');
}

try {
    $pdo->prepare('UPDATE users SET status = 1 - status WHERE id = ?')->execute([$id]);
    flash_set('success', 'User status updated.');
} catch (PDOException $e) {
    error_log('[users.toggle] ' . $e->getMessage());
    flash_set('error', 'Could not update status.');
}

/* Send back to referrer if available */
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($ref, '/users/view.php') !== false) {
    redirect('admin/modules/users/view.php?id=' . $id);
}
redirect('admin/modules/users/index.php');