<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Gyan Categories';
$adminPageSub   = 'Manage Dharmik Gyan categories';

/* ==================================================================
 |  POST — delete / toggle
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                /* Refuse if articles reference this category */
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
            } else {
                $pdo->prepare('UPDATE gyan_categories SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[gyan] ' . $e->getMessage());
            flash_set('error', 'Action failed.');
        }
    }
    redirect('admin/modules/gyan/index.php');
}

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'  => (int) $pdo->query('SELECT COUNT(*) FROM gyan_categories')->fetchColumn(),
    'active' => (int) $pdo->query('SELECT COUNT(*) FROM gyan_categories WHERE is_active = 1')->fetchColumn(),
];

/* ==================================================================
 |  Fetch all categories with article count
 * ================================================================== */
$categories = $pdo->query(
    'SELECT c.*,
            (SELECT COUNT(*) FROM articles WHERE category_id = c.id) AS article_count
     FROM gyan_categories c
     ORDER BY c.sort_order ASC, c.name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.gy-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:22px;max-width:560px}
.gy-stat{background:#fff;border-radius:14px;padding:20px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.gy-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:8px}
.gy-stat-value{font-size:26px;font-weight:700;color:#1F2937;line-height:1}
.gy-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.gy-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB}
.gy-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.gy-btn-add:hover{background:#9B4C34;color:#fff}
table.dataTable.gy-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.gy-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.gy-table thead th:first-child{border-radius:8px 0 0 8px}
table.dataTable.gy-table thead th:last-child{border-radius:0 8px 8px 0}
table.dataTable.gy-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.gy-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.gy-pill.green{background:#ECFDF5;color:#059669}
.gy-pill.red{background:#FEF2F2;color:#DC2626}
.gy-pill.plum{background:#F4EAF0;color:#351B2F}
.gy-actions{display:flex;gap:6px;flex-wrap:nowrap}
.gy-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.gy-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.gy-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.gy-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.gy-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="gy-stats">
  <div class="gy-stat"><div class="gy-stat-label">Total Categories</div>
    <div class="gy-stat-value"><?= number_format($stats['total']) ?></div></div>
  <div class="gy-stat"><div class="gy-stat-label">Active</div>
    <div class="gy-stat-value"><?= number_format($stats['active']) ?></div></div>
</div>

<div class="gy-card">
  <div class="gy-head">
    <div style="font-weight:600;color:#1F2937">All Gyan Categories</div>
    <a href="<?= url('admin/modules/gyan/create.php') ?>" class="gy-btn-add">
      <i class="bi bi-plus-lg"></i> Add Category
    </a>
  </div>

  <table id="gyanTable" class="gy-table">
    <thead>
      <tr>
        <th style="width:60px">ID</th>
        <th>Name</th>
        <th>Slug</th>
        <th>Description</th>
        <th style="width:100px">Articles</th>
        <th style="width:80px">Order</th>
        <th style="width:110px">Status</th>
        <th style="width:220px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td style="color:#6B7280;font-weight:600">#<?= (int) $c['id'] ?></td>
          <td style="font-weight:600"><?= e($c['name']) ?></td>
          <td style="color:#6B7280;font-family:monospace;font-size:12px"><?= e($c['slug']) ?></td>
          <td style="color:#6B7280;font-size:13px"><?= e($c['description'] ?: '—') ?></td>
          <td style="color:#6B7280;font-weight:600"><?= (int) $c['article_count'] ?></td>
          <td style="color:#6B7280;font-weight:600"><?= (int) $c['sort_order'] ?></td>
          <td>
            <?php if ($c['is_active']): ?>
              <span class="gy-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
            <?php else: ?>
              <span class="gy-pill red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="gy-actions">
              <a href="<?= url('admin/modules/gyan/edit.php?id=' . (int) $c['id']) ?>"
                 class="gy-act gy-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>

              <form method="post" style="margin:0"
                    data-confirm="<?= $c['is_active'] ? 'Disable' : 'Enable' ?> '<?= e($c['name']) ?>'?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <?php if ($c['is_active']): ?>
                  <button class="gy-act gy-act-warn"><i class="bi bi-pause-circle-fill"></i> Disable</button>
                <?php else: ?>
                  <button class="gy-act gy-act-ok"><i class="bi bi-play-circle-fill"></i> Enable</button>
                <?php endif; ?>
              </form>

              <form method="post" style="margin:0"
                    action="<?= url('admin/modules/gyan/delete.php') ?>"
                    data-confirm="Delete '<?= e($c['name']) ?>' permanently?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="gy-act gy-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
(function () {
  function init() {
    if (!window.jQuery || !$.fn.DataTable) return;
    if ($.fn.DataTable.isDataTable('#gyanTable')) return;
    $('#gyanTable').DataTable({
      order: [[5, 'asc']],
      columnDefs: [{ targets: [0, 7], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>