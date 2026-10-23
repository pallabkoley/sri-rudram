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

/* Prevent self-deletion */
$currentAdminId = current_user_id();
if ($id === (int) $currentAdminId) {
    flash_set('error', 'You cannot delete your own account.');
    redirect('admin/modules/users/index.php');
}

/* Verify user exists */
$stmt = $pdo->prepare('SELECT name, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) {
    flash_set('error', 'User not found.');
    redirect('admin/modules/users/index.php');
}

try {
    /* Order/booking FK ON DELETE SET NULL → history preserved.
       Cart/wishlist FK ON DELETE CASCADE → cleaned automatically. */
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    flash_set('success', 'User "' . $u['name'] . '" deleted successfully.');
} catch (PDOException $e) {
    error_log('[users.delete] ' . $e->getMessage());
    flash_set('error', 'Delete failed: ' . $e->getMessage());
}

redirect('admin/modules/users/index.php');