<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Subscribers';
$adminPageSub   = 'Manage newsletter subscribers';

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'   => (int) $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn(),
    'active'  => (int) $pdo->query("SELECT COUNT(*) FROM subscribers WHERE status = 'active'")->fetchColumn(),
    'unsub'   => (int) $pdo->query("SELECT COUNT(*) FROM subscribers WHERE status = 'unsubscribed'")->fetchColumn(),
    'monthly' => (int) $pdo->query("SELECT COUNT(*) FROM subscribers WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
];

/* ==================================================================
 |  Filter
 * ================================================================== */
$statusFilter = trim((string)($_GET['status'] ?? ''));

if ($statusFilter === 'active' || $statusFilter === 'unsubscribed') {
    $stmt = $pdo->prepare(
        'SELECT * FROM subscribers WHERE status = ? ORDER BY subscribed_at DESC'
    );
    $stmt->execute([$statusFilter]);
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $subscribers = $pdo->query(
        'SELECT * FROM subscribers ORDER BY subscribed_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
}

$search = trim((string)($_GET['q'] ?? ''));
if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM subscribers WHERE email LIKE ? ORDER BY subscribed_at DESC'
    );
    $stmt->execute(['%' . $search . '%']);
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.sb-wrap{margin:-8px 0 0}
.sb-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.sb-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.sb-stats{grid-template-columns:1fr}}
.sb-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.sb-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.sb-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.sb-stat-foot{font-size:12px;color:#6B7280}
.sb-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.sb-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.sb-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.sb-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.sb-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.sb-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.sb-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.sb-chips{display:flex;gap:8px;flex-wrap:wrap}
.sb-chip{display:inline-flex;align-items:center;padding:7px 16px;border-radius:8px;
  font-size:12.5px;font-weight:600;text-decoration:none;transition:all .18s;
  background:#fff;color:#1F2937;border:1px solid #E5E7EB;white-space:nowrap}
.sb-chip:hover{border-color:#B76146;color:#B76146}
.sb-chip.active{background:#351B2F;color:#fff;border-color:#351B2F}
.sb-search{display:flex;gap:8px;align-items:center}
.sb-search input{padding:8px 14px;font-size:13px;border:1px solid #E5E7EB;border-radius:8px;
  font-family:inherit;color:#1F2937;min-width:200px}
.sb-search input:focus{outline:none;border-color:#B76146;box-shadow:0 0 0 3px rgba(183,97,70,.12)}
.sb-search button{padding:8px 16px;font-size:13px;font-weight:600;border-radius:8px;
  background:#B76146;color:#fff;border:1px solid #B76146;cursor:pointer;font-family:inherit}
.sb-search button:hover{background:#9B4C34}
.sb-export{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;white-space:nowrap}
.sb-export:hover{border-color:#B76146;color:#B76146}
table.dataTable.sb-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.sb-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.sb-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.sb-email{font-weight:600;color:#1F2937;font-size:13.5px;line-height:1.3}
.sb-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.sb-pill.green{background:#ECFDF5;color:#059669}
.sb-pill.red{background:#FEF2F2;color:#DC2626}
.sb-actions{display:flex;gap:6px;flex-wrap:nowrap}
.sb-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.sb-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
.sb-act-del:hover{background:#FEE2E2}
</style>

<div class="sb-wrap">

  <div class="sb-stats">
    <div class="sb-stat"><div><div class="sb-stat-label">Total Subscribers</div>
      <div class="sb-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="sb-stat-foot">All-time</div></div>
      <div class="sb-stat-icon plum"><i class="bi bi-people"></i></div></div>
    <div class="sb-stat"><div><div class="sb-stat-label">Active</div>
      <div class="sb-stat-value"><?= number_format($stats['active']) ?></div>
      <div class="sb-stat-foot">Receiving emails</div></div>
      <div class="sb-stat-icon green"><i class="bi bi-envelope-check"></i></div></div>
    <div class="sb-stat"><div><div class="sb-stat-label">Unsubscribed</div>
      <div class="sb-stat-value"><?= number_format($stats['unsub']) ?></div>
      <div class="sb-stat-foot">Opted out</div></div>
      <div class="sb-stat-icon amber"><i class="bi bi-envelope-slash"></i></div></div>
    <div class="sb-stat"><div><div class="sb-stat-label">Last 30 Days</div>
      <div class="sb-stat-value"><?= number_format($stats['monthly']) ?></div>
      <div class="sb-stat-foot">New signups</div></div>
      <div class="sb-stat-icon terra"><i class="bi bi-graph-up-arrow"></i></div></div>
  </div>

  <div class="sb-card">
    <div class="sb-head">
      <div class="sb-chips">
        <a href="<?= url('admin/modules/subscribers/index.php') ?>"
           class="sb-chip<?= $statusFilter === '' && $search === '' ? ' active' : '' ?>">All</a>
        <a href="<?= url('admin/modules/subscribers/index.php?status=active') ?>"
           class="sb-chip<?= $statusFilter === 'active' ? ' active' : '' ?>">Active</a>
        <a href="<?= url('admin/modules/subscribers/index.php?status=unsubscribed') ?>"
           class="sb-chip<?= $statusFilter === 'unsubscribed' ? ' active' : '' ?>">Unsubscribed</a>
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <form class="sb-search" method="get" action="<?= url('admin/modules/subscribers/index.php') ?>">
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search email…">
          <button type="submit"><i class="bi bi-search"></i> Search</button>
        </form>

        <a href="<?= url('admin/modules/subscribers/export.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : '')) ?>"
           class="sb-export">
          <i class="bi bi-download"></i> Export CSV
        </a>
      </div>
    </div>

    <table id="subsTable" class="sb-table">
      <thead>
        <tr>
          <th style="width:60px">ID</th>
          <th>Email Address</th>
          <th>Status</th>
          <th>Subscribed</th>
          <th style="width:120px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subscribers as $s): ?>
          <tr>
            <td style="color:#6B7280;font-weight:600">#<?= (int) $s['id'] ?></td>
            <td>
              <div class="sb-email"><?= e($s['email']) ?></div>
            </td>
            <td data-order="<?= e($s['status']) ?>">
              <?php if ($s['status'] === 'active'): ?>
                <span class="sb-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="sb-pill red"><i class="bi bi-x-circle-fill"></i> Unsubscribed</span>
              <?php endif; ?>
            </td>
            <td style="color:#6B7280;font-size:12.5px">
              <?= date('d M Y, h:i A', strtotime($s['subscribed_at'])) ?>
            </td>
            <td>
              <div class="sb-actions">
                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/subscribers/delete.php') ?>"
                      data-confirm="Delete '<?= e($s['email']) ?>'? This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                  <button class="sb-act sb-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
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
    if ($.fn.DataTable.isDataTable('#subsTable')) return;
    $('#subsTable').DataTable({
      order: [[3, 'desc']],
      columnDefs: [{ targets: [0, 4], orderable: false }],
      pageLength: 25, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>