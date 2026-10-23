<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Inline image helpers
 * ================================================================== */
if (!function_exists('pj_img_url')) {
    function pj_img_url(?string $s): string {
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
if (!function_exists('pj_img')) {
    function pj_img(?string $stored, string $alt = '', string $cls = 'pj-thumb'): string {
        $src = pj_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($cls, ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;color:#9A8B7C">'
                 . '<i class="bi bi-flower1"></i></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="'
             . htmlspecialchars($alt, ENT_QUOTES) . '" class="'
             . htmlspecialchars($cls, ENT_QUOTES) . '" loading="lazy">';
    }
}
if (!function_exists('pj_temple_label')) {
    function pj_temple_label(array $p): array {
        if (!empty($p['temple_name'])) return ['text' => $p['temple_name'], 'custom' => false];
        if (!empty($p['custom_temple_name'])) return ['text' => $p['custom_temple_name'], 'custom' => true];
        return ['text' => '', 'custom' => false];
    }
}

$adminPageTitle = 'Pujas';
$adminPageSub   = 'Manage all pujas and rituals';

/* ==================================================================
 |  POST — delete + toggle
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));
    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                $b = $pdo->prepare('SELECT COUNT(*) FROM puja_bookings WHERE puja_id = ?');
                $b->execute([$id]);
                $bookings = (int) $b->fetchColumn();

                if ($bookings > 0) {
                    $pdo->prepare('UPDATE pujas SET is_active = 0 WHERE id = ?')->execute([$id]);
                    flash_set('info', "Puja has {$bookings} booking(s) — deactivated instead of deleted.");
                } else {
                    $img = (string) $pdo->query("SELECT image_url FROM pujas WHERE id = " . (int) $id)->fetchColumn();
                    $pdo->prepare('DELETE FROM pujas WHERE id = ?')->execute([$id]);
                    if ($img !== '' && strpos($img, 'http') !== 0) {
                        $rel = ltrim($img, '/');
                        if (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/img/'));
                        $m = 'assets/img/';
                        $pos = strpos($rel, $m);
                        if ($pos !== false) $rel = substr($rel, $pos + strlen($m));
                        $path = ASSETS_PATH . '/img/' . ltrim($rel, '/');
                        if (defined('ASSETS_PATH') && is_file($path)) @unlink($path);
                    }
                    flash_set('success', 'Puja deleted.');
                }
            } else {
                $pdo->prepare('UPDATE pujas SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[pujas] ' . $e->getMessage());
            flash_set('error', 'Action failed.');
        }
    }
    redirect('admin/modules/pujas/index.php');
}

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM pujas')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM pujas WHERE is_active = 1')->fetchColumn(),
    'popular'  => (int) $pdo->query('SELECT COUNT(*) FROM pujas WHERE is_popular = 1')->fetchColumn(),
    'bookings' => (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings')->fetchColumn(),
];

/* ==================================================================
 |  Fetch pujas + sub-content counts (packages, benefits, faqs, procedures)
 * ================================================================== */
$pujas = $pdo->query(
    "SELECT p.*,
            t.name AS temple_name,
            (SELECT COUNT(*) FROM puja_packages   WHERE puja_id = p.id AND is_active = 1) AS pkg_count,
            (SELECT COUNT(*) FROM puja_benefits   WHERE puja_id = p.id AND is_active = 1) AS ben_count,
            (SELECT COUNT(*) FROM puja_procedures WHERE puja_id = p.id AND is_active = 1) AS prc_count,
            (SELECT COUNT(*) FROM puja_faqs       WHERE puja_id = p.id AND is_active = 1) AS faq_count,
            (SELECT COUNT(*) FROM puja_related    WHERE puja_id = p.id) AS rel_count
     FROM pujas p
     LEFT JOIN temples t ON t.id = p.temple_id
     ORDER BY p.sort_order ASC, p.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.pj-wrap{margin:-8px 0 0}
.pj-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.pj-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.pj-stats{grid-template-columns:1fr}}
.pj-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.pj-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.pj-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.pj-stat-foot{font-size:12px;color:#6B7280}
.pj-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.pj-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.pj-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.pj-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.pj-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.pj-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.pj-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.pj-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.pj-btn-add:hover{background:#9B4C34;color:#fff}
table.dataTable.pj-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.pj-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 12px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.pj-table tbody td{padding:14px 12px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.pj-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB;display:block}
.pj-name{font-weight:600;color:#1F2937;line-height:1.3;max-width:240px}
.pj-slug{font-size:11.5px;color:#6B7280;font-family:monospace;margin-top:3px}
.pj-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.pj-pill.green{background:#ECFDF5;color:#059669}
.pj-pill.red{background:#FEF2F2;color:#DC2626}
.pj-pill.amber{background:#FFFBEB;color:#D97706}
.pj-pill.plum{background:#F4EAF0;color:#351B2F}
.pj-pill.blue{background:#EFF6FF;color:#2563EB}
.pj-pill.gold{background:#FFF7E0;color:#92700C}
.pj-actions{display:flex;gap:6px;flex-wrap:nowrap}
.pj-act{display:inline-flex;align-items:center;gap:5px;padding:7px 11px;font-size:11.5px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.pj-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.pj-act-manage{background:#F5F3FF;color:#7C3AED;border-color:#DDD6FE}
.pj-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.pj-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.pj-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}

/* Sub-content badges */
.pj-sub{display:flex;gap:4px;flex-wrap:wrap}
.pj-sub-badge{
  display:inline-flex;align-items:center;gap:3px;
  padding:3px 7px;font-size:10px;font-weight:700;border-radius:5px;
  background:#F4EAF0;color:#5B3A55;white-space:nowrap;
}
.pj-sub-badge.ok{background:#ECFDF5;color:#059669}
.pj-sub-badge.warn{background:#FEF2F2;color:#DC2626}
.pj-sub-badge i{font-size:10px}
</style>

<div class="pj-wrap">

  <div class="pj-stats">
    <div class="pj-stat"><div><div class="pj-stat-label">Total Pujas</div>
      <div class="pj-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="pj-stat-foot">Sacred rituals</div></div>
      <div class="pj-stat-icon plum"><i class="bi bi-flower1"></i></div></div>
    <div class="pj-stat"><div><div class="pj-stat-label">Active</div>
      <div class="pj-stat-value"><?= number_format($stats['active']) ?></div>
      <div class="pj-stat-foot">Live for booking</div></div>
      <div class="pj-stat-icon green"><i class="bi bi-check2-circle"></i></div></div>
    <div class="pj-stat"><div><div class="pj-stat-label">Popular</div>
      <div class="pj-stat-value"><?= number_format($stats['popular']) ?></div>
      <div class="pj-stat-foot">Homepage highlight</div></div>
      <div class="pj-stat-icon amber"><i class="bi bi-star-fill"></i></div></div>
    <div class="pj-stat"><div><div class="pj-stat-label">Total Bookings</div>
      <div class="pj-stat-value"><?= number_format($stats['bookings']) ?></div>
      <div class="pj-stat-foot">All-time</div></div>
      <div class="pj-stat-icon terra"><i class="bi bi-journal-check"></i></div></div>
  </div>

  <div class="pj-card">
    <div class="pj-head">
      <div style="font-weight:600;color:#1F2937">All Pujas</div>
      <a href="<?= url('admin/modules/pujas/create.php') ?>" class="pj-btn-add">
        <i class="bi bi-plus-lg"></i> Add Puja
      </a>
    </div>

    <table id="pujasTable" class="pj-table">
      <thead>
        <tr>
          <th style="width:70px">Image</th>
          <th>Puja</th>
          <th>Temple</th>
          <th>Price</th>
          <th>Sub-Content</th>
          <th>Popular</th>
          <th>Order</th>
          <th>Status</th>
          <th style="width:290px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pujas as $p): ?>
          <?php $tl = pj_temple_label($p); ?>
          <tr>
            <td><?= pj_img($p['image_url'], $p['name']) ?></td>

            <td>
              <div class="pj-name"><?= e($p['name']) ?></div>
              <div class="pj-slug"><?= e($p['slug']) ?></div>
            </td>

            <td>
              <?php if ($tl['text'] !== ''): ?>
                <span class="pj-pill <?= $tl['custom'] ? 'blue' : 'plum' ?>">
                  <?= e($tl['text']) ?><?= $tl['custom'] ? ' ✱' : '' ?>
                </span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td style="font-weight:600"><?= inr($p['price']) ?></td>

            <td>
              <div class="pj-sub">
                <span class="pj-sub-badge <?= $p['pkg_count'] > 0 ? 'ok' : '' ?>">
                  <i class="bi bi-box"></i> Pkg <?= (int)$p['pkg_count'] ?>
                </span>
                <span class="pj-sub-badge <?= $p['ben_count'] > 0 ? 'ok' : '' ?>">
                  <i class="bi bi-star"></i> Ben <?= (int)$p['ben_count'] ?>
                </span>
                <span class="pj-sub-badge <?= $p['prc_count'] > 0 ? 'ok' : '' ?>">
                  <i class="bi bi-list-ol"></i> Step <?= (int)$p['prc_count'] ?>
                </span>
                <span class="pj-sub-badge <?= $p['faq_count'] > 0 ? 'ok' : '' ?>">
                  <i class="bi bi-question-circle"></i> FAQ <?= (int)$p['faq_count'] ?>
                </span>
                <span class="pj-sub-badge <?= $p['rel_count'] > 0 ? 'ok' : '' ?>">
                  <i class="bi bi-link-45deg"></i> Rel <?= (int)$p['rel_count'] ?>
                </span>
              </div>
            </td>

            <td data-order="<?= (int) $p['is_popular'] ?>">
              <?php if ($p['is_popular']): ?>
                <span class="pj-pill amber"><i class="bi bi-star-fill"></i> Yes</span>
              <?php else: ?><span style="color:#9CA3AF">—</span><?php endif; ?>
            </td>

            <td style="color:#6B7280;font-weight:600"><?= (int) $p['sort_order'] ?></td>

            <td data-order="<?= (int) $p['is_active'] ?>">
              <?php if ($p['is_active']): ?>
                <span class="pj-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="pj-pill red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="pj-actions">
                <a href="<?= url('admin/modules/pujas/edit.php?id=' . (int) $p['id']) ?>"
                   class="pj-act pj-act-manage">
                  <i class="bi bi-gear-fill"></i> Manage
                </a>

                <form method="post" style="margin:0"
                      data-confirm="<?= $p['is_active'] ? 'Disable' : 'Enable' ?> '<?= e($p['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <?php if ($p['is_active']): ?>
                    <button class="pj-act pj-act-warn"><i class="bi bi-pause-circle-fill"></i> Disable</button>
                  <?php else: ?>
                    <button class="pj-act pj-act-ok"><i class="bi bi-play-circle-fill"></i> Enable</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/pujas/delete.php') ?>"
                      data-confirm="Delete '<?= e($p['name']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button class="pj-act pj-act-del"><i class="bi bi-trash-fill"></i></button>
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
    if ($.fn.DataTable.isDataTable('#pujasTable')) return;
    $('#pujasTable').DataTable({
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