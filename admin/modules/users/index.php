<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Users';
$adminPageSub   = 'Manage all registered users';

/* ==================================================================
 |  Stats
 * ================================================================== */
$stats = [
    'total'     => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'devotees'  => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'devotee'")->fetchColumn(),
    'admins'    => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'active'    => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE status = 1')->fetchColumn(),
];

/* ==================================================================
 |  Role / status filter
 * ================================================================== */
$roleFilter   = trim((string)($_GET['role']   ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($roleFilter === 'admin' || $roleFilter === 'devotee') {
    $where[] = 'u.role = ?';
    $params[] = $roleFilter;
}

if ($statusFilter === 'active') {
    $where[] = 'u.status = 1';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'u.status = 0';
}

$sql = 'SELECT u.*,
               (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS order_count,
               (SELECT COUNT(*) FROM puja_bookings WHERE user_id = u.id) AS booking_count
        FROM users u';

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY u.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.us-wrap{margin:-8px 0 0}
.us-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:22px}
@media (max-width:1100px){.us-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.us-stats{grid-template-columns:1fr}}
.us-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.us-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.us-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.us-stat-foot{font-size:12px;color:#6B7280}
.us-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.us-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.us-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.us-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.us-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.us-card{background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06)}
.us-head{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;gap:12px;flex-wrap:wrap}
.us-chips{display:flex;gap:8px;flex-wrap:wrap}
.us-chip{display:inline-flex;align-items:center;padding:7px 16px;border-radius:8px;
  font-size:12.5px;font-weight:600;text-decoration:none;transition:all .18s;
  background:#fff;color:#1F2937;border:1px solid #E5E7EB;white-space:nowrap}
.us-chip:hover{border-color:#B76146;color:#B76146}
.us-chip.active{background:#351B2F;color:#fff;border-color:#351B2F}
.us-btn-add{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;
  font-size:13px;font-weight:600;border-radius:8px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:background .18s}
.us-btn-add:hover{background:#9B4C34;color:#fff}
table.dataTable.us-table{width:100% !important;border-collapse:separate !important;
  border-spacing:0;margin:0 !important}
table.dataTable.us-table thead th{background:#351B2F !important;color:#F7EBDD !important;
  text-align:left;padding:14px 16px !important;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;white-space:nowrap;border:none !important}
table.dataTable.us-table tbody td{padding:14px 16px !important;
  border-bottom:1px solid #E5E7EB !important;vertical-align:middle;color:#1F2937;background:#fff}
.us-avatar{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;font-weight:700;
  font-size:16px;font-family:var(--font-serif,Georgia,serif)}
.us-name{font-weight:600;color:#1F2937;line-height:1.3;font-size:13.5px}
.us-meta{font-size:11.5px;color:#6B7280;margin-top:3px;font-family:monospace}
.us-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px;white-space:nowrap}
.us-pill.green{background:#ECFDF5;color:#059669}
.us-pill.red{background:#FEF2F2;color:#DC2626}
.us-pill.amber{background:#FFFBEB;color:#D97706}
.us-pill.plum{background:#F4EAF0;color:#351B2F}
.us-pill.blue{background:#EFF6FF;color:#2563EB}
.us-actions{display:flex;gap:6px;flex-wrap:nowrap}
.us-act{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;
  font-weight:600;border-radius:7px;border:1px solid transparent;cursor:pointer;
  text-decoration:none;white-space:nowrap;font-family:inherit;background:#fff;transition:all .15s}
.us-act-view{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.us-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.us-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.us-act-ok{background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.us-act-del{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
</style>

<div class="us-wrap">

  <div class="us-stats">
    <div class="us-stat"><div><div class="us-stat-label">Total Users</div>
      <div class="us-stat-value"><?= number_format($stats['total']) ?></div>
      <div class="us-stat-foot">All registered</div></div>
      <div class="us-stat-icon plum"><i class="bi bi-people"></i></div></div>
    <div class="us-stat"><div><div class="us-stat-label">Devotees</div>
      <div class="us-stat-value"><?= number_format($stats['devotees']) ?></div>
      <div class="us-stat-foot">Customer accounts</div></div>
      <div class="us-stat-icon green"><i class="bi bi-person-heart"></i></div></div>
    <div class="us-stat"><div><div class="us-stat-label">Admins</div>
      <div class="us-stat-value"><?= number_format($stats['admins']) ?></div>
      <div class="us-stat-foot">Panel access</div></div>
      <div class="us-stat-icon amber"><i class="bi bi-shield-check"></i></div></div>
    <div class="us-stat"><div><div class="us-stat-label">Active</div>
      <div class="us-stat-value"><?= number_format($stats['active']) ?></div>
      <div class="us-stat-foot">Enabled accounts</div></div>
      <div class="us-stat-icon terra"><i class="bi bi-check2-circle"></i></div></div>
  </div>

  <div class="us-card">
    <div class="us-head">
      <div class="us-chips">
        <a href="<?= url('admin/modules/users/index.php') ?>"
           class="us-chip<?= $roleFilter === '' && $statusFilter === '' ? ' active' : '' ?>">All</a>
        <a href="<?= url('admin/modules/users/index.php?role=devotee') ?>"
           class="us-chip<?= $roleFilter === 'devotee' ? ' active' : '' ?>">Devotees</a>
        <a href="<?= url('admin/modules/users/index.php?role=admin') ?>"
           class="us-chip<?= $roleFilter === 'admin' ? ' active' : '' ?>">Admins</a>
        <a href="<?= url('admin/modules/users/index.php?status=active') ?>"
           class="us-chip<?= $statusFilter === 'active' ? ' active' : '' ?>">Active</a>
        <a href="<?= url('admin/modules/users/index.php?status=inactive') ?>"
           class="us-chip<?= $statusFilter === 'inactive' ? ' active' : '' ?>">Inactive</a>
      </div>
      <a href="<?= url('admin/modules/users/create.php') ?>" class="us-btn-add">
        <i class="bi bi-plus-lg"></i> Add User
      </a>
    </div>

    <table id="usersTable" class="us-table">
      <thead>
        <tr>
          <th style="width:60px"></th>
          <th>Name</th>
          <th>Email / Phone</th>
          <th>Role</th>
          <th>Orders</th>
          <th>Bookings</th>
          <th>Joined</th>
          <th>Status</th>
          <th style="width:230px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <?php
          $initial = strtoupper(mb_substr($u['name'] ?: 'U', 0, 1));
          ?>
          <tr>
            <td><div class="us-avatar"><?= e($initial) ?></div></td>
            <td>
              <div class="us-name"><?= e($u['name']) ?></div>
              <?php if (!empty($u['gotra'])): ?>
                <div class="us-meta" style="font-family:inherit">Gotra: <?= e($u['gotra']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="us-meta"><?= e($u['email']) ?></div>
              <?php if (!empty($u['phone'])): ?>
                <div class="us-meta" style="margin-top:2px"><?= e($u['phone']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="us-pill <?= $u['role'] === 'admin' ? 'plum' : 'blue' ?>">
                <?= e(ucfirst($u['role'])) ?>
              </span>
            </td>
            <td style="font-weight:600;color:#6B7280"><?= (int) $u['order_count'] ?></td>
            <td style="font-weight:600;color:#6B7280"><?= (int) $u['booking_count'] ?></td>
            <td style="color:#6B7280;font-size:12.5px"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td data-order="<?= (int) $u['status'] ?>">
              <?php if ($u['status']): ?>
                <span class="us-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="us-pill red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="us-actions">
                <a href="<?= url('admin/modules/users/view.php?id=' . (int) $u['id']) ?>"
                   class="us-act us-act-view"><i class="bi bi-eye"></i> View</a>

                <a href="<?= url('admin/modules/users/edit.php?id=' . (int) $u['id']) ?>"
                   class="us-act us-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/users/toggle-status.php') ?>"
                      data-confirm="<?= $u['status'] ? 'Disable' : 'Enable' ?> '<?= e($u['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <?php if ($u['status']): ?>
                    <button class="us-act us-act-warn"><i class="bi bi-pause-circle-fill"></i> Disable</button>
                  <?php else: ?>
                    <button class="us-act us-act-ok"><i class="bi bi-play-circle-fill"></i> Enable</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      action="<?= url('admin/modules/users/delete.php') ?>"
                      data-confirm="Delete '<?= e($u['name']) ?>' permanently? This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="us-act us-act-del"><i class="bi bi-trash-fill"></i> Delete</button>
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
    if ($.fn.DataTable.isDataTable('#usersTable')) return;
    $('#usersTable').DataTable({
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