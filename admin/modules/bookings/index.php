<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Puja Bookings';
$adminPageSub   = 'Manage all devotee puja bookings';

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'     => (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings')->fetchColumn(),
    'pending'   => (int) $pdo->query("SELECT COUNT(*) FROM puja_bookings WHERE status = 'pending'")->fetchColumn(),
    'confirmed' => (int) $pdo->query("SELECT COUNT(*) FROM puja_bookings WHERE status = 'confirmed'")->fetchColumn(),
    'completed' => (int) $pdo->query("SELECT COUNT(*) FROM puja_bookings WHERE status = 'completed'")->fetchColumn(),
    'revenue'   => (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM puja_bookings WHERE status <> 'cancelled'")->fetchColumn(),
];

/* ==================================================================
 |  Status filter
 * ================================================================== */
$statusFilter = trim((string)($_GET['status'] ?? ''));
$validStatuses = ['pending','confirmed','completed','cancelled'];

if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
    $stmt = $pdo->prepare(
        'SELECT b.*, p.name AS puja_name, p.slug AS puja_slug, p.image_url AS puja_image,
                t.name AS temple_name, t.city AS temple_city,
                u.name AS user_name, u.email AS user_email
         FROM puja_bookings b
         LEFT JOIN pujas p   ON p.id = b.puja_id
         LEFT JOIN temples t ON t.id = b.temple_id
         LEFT JOIN users u   ON u.id = b.user_id
         WHERE b.status = ?
         ORDER BY b.created_at DESC'
    );
    $stmt->execute([$statusFilter]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $bookings = $pdo->query(
        'SELECT b.*, p.name AS puja_name, p.slug AS puja_slug, p.image_url AS puja_image,
                t.name AS temple_name, t.city AS temple_city,
                u.name AS user_name, u.email AS user_email
         FROM puja_bookings b
         LEFT JOIN pujas p   ON p.id = b.puja_id
         LEFT JOIN temples t ON t.id = b.temple_id
         LEFT JOIN users u   ON u.id = b.user_id
         ORDER BY b.created_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
}

if (!function_exists('bk_status_class')) {
    function bk_status_class(string $s): string {
        return match ($s) {
            'completed' => 'green',
            'confirmed' => 'blue',
            'cancelled' => 'red',
            default     => 'amber',
        };
    }
}

if (!function_exists('bk_img_url')) {
    function bk_img_url(?string $s): string {
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

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.bk-wrap{margin:-8px 0 0}
.bk-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.bk-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.bk-stats{grid-template-columns:1fr}}
.bk-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.bk-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.bk-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.bk-stat-value.small{font-size:22px}
.bk-stat-foot{font-size:12px;color:#6B7280}
.bk-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.bk-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.bk-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.bk-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.bk-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.bk-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.bk-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.bk-chips{display:flex;gap:8px;flex-wrap:wrap}
.bk-chip{display:inline-flex;align-items:center;padding:7px 16px;border-radius:8px;
  font-size:12.5px;font-weight:600;text-decoration:none;transition:all .18s;
  background:#fff;color:#1F2937;border:1px solid #E5E7EB;white-space:nowrap}
.bk-chip:hover{border-color:#B76146;color:#B76146}
.bk-chip.active{background:#351B2F;color:#fff;border-color:#351B2F}
.bk-export{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;white-space:nowrap}
.bk-export:hover{border-color:#B76146;color:#B76146}
table.dataTable.bk-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.bk-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.bk-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.bk-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB;display:block}
.bk-thumb.ph{display:grid;place-items:center;color:#9A8B7C}
.bk-ref{font-family:monospace;font-size:12px;font-weight:600;color:#1F2937}
.bk-name{font-weight:600;color:#1F2937;line-height:1.3;font-size:13.5px}
.bk-meta{font-size:11.5px;color:#6B7280;margin-top:3px}
.bk-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.bk-pill.green{background:#ECFDF5;color:#059669}
.bk-pill.red{background:#FEF2F2;color:#DC2626}
.bk-pill.amber{background:#FFFBEB;color:#D97706}
.bk-pill.blue{background:#EFF6FF;color:#2563EB}
.bk-amount{font-weight:700;color:#351B2F;font-size:14px}
.bk-actions{display:flex;gap:6px;flex-wrap:nowrap}
.bk-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.bk-act-view{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
</style>

<div class="bk-wrap">

  <div class="bk-stats">
    <div class="bk-stat"><div><div class="bk-stat-label">Total Bookings</div>
      <div class="bk-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="bk-stat-foot">All-time</div></div>
      <div class="bk-stat-icon plum"><i class="bi bi-calendar-check"></i></div></div>

    <div class="bk-stat"><div><div class="bk-stat-label">Pending</div>
      <div class="bk-stat-value"><?= number_format($stats['pending']) ?></div>
      <div class="bk-stat-foot">Awaiting confirmation</div></div>
      <div class="bk-stat-icon amber"><i class="bi bi-hourglass-split"></i></div></div>

    <div class="bk-stat"><div><div class="bk-stat-label">Confirmed</div>
      <div class="bk-stat-value"><?= number_format($stats['confirmed']) ?></div>
      <div class="bk-stat-foot">Ready for puja</div></div>
      <div class="bk-stat-icon green"><i class="bi bi-check2-circle"></i></div></div>

    <div class="bk-stat"><div><div class="bk-stat-label">Revenue</div>
      <div class="bk-stat-value small"><?= inr($stats['revenue']) ?></div>
      <div class="bk-stat-foot">Excludes cancelled</div></div>
      <div class="bk-stat-icon terra"><i class="bi bi-currency-rupee"></i></div></div>
  </div>

  <div class="bk-card">
    <div class="bk-head">
      <div class="bk-chips">
        <a href="<?= url('admin/modules/bookings/index.php') ?>"
           class="bk-chip<?= $statusFilter === '' ? ' active' : '' ?>">All</a>
        <?php foreach ($validStatuses as $s): ?>
          <a href="<?= url('admin/modules/bookings/index.php?status=' . $s) ?>"
             class="bk-chip<?= $statusFilter === $s ? ' active' : '' ?>">
            <?= ucfirst($s) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <a href="<?= url('admin/modules/bookings/export.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : '')) ?>"
         class="bk-export">
        <i class="bi bi-download"></i> Export CSV
      </a>
    </div>

    <table id="bookingsTable" class="bk-table">
      <thead>
        <tr>
          <th style="width:70px">Image</th>
          <th>Booking Ref</th>
          <th>Puja / Temple</th>
          <th>Devotee</th>
          <th>Date</th>
          <th>Amount</th>
          <th>Status</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <?php $imgSrc = bk_img_url($b['puja_image']); ?>
          <tr>
            <td>
              <?php if ($imgSrc): ?>
                <img src="<?= e($imgSrc) ?>" alt="" class="bk-thumb" onerror="this.style.display='none'">
              <?php else: ?>
                <div class="bk-thumb ph"><i class="bi bi-flower1"></i></div>
              <?php endif; ?>
            </td>
            <td><div class="bk-ref"><?= e($b['booking_ref']) ?></div></td>
            <td>
              <div class="bk-name"><?= e($b['puja_name'] ?: 'Puja') ?></div>
              <div class="bk-meta">
                <?php if ($b['temple_name']): ?>🛕 <?= e($b['temple_name']) ?><?php endif; ?>
                <?php if ($b['temple_city']): ?> · <?= e($b['temple_city']) ?><?php endif; ?>
              </div>
            </td>
            <td>
              <div class="bk-name"><?= e($b['devotee_name']) ?></div>
              <div class="bk-meta">
                <?php if ($b['user_email']): ?><?= e($b['user_email']) ?><?php endif; ?>
              </div>
            </td>
            <td style="color:#6B7280;font-size:12.5px">
              <?= date('d M Y', strtotime($b['booking_date'])) ?>
              <?php if ($b['muhurat_time']): ?>
                <div style="font-size:11px"><?= date('h:i A', strtotime($b['muhurat_time'])) ?></div>
              <?php endif; ?>
            </td>
            <td><div class="bk-amount"><?= inr($b['amount']) ?></div></td>
            <td data-order="<?= e($b['status']) ?>">
              <span class="bk-pill <?= bk_status_class($b['status']) ?>"><?= e($b['status']) ?></span>
            </td>
            <td>
              <div class="bk-actions">
                <a href="<?= url('admin/modules/bookings/view.php?id=' . (int) $b['id']) ?>"
                   class="bk-act bk-act-view"><i class="bi bi-eye"></i> View</a>
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
    if ($.fn.DataTable.isDataTable('#bookingsTable')) return;
    $('#bookingsTable').DataTable({
      order: [[4, 'desc']],
      columnDefs: [{ targets: [0, 7], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>