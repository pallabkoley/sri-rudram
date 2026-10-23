<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require BASE_PATH . '/includes/handlers/cart-handler.php';
}

/* ==================================================================
 |  SAFE CART IMAGE HELPER — self-contained, handles all cases
 * ================================================================== */
if (!function_exists('cart_img_url')) {
    function cart_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';

        // External URL — use as-is
        if (preg_match('#^https?://#i', $stored)) return $stored;

        $stored = ltrim($stored, '/');

        // Strip any leading "assets/img/" duplicates
        if (strpos($stored, 'assets/img/') === 0) {
            $stored = substr($stored, strlen('assets/img/'));
        }
        $marker = 'assets/img/';
        $pos = strpos($stored, $marker);
        if ($pos !== false) {
            $stored = substr($stored, $pos + strlen($marker));
        }

        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}

if (!function_exists('cart_img')) {
    function cart_img(?string $stored, string $alt = ''): string {
        $src = cart_img_url($stored);
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES);

        if ($src === '') {
            return '<div class="ct-img-ph" role="img" aria-label="' . $safeAlt . '">'
                 .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
                 .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
                 .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
                 .     '<polyline points="21 15 16 10 5 21"/>'
                 .   '</svg>'
                 . '</div>';
        }

        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" '
             . 'alt="' . $safeAlt . '" loading="lazy" '
             . 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'grid\';">'
             . '<div class="ct-img-ph" style="display:none" role="img" aria-label="' . $safeAlt . '">'
             .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
             .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
             .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
             .     '<polyline points="21 15 16 10 5 21"/>'
             .   '</svg>'
             . '</div>';
    }
}

$cartId    = cart_current_id();
$items     = cart_items($cartId);
$subtotal  = cart_subtotal($cartId);
$itemCount = 0;
foreach ($items as $it) { $itemCount += (int) $it['qty']; }

/* Free shipping threshold */
$freeShipMin = (float) setting('free_shipping_min', 999);
$remaining   = max(0, $freeShipMin - $subtotal);
$progressPct = $freeShipMin > 0 ? min(100, ($subtotal / $freeShipMin) * 100) : 100;

$pageTitle = 'Your Cart — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   CART PAGE — scoped styles (ct-* prefix)
   ============================================================ */
.ct-page{background:#FAF6EF;padding:0 0 88px;min-height:70vh}
.ct-wrap{max-width:1200px;margin:0 auto;padding:0 22px}

/* ---------- Image placeholder (broken/missing image) ---------- */
.ct-img-ph{
  width:100%;height:100%;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;
}
.ct-img-ph svg{
  width:36%;height:36%;max-width:36px;max-height:36px;
  opacity:.55;
}

/* ---------- Page header ---------- */
.ct-hero{
  background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
  padding:44px 0 48px;position:relative;overflow:hidden;
}
.ct-hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(circle at 12% 30%,rgba(183,97,70,.28) 0%,transparent 55%),
    radial-gradient(circle at 88% 70%,rgba(212,175,55,.16) 0%,transparent 55%);
}
.ct-hero::after{
  content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent);
}
.ct-hero .ct-wrap{position:relative;z-index:2}
.ct-breadcrumb{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  font-size:12.5px;color:rgba(247,235,221,.72);margin-bottom:18px;
}
.ct-breadcrumb a{color:rgba(247,235,221,.9);text-decoration:none}
.ct-breadcrumb a:hover{color:#D4AF37}
.ct-breadcrumb .sep{color:rgba(247,235,221,.35)}
.ct-breadcrumb .current{color:#F7EBDD;font-weight:600}
.ct-hero h1{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:38px;line-height:1.15;color:#F7EBDD;margin:0 0 8px;font-weight:600;
  display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;
}
.ct-hero h1 .cnt{
  font-family:'Inter',sans-serif;font-size:14px;font-weight:700;
  color:#D4AF37;background:rgba(212,175,55,.14);
  border:1px solid rgba(212,175,55,.35);
  padding:4px 12px;border-radius:99px;letter-spacing:.4px;
}
.ct-hero p{color:rgba(247,235,221,.75);font-size:14.5px;line-height:1.65;margin:0;max-width:560px}
@media(max-width:640px){
  .ct-hero{padding:32px 0 38px}
  .ct-hero h1{font-size:26px}
  .ct-hero p{font-size:13.5px}
}

/* ---------- Free shipping progress ---------- */
.ct-ship{max-width:1200px;margin:-24px auto 0;padding:0 22px;position:relative;z-index:3}
.ct-ship__inner{
  background:#fff;border:1px solid #EFE6D9;border-radius:14px;
  padding:16px 20px;box-shadow:0 6px 20px -8px rgba(53,27,47,.12);
  display:flex;flex-direction:column;gap:10px;
}
.ct-ship__row{display:flex;align-items:center;gap:10px;font-size:13.5px;color:#351B2F;font-weight:500}
.ct-ship__row strong{color:#B76146;font-weight:700}
.ct-ship__ico{
  width:32px;height:32px;border-radius:9px;display:grid;place-items:center;flex:0 0 auto;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;font-size:15px;
}
.ct-ship__bar{height:6px;border-radius:99px;background:#F3E8D8;overflow:hidden;position:relative}
.ct-ship__fill{
  height:100%;border-radius:99px;
  background:linear-gradient(90deg,#D4AF37,#B76146);
  transition:width .6s cubic-bezier(.22,.68,.32,1);position:relative;
}
.ct-ship__fill::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);
  animation:ctShimmer 2s ease-in-out infinite;
}
@keyframes ctShimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
.ct-ship__success .ct-ship__row{color:#4F5340}
.ct-ship__success .ct-ship__row strong{color:#4F5340}
.ct-ship__success .ct-ship__ico{background:linear-gradient(135deg,#DCE5CD,#C8D4B5);color:#4F5340}
@media(max-width:640px){
  .ct-ship{padding:0 18px;margin-top:-20px}
  .ct-ship__inner{padding:14px 16px}
  .ct-ship__row{font-size:12.5px}
}

/* ---------- Main layout ---------- */
.ct-main{
  max-width:1200px;margin:0 auto;padding:40px 22px 0;
  display:grid;grid-template-columns:1.5fr 1fr;gap:32px;align-items:start;
}
@media(max-width:960px){.ct-main{grid-template-columns:1fr;gap:24px;padding:32px 18px 0}}

/* ---------- Cart list ---------- */
.ct-list{
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  overflow:hidden;box-shadow:0 1px 3px rgba(53,27,47,.04);
}
.ct-list__head{
  display:flex;align-items:center;justify-content:space-between;
  padding:20px 24px;border-bottom:1px solid #F3EEE6;
  background:linear-gradient(180deg,#FCF8F2,#FBF5EC);
}
.ct-list__head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:20px;color:#351B2F;margin:0;font-weight:600;
}
.ct-list__head .count{
  font-size:12.5px;font-weight:700;
  padding:4px 12px;border-radius:99px;
  background:#F3E8D8;color:#8A6A08;
}
.ct-list__body{padding:8px}

/* ---------- Cart row ---------- */
.ct-row{
  display:grid;grid-template-columns:96px 1fr auto auto;
  gap:20px;align-items:center;padding:18px 16px;border-radius:12px;
  transition:background .18s ease;position:relative;
}
.ct-row:hover{background:#FAF6EF}
.ct-row + .ct-row{border-top:1px solid #F3EEE6;border-radius:0}
.ct-row:first-child{border-radius:12px 12px 0 0}
.ct-row:last-child{border-radius:0 0 12px 12px}

.ct-row__media{
  width:96px;height:96px;border-radius:12px;
  overflow:hidden;background:#F5F1EC;border:1px solid #EFE6D9;
  flex:0 0 auto;position:relative;
}
.ct-row__media a{display:block;width:100%;height:100%;position:relative}
.ct-row__media img{
  width:100%;height:100%;object-fit:cover;display:block;
  transition:transform .35s ease;position:absolute;inset:0;
}
.ct-row__media .ct-img-ph{position:absolute;inset:0}
.ct-row:hover .ct-row__media img{transform:scale(1.04)}

.ct-row__info{min-width:0;display:flex;flex-direction:column;gap:5px}
.ct-row__cat{
  font-size:10px;font-weight:700;letter-spacing:1px;
  text-transform:uppercase;color:#B76146;
}
.ct-row__name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:15.5px;line-height:1.35;color:#351B2F;margin:0;font-weight:600;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.ct-row__name a{color:inherit;text-decoration:none}
.ct-row__name a:hover{color:#B76146}
.ct-row__price-unit{font-size:12.5px;color:#6B7280}
.ct-row__price-unit b{color:#351B2F;font-weight:600}

.ct-qty{
  display:inline-flex;align-items:center;
  border:1px solid #E5DDD2;border-radius:10px;
  overflow:hidden;background:#FCF8F2;height:38px;
}
.ct-qty button{
  width:34px;height:100%;display:grid;place-items:center;
  font-size:16px;font-weight:600;color:#351B2F;
  background:transparent;border:none;cursor:pointer;transition:all .16s ease;
}
.ct-qty button:hover:not(:disabled){background:#B76146;color:#fff}
.ct-qty button:disabled{opacity:.5;cursor:not-allowed}
.ct-qty span{
  min-width:34px;height:100%;display:grid;place-items:center;
  font-size:13.5px;font-weight:700;color:#351B2F;background:#fff;
  border-left:1px solid #E5DDD2;border-right:1px solid #E5DDD2;
}

.ct-row__right{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
.ct-row__total{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;font-weight:700;color:#B76146;line-height:1;
}
.ct-row__remove{
  display:inline-flex;align-items:center;gap:4px;
  font-size:11.5px;font-weight:600;color:#9CA3AF;
  background:none;border:none;cursor:pointer;
  padding:4px 8px;border-radius:6px;transition:all .18s ease;
}
.ct-row__remove:hover{color:#B76146;background:rgba(183,97,70,.08)}
.ct-row__remove:disabled{opacity:.5;cursor:not-allowed}
.ct-row__remove svg{
  width:12px;height:12px;stroke:currentColor;fill:none;
  stroke-width:2;stroke-linecap:round;
}

@media(max-width:640px){
  .ct-row{
    grid-template-columns:80px 1fr;
    grid-template-areas:
      "media info"
      "media qty"
      "right right";
    gap:12px 14px;padding:16px 12px;
  }
  .ct-row__media{grid-area:media;width:80px;height:80px}
  .ct-row__info{grid-area:info}
  .ct-qty{grid-area:qty;width:fit-content;height:34px}
  .ct-qty button{width:30px;font-size:15px}
  .ct-qty span{min-width:30px;font-size:13px}
  .ct-row__right{
    grid-area:right;flex-direction:row;justify-content:space-between;
    align-items:center;width:100%;
    padding-top:10px;border-top:1px dashed #EFE6D9;
  }
  .ct-row__name{font-size:14.5px}
  .ct-row__total{font-size:16.5px}
}

/* ---------- Empty state ---------- */
.ct-empty{
  max-width:560px;margin:60px auto;text-align:center;
  padding:60px 32px;background:#fff;
  border:1px solid #EFE6D9;border-radius:20px;
  box-shadow:0 12px 32px -18px rgba(53,27,47,.18);
}
.ct-empty__ico{
  width:88px;height:88px;margin:0 auto 22px;border-radius:50%;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;font-size:36px;
  border:1px solid rgba(212,175,55,.28);
}
.ct-empty h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:24px;color:#351B2F;margin:0 0 10px;font-weight:600;
}
.ct-empty p{
  color:#6B7280;font-size:14.5px;line-height:1.7;
  margin:0 auto 26px;max-width:380px;
}
.ct-empty .ct-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}

/* ---------- Summary ---------- */
.ct-summary{
  position:sticky;top:96px;background:#fff;
  border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 8px 32px -20px rgba(53,27,47,.20);
}
.ct-summary__head{
  padding:22px 24px 18px;border-bottom:1px solid #F3EEE6;
  background:linear-gradient(180deg,#FCF8F2,#FBF5EC);
}
.ct-summary__head h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;color:#351B2F;margin:0 0 4px;font-weight:600;
}
.ct-summary__head p{font-size:12px;color:#9A8B7C;margin:0;letter-spacing:.3px}
.ct-summary__body{padding:20px 24px 24px}
.ct-summary__row{
  display:flex;justify-content:space-between;align-items:center;
  font-size:13.5px;color:#6B7280;padding:9px 0;
}
.ct-summary__row span:last-child{color:#351B2F;font-weight:600}
.ct-summary__row--free span:last-child{
  color:#4F5340;font-weight:700;
  display:inline-flex;align-items:center;gap:5px;
}
.ct-summary__divider{
  height:1px;background:linear-gradient(90deg,transparent,#E5DDD2,transparent);
  margin:12px 0;
}
.ct-summary__total{
  display:flex;justify-content:space-between;align-items:baseline;
  padding:6px 0 18px;
}
.ct-summary__total span:first-child{
  font-size:14px;color:#6B7280;letter-spacing:.3px;
  text-transform:uppercase;font-weight:600;
}
.ct-summary__total span:last-child{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:28px;font-weight:700;color:#351B2F;
}
.ct-summary__cta{display:flex;flex-direction:column;gap:10px}
.ct-summary__cta .ct-btn{width:100%;justify-content:center}

/* Coupon */
.ct-coupon{
  margin-top:6px;display:flex;align-items:center;gap:10px;
  padding:11px 14px;
  background:linear-gradient(135deg,#FFF7E0,#FCEFD5);
  border:1px dashed rgba(212,175,55,.55);
  border-radius:10px;font-size:12px;color:#8A6A08;font-weight:600;
}
.ct-coupon svg{
  width:15px;height:15px;flex:0 0 auto;
  stroke:#8A6A08;fill:none;stroke-width:2;
  stroke-linecap:round;stroke-linejoin:round;
}

/* Trust */
.ct-trust{
  display:flex;flex-direction:column;gap:10px;
  margin-top:20px;padding-top:20px;border-top:1px solid #F3EEE6;
}
.ct-trust__item{
  display:flex;align-items:center;gap:10px;
  font-size:12px;color:#6B7280;line-height:1.4;
}
.ct-trust__item svg{
  width:16px;height:16px;flex:0 0 auto;
  stroke:#B76146;fill:none;stroke-width:1.8;
  stroke-linecap:round;stroke-linejoin:round;
}
.ct-trust__item b{color:#351B2F;font-weight:600;display:block}

/* Buttons */
.ct-btn{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 24px;font-size:13.5px;font-weight:600;
  border-radius:11px;text-decoration:none;
  border:1px solid transparent;cursor:pointer;font-family:inherit;
  transition:all .2s cubic-bezier(.22,.68,.32,1);white-space:nowrap;
}
.ct-btn svg{
  width:15px;height:15px;flex:0 0 auto;
  stroke:currentColor;fill:none;stroke-width:2;
  stroke-linecap:round;stroke-linejoin:round;
}
.ct-btn--primary{
  background:linear-gradient(135deg,#C9704F,#B76146);
  color:#fff;border-color:#B76146;
  box-shadow:0 10px 24px -12px rgba(183,97,70,.85);
}
.ct-btn--primary:hover{
  background:linear-gradient(135deg,#B76146,#9B4C34);
  color:#fff;transform:translateY(-2px);
  box-shadow:0 16px 32px -14px rgba(183,97,70,.95);
}
.ct-btn--outline{
  background:#fff;color:#351B2F;border-color:#E5DDD2;
}
.ct-btn--outline:hover{border-color:#B76146;color:#B76146;background:#FFF7E0}

/* Sticky mobile bar */
.ct-mobile-bar{display:none}
@media(max-width:640px){
  .ct-mobile-bar{
    display:block;position:fixed;bottom:0;left:0;right:0;z-index:900;
    padding:12px 16px calc(12px + env(safe-area-inset-bottom));
    background:rgba(53,27,47,.97);backdrop-filter:blur(10px);
    border-top:1px solid rgba(212,175,55,.35);
    box-shadow:0 -6px 20px rgba(0,0,0,.18);
  }
  .ct-mobile-bar__row{display:flex;align-items:center;justify-content:space-between;gap:12px}
  .ct-mobile-bar__total{display:flex;flex-direction:column;line-height:1.15}
  .ct-mobile-bar__total small{
    font-size:9.5px;color:rgba(247,235,221,.55);
    letter-spacing:.5px;text-transform:uppercase;
  }
  .ct-mobile-bar__total b{
    font-family:var(--font-serif,Georgia,serif);
    font-size:19px;font-weight:700;color:#F7EBDD;margin-top:2px;
  }
  .ct-mobile-bar .ct-btn{flex:0 0 auto;padding:12px 20px;font-size:13px}
  .ct-page{padding-bottom:100px}
}

@media(max-width:480px){
  .ct-empty{padding:44px 22px;margin:40px auto}
  .ct-empty__ico{width:72px;height:72px;font-size:30px;margin-bottom:18px}
  .ct-empty h3{font-size:20px}
  .ct-empty p{font-size:13.5px}
  .ct-empty .ct-actions{flex-direction:column}
  .ct-empty .ct-btn{width:100%;justify-content:center}
}

.ct-list,.ct-summary{animation:ctFade .5s cubic-bezier(.22,.68,.32,1) both}
.ct-summary{animation-delay:.08s}
@keyframes ctFade{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
</style>

<div class="ct-page">

  <!-- ═══════════════ HERO ═══════════════ -->
  <section class="ct-hero">
    <div class="ct-wrap">
      <nav class="ct-breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span class="sep">›</span>
        <a href="<?= url('shop.php') ?>">Siddha Store</a>
        <span class="sep">›</span>
        <span class="current">Cart</span>
      </nav>
      <h1>
        Your Cart
        <?php if (!empty($items)): ?>
          <span class="cnt"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></span>
        <?php endif; ?>
      </h1>
      <p>
        Review your sacred selections and proceed when you are ready to receive
        the blessings.
      </p>
    </div>
  </section>

  <?php if (!empty($items)): ?>

    <!-- ═══════════════ FREE SHIPPING PROGRESS ═══════════════ -->
    <section class="ct-ship">
      <div class="ct-ship__inner <?= $remaining <= 0 ? 'ct-ship__success' : '' ?>">
        <div class="ct-ship__row">
          <span class="ct-ship__ico"><?= $remaining <= 0 ? '✓' : '🚚' ?></span>
          <?php if ($remaining > 0): ?>
            <span>Add <strong><?= inr($remaining) ?></strong> more to unlock <strong>Free Shipping</strong></span>
          <?php else: ?>
            <span><strong>Congratulations!</strong> You've unlocked <strong>Free Shipping</strong> on this order</span>
          <?php endif; ?>
        </div>
        <div class="ct-ship__bar">
          <div class="ct-ship__fill" style="width:<?= number_format($progressPct, 2, '.', '') ?>%"></div>
        </div>
      </div>
    </section>

    <!-- ═══════════════ MAIN ═══════════════ -->
    <section class="ct-main">

      <!-- ---------- Cart list ---------- -->
      <div class="ct-list">
        <div class="ct-list__head">
          <h2>Items in your cart</h2>
          <span class="count"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></span>
        </div>

        <div class="ct-list__body">
          <?php foreach ($items as $item): ?>
            <?php $lineTotal = (float) $item['price'] * (int) $item['qty']; ?>
            <div class="ct-row" data-id="<?= (int) $item['id'] ?>">

              <div class="ct-row__media">
                <a href="<?= url('product.php?slug=' . urlencode($item['slug'])) ?>">
                  <?= cart_img($item['image_url'], $item['name']) ?>
                </a>
              </div>

              <div class="ct-row__info">
                <?php if (!empty($item['category_name'])): ?>
                  <span class="ct-row__cat"><?= e($item['category_name']) ?></span>
                <?php endif; ?>
                <h3 class="ct-row__name">
                  <a href="<?= url('product.php?slug=' . urlencode($item['slug'])) ?>">
                    <?= e($item['name']) ?>
                  </a>
                </h3>
                <span class="ct-row__price-unit">
                  <b><?= inr($item['price']) ?></b> × <?= (int) $item['qty'] ?>
                </span>
              </div>

              <div class="ct-qty">
                <button type="button" data-act="dec" aria-label="Decrease quantity">−</button>
                <span><?= (int) $item['qty'] ?></span>
                <button type="button" data-act="inc" aria-label="Increase quantity">+</button>
              </div>

              <div class="ct-row__right">
                <span class="ct-row__total"><?= inr($lineTotal) ?></span>
                <button type="button" class="ct-row__remove" data-act="remove" aria-label="Remove item">
                  <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14"/></svg>
                  Remove
                </button>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ---------- Summary ---------- -->
      <aside class="ct-summary">
        <div class="ct-summary__head">
          <h3>Order Summary</h3>
          <p>All prices include GST</p>
        </div>

        <div class="ct-summary__body">

          <div class="ct-summary__row">
            <span>Subtotal</span>
            <span id="cartSubtotal"><?= inr($subtotal) ?></span>
          </div>

          <div class="ct-summary__row ct-summary__row--free">
            <span>Shipping</span>
            <span>
              <?php if ($remaining <= 0): ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Complimentary
              <?php else: ?>
                Calculated at checkout
              <?php endif; ?>
            </span>
          </div>

          <div class="ct-summary__row">
            <span>Estimated GST</span>
            <span>Included</span>
          </div>

          <div class="ct-summary__divider"></div>

          <div class="ct-summary__total">
            <span>Total</span>
            <span id="cartTotal"><?= inr($subtotal) ?></span>
          </div>

          <div class="ct-summary__cta">
            <a href="<?= url('checkout.php') ?>" class="ct-btn ct-btn--primary">
              <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              Secure Checkout
            </a>
            <a href="<?= url('shop.php') ?>" class="ct-btn ct-btn--outline">
              <svg viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
              Continue Shopping
            </a>
          </div>

          <div class="ct-coupon">
            <svg viewBox="0 0 24 24"><path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7a4 4 0 1 0 0-5 4 4 0 0 0 0 5zM12 7a4 4 0 1 1 0-5 4 4 0 0 1 0 5z"/></svg>
            Use code <b>DIVYA10</b> for 10% off on orders above ₹999
          </div>

          <div class="ct-trust">
            <div class="ct-trust__item">
              <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
              <span><b>100% Secure Payments</b>UPI · Cards · Net Banking</span>
            </div>
            <div class="ct-trust__item">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              <span><b>Fast Dispatch</b>Shipped within 24 hours</span>
            </div>
            <div class="ct-trust__item">
              <svg viewBox="0 0 24 24"><path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7a4 4 0 1 0 0-5 4 4 0 0 0 0 5z"/></svg>
              <span><b>Authentic &amp; Energized</b>Temple-sourced sacred items</span>
            </div>
          </div>

        </div>
      </aside>

    </section>

  <?php else: ?>

    <!-- ═══════════════ EMPTY CART ═══════════════ -->
    <div class="ct-empty">
      <div class="ct-empty__ico">🛕</div>
      <h3>Your cart is empty</h3>
      <p>Add sacred essentials to begin your spiritual journey — energized rudraksha, sacred tools and temple-sourced items await you.</p>
      <div class="ct-actions">
        <a href="<?= url('shop.php') ?>" class="ct-btn ct-btn--primary">
          <svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Explore Siddha Store
        </a>
        <a href="<?= url('pujas.php') ?>" class="ct-btn ct-btn--outline">
          <svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.5 9.5 8 10 4.5-.5 8-5 8-10V6l-8-4z"/></svg>
          Book a Puja
        </a>
      </div>
    </div>

  <?php endif; ?>

</div>

<!-- ═══════════════ STICKY MOBILE CHECKOUT BAR ═══════════════ -->
<?php if (!empty($items)): ?>
<div class="ct-mobile-bar">
  <div class="ct-mobile-bar__row">
    <div class="ct-mobile-bar__total">
      <small>Total Amount</small>
      <b id="cartTotalMobile"><?= inr($subtotal) ?></b>
    </div>
    <a href="<?= url('checkout.php') ?>" class="ct-btn ct-btn--primary">
      Checkout
      <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════
     CART PAGE ACTIONS — Remove / Qty +/− with AJAX
     ═══════════════════════════════════════════════════════════════ -->
<script>
(function () {
  'use strict';

  var cartBody = document.querySelector('.ct-list__body');
  if (!cartBody) return;

  /* ---------- Get CSRF token ---------- */
  function getToken() {
    var input = document.querySelector('input[name="_token"], input[name="_csrf"]');
    if (input && input.value) return input.value;
    if (document.body && document.body.dataset && document.body.dataset.csrf) {
      return document.body.dataset.csrf;
    }
    return '';
  }

  /* ---------- Toast notification ---------- */
  function toast(msg, isError) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText =
      'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
      'z-index:99999;padding:12px 22px;border-radius:10px;' +
      'font:600 13.5px/1.4 system-ui,-apple-system,sans-serif;color:#fff;' +
      'box-shadow:0 12px 32px rgba(0,0,0,.25);opacity:0;' +
      'transition:opacity .25s ease;pointer-events:none;' +
      (isError ? 'background:#DC2626;' : 'background:#351B2F;');
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.style.opacity = '1'; });
    setTimeout(function () {
      t.style.opacity = '0';
      setTimeout(function () { t.remove(); }, 300);
    }, 2200);
  }

  /* ---------- Send POST request ---------- */
  function sendAction(action, data, onDone) {
    var fd = new FormData();
    fd.append('action', action);

    var token = getToken();
    if (token) {
      fd.append('_token', token);
      fd.append('_csrf',  token);
    }

    Object.keys(data).forEach(function (k) {
      fd.append(k, data[k]);
    });

    fetch(window.location.pathname, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: fd
    })
    .then(function (r) { return r.text(); })
    .then(function (raw) {
      var res = null;
      try { res = JSON.parse(raw); } catch (e) { res = null; }

      if (res && res.ok) {
        if (res.message) toast(res.message);
      } else if (res && res.error) {
        toast(res.error, true);
      }
      if (onDone) onDone(res, raw);
    })
    .catch(function (err) {
      console.error('[cart]', err);
      toast('Network error. Please try again.', true);
      if (onDone) onDone(null, null);
    });
  }

  /* ---------- Event delegation ---------- */
  cartBody.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-act]');
    if (!btn) return;
    e.preventDefault();

    var row = btn.closest('.ct-row');
    if (!row) return;

    var itemId = row.getAttribute('data-id');
    if (!itemId) return;

    var act = btn.getAttribute('data-act');

    /* ============ REMOVE ============ */
    if (act === 'remove') {
      if (!confirm('Remove this item from your cart?')) return;

      btn.disabled = true;
      btn.style.opacity = '.5';

      sendAction('cart_remove', { item_id: itemId }, function (res) {
        // Reload regardless — DB now reflects the change
        window.location.reload();
      });
      return;
    }

    /* ============ QTY +/− ============ */
    if (act === 'inc' || act === 'dec') {
      var qtyEl = row.querySelector('.ct-qty span');
      var current = qtyEl ? parseInt(qtyEl.textContent, 10) : 1;
      if (isNaN(current) || current < 1) current = 1;

      var newQty = (act === 'inc') ? current + 1 : Math.max(1, current - 1);
      if (newQty === current) return;

      btn.disabled = true;
      if (qtyEl) qtyEl.textContent = String(newQty);

      sendAction('cart_update', { item_id: itemId, qty: newQty }, function () {
        // Reload so subtotal / total / free-shipping bar update
        window.location.reload();
      });
    }
  });
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>