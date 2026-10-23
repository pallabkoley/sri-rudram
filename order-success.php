<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  Helpers
 * ================================================================== */
if (!function_exists('success_img_url')) {
    function success_img_url(?string $stored): string {
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

if (!function_exists('success_img')) {
    function success_img(?string $stored, string $alt = ''): string {
        $src     = success_img_url($stored);
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES);
        if ($src === '') {
            return '<div class="os-img-ph" role="img" aria-label="' . $safeAlt . '">'
                 .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
                 .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
                 .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
                 .     '<polyline points="21 15 16 10 5 21"/>'
                 .   '</svg></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . $safeAlt . '" loading="lazy" '
             . 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'grid\';">'
             . '<div class="os-img-ph" style="display:none" role="img" aria-label="' . $safeAlt . '">'
             .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
             .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
             .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
             .     '<polyline points="21 15 16 10 5 21"/>'
             .   '</svg></div>';
    }
}

/* ==================================================================
 |  Fetch order
 * ================================================================== */
$orderRef = trim((string) ($_GET['order'] ?? ''));
if ($orderRef === '') {
    flash_set('error', 'Order reference not specified.');
    redirect('index.php');
}

try {
    $stmt = $pdo->prepare(
        "SELECT o.*,
                u.name  AS user_name,
                u.email AS user_email
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.order_number = ?
         LIMIT 1"
    );
    $stmt->execute([$orderRef]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[order-success] ' . $e->getMessage());
    $order = null;
}

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('index.php');
}

$orderId = (int) $order['id'];

/* ==================================================================
 |  Fetch order items
 * ================================================================== */
$orderItems = [];
try {
    $stmt = $pdo->prepare(
        "SELECT oi.*, p.slug AS product_slug, p.image_url AS product_image
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC"
    );
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $orderItems = [];
}

/* ==================================================================
 |  Estimate delivery date (3-5 business days)
 * ================================================================== */
$orderDate = !empty($order['created_at']) ? strtotime($order['created_at']) : time();
$deliveryFrom = date('d M', strtotime('+3 weekdays', $orderDate));
$deliveryTo   = date('d M Y', strtotime('+5 weekdays', $orderDate));

/* ==================================================================
 |  Status label
 * ================================================================== */
$statusMap = [
    'pending'   => ['label' => 'Order Received',   'class' => 'pending'],
    'paid'      => ['label' => 'Payment Confirmed','class' => 'paid'],
    'packed'    => ['label' => 'Packed',           'class' => 'packed'],
    'shipped'   => ['label' => 'Shipped',          'class' => 'shipped'],
    'delivered' => ['label' => 'Delivered',        'class' => 'delivered'],
    'cancelled' => ['label' => 'Cancelled',        'class' => 'cancelled'],
];
$statusInfo = $statusMap[$order['status']] ?? ['label' => 'Processing', 'class' => 'pending'];

/* ==================================================================
 |  Payment method label
 * ================================================================== */
$pmLabels = [
    'cod'  => ['label' => 'Cash on Delivery', 'desc' => 'Pay when your order arrives'],
    'upi'  => ['label' => 'UPI',              'desc' => 'Google Pay · PhonePe · Paytm'],
    'card' => ['label' => 'Card Payment',     'desc' => 'Visa · Mastercard · RuPay'],
];
$pmInfo = $pmLabels[$order['payment_method'] ?? 'cod'] ?? ['label' => 'Cash on Delivery', 'desc' => ''];

$pageTitle = 'Order Confirmed — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   ORDER SUCCESS PAGE — scoped styles (os-* prefix)
   ============================================================ */
.os-page{background:#FAF6EF;padding:0 0 100px;min-height:70vh}
.os-wrap{max-width:1000px;margin:0 auto;padding:0 22px}

.os-img-ph{
  width:100%;height:100%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;
}
.os-img-ph svg{width:36%;height:36%;max-width:36px;max-height:36px;opacity:.55}

/* ---------- Hero ---------- */
.os-hero{
  background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
  padding:56px 0 60px;position:relative;overflow:hidden;text-align:center;
}
.os-hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(circle at 15% 25%,rgba(183,97,70,.30) 0%,transparent 55%),
    radial-gradient(circle at 85% 75%,rgba(212,175,55,.20) 0%,transparent 55%);
}
.os-hero::after{
  content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent);
}
.os-hero .os-wrap{position:relative;z-index:2}

.os-check{
  width:88px;height:88px;margin:0 auto 22px;
  border-radius:50%;display:grid;place-items:center;
  background:linear-gradient(135deg,#DCE5CD,#C8D4B5);
  color:#3A4A28;
  box-shadow:0 0 0 10px rgba(200,212,181,.20), 0 14px 32px -12px rgba(58,74,40,.5);
  animation:osPop .6s cubic-bezier(.34,1.56,.64,1) both;
}
.os-check svg{
  width:44px;height:44px;
  stroke:currentColor;fill:none;
  stroke-width:2.6;stroke-linecap:round;stroke-linejoin:round;
}
.os-check svg polyline{
  stroke-dasharray:40;
  stroke-dashoffset:40;
  animation:osDraw .8s ease-out .3s forwards;
}
@keyframes osPop{
  0%{opacity:0;transform:scale(.4)}
  100%{opacity:1;transform:scale(1)}
}
@keyframes osDraw{
  to{stroke-dashoffset:0}
}

.os-hero h1{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:38px;line-height:1.15;color:#F7EBDD;margin:0 0 12px;font-weight:600;
}
.os-hero p{
  color:rgba(247,235,221,.82);
  font-size:15px;line-height:1.7;
  margin:0 auto;max-width:540px;
}
.os-hero p strong{color:#D4AF37;font-weight:600}

.os-ref{
  display:inline-flex;align-items:center;gap:8px;
  margin-top:22px;padding:9px 18px;border-radius:99px;
  background:rgba(212,175,55,.14);
  border:1px solid rgba(212,175,55,.42);
  color:#F5D76E;
  font-size:12.5px;font-weight:700;letter-spacing:1.2px;
  text-transform:uppercase;
}
.os-ref .lbl{color:rgba(247,235,221,.6);font-weight:600;letter-spacing:.8px}
.os-ref .val{color:#F5D76E;font-family:'Inter',sans-serif;letter-spacing:.6px}

@media(max-width:640px){
  .os-hero{padding:40px 0 44px}
  .os-hero h1{font-size:24px}
  .os-hero p{font-size:13.5px}
  .os-check{width:72px;height:72px;margin-bottom:18px}
  .os-check svg{width:36px;height:36px}
}

/* ---------- Cards ---------- */
.os-card{
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  overflow:hidden;box-shadow:0 1px 3px rgba(53,27,47,.04);
  margin-top:22px;
  animation:osFade .5s cubic-bezier(.22,.68,.32,1) both;
}
.os-card:nth-child(2){animation-delay:.06s}
.os-card:nth-child(3){animation-delay:.12s}
.os-card:nth-child(4){animation-delay:.18s}
@keyframes osFade{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}

.os-card__head{
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  padding:18px 24px;border-bottom:1px solid #F3EEE6;
  background:linear-gradient(180deg,#FCF8F2,#FBF5EC);
}
.os-card__head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;color:#351B2F;margin:0;font-weight:600;
}
.os-card__body{padding:22px 24px}

/* Status chip */
.os-status{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:99px;
  font-size:11px;font-weight:700;letter-spacing:.6px;
  text-transform:uppercase;
}
.os-status--pending  {background:#FFF7E0;color:#8A6A08;border:1px solid rgba(212,175,55,.4)}
.os-status--paid     {background:#E3F0E0;color:#3A5A2E;border:1px solid rgba(124,128,100,.35)}
.os-status--packed   {background:#E5EEF7;color:#2E4A6B;border:1px solid rgba(100,140,180,.35)}
.os-status--shipped  {background:#FCE8E0;color:#8B3A22;border:1px solid rgba(183,97,70,.32)}
.os-status--delivered{background:#E3F0E0;color:#3A5A2E;border:1px solid rgba(124,128,100,.4)}
.os-status--cancelled{background:#F3F3F3;color:#6B7280;border:1px solid #E5E5E5}

/* Delivery estimate */
.os-delivery{
  display:grid;grid-template-columns:auto 1fr;gap:18px;align-items:center;
  padding:20px 24px;
  background:linear-gradient(135deg,#FCF8F2,#FAF3E6);
  border-radius:12px;border:1px dashed rgba(212,175,55,.45);
}
.os-delivery__ico{
  width:56px;height:56px;border-radius:14px;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#C9704F,#B76146);color:#fff;
  box-shadow:0 10px 24px -10px rgba(183,97,70,.8);
}
.os-delivery__ico svg{
  width:26px;height:26px;
  stroke:currentColor;fill:none;
  stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;
}
.os-delivery__lbl{
  font-size:11px;font-weight:700;letter-spacing:1.2px;
  text-transform:uppercase;color:#B76146;margin-bottom:4px;
}
.os-delivery__val{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;font-weight:600;color:#351B2F;line-height:1.3;
}
.os-delivery__val b{color:#B76146}
.os-delivery__sub{font-size:12.5px;color:#6B7280;margin-top:4px}

@media(max-width:520px){
  .os-delivery{grid-template-columns:1fr;text-align:center;gap:12px}
  .os-delivery__ico{margin:0 auto}
}

/* Order items */
.os-item{
  display:grid;grid-template-columns:72px 1fr auto;
  gap:16px;align-items:center;
  padding:16px 0;
}
.os-item + .os-item{border-top:1px dashed #F3EEE6}
.os-item__media{
  position:relative;width:72px;height:72px;border-radius:12px;
  overflow:hidden;background:#F5F1EC;border:1px solid #EFE6D9;
}
.os-item__media img{width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0}
.os-item__media .os-img-ph{position:absolute;inset:0}
.os-item__info{min-width:0}
.os-item__name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:15px;line-height:1.35;color:#351B2F;
  font-weight:600;margin:0 0 6px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.os-item__name a{color:inherit;text-decoration:none}
.os-item__name a:hover{color:#B76146}
.os-item__meta{font-size:12px;color:#6B7280}
.os-item__meta b{color:#351B2F;font-weight:600}
.os-item__price{
  font-family:var(--font-serif,Georgia,serif);
  font-size:16px;font-weight:700;color:#351B2F;
  text-align:right;white-space:nowrap;
}
@media(max-width:520px){
  .os-item{grid-template-columns:64px 1fr;grid-template-areas:
    "media info"
    "media price";gap:8px 12px}
  .os-item__media{grid-area:media;width:64px;height:64px}
  .os-item__info{grid-area:info}
  .os-item__price{grid-area:price;text-align:left;font-size:15px}
  .os-item__name{font-size:14px}
}

/* Totals */
.os-totals{margin-top:16px;padding-top:18px;border-top:1px solid #F3EEE6}
.os-row{
  display:flex;justify-content:space-between;align-items:center;
  font-size:13.5px;color:#6B7280;padding:7px 0;
}
.os-row span:last-child{color:#351B2F;font-weight:600}
.os-row--free span:last-child{color:#4F5340;font-weight:700}
.os-row--discount span:last-child{color:#4F5340}
.os-total{
  display:flex;justify-content:space-between;align-items:baseline;
  padding:14px 0 0;margin-top:8px;border-top:1px dashed #E5DDD2;
}
.os-total span:first-child{
  font-size:14px;color:#6B7280;letter-spacing:.3px;
  text-transform:uppercase;font-weight:600;
}
.os-total span:last-child{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:26px;font-weight:700;color:#351B2F;
}

/* Info grid */
.os-info{
  display:grid;grid-template-columns:1fr 1fr;gap:14px;
}
@media(max-width:640px){.os-info{grid-template-columns:1fr}}

.os-info__item{
  padding:16px 18px;border:1px solid #EFE6D9;border-radius:12px;
  background:#FCF8F2;
}
.os-info__lbl{
  font-size:10.5px;font-weight:700;letter-spacing:1.3px;
  text-transform:uppercase;color:#B76146;margin-bottom:8px;
}
.os-info__val{
  font-size:14px;color:#351B2F;line-height:1.55;font-weight:500;
}
.os-info__val b{font-weight:700}
.os-info__val .sub{display:block;color:#6B7280;font-size:13px;margin-top:2px;font-weight:400}

/* Next steps */
.os-steps{
  display:grid;grid-template-columns:repeat(3,1fr);gap:16px;
}
@media(max-width:768px){.os-steps{grid-template-columns:1fr;gap:12px}}

.os-step{
  padding:20px 18px;border:1px solid #EFE6D9;border-radius:12px;
  background:#fff;text-align:center;position:relative;
}
.os-step__ico{
  width:48px;height:48px;border-radius:12px;margin:0 auto 12px;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;
  font-size:22px;
}
.os-step__ico svg{
  width:22px;height:22px;
  stroke:currentColor;fill:none;
  stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;
}
.os-step h4{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:15.5px;color:#351B2F;margin:0 0 6px;font-weight:600;
}
.os-step p{font-size:12.5px;color:#6B7280;line-height:1.6;margin:0}

/* Actions */
.os-actions{
  display:flex;flex-wrap:wrap;gap:12px;justify-content:center;
  margin-top:32px;
}
.os-btn{
  display:inline-flex;align-items:center;gap:9px;
  padding:14px 28px;font-size:14px;font-weight:600;
  border-radius:12px;text-decoration:none;
  border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .2s cubic-bezier(.22,.68,.32,1);
  white-space:nowrap;
}
.os-btn svg{
  width:16px;height:16px;flex:0 0 auto;
  stroke:currentColor;fill:none;
  stroke-width:2;stroke-linecap:round;stroke-linejoin:round;
}
.os-btn--primary{
  background:linear-gradient(135deg,#C9704F,#B76146);
  color:#fff;border-color:#B76146;
  box-shadow:0 10px 24px -12px rgba(183,97,70,.85);
}
.os-btn--primary:hover{
  background:linear-gradient(135deg,#B76146,#9B4C34);
  color:#fff;transform:translateY(-2px);
  box-shadow:0 16px 32px -14px rgba(183,97,70,.95);
}
.os-btn--plum{
  background:linear-gradient(135deg,#4A2440,#351B2F);
  color:#F7EBDD;border-color:#351B2F;
}
.os-btn--plum:hover{
  background:linear-gradient(135deg,#351B2F,#24141F);
  color:#fff;transform:translateY(-2px);
}
.os-btn--outline{
  background:#fff;color:#351B2F;border-color:#E5DDD2;
}
.os-btn--outline:hover{
  border-color:#B76146;color:#B76146;background:#FFF7E0;
}
@media(max-width:640px){
  .os-actions{flex-direction:column;align-items:stretch}
  .os-btn{width:100%;justify-content:center}
}

/* Support note */
.os-support{
  margin-top:28px;padding:18px 22px;
  background:linear-gradient(135deg,#FCF8F2,#FAF3E6);
  border:1px solid #EFE6D9;border-radius:12px;
  display:flex;gap:14px;align-items:flex-start;
  font-size:13px;color:#351B2F;line-height:1.6;
}
.os-support svg{
  width:22px;height:22px;flex:0 0 auto;margin-top:1px;
  stroke:#B76146;fill:none;stroke-width:1.8;
  stroke-linecap:round;stroke-linejoin:round;
}
.os-support b{display:block;margin-bottom:3px}
.os-support a{color:#B76146;font-weight:600;text-decoration:none}
.os-support a:hover{color:#9B4C34;text-decoration:underline}
</style>

<div class="os-page">

  <!-- ═══════════════ HERO ═══════════════ -->
  <section class="os-hero">
    <div class="os-wrap">
      <div class="os-check" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h1>Order Placed Successfully</h1>
      <p>
        Thank you, <strong><?= e($order['ship_name'] ?: 'Devotee') ?></strong>!
        Your sacred order has been received and is being prepared with devotion.
      </p>
      <div class="os-ref">
        <span class="lbl">Order Ref:</span>
        <span class="val"><?= e($order['order_number']) ?></span>
      </div>
    </div>
  </section>

  <div class="os-wrap">

    <!-- ═══════════════ DELIVERY ESTIMATE ═══════════════ -->
    <div class="os-card">
      <div class="os-card__body">
        <div class="os-delivery">
          <div class="os-delivery__ico">
            <svg viewBox="0 0 24 24">
              <rect x="1" y="3" width="15" height="13" rx="1"/>
              <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
              <circle cx="5.5" cy="18.5" r="2.5"/>
              <circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
          </div>
          <div>
            <div class="os-delivery__lbl">Estimated Delivery</div>
            <div class="os-delivery__val">
              <b><?= e($deliveryFrom) ?></b> – <?= e($deliveryTo) ?>
            </div>
            <div class="os-delivery__sub">
              Dispatched within 24 hours · Delivered with tracking
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════ ORDER ITEMS ═══════════════ -->
    <div class="os-card">
      <div class="os-card__head">
        <h2>Your Order Items</h2>
        <span class="os-status os-status--<?= e($statusInfo['class']) ?>">
          <?= e($statusInfo['label']) ?>
        </span>
      </div>
      <div class="os-card__body">

        <?php foreach ($orderItems as $it):
          $productSlug = $it['product_slug'] ?? '';
        ?>
          <div class="os-item">
            <div class="os-item__media">
              <?= success_img($it['product_image'] ?? '', $it['product_name']) ?>
            </div>
            <div class="os-item__info">
              <h3 class="os-item__name">
                <?php if ($productSlug): ?>
                  <a href="<?= url('product.php?slug=' . urlencode($productSlug)) ?>">
                    <?= e($it['product_name']) ?>
                  </a>
                <?php else: ?>
                  <?= e($it['product_name']) ?>
                <?php endif; ?>
              </h3>
              <span class="os-item__meta">
                Qty <b><?= (int) $it['qty'] ?></b> × <?= inr($it['price']) ?>
              </span>
            </div>
            <span class="os-item__price"><?= inr($it['line_total']) ?></span>
          </div>
        <?php endforeach; ?>

        <!-- Totals -->
        <div class="os-totals">
          <div class="os-row">
            <span>Subtotal</span>
            <span><?= inr($order['subtotal']) ?></span>
          </div>

          <div class="os-row <?= (float) $order['shipping'] == 0 ? 'os-row--free' : '' ?>">
            <span>Shipping</span>
            <span>
              <?php if ((float) $order['shipping'] == 0): ?>
                Free
              <?php else: ?>
                <?= inr($order['shipping']) ?>
              <?php endif; ?>
            </span>
          </div>

          <?php if ((float) $order['discount'] > 0): ?>
            <div class="os-row os-row--discount">
              <span>Discount<?= !empty($order['coupon_code']) ? ' (' . e($order['coupon_code']) . ')' : '' ?></span>
              <span>− <?= inr($order['discount']) ?></span>
            </div>
          <?php endif; ?>

          <div class="os-total">
            <span>Total Paid</span>
            <span><?= inr($order['total']) ?></span>
          </div>
        </div>

      </div>
    </div>

    <!-- ═══════════════ SHIPPING + PAYMENT ═══════════════ -->
    <div class="os-card">
      <div class="os-card__head">
        <h2>Shipping &amp; Payment</h2>
      </div>
      <div class="os-card__body">
        <div class="os-info">

          <div class="os-info__item">
            <div class="os-info__lbl">Ship To</div>
            <div class="os-info__val">
              <b><?= e($order['ship_name']) ?></b>
              <span class="sub">
                <?= e($order['ship_line1']) ?>
                <?php if (!empty($order['ship_line2'])): ?><br><?= e($order['ship_line2']) ?><?php endif; ?>
                <br><?= e($order['ship_city']) ?>, <?= e($order['ship_state']) ?> – <?= e($order['ship_pincode']) ?>
                <br>📞 <?= e($order['ship_phone']) ?>
              </span>
            </div>
          </div>

          <div class="os-info__item">
            <div class="os-info__lbl">Payment Method</div>
            <div class="os-info__val">
              <b><?= e($pmInfo['label']) ?></b>
              <?php if (!empty($pmInfo['desc'])): ?>
                <span class="sub"><?= e($pmInfo['desc']) ?></span>
              <?php endif; ?>
              <?php if (($order['payment_method'] ?? '') === 'cod'): ?>
                <span class="sub" style="color:#8A6A08;font-weight:600">
                  Please keep <?= inr($order['total']) ?> ready at delivery.
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="os-info__item">
            <div class="os-info__lbl">Order Date</div>
            <div class="os-info__val">
              <b><?= date('d M Y', $orderDate) ?></b>
              <span class="sub"><?= date('h:i A', $orderDate) ?></span>
            </div>
          </div>

          <div class="os-info__item">
            <div class="os-info__lbl">Order Reference</div>
            <div class="os-info__val">
              <b style="font-family:monospace;letter-spacing:.5px"><?= e($order['order_number']) ?></b>
              <span class="sub">Save this for tracking</span>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ═══════════════ WHAT HAPPENS NEXT ═══════════════ -->
    <div class="os-card">
      <div class="os-card__head">
        <h2>What Happens Next</h2>
      </div>
      <div class="os-card__body">
        <div class="os-steps">
          <div class="os-step">
            <div class="os-step__ico">
              <svg viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <h4>Order Confirmed</h4>
            <p>We've received your order and notified our team.</p>
          </div>

          <div class="os-step">
            <div class="os-step__ico">
              <svg viewBox="0 0 24 24">
                <rect x="1" y="3" width="15" height="13" rx="1"/>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                <circle cx="5.5" cy="18.5" r="2.5"/>
                <circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
            </div>
            <h4>Packed &amp; Shipped</h4>
            <p>Sacred items are energized and dispatched within 24 hours.</p>
          </div>

          <div class="os-step">
            <div class="os-step__ico">
              <svg viewBox="0 0 24 24">
                <path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7a4 4 0 1 0 0-5 4 4 0 0 0 0 5z"/>
              </svg>
            </div>
            <h4>Delivered Home</h4>
            <p>Receive blessings at your doorstep with love and care.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════ ACTIONS ═══════════════ -->
    <div class="os-actions">
      <a href="<?= url('shop.php') ?>" class="os-btn os-btn--primary">
        <svg viewBox="0 0 24 24">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <path d="M3 6h18"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        Continue Shopping
      </a>

      <?php if (is_logged_in()): ?>
        <a href="<?= url('account-orders.php') ?>" class="os-btn os-btn--plum">
          <svg viewBox="0 0 24 24">
            <path d="M9 11H3v10h6V11zM21 3h-6v18h6V3zM15 7H9v4h6V7z"/>
          </svg>
          View My Orders
        </a>
      <?php endif; ?>

      <a href="<?= url('index.php') ?>" class="os-btn os-btn--outline">
        <svg viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Back to Home
      </a>
    </div>

    <!-- ═══════════════ SUPPORT NOTE ═══════════════ -->
    <div class="os-support">
      <svg viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/>
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
      <div>
        <b>Need help with your order?</b>
        Reach out to our seva team at
        <a href="mailto:<?= e(setting('contact_email', 'seva@divyaaastha.in')) ?>">
          <?= e(setting('contact_email', 'seva@divyaaastha.in')) ?>
        </a>
        or call
        <a href="tel:<?= e(setting('contact_phone', '+919000000000')) ?>">
          <?= e(setting('contact_phone', '+91 90000 00000')) ?>
        </a>.
        Please quote your order reference <b><?= e($order['order_number']) ?></b>.
      </div>
    </div>

  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>