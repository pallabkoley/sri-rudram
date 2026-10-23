<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid user ID.'); redirect('admin/modules/users/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { flash_set('error','User not found.'); redirect('admin/modules/users/index.php'); }

$adminPageTitle = 'User: ' . $user['name'];
$adminPageSub   = 'Account overview';

/* Recent orders */
$ro = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$ro->execute([$id]);
$recentOrders = $ro->fetchAll(PDO::FETCH_ASSOC);

/* Recent bookings */
$rb = $pdo->prepare(
    'SELECT b.*, p.name AS puja_name, t.name AS temple_name
     FROM puja_bookings b
     LEFT JOIN pujas p ON p.id = b.puja_id
     LEFT JOIN temples t ON t.id = b.temple_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC LIMIT 5'
);
$rb->execute([$id]);
$recentBookings = $rb->fetchAll(PDO::FETCH_ASSOC);

/* Totals */
$totalOrders   = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE user_id = ' . (int) $id)->fetchColumn();
$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings WHERE user_id = ' . (int) $id)->fetchColumn();
$totalSpent    = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = " . (int) $id . " AND status <> 'cancelled'")->fetchColumn();

/* Helper for status pill */
if (!function_exists('uv_status_class')) {
    function uv_status_class(string $s): string {
        return match ($s) {
            'delivered','completed','confirmed' => 'green',
            'cancelled' => 'red',
            'shipped','packed' => 'blue',
            default     => 'amber',
        };
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.uv-wrap{margin:-8px 0 0}
.uv-back{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
  font-size:12.5px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;margin-bottom:20px}
.uv-back:hover{border-color:#B76146;color:#B76146}
.uv-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:24px;align-items:flex-start}
@media (max-width:1000px){.uv-grid{grid-template-columns:1fr}}
.uv-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:20px}
.uv-card__head{padding:16px 22px;border-bottom:1px solid #E5E7EB;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.uv-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);
  font-size:16px;color:#351B2F;font-weight:600}
.uv-hero{padding:22px;background:#FAF6EF;display:flex;align-items:center;gap:20px}
@media (max-width:600px){.uv-hero{flex-direction:column;text-align:center}}
.uv-hero__avatar{width:80px;height:80px;border-radius:50%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;
  font-weight:700;font-size:32px;font-family:var(--font-serif,Georgia,serif);flex-shrink:0}
.uv-hero__name{font-family:var(--font-serif,Georgia,serif);font-size:22px;
  color:#351B2F;font-weight:600;margin:0 0 4px;line-height:1.25}
.uv-hero__meta{font-size:13px;color:#6B7280;line-height:1.6}
.uv-rows{list-style:none;padding:0;margin:0}
.uv-rows li{padding:12px 22px;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;gap:14px;font-size:13.5px}
.uv-rows li:last-child{border-bottom:0}
.uv-rows .lbl{color:#6B7280}
.uv-rows .val{color:#351B2F;font-weight:600;text-align:right}
.uv-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.uv-pill.green{background:#ECFDF5;color:#059669}
.uv-pill.red{background:#FEF2F2;color:#DC2626}
.uv-pill.amber{background:#FFFBEB;color:#D97706}
.uv-pill.blue{background:#EFF6FF;color:#2563EB}
.uv-pill.plum{background:#F4EAF0;color:#351B2F}
.uv-list{list-style:none;padding:0;margin:0}
.uv-list li{padding:14px 22px;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;gap:14px;align-items:center}
.uv-list li:last-child{border-bottom:0}
.uv-list__title{font-weight:600;color:#351B2F;font-size:13.5px}
.uv-list__meta{font-size:12px;color:#6B7280;margin-top:3px}
.uv-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;
  font-size:12.5px;font-weight:600;border-radius:9px;border:1px solid transparent;
  cursor:pointer;font-family:inherit;text-decoration:none;transition:all .15s;white-space:nowrap}
.uv-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.uv-btn-primary:hover{background:#9B4C34;color:#fff}
.uv-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.uv-btn-outline:hover{border-color:#B76146;color:#B76146}
.uv-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:22px}
@media (max-width:600px){.uv-stats{grid-template-columns:1fr}}
.uv-stat{background:#FAF6EF;border-radius:12px;padding:16px}
.uv-stat__lbl{font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;
  color:#6B7280;margin-bottom:6px}
.uv-stat__val{font-size:22px;font-weight:700;color:#351B2F;line-height:1}
.uv-stat__val.small{font-size:18px}
</style>

<div class="uv-wrap">

  <a href="<?= url('admin/modules/users/index.php') ?>" class="uv-back">
    <i class="bi bi-arrow-left"></i> Back to Users
  </a>

  <div class="uv-grid">

    <!-- LEFT -->
    <div>

      <div class="uv-card">
        <div class="uv-hero">
          <div class="uv-hero__avatar"><?= e(strtoupper(mb_substr($user['name'] ?: 'U', 0, 1))) ?></div>
          <div>
            <h2 class="uv-hero__name"><?= e($user['name']) ?></h2>
            <div class="uv-hero__meta">
              <?= e($user['email']) ?>
              <?php if (!empty($user['phone'])): ?><br><?= e($user['phone']) ?><?php endif; ?>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
              <span class="uv-pill <?= $user['role'] === 'admin' ? 'plum' : 'blue' ?>">
                <?= e(ucfirst($user['role'])) ?>
              </span>
              <?php if ($user['status']): ?>
                <span class="uv-pill green">Active</span>
              <?php else: ?>
                <span class="uv-pill red">Inactive</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="uv-card">
        <div class="uv-card__head"><h3>Account Activity</h3></div>
        <div class="uv-stats">
          <div class="uv-stat"><div class="uv-stat__lbl">Orders</div>
            <div class="uv-stat__val"><?= $totalOrders ?></div></div>
          <div class="uv-stat"><div class="uv-stat__lbl">Puja Bookings</div>
            <div class="uv-stat__val"><?= $totalBookings ?></div></div>
          <div class="uv-stat"><div class="uv-stat__lbl">Total Spent</div>
            <div class="uv-stat__val small"><?= inr($totalSpent) ?></div></div>
        </div>
      </div>

      <div class="uv-card">
        <div class="uv-card__head"><h3>Recent Orders</h3></div>
        <?php if (empty($recentOrders)): ?>
          <div style="padding:30px;text-align:center;color:#6B7280;font-size:13.5px">No orders yet.</div>
        <?php else: ?>
          <ul class="uv-list">
            <?php foreach ($recentOrders as $o): ?>
              <li>
                <div>
                  <div class="uv-list__title">#<?= e($o['order_number']) ?></div>
                  <div class="uv-list__meta"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                </div>
                <div style="text-align:right">
                  <div style="font-weight:700;color:#351B2F"><?= inr($o['total']) ?></div>
                  <span class="uv-pill <?= uv_status_class($o['status']) ?>" style="margin-top:6px">
                    <?= e($o['status']) ?>
                  </span>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="uv-card">
        <div class="uv-card__head"><h3>Recent Puja Bookings</h3></div>
        <?php if (empty($recentBookings)): ?>
          <div style="padding:30px;text-align:center;color:#6B7280;font-size:13.5px">No bookings yet.</div>
        <?php else: ?>
          <ul class="uv-list">
            <?php foreach ($recentBookings as $b): ?>
              <li>
                <div>
                  <div class="uv-list__title"><?= e($b['puja_name'] ?: 'Puja') ?></div>
                  <div class="uv-list__meta">
                    <?= date('d M Y', strtotime($b['booking_date'])) ?>
                    <?php if (!empty($b['temple_name'])): ?> · <?= e($b['temple_name']) ?><?php endif; ?>
                  </div>
                </div>
                <div style="text-align:right">
                  <div style="font-weight:700;color:#351B2F"><?= inr($b['amount']) ?></div>
                  <span class="uv-pill <?= uv_status_class($b['status']) ?>" style="margin-top:6px">
                    <?= e($b['status']) ?>
                  </span>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

    </div>

    <!-- RIGHT -->
    <div>

      <div class="uv-card">
        <div class="uv-card__head"><h3>Account Details</h3></div>
        <ul class="uv-rows">
          <li><span class="lbl">User ID</span><span class="val">#<?= (int) $user['id'] ?></span></li>
          <li><span class="lbl">Gotra</span><span class="val"><?= e($user['gotra'] ?: '—') ?></span></li>
          <li><span class="lbl">Joined</span><span class="val"><?= date('d M Y', strtotime($user['created_at'])) ?></span></li>
          <li><span class="lbl">Updated</span><span class="val"><?= date('d M Y', strtotime($user['updated_at'])) ?></span></li>
        </ul>
      </div>

      <div class="uv-card">
        <div class="uv-card__head"><h3>Quick Actions</h3></div>
        <div style="padding:22px;display:flex;flex-direction:column;gap:10px">
          <a href="<?= url('admin/modules/users/edit.php?id=' . (int) $user['id']) ?>"
             class="uv-btn uv-btn-primary" style="width:100%;justify-content:center">
            <i class="bi bi-pencil-square"></i> Edit User
          </a>

          <form method="post" action="<?= url('admin/modules/users/toggle-status.php') ?>"
                data-confirm="<?= $user['status'] ? 'Disable' : 'Enable' ?> this user?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
            <button type="submit" class="uv-btn uv-btn-outline" style="width:100%;justify-content:center">
              <i class="bi bi-<?= $user['status'] ? 'pause-circle' : 'play-circle' ?>"></i>
              <?= $user['status'] ? 'Disable' : 'Enable' ?> User
            </button>
          </form>
        </div>
      </div>

    </div>

  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>