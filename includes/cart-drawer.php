<?php
/* ==================================================================
 |  CART DRAWER — self-contained, always shows fresh prices
 |  Rendered on every page by includes/header.php
 * ================================================================== */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

/* ---------- Cart ID (session or logged-in user) ---------- */
$drawerCartId = function_exists('cart_current_id') ? cart_current_id() : 0;

/* ---------- Fetch cart items with FRESH product price ---------- */
$drawerItems    = [];
$drawerSubtotal = 0.0;
$drawerCount    = 0;

if ($drawerCartId) {
    try {
        $sql = "SELECT ci.id            AS cart_item_id,
                       ci.product_id,
                       ci.qty,
                       ci.price_at_add,
                       p.name           AS product_name,
                       p.slug           AS product_slug,
                       p.price          AS current_price,
                       p.mrp,
                       p.image_url,
                       p.stock,
                       p.is_active
                FROM cart_items ci
                INNER JOIN products p ON p.id = ci.product_id
                WHERE ci.cart_id = ?
                  AND p.is_active = 1
                ORDER BY ci.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$drawerCartId]);
        $drawerItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($drawerItems as $row) {
            /* Prefer current price, fall back to price_at_add */
            $price = (float) ($row['current_price'] ?? 0);
            if ($price <= 0) {
                $price = (float) ($row['price_at_add'] ?? 0);
            }
            $qty = max(1, (int) $row['qty']);

            $drawerSubtotal += $price * $qty;
            $drawerCount    += $qty;
        }
    } catch (Throwable $e) {
        error_log('[cart-drawer] ' . $e->getMessage());
        $drawerItems = [];
    }
}

/* ---------- Image helper (self-contained) ---------- */
if (!function_exists('cdrawer_img_url')) {
    function cdrawer_img_url(?string $stored): string {
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

/* ---------- Free shipping info ---------- */
$freeShipMin = function_exists('setting') ? (float) setting('free_shipping_min', 999) : 999;
$freeShipTxt = $drawerSubtotal >= $freeShipMin ? 'Complimentary' : inr(79);
?>

<style>
/* ============================================================
   CART DRAWER — scoped (cd-*)
   ============================================================ */
.cart-drawer{
  position:fixed;top:0;right:0;bottom:0;
  width:min(420px,92vw);z-index:800;
  background:#FCF8F2;
  display:flex;flex-direction:column;
  transform:translateX(102%);
  transition:transform .42s cubic-bezier(.22,.68,.32,1);
  box-shadow:-30px 0 70px -30px rgba(36,20,31,.5);
}
.cart-drawer.open{transform:translateX(0)}

.cd-head{
  padding:20px 22px;
  border-bottom:1px solid #EFE6D9;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
  color:#F7EBDD;
}
.cd-head h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;margin:0;font-weight:600;
  display:flex;align-items:center;gap:10px;color:#F7EBDD;
}
.cd-head h3 .cd-count{
  font-family:'Inter',sans-serif;
  font-size:11px;font-weight:700;
  background:rgba(212,175,55,.18);
  border:1px solid rgba(212,175,55,.4);
  color:#F5D76E;
  padding:4px 10px;border-radius:99px;
  letter-spacing:.3px;
}
.cd-close{
  width:36px;height:36px;border-radius:10px;
  border:1px solid rgba(212,175,55,.35);
  background:rgba(255,255,255,.06);
  color:#F7EBDD;
  display:grid;place-items:center;
  cursor:pointer;transition:all .2s ease;
}
.cd-close:hover{
  background:rgba(212,175,55,.2);
  border-color:rgba(212,175,55,.6);
  color:#fff;
}
.cd-close svg{
  width:16px;height:16px;
  stroke:currentColor;fill:none;
  stroke-width:2;stroke-linecap:round;
}

.cd-body{
  flex:1;overflow-y:auto;padding:16px 18px;
}
.cd-body::-webkit-scrollbar{width:6px}
.cd-body::-webkit-scrollbar-thumb{background:#E5DDD2;border-radius:99px}

/* ---------- Item ---------- */
.cd-item{
  display:grid;
  grid-template-columns:64px 1fr;
  gap:14px;
  padding:14px;
  background:#fff;
  border:1px solid #EFE6D9;
  border-radius:12px;
  margin-bottom:12px;
  transition:all .2s ease;
}
.cd-item:hover{border-color:rgba(183,97,70,.32);box-shadow:0 6px 16px -10px rgba(53,27,47,.18)}

.cd-item__media{
  position:relative;
  width:64px;height:64px;
  border-radius:10px;overflow:hidden;
  background:#F5F1EC;
  border:1px solid #EFE6D9;
  flex:0 0 auto;
}
.cd-item__media a{display:block;width:100%;height:100%;position:relative}
.cd-item__media img{
  position:absolute;inset:0;
  width:100%;height:100%;
  object-fit:cover;display:block;
}
.cd-item__ph{
  position:absolute;inset:0;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;
}
.cd-item__ph svg{
  width:32%;height:32%;max-width:26px;max-height:26px;
  stroke:currentColor;fill:none;
  stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;
  opacity:.6;
}

.cd-item__info{min-width:0;display:flex;flex-direction:column;gap:6px}
.cd-item__name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:13.5px;line-height:1.35;color:#351B2F;
  margin:0;font-weight:600;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;
  overflow:hidden;
}
.cd-item__name a{color:inherit;text-decoration:none}
.cd-item__name a:hover{color:#B76146}

.cd-item__price{
  font-family:'Inter',sans-serif;
  font-size:15px;font-weight:700;color:#B76146;
  line-height:1;
}
.cd-item__price .cd-unit{
  font-size:11px;color:#9CA3AF;font-weight:500;
  margin-left:4px;
}

.cd-item__row{
  display:flex;align-items:center;justify-content:space-between;
  gap:10px;margin-top:6px;
}

/* Qty stepper */
.cd-qty{
  display:inline-flex;align-items:center;
  border:1px solid #E5DDD2;border-radius:8px;
  overflow:hidden;background:#FCF8F2;
  height:32px;
}
.cd-qty button{
  width:28px;height:100%;
  display:grid;place-items:center;
  font-size:14px;font-weight:600;
  color:#351B2F;background:transparent;
  border:none;cursor:pointer;transition:all .15s ease;
}
.cd-qty button:hover{background:#B76146;color:#fff}
.cd-qty span{
  min-width:28px;height:100%;
  display:grid;place-items:center;
  font-size:12.5px;font-weight:700;color:#351B2F;
  background:#fff;
  border-left:1px solid #E5DDD2;
  border-right:1px solid #E5DDD2;
}

.cd-remove{
  background:none;border:none;cursor:pointer;
  font-size:11.5px;font-weight:600;color:#9CA3AF;
  padding:5px 9px;border-radius:6px;
  font-family:inherit;transition:all .16s ease;
}
.cd-remove:hover{color:#B76146;background:rgba(183,97,70,.08)}

/* ---------- Empty ---------- */
.cd-empty{
  text-align:center;padding:60px 20px;
  display:flex;flex-direction:column;align-items:center;gap:14px;
}
.cd-empty .ico{
  width:80px;height:80px;border-radius:50%;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  border:1px solid rgba(212,175,55,.35);
  color:#B76146;font-size:34px;
}
.cd-empty h4{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;color:#351B2F;margin:0;font-weight:600;
}
.cd-empty p{
  font-size:13px;color:#6B7280;margin:0 0 4px;
  max-width:260px;line-height:1.6;
}
.cd-empty .cd-empty-btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:11px 22px;font-size:13px;font-weight:600;
  border-radius:10px;
  background:linear-gradient(135deg,#C9704F,#B76146);
  color:#fff;text-decoration:none;border:none;cursor:pointer;
  box-shadow:0 10px 24px -12px rgba(183,97,70,.85);
  transition:all .2s ease;margin-top:8px;
}
.cd-empty .cd-empty-btn:hover{
  background:linear-gradient(135deg,#B76146,#9B4C34);
  transform:translateY(-2px);
}

/* ---------- Footer ---------- */
.cd-foot{
  padding:18px 20px calc(18px + env(safe-area-inset-bottom));
  background:#FAF3E6;
  border-top:1px solid #EFE6D9;
}
.cd-row{
  display:flex;justify-content:space-between;align-items:center;
  font-size:13.5px;color:#6B7280;padding:5px 0;
}
.cd-row span:last-child{color:#351B2F;font-weight:600}
.cd-row--free span:last-child{color:#4F5340;font-weight:700}

.cd-total{
  display:flex;justify-content:space-between;align-items:baseline;
  padding:12px 0 16px;margin-top:6px;
  border-top:1px dashed #E5DDD2;
}
.cd-total span:first-child{
  font-size:13.5px;color:#6B7280;
  text-transform:uppercase;font-weight:600;letter-spacing:.3px;
}
.cd-total span:last-child{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:23px;font-weight:700;color:#351B2F;
}

.cd-actions{
  display:grid;grid-template-columns:1fr 1fr;gap:10px;
}
.cd-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:13px 16px;font-size:13px;font-weight:600;
  border-radius:10px;text-decoration:none;border:1px solid transparent;
  cursor:pointer;font-family:inherit;
  transition:all .2s ease;white-space:nowrap;
}
.cd-btn--outline{
  background:#fff;color:#351B2F;border-color:#E5DDD2;
}
.cd-btn--outline:hover{border-color:#B76146;color:#B76146;background:#FFF7E0}
.cd-btn--primary{
  background:linear-gradient(135deg,#4A2440,#351B2F);
  color:#F7EBDD;border-color:#351B2F;
}
.cd-btn--primary:hover{
  background:linear-gradient(135deg,#351B2F,#24141F);
  transform:translateY(-2px);
}
.cd-btn svg{
  width:14px;height:14px;
  stroke:currentColor;fill:none;
  stroke-width:2;stroke-linecap:round;stroke-linejoin:round;
}
</style>

<!-- ═══════════════════════════════════════════════════
     CART DRAWER
     ═══════════════════════════════════════════════════ -->
<aside class="cart-drawer" id="cartDrawer" aria-hidden="true" aria-label="Shopping Cart">

  <div class="cd-head">
    <h3>
      Your Cart
      <?php if ($drawerCount > 0): ?>
        <span class="cd-count"><?= (int) $drawerCount ?> item<?= $drawerCount === 1 ? '' : 's' ?></span>
      <?php endif; ?>
    </h3>
    <button class="cd-close" id="cartClose" type="button" aria-label="Close cart">
      <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6l-12 12"/></svg>
    </button>
  </div>

  <div class="cd-body" id="cartBody">

    <?php if (empty($drawerItems)): ?>

      <!-- ---------- Empty State ---------- -->
      <div class="cd-empty">
        <div class="ico">🛕</div>
        <h4>Your cart is empty</h4>
        <p>Add sacred essentials to begin your spiritual journey.</p>
        <a href="<?= url('shop.php') ?>" class="cd-empty-btn">
          Explore Siddha Store
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>

    <?php else: ?>

      <!-- ---------- Items ---------- -->
      <?php foreach ($drawerItems as $row):
        $price    = (float) ($row['current_price'] ?? 0);
        if ($price <= 0) $price = (float) ($row['price_at_add'] ?? 0);
        $qty      = max(1, (int) $row['qty']);
        $lineTot  = $price * $qty;
        $imgSrc   = cdrawer_img_url($row['image_url'] ?? '');
      ?>
        <div class="cd-item" data-id="<?= (int) $row['cart_item_id'] ?>">

          <!-- Product Image -->
          <div class="cd-item__media">
            <a href="<?= url('product.php?slug=' . urlencode($row['product_slug'])) ?>">
              <?php if ($imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>"
                     alt="<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>"
                     loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                <div class="cd-item__ph" style="display:none">
                  <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
              <?php else: ?>
                <div class="cd-item__ph">
                  <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
              <?php endif; ?>
            </a>
          </div>

          <!-- Product Info -->
          <div class="cd-item__info">
            <h4 class="cd-item__name">
              <a href="<?= url('product.php?slug=' . urlencode($row['product_slug'])) ?>">
                <?= e($row['product_name']) ?>
              </a>
            </h4>

            <div class="cd-item__price">
              <?= inr($price) ?>
              <span class="cd-unit">× <?= $qty ?></span>
            </div>

            <div class="cd-item__row">
              <div class="cd-qty">
                <button type="button" data-act="dec" aria-label="Decrease">−</button>
                <span><?= $qty ?></span>
                <button type="button" data-act="inc" aria-label="Increase">+</button>
              </div>
              <button type="button" class="cd-remove" data-act="remove">Remove</button>
            </div>
          </div>

        </div>
      <?php endforeach; ?>

    <?php endif; ?>

  </div>

  <?php if (!empty($drawerItems)): ?>
    <div class="cd-foot">

      <div class="cd-row">
        <span>Subtotal</span>
        <span id="cartSubtotal"><?= inr($drawerSubtotal) ?></span>
      </div>

      <div class="cd-row cd-row--free">
        <span>Shipping</span>
        <span>
          <?php if ($drawerSubtotal >= $freeShipMin): ?>
            Complimentary
          <?php else: ?>
            <?= inr(79) ?>
          <?php endif; ?>
        </span>
      </div>

      <div class="cd-total">
        <span>Total</span>
        <span id="cartTotal"><?= inr($drawerSubtotal) ?></span>
      </div>

      <div class="cd-actions">
        <a href="<?= url('cart.php') ?>" class="cd-btn cd-btn--outline">
          <svg viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
          View Cart
        </a>
        <a href="<?= url('checkout.php') ?>" class="cd-btn cd-btn--primary">
          Checkout
          <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>

    </div>
  <?php endif; ?>

</aside>

<!-- Backdrop -->
<div class="drawer-backdrop" id="drawerBackdrop" aria-hidden="true"></div>