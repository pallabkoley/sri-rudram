<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account-orders.php';
    redirect('login.php');
}

$userId  = current_user_id();
$orderId = (int) ($_GET['id'] ?? 0);

if ($orderId <= 0) {
    flash_set('error', 'Invalid order.');
    redirect('account-orders.php');
}

/* ==================================================================
 |  Fetch the order belonging to this user
 * ================================================================== */
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('account-orders.php');
}

/* ==================================================================
 |  Fetch order items WITH product image & slug
 * ================================================================== */
$it = $pdo->prepare(
    'SELECT oi.*,
            p.slug       AS product_slug,
            p.image_url  AS product_image
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC'
);
$it->execute([$orderId]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

/* ==================================================================
 |  Product image helper — handles local + external + fallback
 * ================================================================== */
if (!function_exists('ord_img_url')) {
    function ord_img_url(?string $stored): string {
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

if (!function_exists('ord_img')) {
    function ord_img(?string $stored, string $alt = ''): string {
        $src     = ord_img_url($stored);
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES);

        if ($src === '') {
            return '<div class="ac-ord-ph" role="img" aria-label="' . $safeAlt . '">'
                 .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
                 .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
                 .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
                 .     '<polyline points="21 15 16 10 5 21"/>'
                 .   '</svg></div>';
        }

        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . $safeAlt . '" loading="lazy" '
             . 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'grid\';">'
             . '<div class="ac-ord-ph" style="display:none" role="img" aria-label="' . $safeAlt . '">'
             .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
             .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
             .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
             .     '<polyline points="21 15 16 10 5 21"/>'
             .   '</svg></div>';
    }
}

$pageTitle = 'Order #' . $order['order_number'] . ' — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('account.php') ?>">My Account</a>
      <span class="sep">›</span>
      <a href="<?= url('account-orders.php') ?>">Orders</a>
      <span class="sep">›</span>
      <span class="current">#<?= e($order['order_number']) ?></span>
    </nav>
    <span class="eyebrow">Order Details</span>
    <h1>Order #<?= e($order['order_number']) ?></h1>
    <p>Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
  </div>
</section>

<style>
.ac-shell{max-width:1200px;margin:0 auto;padding:36px 22px 80px;background:#FAF6EF}
.ac-layout{display:grid;grid-template-columns:240px 1fr;gap:26px}
@media(max-width:900px){.ac-layout{grid-template-columns:1fr;gap:18px}}
.ac-nav{background:#fff;border:1px solid #EFE6D9;border-radius:16px;padding:14px;height:fit-content;
  position:sticky;top:90px;box-shadow:0 1px 3px rgba(53,27,47,.05)}
@media(max-width:900px){.ac-nav{position:static;display:flex;overflow-x:auto;gap:8px;padding:10px}}
.ac-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;
  font-size:13.5px;font-weight:600;color:#4B5563;text-decoration:none;transition:all .15s;margin-bottom:4px}
@media(max-width:900px){.ac-nav a{flex-shrink:0;margin-bottom:0;white-space:nowrap}}
.ac-nav a:hover{background:#FAF6EF;color:#B76146}
.ac-nav a.active{background:#351B2F;color:#fff}
.ac-nav a i{font-size:16px;width:18px;text-align:center}
.ac-nav a.logout{color:#DC2626;margin-top:8px;border-top:1px solid #F3EEE6;padding-top:16px}
.ac-nav a.logout:hover{background:#FEF2F2;color:#DC2626}
.ac-content{min-width:0}
.ac-h1{font-family:var(--font-serif,Georgia,serif);font-size:26px;color:#351B2F;margin:0 0 6px;font-weight:600}
.ac-sub{color:#6B7280;font-size:13.5px;margin:0 0 24px;line-height:1.6}
.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__body{padding:22px}

/* ---- ORDER ITEM ROW (with image) ---- */
.ac-list{list-style:none;padding:0;margin:0}
.ac-list li{
  padding:16px 22px;border-bottom:1px solid #F3EEE6;
  display:grid;grid-template-columns:72px 1fr auto;
  gap:16px;align-items:center;
}
.ac-list li:last-child{border-bottom:0}
@media(max-width:520px){
  .ac-list li{
    grid-template-columns:64px 1fr;
    grid-template-areas:
      "media info"
      "media price";
    gap:8px 12px;
    padding:14px 18px;
  }
}

/* Product image */
.ac-ord-media{
  position:relative;width:72px;height:72px;border-radius:12px;
  overflow:hidden;background:#F5F1EC;border:1px solid #EFE6D9;
  flex:0 0 auto;
}
@media(max-width:520px){.ac-ord-media{grid-area:media;width:64px;height:64px}}
.ac-ord-media img{
  width:100%;height:100%;object-fit:cover;display:block;
  position:absolute;inset:0;
  transition:transform .35s ease;
}
.ac-list li:hover .ac-ord-media img{transform:scale(1.05)}

.ac-ord-ph{
  position:absolute;inset:0;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;
}
.ac-ord-ph svg{
  width:36%;height:36%;max-width:32px;max-height:32px;
  opacity:.55;
}

.ac-ord-info{min-width:0}
@media(max-width:520px){.ac-ord-info{grid-area:info}}

.ac-list__title{font-weight:600;color:#351B2F;font-size:14px;line-height:1.4;margin:0 0 5px}
.ac-list__title a{color:inherit;text-decoration:none}
.ac-list__title a:hover{color:#B76146}
.ac-list__meta{font-size:12px;color:#6B7280;margin-top:2px}
.ac-list__meta b{color:#351B2F;font-weight:600}

.ac-ord-price{
  font-family:var(--font-serif,Georgia,serif);
  font-size:16px;font-weight:700;color:#351B2F;
  text-align:right;white-space:nowrap;
  align-self:center;
}
@media(max-width:520px){
  .ac-ord-price{grid-area:price;text-align:left;font-size:15px;align-self:auto}
}

.ac-pill{display:inline-flex;padding:4px 11px;font-size:10.5px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.ac-pill.green{background:#ECFDF5;color:#059669}
.ac-pill.amber{background:#FFFBEB;color:#D97706}
.ac-pill.red{background:#FEF2F2;color:#DC2626}
.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:9px 18px;font-size:12.5px;font-weight:600;border-radius:9px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.ac-btn-outline:hover{border-color:#B76146;color:#B76146}
.ac-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
@media(max-width:700px){.ac-row{grid-template-columns:1fr}}
</style>

<div class="ac-shell">
  <div class="ac-layout">
    <aside class="ac-nav">
      <a href="<?= url('account.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>" class="active"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <div class="ac-content">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:20px">
        <div>
          <h1 class="ac-h1">Order #<?= e($order['order_number']) ?></h1>
          <p class="ac-sub" style="margin-bottom:0">
            Status:
            <span class="ac-pill <?= $order['status'] === 'delivered' ? 'green' : ($order['status'] === 'cancelled' ? 'red' : 'amber') ?>">
              <?= e($order['status']) ?>
            </span>
          </p>
        </div>
        <a href="<?= url('account-orders.php') ?>" class="ac-btn ac-btn-outline">← Back to Orders</a>
      </div>

      <!-- ═══════════ ITEMS ORDERED (with images) ═══════════ -->
      <div class="ac-card">
        <div class="ac-card__head"><h3>Items Ordered</h3></div>
        <?php if (empty($items)): ?>
          <div style="padding:40px;text-align:center;color:#6B7280">No items found.</div>
        <?php else: ?>
          <ul class="ac-list">
            <?php foreach ($items as $i):
              $imgSrc = $i['product_image'] ?? '';
              $slug   = $i['product_slug'] ?? '';
            ?>
              <li>
                <div class="ac-ord-media">
                  <?php if ($slug): ?>
                    <a href="<?= url('product.php?slug=' . urlencode($slug)) ?>">
                      <?= ord_img($imgSrc, $i['product_name']) ?>
                    </a>
                  <?php else: ?>
                    <?= ord_img($imgSrc, $i['product_name']) ?>
                  <?php endif; ?>
                </div>

                <div class="ac-ord-info">
                  <div class="ac-list__title">
                    <?php if ($slug): ?>
                      <a href="<?= url('product.php?slug=' . urlencode($slug)) ?>">
                        <?= e($i['product_name']) ?>
                      </a>
                    <?php else: ?>
                      <?= e($i['product_name']) ?>
                    <?php endif; ?>
                  </div>
                  <div class="ac-list__meta">
                    Qty: <b><?= (int) $i['qty'] ?></b> × <?= inr($i['price']) ?>
                  </div>
                </div>

                <div class="ac-ord-price"><?= inr($i['line_total']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="ac-row">
        <div class="ac-card" style="margin-bottom:0">
          <div class="ac-card__head"><h3>Order Summary</h3></div>
          <div class="ac-card__body">
            <table style="width:100%;font-size:13.5px;color:#4B5563">
              <tr><td style="padding:6px 0">Subtotal</td><td style="text-align:right"><?= inr($order['subtotal']) ?></td></tr>
              <tr><td style="padding:6px 0">Shipping</td><td style="text-align:right"><?= inr($order['shipping']) ?></td></tr>
              <?php if ((float) $order['discount'] > 0): ?>
                <tr><td style="padding:6px 0;color:#059669">Discount</td><td style="text-align:right;color:#059669">− <?= inr($order['discount']) ?></td></tr>
              <?php endif; ?>
              <tr style="border-top:1px solid #F3EEE6">
                <td style="padding:12px 0 0;font-weight:700;color:#351B2F;font-size:15px">Total</td>
                <td style="padding:12px 0 0;text-align:right;font-weight:700;color:#351B2F;font-size:15px"><?= inr($order['total']) ?></td>
              </tr>
            </table>
            <?php if (!empty($order['coupon_code'])): ?>
              <div style="margin-top:10px;font-size:12.5px;color:#6B7280">
                Coupon: <b><?= e($order['coupon_code']) ?></b>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="ac-card" style="margin-bottom:0">
          <div class="ac-card__head"><h3>Shipping Address</h3></div>
          <div class="ac-card__body" style="font-size:13.5px;color:#4B5563;line-height:1.7">
            <b style="color:#351B2F"><?= e($order['ship_name']) ?></b><br>
            <?= e($order['ship_phone']) ?><br>
            <?= e($order['ship_line1']) ?><br>
            <?php if (!empty($order['ship_line2'])): ?><?= e($order['ship_line2']) ?><br><?php endif; ?>
            <?= e($order['ship_city']) ?>, <?= e($order['ship_state']) ?> — <?= e($order['ship_pincode']) ?>
          </div>
        </div>
      </div>

      <?php if (!empty($order['payment_method'])): ?>
        <div class="ac-card" style="margin-top:20px">
          <div class="ac-card__head"><h3>Payment</h3></div>
          <div class="ac-card__body" style="font-size:13.5px;color:#4B5563">
            <b style="color:#351B2F">Method:</b> <?= e(strtoupper($order['payment_method'])) ?>
            <?php if (!empty($order['payment_ref'])): ?>
              <br><b style="color:#351B2F">Ref:</b> <?= e($order['payment_ref']) ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>