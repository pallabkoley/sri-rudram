<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Inline image helpers */
if (!defined('ASSETS_PATH')) {
    foreach ([dirname(__DIR__, 3) . '/assets', dirname(__DIR__, 2) . '/assets'] as $p) {
        if (is_dir($p)) { define('ASSETS_PATH', $p); break; }
    }
}

if (!function_exists('prod_delete_image')) {
    function prod_delete_image(?string $p): void {
        if (!$p) return;
        if (preg_match('#^https?://#i', $p)) return;
        $rel = $p;
        if (strpos($rel, '/assets/img/') === 0) $rel = substr($rel, strlen('/assets/'));
        elseif (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/'));
        elseif (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);
        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
        if (!defined('ASSETS_PATH')) return;
        $path = ASSETS_PATH . '/img/' . $rel;
        $real = realpath($path); $root = realpath(ASSETS_PATH . '/img');
        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) @unlink($real);
    }
}

if (!function_exists('prod_img_url')) {
    function prod_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;
        $stored = ltrim($stored, '/');
        if (strpos($stored, 'assets/img/') === 0) $stored = substr($stored, strlen('assets/img/'));
        $marker = 'assets/img/';
        $pos = strpos($stored, $marker);
        if ($pos !== false) $stored = substr($stored, $pos + strlen($marker));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}

if (!function_exists('prod_img')) {
    function prod_img(?string $stored, string $alt = '', string $cls = 'pd-thumb'): string {
        $src = prod_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($cls, ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;color:#9A8B7C">'
                 . '<i class="bi bi-image"></i></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="'
             . htmlspecialchars($alt, ENT_QUOTES) . '" class="'
             . htmlspecialchars($cls, ENT_QUOTES) . '" loading="lazy">';
    }
}

$adminPageTitle = 'Products';
$adminPageSub   = 'Manage your Siddha Store catalogue';

/* POST delete/toggle */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));
    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                $img = (string) $pdo->query("SELECT image_url FROM products WHERE id = " . (int) $id)->fetchColumn();
                $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
                prod_delete_image($img);
                flash_set('success', 'Product deleted.');
            } else {
                $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[products] ' . $e->getMessage());
            flash_set('error', 'Action failed.');
        }
    }
    redirect('admin/modules/products/index.php');
}

$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'featured' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_featured = 1')->fetchColumn(),
    'outstock' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 0')->fetchColumn(),
];

$products = $pdo->query(
    'SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN product_categories c ON c.id = p.category_id
     ORDER BY p.sort_order ASC, p.id DESC'
)->fetchAll();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.pd-wrap{margin:-8px 0 0}
.pd-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.pd-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.pd-stats{grid-template-columns:1fr}}
.pd-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.pd-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.pd-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.pd-stat-foot{font-size:12px;color:#6B7280}
.pd-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.pd-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.pd-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.pd-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.pd-stat-icon.red{background:linear-gradient(135deg,#EF4444,#B91C1C)}
.pd-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.pd-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.pd-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.pd-btn-add:hover{background:#9B4C34;color:#fff}
.pd-btn-alt{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 16px;
  font-size:13px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;white-space:nowrap}
.pd-btn-alt:hover{border-color:#B76146;color:#B76146}
table.dataTable.pd-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.pd-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.pd-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.pd-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB;display:block}
.pd-name{font-weight:600;color:#1F2937;line-height:1.3}
.pd-slug{font-size:11.5px;color:#6B7280;font-family:monospace;margin-top:3px}
.pd-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.pd-pill.green{background:#ECFDF5;color:#059669}
.pd-pill.red{background:#FEF2F2;color:#DC2626}
.pd-pill.amber{background:#FFFBEB;color:#D97706}
.pd-pill.plum{background:#F4EAF0;color:#351B2F}
.pd-pill.blue{background:#EFF6FF;color:#2563EB}
.pd-pill.sage{background:#ECFDF5;color:#047857}
.pd-pill.terra{background:#FFF7ED;color:#C2410C}
.pd-actions{display:flex;gap:6px;flex-wrap:nowrap}
.pd-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.pd-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.pd-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.pd-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.pd-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="pd-wrap">

  <div class="pd-stats">
    <div class="pd-stat">
      <div>
        <div class="pd-stat-label">Total Products</div>
        <div class="pd-stat-value"><?= number_format($stats['total']) ?></div>
        <div class="pd-stat-foot">Sacred items in store</div>
      </div>
      <div class="pd-stat-icon plum"><i class="bi bi-bag"></i></div>
    </div>
    <div class="pd-stat">
      <div>
        <div class="pd-stat-label">Active</div>
        <div class="pd-stat-value"><?= number_format($stats['active']) ?></div>
        <div class="pd-stat-foot">Live on the site</div>
      </div>
      <div class="pd-stat-icon green"><i class="bi bi-check2-circle"></i></div>
    </div>
    <div class="pd-stat">
      <div>
        <div class="pd-stat-label">Featured</div>
        <div class="pd-stat-value"><?= number_format($stats['featured']) ?></div>
        <div class="pd-stat-foot">Highlighted products</div>
      </div>
      <div class="pd-stat-icon amber"><i class="bi bi-star-fill"></i></div>
    </div>
    <div class="pd-stat">
      <div>
        <div class="pd-stat-label">Out of Stock</div>
        <div class="pd-stat-value"><?= number_format($stats['outstock']) ?></div>
        <div class="pd-stat-foot">Needs restocking</div>
      </div>
      <div class="pd-stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
    </div>
  </div>

  <div class="pd-card">
    <div class="pd-head">
      <div style="font-weight:600;color:#1F2937">All Products</div>
      <div style="display:flex;gap:10px">
        <a href="<?= url('admin/modules/products/categories.php') ?>" class="pd-btn-alt">
          <i class="bi bi-tags"></i> Categories
        </a>
        <a href="<?= url('admin/modules/products/create.php') ?>" class="pd-btn-add">
          <i class="bi bi-plus-lg"></i> Add Product
        </a>
      </div>
    </div>

    <table id="productsTable" class="pd-table">
      <thead>
        <tr>
          <th style="width:70px">Image</th>
          <th>Product</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Featured</th>
          <th>Order</th>
          <th>Status</th>
          <th style="width:290px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= prod_img($p['image_url'], $p['name']) ?></td>
            <td>
              <div class="pd-name"><?= e($p['name']) ?></div>
              <div class="pd-slug"><?= e($p['slug']) ?></div>
            </td>
            <td>
              <?php if ($p['category_name']): ?>
                <span class="pd-pill plum"><?= e($p['category_name']) ?></span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>
            <td style="font-weight:600">
              <?= inr($p['price']) ?>
              <?php if (!empty($p['mrp']) && $p['mrp'] > $p['price']): ?>
                <div style="font-size:11px;color:#9CA3AF;text-decoration:line-through;font-weight:400">
                  <?= inr($p['mrp']) ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['stock'] <= 0): ?>
                <span class="pd-pill red">Out of stock</span>
              <?php elseif ($p['stock'] < 10): ?>
                <span class="pd-pill amber"><?= (int) $p['stock'] ?> left</span>
              <?php else: ?>
                <span style="font-weight:600;color:#059669"><?= (int) $p['stock'] ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['is_featured']): ?>
                <span class="pd-pill amber"><i class="bi bi-star-fill"></i> Yes</span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>
            <td style="color:#6B7280;font-weight:600"><?= (int) $p['sort_order'] ?></td>
            <td>
              <?php if ($p['is_active']): ?>
                <span class="pd-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="pd-pill red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="pd-actions">
                <a href="<?= url('admin/modules/products/edit.php?id=' . (int) $p['id']) ?>"
                   class="pd-act pd-act-edit">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
                <a href="<?= url('admin/modules/products/stock-update.php?id=' . (int) $p['id']) ?>"
                   class="pd-act pd-act-warn">
                  <i class="bi bi-box-seam"></i> Stock
                </a>
                <form method="post" style="margin:0"
                      data-confirm="<?= $p['is_active'] ? 'Disable' : 'Enable' ?> '<?= e($p['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <?php if ($p['is_active']): ?>
                    <button class="pd-act pd-act-warn" style="display:none"></button>
                  <?php endif; ?>
                </form>
                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/products/delete.php') ?>"
                      data-confirm="Delete '<?= e($p['name']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button class="pd-act pd-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
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
    if ($.fn.DataTable.isDataTable('#productsTable')) return;
    $('#productsTable').DataTable({
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