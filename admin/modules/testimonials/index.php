<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Testimonials';
$adminPageSub   = 'Manage devotee voices';

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
                $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
                flash_set('success', 'Testimonial deleted.');
            } else {
                $pdo->prepare('UPDATE testimonials SET is_active = 1 - is_active WHERE id = ?')
                    ->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[testimonials] ' . $e->getMessage());
            flash_set('error', 'Action failed.');
        }
    }
    redirect('admin/modules/testimonials/index.php');
}

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM testimonials WHERE is_active = 1')->fetchColumn(),
    'avg'      => (float) $pdo->query('SELECT COALESCE(AVG(rating),0) FROM testimonials WHERE is_active = 1')->fetchColumn(),
    'five'     => (int) $pdo->query('SELECT COUNT(*) FROM testimonials WHERE rating = 5')->fetchColumn(),
];

/* ==================================================================
 |  Fetch all
 * ================================================================== */
$items = $pdo->query(
    'SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.tm-wrap{margin:-8px 0 0}
.tm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.tm-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.tm-stats{grid-template-columns:1fr}}
.tm-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.tm-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.tm-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.tm-stat-foot{font-size:12px;color:#6B7280}
.tm-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.tm-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.tm-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.tm-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.tm-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.tm-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.tm-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB}
.tm-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.tm-btn-add:hover{background:#9B4C34;color:#fff}
table.dataTable.tm-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.tm-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.tm-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.tm-avatar{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;
  font-weight:700;font-size:15px;font-family:var(--font-serif,Georgia,serif)}
.tm-name{font-weight:600;color:#1F2937;font-size:13.5px;line-height:1.3}
.tm-city{font-size:11.5px;color:#6B7280;margin-top:3px}
.tm-msg{color:#4B5563;font-size:13px;line-height:1.55;max-width:380px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-stars{color:#F59E0B;font-size:14px;letter-spacing:1px}
.tm-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.tm-pill.green{background:#ECFDF5;color:#059669}
.tm-pill.red{background:#FEF2F2;color:#DC2626}
.tm-actions{display:flex;gap:6px;flex-wrap:nowrap}
.tm-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.tm-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.tm-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.tm-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.tm-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="tm-wrap">

  <div class="tm-stats">
    <div class="tm-stat"><div><div class="tm-stat-label">Total Testimonials</div>
      <div class="tm-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="tm-stat-foot">All devotee voices</div></div>
      <div class="tm-stat-icon plum"><i class="bi bi-chat-quote"></i></div></div>
    <div class="tm-stat"><div><div class="tm-stat-label">Active</div>
      <div class="tm-stat-value"><?= number_format($stats['active']) ?></div>
      <div class="tm-stat-foot">Shown on site</div></div>
      <div class="tm-stat-icon green"><i class="bi bi-check2-circle"></i></div></div>
    <div class="tm-stat"><div><div class="tm-stat-label">Average Rating</div>
      <div class="tm-stat-value"><?= number_format($stats['avg'], 1) ?></div>
      <div class="tm-stat-foot">Out of 5 stars</div></div>
      <div class="tm-stat-icon amber"><i class="bi bi-star-fill"></i></div></div>
    <div class="tm-stat"><div><div class="tm-stat-label">5-Star Reviews</div>
      <div class="tm-stat-value"><?= number_format($stats['five']) ?></div>
      <div class="tm-stat-foot">Perfect ratings</div></div>
      <div class="tm-stat-icon terra"><i class="bi bi-trophy"></i></div></div>
  </div>

  <div class="tm-card">
    <div class="tm-head">
      <div style="font-weight:600;color:#1F2937">All Testimonials</div>
      <a href="<?= url('admin/modules/testimonials/create.php') ?>" class="tm-btn-add">
        <i class="bi bi-plus-lg"></i> Add Testimonial
      </a>
    </div>

    <table id="testiTable" class="tm-table">
      <thead>
        <tr>
          <th style="width:60px"></th>
          <th>Devotee</th>
          <th>Message</th>
          <th style="width:110px">Rating</th>
          <th style="width:80px">Order</th>
          <th style="width:110px">Status</th>
          <th style="width:250px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $t): ?>
          <?php
          $initial = strtoupper(mb_substr($t['initials'] ?: ($t['name'] ?: 'U'), 0, 2));
          ?>
          <tr>
            <td><div class="tm-avatar"><?= e($initial) ?></div></td>
            <td>
              <div class="tm-name"><?= e($t['name']) ?></div>
              <?php if (!empty($t['city'])): ?>
                <div class="tm-city"><?= e($t['city']) ?></div>
              <?php endif; ?>
            </td>
            <td><div class="tm-msg"><?= e($t['message']) ?></div></td>
            <td data-order="<?= (int) $t['rating'] ?>">
              <div class="tm-stars"><?= str_repeat('★', max(0, min(5, (int) $t['rating']))) ?></div>
            </td>
            <td style="color:#6B7280;font-weight:600"><?= (int) $t['sort_order'] ?></td>
            <td data-order="<?= (int) $t['is_active'] ?>">
              <?php if ($t['is_active']): ?>
                <span class="tm-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="tm-pill red"><i class="bi bi-x-circle-fill"></i> Hidden</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="tm-actions">
                <a href="<?= url('admin/modules/testimonials/edit.php?id=' . (int) $t['id']) ?>"
                   class="tm-act tm-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>

                <form method="post" style="margin:0"
                      data-confirm="<?= $t['is_active'] ? 'Hide' : 'Show' ?> '<?= e($t['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <?php if ($t['is_active']): ?>
                    <button class="tm-act tm-act-warn"><i class="bi bi-eye-slash-fill"></i> Hide</button>
                  <?php else: ?>
                    <button class="tm-act tm-act-ok"><i class="bi bi-eye-fill"></i> Show</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/testimonials/delete.php') ?>"
                      data-confirm="Delete '<?= e($t['name']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <button class="tm-act tm-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
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
    if ($.fn.DataTable.isDataTable('#testiTable')) return;
    $('#testiTable').DataTable({
      order: [[4, 'asc']],
      columnDefs: [{ targets: [0, 6], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>