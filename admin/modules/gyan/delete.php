<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/gyan/index.php');
}
csrf_verify(post('_csrf', ''));

$id = (int) post('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid category ID.');
    redirect('admin/modules/gyan/index.php');
}

try {
    /* Refuse hard-delete if articles reference this category */
    $c = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = ?');
    $c->execute([$id]);
    $refs = (int) $c->fetchColumn();

    if ($refs > 0) {
        $pdo->prepare('UPDATE gyan_categories SET is_active = 0 WHERE id = ?')->execute([$id]);
        flash_set('info', "Category has {$refs} article(s) — deactivated instead of deleted.");
    } else {
        $pdo->prepare('DELETE FROM gyan_categories WHERE id = ?')->execute([$id]);
        flash_set('success', 'Category deleted.');
    }
} catch (PDOException $e) {
    error_log('[gyan.delete] ' . $e->getMessage());
    flash_set('error', 'Delete failed.');
}

redirect('admin/modules/gyan/index.php');