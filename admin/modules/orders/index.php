<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Orders';
$adminPageSub   = 'Manage all customer orders';

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'     => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending'   => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'shipped'   => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('packed','shipped')")->fetchColumn(),
    'revenue'   => (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn(),
];

/* ==================================================================
 |  Status filter
 * ================================================================== */
$statusFilter = trim((string)($_GET['status'] ?? ''));
$validStatuses = ['pending','paid','packed','shipped','delivered','cancelled'];

if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
    $stmt = $pdo->prepare(
        'SELECT o.*, u.name AS customer_name, u.email AS customer_email
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.status = ?
         ORDER BY o.created_at DESC'
    );
    $stmt->execute([$statusFilter]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $orders = $pdo->query(
        'SELECT o.*, u.name AS customer_name, u.email AS customer_email
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
}

/* ==================================================================
 |  Status pill colors
 * ================================================================== */
if (!function_exists('ord_status_class')) {
    function ord_status_class(string $status): string {
        return match ($status) {
            'delivered' => 'green',
            'shipped','packed' => 'blue',
            'paid'      => 'amber',
            'cancelled' => 'red',
            default     => 'amber',
        };
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.od-wrap{margin:-8px 0 0}
.od-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.od-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.od-stats{grid-template-columns:1fr}}
.od-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.od-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.od-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.od-stat-value.small{font-size:22px}
.od-stat-foot{font-size:12px;color:#6B7280}
.od-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.od-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.od-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.od-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.od-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.od-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.od-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.od-chips{display:flex;gap:8px;flex-wrap:wrap}
.od-chip{display:inline-flex;align-items:center;padding:7px 16px;border-radius:8px;
  font-size:12.5px;font-weight:600;text-decoration:none;transition:all .18s;
  background:#fff;color:#1F2937;border:1px solid #E5E7EB;white-space:nowrap}
.od-chip:hover{border-color:#B76146;color:#B76146}
.od-chip.active{background:#351B2F;color:#fff;border-color:#351B2F}
.od-export{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;white-space:nowrap}
.od-export:hover{border-color:#B76146;color:#B76146}
table.dataTable.od-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.od-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.od-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.od-num{font-family:monospace;font-size:12.5px;font-weight:600;color:#1F2937}
.od-name{font-weight:600;color:#1F2937;line-height:1.3;font-size:13.5px}
.od-meta{font-size:11.5px;color:#6B7280;margin-top:3px}
.od-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.od-pill.green{background:#ECFDF5;color:#059669}
.od-pill.red{background:#FEF2F2;color:#DC2626}
.od-pill.amber{background:#FFFBEB;color:#D97706}
.od-pill.blue{background:#EFF6FF;color:#2563EB}
.od-amount{font-weight:700;color:#351B2F;font-size:14px}
.od-actions{display:flex;gap:6px;flex-wrap:nowrap}
.od-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.od-act-view{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.od-act-inv{background:#F4EAF0;color:#351B2F;border-color:#E5D5DD}
</style>

<div class="od-wrap">

  <div class="od-stats">
    <div class="od-stat"><div><div class="od-stat-label">Total Orders</div>
      <div class="od-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="od-stat-foot">All-time</div></div>
      <div class="od-stat-icon plum"><i class="bi bi-box-seam"></i></div></div>

    <div class="od-stat"><div><div class="od-stat-label">Pending</div>
      <div class="od-stat-value"><?= number_format($stats['pending']) ?></div>
      <div class="od-stat-foot">Awaiting action</div></div>
      <div class="od-stat-icon amber"><i class="bi bi-hourglass-split"></i></div></div>

    <div class="od-stat"><div><div class="od-stat-label">Packed / Shipped</div>
      <div class="od-stat-value"><?= number_format($stats['shipped']) ?></div>
      <div class="od-stat-foot">In transit</div></div>
      <div class="od-stat-icon green"><i class="bi bi-truck"></i></div></div>

    <div class="od-stat"><div><div class="od-stat-label">Revenue</div>
      <div class="od-stat-value small"><?= inr($stats['revenue']) ?></div>
      <div class="od-stat-foot">Excludes cancelled</div></div>
      <div class="od-stat-icon terra"><i class="bi bi-currency-rupee"></i></div></div>
  </div>

  <div class="od-card">
    <div class="od-head">
      <div class="od-chips">
        <a href="<?= url('admin/modules/orders/index.php') ?>"
           class="od-chip<?= $statusFilter === '' ? ' active' : '' ?>">All</a>
        <?php foreach ($validStatuses as $s): ?>
          <a href="<?= url('admin/modules/orders/index.php?status=' . $s) ?>"
             class="od-chip<?= $statusFilter === $s ? ' active' : '' ?>">
            <?= ucfirst($s) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <a href="<?= url('admin/modules/orders/export.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : '')) ?>"
         class="od-export">
        <i class="bi bi-download"></i> Export CSV
      </a>
    </div>

    <table id="ordersTable" class="od-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th style="width:180px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <?php
          /* Count items */
          $itemCount = (int) $pdo->query('SELECT COUNT(*) FROM order_items WHERE order_id = ' . (int) $o['id'])->fetchColumn();
          ?>
          <tr>
            <td>
              <div class="od-num">#<?= e($o['order_number']) ?></div>
            </td>
            <td>
              <div class="od-name"><?= e($o['ship_name'] ?: 'Guest') ?></div>
              <div class="od-meta">
                <?php if ($o['customer_email']): ?><?= e($o['customer_email']) ?><?php endif; ?>
                <?php if ($o['ship_phone']): ?> · <?= e($o['ship_phone']) ?><?php endif; ?>
              </div>
            </td>
            <td style="color:#6B7280;font-weight:600"><?= $itemCount ?></td>
            <td><div class="od-amount"><?= inr($o['total']) ?></div></td>
            <td style="color:#6B7280;font-size:12.5px"><?= e($o['payment_method'] ?: '—') ?></td>
            <td data-order="<?= e($o['status']) ?>">
              <span class="od-pill <?= ord_status_class($o['status']) ?>"><?= e($o['status']) ?></span>
            </td>
            <td style="color:#6B7280;font-size:12.5px"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
              <div class="od-actions">
                <a href="<?= url('admin/modules/orders/view.php?id=' . (int) $o['id']) ?>"
                   class="od-act od-act-view"><i class="bi bi-eye"></i> View</a>
                <a href="<?= url('admin/modules/orders/invoice.php?id=' . (int) $o['id']) ?>"
                   target="_blank" class="od-act od-act-inv"><i class="bi bi-file-earmark-text"></i> Invoice</a>
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
    if ($.fn.DataTable.isDataTable('#ordersTable')) return;
    $('#ordersTable').DataTable({
      order: [[6, 'desc']],
      columnDefs: [{ targets: [7], orderable: false }],
      pageLength: 10, stateSave: true, stateDuration: 86400
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>