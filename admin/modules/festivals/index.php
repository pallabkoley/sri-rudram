<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Festivals';
$adminPageSub   = 'Manage festival calendar';

/* POST — delete / toggle */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                $pdo->prepare('DELETE FROM festivals WHERE id = ?')->execute([$id]);
                flash_set('success', 'Festival deleted.');
            } else {
                $pdo->prepare('UPDATE festivals SET is_active = 1 - is_active WHERE id = ?')
                    ->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[festivals] ' . $e->getMessage());
            flash_set('error', 'Action failed.');
        }
    }
    redirect('admin/modules/festivals/index.php');
}

/* Stats */
$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM festivals')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM festivals WHERE is_active = 1')->fetchColumn(),
    'upcoming' => (int) $pdo->query('SELECT COUNT(*) FROM festivals WHERE festival_date >= CURDATE()')->fetchColumn(),
    'past'     => (int) $pdo->query('SELECT COUNT(*) FROM festivals WHERE festival_date < CURDATE()')->fetchColumn(),
];

/* Fetch */
$items = $pdo->query(
    'SELECT id, name, slug, festival_date, short_desc, is_active
     FROM festivals
     ORDER BY festival_date ASC'
)->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.fe-wrap{margin:-8px 0 0}
.fe-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.fe-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.fe-stats{grid-template-columns:1fr}}
.fe-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.fe-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.fe-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.fe-stat-foot{font-size:12px;color:#6B7280}
.fe-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.fe-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.fe-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.fe-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.fe-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.fe-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.fe-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB}
.fe-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.fe-btn-add:hover{background:#9B4C34;color:#fff}
table.dataTable.fe-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.fe-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.fe-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.fe-name{font-weight:600;color:#1F2937;line-height:1.3;font-size:13.5px}
.fe-slug{font-size:11.5px;color:#6B7280;font-family:monospace;margin-top:3px}
.fe-date{color:#351B2F;font-weight:600;font-size:13px}
.fe-date small{color:#6B7280;font-weight:400;font-size:11px;display:block;margin-top:2px}
.fe-desc{color:#6B7280;font-size:12.5px;line-height:1.5;max-width:380px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.fe-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.fe-pill.red{background:#FEF2F2;color:#DC2626}
.fe-pill.upcoming{background:#EFF6FF;color:#2563EB}
.fe-pill.past{background:#F3F4F6;color:#6B7280}
.fe-actions{display:flex;gap:6px;flex-wrap:nowrap}
.fe-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.fe-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.fe-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.fe-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.fe-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="fe-wrap">

  <div class="fe-stats">
    <div class="fe-stat"><div><div class="fe-stat-label">Total Festivals</div>
      <div class="fe-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="fe-stat-foot">All-time</div></div>
      <div class="fe-stat-icon plum"><i class="bi bi-calendar-event"></i></div></div>
    <div class="fe-stat"><div><div class="fe-stat-label">Active</div>
      <div class="fe-stat-value"><?= number_format($stats['active']) ?></div>
      <div class="fe-stat-foot">Visible on site</div></div>
      <div class="fe-stat-icon green"><i class="bi bi-check2-circle"></i></div></div>
    <div class="fe-stat"><div><div class="fe-stat-label">Upcoming</div>
      <div class="fe-stat-value"><?= number_format($stats['upcoming']) ?></div>
      <div class="fe-stat-foot">Future dates</div></div>
      <div class="fe-stat-icon amber"><i class="bi bi-calendar-plus"></i></div></div>
    <div class="fe-stat"><div><div class="fe-stat-label">Past</div>
      <div class="fe-stat-value"><?= number_format($stats['past']) ?></div>
      <div class="fe-stat-foot">Archived</div></div>
      <div class="fe-stat-icon terra"><i class="bi bi-calendar-x"></i></div></div>
  </div>

  <div class="fe-card">
    <div class="fe-head">
      <div style="font-weight:600;color:#1F2937">All Festivals</div>
      <a href="<?= url('admin/modules/festivals/create.php') ?>" class="fe-btn-add">
        <i class="bi bi-plus-lg"></i> Add Festival
      </a>
    </div>

    <table id="festTable" class="fe-table">
      <thead>
        <tr>
          <th>Festival</th>
          <th style="width:150px">Date</th>
          <th>Description</th>
          <th style="width:130px">Status</th>
          <th style="width:230px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $f): ?>
          <?php
          $fDate  = strtotime($f['festival_date']);
          $isPast = $fDate < strtotime(date('Y-m-d'));
          ?>
          <tr>
            <td>
              <div class="fe-name"><?= e($f['name']) ?></div>
              <div class="fe-slug"><?= e($f['slug']) ?></div>
            </td>
            <td>
              <div class="fe-date">
                <?= date('d M Y', $fDate) ?>
                <small><?= date('l', $fDate) ?></small>
              </div>
            </td>
            <td><div class="fe-desc"><?= e($f['short_desc']) ?></div></td>
            <td data-order="<?= (int) $f['is_active'] ?>">
              <?php if (!$f['is_active']): ?>
                <span class="fe-pill red"><i class="bi bi-x-circle-fill"></i> Hidden</span>
              <?php elseif ($isPast): ?>
                <span class="fe-pill past"><i class="bi bi-clock-history"></i> Past</span>
              <?php else: ?>
                <span class="fe-pill upcoming"><i class="bi bi-calendar-check"></i> Upcoming</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fe-actions">
                <a href="<?= url('admin/modules/festivals/edit.php?id=' . (int) $f['id']) ?>"
                   class="fe-act fe-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>

                <form method="post" style="margin:0"
                      data-confirm="<?= $f['is_active'] ? 'Hide' : 'Show' ?> '<?= e($f['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                  <?php if ($f['is_active']): ?>
                    <button class="fe-act fe-act-warn"><i class="bi bi-eye-slash-fill"></i> Hide</button>
                  <?php else: ?>
                    <button class="fe-act fe-act-ok"><i class="bi bi-eye-fill"></i> Show</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/festivals/delete.php') ?>"
                      data-confirm="Delete '<?= e($f['name']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                  <button class="fe-act fe-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
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
    if ($.fn.DataTable.isDataTable('#festTable')) return;
    $('#festTable').DataTable({
      order: [[1, 'asc']],
      columnDefs: [{ targets: [4], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>