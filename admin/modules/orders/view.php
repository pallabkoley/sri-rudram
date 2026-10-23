<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid order ID.'); redirect('admin/modules/orders/index.php'); }

/* Fetch order */
$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS customer_name, u.email AS customer_email,
            u.phone AS customer_phone
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { flash_set('error','Order not found.'); redirect('admin/modules/orders/index.php'); }

/* Fetch items */
$it = $pdo->prepare(
    'SELECT oi.*, p.image_url AS product_image, p.slug AS product_slug
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?'
);
$it->execute([$id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = 'Order #' . $order['order_number'];
$adminPageSub   = 'Order details and management';

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

$validStatuses = ['pending','paid','packed','shipped','delivered','cancelled'];

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.ov-wrap{margin:-8px 0 0}
.ov-back{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
  font-size:12.5px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;margin-bottom:20px}
.ov-back:hover{border-color:#B76146;color:#B76146}

.ov-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:24px;align-items:flex-start}
@media (max-width:1000px){.ov-grid{grid-template-columns:1fr}}

.ov-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:20px}
.ov-card__head{padding:16px 22px;border-bottom:1px solid #E5E7EB;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.ov-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);
  font-size:16px;color:#351B2F;font-weight:600}
.ov-card__body{padding:22px}

.ov-rows{list-style:none;padding:0;margin:0}
.ov-rows li{padding:12px 0;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;gap:14px;font-size:13.5px}
.ov-rows li:last-child{border-bottom:0}
.ov-rows .lbl{color:#6B7280}
.ov-rows .val{color:#351B2F;font-weight:600;text-align:right}

.ov-item{display:grid;grid-template-columns:60px 1fr auto;gap:14px;
  padding:14px 22px;border-bottom:1px solid #F3EEE6;align-items:center}
.ov-item:last-child{border-bottom:0}
.ov-item__img{width:60px;height:60px;border-radius:8px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB;display:block}
.ov-item__img.ph{display:grid;place-items:center;color:#9A8B7C;font-size:22px}
.ov-item__name{font-weight:600;color:#351B2F;font-size:13.5px;line-height:1.35}
.ov-item__meta{font-size:12px;color:#6B7280;margin-top:3px}

.ov-total{background:#FAF6EF;padding:16px 22px;display:flex;justify-content:space-between;
  align-items:center;font-size:15px;font-weight:700;color:#351B2F}

.ov-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.ov-pill.green{background:#ECFDF5;color:#059669}
.ov-pill.red{background:#FEF2F2;color:#DC2626}
.ov-pill.amber{background:#FFFBEB;color:#D97706}
.ov-pill.blue{background:#EFF6FF;color:#2563EB}

.ov-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;
  font-size:12.5px;font-weight:600;border-radius:9px;border:1px solid transparent;
  cursor:pointer;font-family:inherit;text-decoration:none;transition:all .15s;white-space:nowrap}
.ov-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ov-btn-primary:hover{background:#9B4C34;color:#fff}
.ov-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.ov-btn-outline:hover{border-color:#B76146;color:#B76146}

.ov-field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.ov-field label{font-size:12px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#6B7280}
.ov-field input,.ov-field select,.ov-field textarea{
  width:100%;padding:11px 14px;border:1px solid #E5E7EB;border-radius:10px;
  font-family:inherit;font-size:14px;color:#1F2937;background:#fff;
  transition:border-color .18s,box-shadow .18s}
.ov-field input:focus,.ov-field select:focus,.ov-field textarea:focus{
  outline:none;border-color:#B76146;box-shadow:0 0 0 3px rgba(183,97,70,.12)}
</style>

<div class="ov-wrap">

  <a href="<?= url('admin/modules/orders/index.php') ?>" class="ov-back">
    <i class="bi bi-arrow-left"></i> Back to Orders
  </a>

  <!-- Alerts -->
  <?php foreach (flash_get() as $f): ?>
    <div style="padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;
      background:<?= $f['type']==='success'?'#ECFDF5':($f['type']==='error'?'#FEF2F2':'#EFF6FF') ?>;
      color:<?= $f['type']==='success'?'#065F46':($f['type']==='error'?'#991B1B':'#1E40AF') ?>;
      border:1px solid <?= $f['type']==='success'?'#A7F3D0':($f['type']==='error'?'#FECACA':'#BFDBFE') ?>">
      <?= e($f['msg']) ?>
    </div>
  <?php endforeach; ?>

  <div class="ov-grid">

    <!-- LEFT -->
    <div>

      <!-- Order summary -->
      <div class="ov-card">
        <div class="ov-card__head">
          <h3>Order #<?= e($order['order_number']) ?></h3>
          <span class="ov-pill <?= ord_status_class($order['status']) ?>"><?= e($order['status']) ?></span>
        </div>
        <div class="ov-card__body">
          <ul class="ov-rows">
            <li><span class="lbl">Placed on</span>
                <span class="val"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span></li>
            <li><span class="lbl">Payment Method</span>
                <span class="val"><?= e($order['payment_method'] ?: '—') ?></span></li>
            <li><span class="lbl">Payment Ref</span>
                <span class="val"><?= e($order['payment_ref'] ?: '—') ?></span></li>
            <li><span class="lbl">Coupon</span>
                <span class="val"><?= e($order['coupon_code'] ?: '—') ?></span></li>
          </ul>
        </div>
      </div>

      <!-- Items -->
      <div class="ov-card">
        <div class="ov-card__head"><h3>Items Ordered</h3></div>
        <?php if (empty($items)): ?>
          <div style="padding:30px;text-align:center;color:#6B7280">No items.</div>
        <?php else: ?>
          <?php foreach ($items as $i): ?>
            <?php
            $imgSrc = '';
            if (!empty($i['product_image'])) {
                $pi = trim($i['product_image']);
                if (preg_match('#^https?://#i', $pi)) {
                    $imgSrc = $pi;
                } else {
                    $pi = ltrim($pi, '/');
                    if (strpos($pi, 'assets/img/') === 0) $pi = substr($pi, strlen('assets/img/'));
                    $imgSrc = 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . $pi;
                }
            }
            ?>
            <div class="ov-item">
              <?php if ($imgSrc): ?>
                <img src="<?= e($imgSrc) ?>" alt="" class="ov-item__img" onerror="this.style.display='none'">
              <?php else: ?>
                <div class="ov-item__img ph"><i class="bi bi-image"></i></div>
              <?php endif; ?>
              <div>
                <div class="ov-item__name"><?= e($i['product_name']) ?></div>
                <div class="ov-item__meta">
                  Qty: <?= (int) $i['qty'] ?> × <?= inr($i['price']) ?>
                </div>
              </div>
              <div style="font-weight:700;color:#351B2F"><?= inr($i['line_total']) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="ov-total">
            <span>Order Total</span>
            <span><?= inr($order['total']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Shipping -->
      <div class="ov-card">
        <div class="ov-card__head"><h3>Shipping Address</h3></div>
        <div class="ov-card__body" style="font-size:13.5px;color:#4B5563;line-height:1.8">
          <b style="color:#351B2F;font-size:14.5px"><?= e($order['ship_name']) ?></b><br>
          <?php if (!empty($order['ship_phone'])): ?><?= e($order['ship_phone']) ?><br><?php endif; ?>
          <?= e($order['ship_line1']) ?><br>
          <?php if (!empty($order['ship_line2'])): ?><?= e($order['ship_line2']) ?><br><?php endif; ?>
          <?= e($order['ship_city']) ?>, <?= e($order['ship_state']) ?> — <b><?= e($order['ship_pincode']) ?></b>
        </div>
      </div>

    </div>

    <!-- RIGHT -->
    <div>

      <!-- Status update -->
      <div class="ov-card">
        <div class="ov-card__head"><h3>Update Status</h3></div>
        <div class="ov-card__body">
          <form method="post" action="<?= url('admin/modules/orders/update-status.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">

            <div class="ov-field">
              <label>Status</label>
              <select name="status" required>
                <?php foreach ($validStatuses as $s): ?>
                  <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="ov-btn ov-btn-primary" style="width:100%">
              <i class="bi bi-check2-circle"></i> Update Status
            </button>
          </form>
        </div>
      </div>

      <!-- Tracking -->
      <div class="ov-card">
        <div class="ov-card__head"><h3>Add Tracking</h3></div>
        <div class="ov-card__body">
          <form method="post" action="<?= url('admin/modules/orders/add-tracking.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">

            <div class="ov-field">
              <label>Tracking / Payment Ref</label>
              <input type="text" name="payment_ref"
                     value="<?= e($order['payment_ref'] ?: '') ?>"
                     placeholder="e.g. AWB123456789">
            </div>

            <button type="submit" class="ov-btn ov-btn-primary" style="width:100%">
              <i class="bi bi-truck"></i> Save Tracking
            </button>
          </form>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="ov-card">
        <div class="ov-card__head"><h3>Actions</h3></div>
        <div class="ov-card__body">
          <a href="<?= url('admin/modules/orders/invoice.php?id=' . (int) $order['id']) ?>"
             target="_blank" class="ov-btn ov-btn-outline" style="width:100%;justify-content:center;margin-bottom:10px">
            <i class="bi bi-file-earmark-text"></i> View Invoice
          </a>
          <a href="<?= url('admin/modules/orders/export.php?id=' . (int) $order['id']) ?>"
             class="ov-btn ov-btn-outline" style="width:100%;justify-content:center">
            <i class="bi bi-download"></i> Export This Order
          </a>
        </div>
      </div>

    </div>

  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>