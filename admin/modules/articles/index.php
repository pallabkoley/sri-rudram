<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Inline image helpers */
if (!function_exists('art_img_url')) {
    function art_img_url(?string $s): string {
        $s = trim((string) $s);
        if ($s === '') return '';
        if (preg_match('#^https?://#i', $s)) return $s;
        $s = ltrim($s, '/');
        if (strpos($s, 'assets/img/') === 0) $s = substr($s, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($s, $m);
        if ($pos !== false) $s = substr($s, $pos + strlen($m));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($s, '/');
    }
}

if (!function_exists('art_img')) {
    function art_img(?string $stored, string $alt = '', string $cls = 'ar-thumb'): string {
        $src = art_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($cls, ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;color:#9A8B7C">'
                 . '<i class="bi bi-journal-text"></i></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="'
             . htmlspecialchars($alt, ENT_QUOTES) . '" class="'
             . htmlspecialchars($cls, ENT_QUOTES) . '" loading="lazy">';
    }
}

$adminPageTitle = 'Articles';
$adminPageSub   = 'Manage your Dharmik Gyan library';

/* POST delete/toggle */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
                flash_set('success', 'Article deleted.');
            } else {
                $pdo->prepare('UPDATE articles SET is_active = 1 - is_active WHERE id = ?')
                    ->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[articles] ' . $e->getMessage());
            flash_set('error', 'Action failed.');
        }
    }
    redirect('admin/modules/articles/index.php');
}

$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM articles WHERE is_active = 1')->fetchColumn(),
    'featured' => (int) $pdo->query('SELECT COUNT(*) FROM articles WHERE is_featured = 1')->fetchColumn(),
    'trending' => (int) $pdo->query('SELECT COUNT(*) FROM articles WHERE is_trending = 1')->fetchColumn(),
];

$articles = $pdo->query(
    'SELECT a.*, c.name AS category_name
     FROM articles a
     LEFT JOIN article_categories c ON c.id = a.category_id
     ORDER BY a.published_at DESC, a.id DESC'
)->fetchAll();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.ar-wrap{margin:-8px 0 0}
.ar-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.ar-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.ar-stats{grid-template-columns:1fr}}
.ar-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.ar-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.ar-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.ar-stat-foot{font-size:12px;color:#6B7280}
.ar-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.ar-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.ar-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.ar-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.ar-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.ar-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.ar-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.ar-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.ar-btn-add:hover{background:#9B4C34;color:#fff}
.ar-btn-alt{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 16px;
  font-size:13px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;white-space:nowrap}
.ar-btn-alt:hover{border-color:#B76146;color:#B76146}
table.dataTable.ar-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.ar-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.ar-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.ar-thumb{width:64px;height:48px;border-radius:8px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB;display:block}
.ar-title{font-weight:600;color:#1F2937;line-height:1.3}
.ar-slug{font-size:11.5px;color:#6B7280;font-family:monospace;margin-top:3px}
.ar-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.ar-pill.green{background:#ECFDF5;color:#059669}
.ar-pill.red{background:#FEF2F2;color:#DC2626}
.ar-pill.amber{background:#FFFBEB;color:#D97706}
.ar-pill.plum{background:#F4EAF0;color:#351B2F}
.ar-pill.blue{background:#EFF6FF;color:#2563EB}
.ar-actions{display:flex;gap:6px;flex-wrap:nowrap}
.ar-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.ar-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.ar-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.ar-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.ar-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="ar-wrap">

  <div class="ar-stats">
    <div class="ar-stat"><div><div class="ar-stat-label">Total Articles</div>
      <div class="ar-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="ar-stat-foot">Published & drafted</div></div>
      <div class="ar-stat-icon plum"><i class="bi bi-file-earmark-text"></i></div></div>
    <div class="ar-stat"><div><div class="ar-stat-label">Active</div>
      <div class="ar-stat-value"><?= number_format($stats['active']) ?></div>
      <div class="ar-stat-foot">Live on site</div></div>
      <div class="ar-stat-icon green"><i class="bi bi-check2-circle"></i></div></div>
    <div class="ar-stat"><div><div class="ar-stat-label">Featured</div>
      <div class="ar-stat-value"><?= number_format($stats['featured']) ?></div>
      <div class="ar-stat-foot">Highlighted</div></div>
      <div class="ar-stat-icon amber"><i class="bi bi-star-fill"></i></div></div>
    <div class="ar-stat"><div><div class="ar-stat-label">Trending</div>
      <div class="ar-stat-value"><?= number_format($stats['trending']) ?></div>
      <div class="ar-stat-foot">On trending list</div></div>
      <div class="ar-stat-icon terra"><i class="bi bi-fire"></i></div></div>
  </div>

  <div class="ar-card">
    <div class="ar-head">
      <div style="font-weight:600;color:#1F2937">All Articles</div>
      <div style="display:flex;gap:10px">
        <a href="<?= url('admin/modules/articles/categories.php') ?>" class="ar-btn-alt">
          <i class="bi bi-tags"></i> Categories
        </a>
        <a href="<?= url('admin/modules/articles/create.php') ?>" class="ar-btn-add">
          <i class="bi bi-plus-lg"></i> Add Article
        </a>
      </div>
    </div>

    <table id="articlesTable" class="ar-table">
      <thead>
        <tr>
          <th style="width:80px">Image</th>
          <th>Title</th>
          <th>Category</th>
          <th>Read</th>
          <th>Featured</th>
          <th>Trending</th>
          <th>Published</th>
          <th>Status</th>
          <th style="width:250px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($articles as $a): ?>
          <tr>
            <td><?= art_img($a['image_url'], $a['title']) ?></td>
            <td>
              <div class="ar-title"><?= e($a['title']) ?></div>
              <div class="ar-slug"><?= e($a['slug']) ?></div>
            </td>
            <td>
              <?php if ($a['category_name']): ?>
                <span class="ar-pill plum"><?= e($a['category_name']) ?></span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>
            <td style="color:#6B7280;font-weight:600"><?= (int) $a['read_minutes'] ?> min</td>
            <td data-order="<?= (int) $a['is_featured'] ?>">
              <?php if ($a['is_featured']): ?>
                <span class="ar-pill amber"><i class="bi bi-star-fill"></i> Yes</span>
              <?php else: ?><span style="color:#9CA3AF">—</span><?php endif; ?>
            </td>
            <td data-order="<?= (int) $a['is_trending'] ?>">
              <?php if ($a['is_trending']): ?>
                <span class="ar-pill blue"><i class="bi bi-fire"></i> Yes</span>
              <?php else: ?><span style="color:#9CA3AF">—</span><?php endif; ?>
            </td>
            <td style="color:#6B7280;font-size:12.5px">
              <?= $a['published_at'] ? date('d M Y', strtotime($a['published_at'])) : '—' ?>
            </td>
            <td data-order="<?= (int) $a['is_active'] ?>">
              <?php if ($a['is_active']): ?>
                <span class="ar-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="ar-pill red"><i class="bi bi-x-circle-fill"></i> Draft</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="ar-actions">
                <a href="<?= url('admin/modules/articles/edit.php?id=' . (int) $a['id']) ?>"
                   class="ar-act ar-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>

                <form method="post" style="margin:0"
                      data-confirm="<?= $a['is_active'] ? 'Unpublish' : 'Publish' ?> '<?= e($a['title']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <?php if ($a['is_active']): ?>
                    <button class="ar-act ar-act-warn"><i class="bi bi-pause-circle-fill"></i> Unpublish</button>
                  <?php else: ?>
                    <button class="ar-act ar-act-ok"><i class="bi bi-play-circle-fill"></i> Publish</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/articles/delete.php') ?>"
                      data-confirm="Delete '<?= e($a['title']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <button class="ar-act ar-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
  function init() {
    if (!window.jQuery || !$.fn.DataTable) return;
    if ($.fn.DataTable.isDataTable('#articlesTable')) return;
    $('#articlesTable').DataTable({
      order: [[6, 'desc']],
      columnDefs: [{ targets: [0, 8], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>