<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/testimonials/index.php');
}
csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid testimonial ID.');
    redirect('admin/modules/testimonials/index.php');
}

try {
    $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
    flash_set('success', 'Testimonial deleted.');
} catch (PDOException $e) {
    error_log('[testimonials.delete] ' . $e->getMessage());
    flash_set('error', 'Delete failed.');
}

redirect('admin/modules/testimonials/index.php');