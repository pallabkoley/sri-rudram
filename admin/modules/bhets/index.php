<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Inline safety net — image helpers missing হলে load */
if (!function_exists('bhet_admin_delete_image')) {
    if (!defined('ASSETS_PATH')) {
        foreach ([
            dirname(__DIR__, 3) . '/assets',
            dirname(__DIR__, 2) . '/assets',
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets',
        ] as $__p) {
            if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
        }
    }
    if (!function_exists('bhet_admin_delete_image')) {
        function bhet_admin_delete_image(?string $pathOrUrl): void {
            if (!$pathOrUrl) return;
            if (preg_match('#^https?://#i', $pathOrUrl)) {
                $prefix = (defined('APP_URL') ? APP_URL : '') . '/assets/img/';
                if (strpos($pathOrUrl, $prefix) !== 0) return;
                $pathOrUrl = substr($pathOrUrl, strlen((defined('APP_URL') ? APP_URL : '') . '/assets/'));
            }
            $rel = $pathOrUrl;
            if (strpos($rel, '/assets/img/') === 0) $rel = substr($rel, strlen('/assets/'));
            elseif (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/'));
            elseif (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);
            $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
            if (!defined('ASSETS_PATH')) return;
            $path = ASSETS_PATH . '/img/' . $rel;
            $real = realpath($path);
            $root = realpath(ASSETS_PATH . '/img');
            if ($real && $root && strpos($real, $root) === 0 && is_file($real)) @unlink($real);
        }
    }
}

$adminPageTitle = 'Bhets';
$adminPageSub   = 'Manage all bhet & daan sevas';

/* ------------------------------------------------------------------
 |  POST-only destructive actions (delete + toggle)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                $img = (string) $pdo->query(
                    "SELECT image_url FROM bhets WHERE id = " . (int) $id
                )->fetchColumn();

                $pdo->prepare('DELETE FROM bhets WHERE id = ?')->execute([$id]);

                if ($img !== '' && strpos($img, 'http') !== 0) {
                    bhet_admin_delete_image($img);
                }
                flash_set('success', 'Bhet deleted.');
            } else {
                $pdo->prepare('UPDATE bhets SET is_active = 1 - is_active WHERE id = ?')
                    ->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[bhets] ' . $e->getMessage());
            flash_set('error', 'Action failed. Please try again.');
        }
    }
    redirect('admin/modules/bhets/index.php');
}

/* ------------------------------------------------------------------
 |  Stats
 * ------------------------------------------------------------------ */
$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM bhets')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM bhets WHERE is_active = 1')->fetchColumn(),
    'featured' => (int) $pdo->query('SELECT COUNT(*) FROM bhets WHERE is_featured = 1')->fetchColumn(),
    'types'    => (int) $pdo->query('SELECT COUNT(*) FROM bhet_types')->fetchColumn(),
];

/* ------------------------------------------------------------------
 |  Fetch all bhets + joined type & temple
 * ------------------------------------------------------------------ */
$bhets = $pdo->query(
    'SELECT b.id, b.name, b.slug, b.price, b.image_url, b.is_featured,
            b.sort_order, b.is_active, b.created_at,
            t.name  AS type_name,
            tp.name AS temple_name
     FROM bhets b
     LEFT JOIN bhet_types t  ON t.id  = b.bhet_type_id
     LEFT JOIN temples    tp ON tp.id = b.temple_id
     ORDER BY b.sort_order ASC, b.id DESC'
)->fetchAll();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.bh-wrap{margin:-8px 0 0}
.bh-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.bh-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.bh-stats{grid-template-columns:1fr}}
.bh-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.bh-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.bh-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.bh-stat-foot{font-size:12px;color:#6B7280}
.bh-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.bh-stat-icon.plum {background:linear-gradient(135deg,#5B3A55,#351B2F)}
.bh-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.bh-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.bh-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.bh-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.bh-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB}
.bh-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.bh-btn-add:hover{background:#9B4C34;color:#fff}
table.dataTable.bh-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.bh-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.bh-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
table.dataTable.bh-table tbody tr:hover td{background:#FAFAFB}
.bh-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB;display:block}
.bh-name{font-weight:600;color:#1F2937;line-height:1.3}
.bh-slug{font-size:11.5px;color:#6B7280;font-family:monospace;margin-top:3px}
.bh-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.bh-pill.green{background:#ECFDF5;color:#059669}
.bh-pill.red{background:#FEF2F2;color:#DC2626}
.bh-pill.amber{background:#FFFBEB;color:#D97706}
.bh-pill.plum{background:#F4EAF0;color:#351B2F}
.bh-pill.blue{background:#EFF6FF;color:#2563EB}
.bh-actions{display:flex;gap:6px;flex-wrap:nowrap}
.bh-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.bh-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.bh-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.bh-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.bh-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="bh-wrap">

  <div class="bh-stats">
    <div class="bh-stat">
      <div>
        <div class="bh-stat-label">Total Bhets</div>
        <div class="bh-stat-value"><?= number_format($stats['total']) ?></div>
        <div class="bh-stat-foot">Sacred offerings</div>
      </div>
      <div class="bh-stat-icon plum"><i class="bi bi-gift"></i></div>
    </div>
    <div class="bh-stat">
      <div>
        <div class="bh-stat-label">Active</div>
        <div class="bh-stat-value"><?= number_format($stats['active']) ?></div>
        <div class="bh-stat-foot">Live on the site</div>
      </div>
      <div class="bh-stat-icon green"><i class="bi bi-check2-circle"></i></div>
    </div>
    <div class="bh-stat">
      <div>
        <div class="bh-stat-label">Featured</div>
        <div class="bh-stat-value"><?= number_format($stats['featured']) ?></div>
        <div class="bh-stat-foot">Highlighted bhets</div>
      </div>
      <div class="bh-stat-icon amber"><i class="bi bi-star-fill"></i></div>
    </div>
    <div class="bh-stat">
      <div>
        <div class="bh-stat-label">Seva Types</div>
        <div class="bh-stat-value"><?= number_format($stats['types']) ?></div>
        <div class="bh-stat-foot">Categories in use</div>
      </div>
      <div class="bh-stat-icon terra"><i class="bi bi-tags"></i></div>
    </div>
  </div>

  <div class="bh-card">
    <div class="bh-head">
      <div style="font-weight:600;color:#1F2937">All Bhets</div>
      <a href="<?= url('admin/modules/bhets/create.php') ?>" class="bh-btn-add">
        <i class="bi bi-plus-lg"></i> Add New Bhet
      </a>
    </div>

    <table id="bhetsTable" class="bh-table">
      <thead>
        <tr>
          <th style="width:70px">Image</th>
          <th>Bhet Name</th>
          <th>Type</th>
          <th>Temple</th>
          <th>Price</th>
          <th>Featured</th>
          <th>Order</th>
          <th>Status</th>
          <th style="width:270px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bhets as $b): ?>
          <tr>
            <td>
              <?php
              /* =====================================================
               |  BHET IMAGE — explicit URL construction (no img_tag)
               * ===================================================== */
              $storedImage = trim((string)($b['image_url'] ?? ''));
              $imageUrl = '';

              if ($storedImage !== '') {

                  // Already a complete URL (http/https)
                  if (preg_match('#^https?://#i', $storedImage)) {
                      $imageUrl = $storedImage;
                  } else {
                      $storedImage = ltrim($storedImage, '/');

                      // Strip accidental "assets/img/" prefix at the start
                      if (strpos($storedImage, 'assets/img/') === 0) {
                          $storedImage = substr($storedImage, strlen('assets/img/'));
                      }

                      // If "assets/img/" appears anywhere, keep what's after it
                      $assetsMarker = 'assets/img/';
                      $pos = strpos($storedImage, $assetsMarker);
                      if ($pos !== false) {
                          $storedImage = substr($storedImage, $pos + strlen($assetsMarker));
                      }

                      $imageUrl = 'https://divyaaastha.xinkpayroll.co.in/assets/img/'
                                . ltrim($storedImage, '/');
                  }
              }
              ?>

              <?php if ($imageUrl !== ''): ?>
                <img
                    src="<?= e($imageUrl) ?>"
                    alt="<?= e($b['name']) ?>"
                    class="bh-thumb"
                    loading="lazy"
                >
              <?php else: ?>
                <div class="bh-thumb"
                     style="display:grid;place-items:center;color:#9A8B7C">
                  <i class="bi bi-image"></i>
                </div>
              <?php endif; ?>
            </td>

            <td>
              <div class="bh-name"><?= e($b['name']) ?></div>
              <div class="bh-slug"><?= e($b['slug']) ?></div>
            </td>

            <td>
              <?php if (!empty($b['type_name'])): ?>
                <span class="bh-pill plum"><?= e($b['type_name']) ?></span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($b['temple_name'])): ?>
                <span class="bh-pill blue"><?= e($b['temple_name']) ?></span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td style="font-weight:600"><?= inr($b['price']) ?></td>

            <td>
              <?php if ($b['is_featured']): ?>
                <span class="bh-pill amber"><i class="bi bi-star-fill"></i> Yes</span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td style="color:#6B7280;font-weight:600"><?= (int) $b['sort_order'] ?></td>

            <td>
              <?php if ($b['is_active']): ?>
                <span class="bh-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="bh-pill red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="bh-actions">
                <a href="<?= url('admin/modules/bhets/edit.php?id=' . (int) $b['id']) ?>"
                   class="bh-act bh-act-edit">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>

                <form method="post" style="margin:0"
                      data-confirm="<?= $b['is_active'] ? 'Disable' : 'Enable' ?> '<?= e($b['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                  <?php if ($b['is_active']): ?>
                    <button class="bh-act bh-act-warn"><i class="bi bi-pause-circle-fill"></i> Disable</button>
                  <?php else: ?>
                    <button class="bh-act bh-act-ok"><i class="bi bi-play-circle-fill"></i> Enable</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/bhets/delete.php') ?>"
                      data-confirm="Delete '<?= e($b['name']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                  <button class="bh-act bh-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
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
    if ($.fn.DataTable.isDataTable('#bhetsTable')) return;
    $('#bhetsTable').DataTable({
      order: [[6, 'asc']],
      columnDefs: [{ targets: [0, 8], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>