<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'wishlist.php';
    flash_set('info', 'Please login to view your wishlist.');
    redirect('login.php');
}

$userId = current_user_id();

/* ==================================================================
 |  Handle remove from wishlist
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'remove_wish') {
    csrf_verify(post('_csrf', ''));
    $productId = (int) post('product_id', 0);
    if ($productId > 0) {
        try {
            $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
                ->execute([$userId, $productId]);
            flash_set('success', 'Removed from wishlist.');
        } catch (PDOException $e) {
            error_log('[wishlist:remove] ' . $e->getMessage());
            flash_set('error', 'Could not remove item.');
        }
    }
    redirect('wishlist.php');
}

/* ==================================================================
 |  Fetch wishlist items
 * ================================================================== */
try {
    $stmt = $pdo->prepare(
        'SELECT w.id AS wish_id, w.created_at AS wished_at,
                p.id, p.name, p.slug, p.short_desc, p.price, p.mrp,
                p.image_url, p.badge, p.badge_style, p.stock, p.is_active
         FROM wishlists w
         INNER JOIN products p ON p.id = w.product_id
         WHERE w.user_id = ? AND p.is_active = 1
         ORDER BY w.created_at DESC'
    );
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[wishlist] ' . $e->getMessage());
    $items = [];
}

$totalCount = count($items);

/* ==================================================================
 |  Safe image helper (self-contained)
 * ================================================================== */
if (!function_exists('wish_img_url')) {
    function wish_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;
        $stored = ltrim($stored, '/');
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

$pageTitle = 'My Wishlist — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('account.php') ?>">My Account</a>
      <span class="sep">›</span>
      <span class="current">Wishlist</span>
    </nav>
    <span class="eyebrow">Saved Items</span>
    <h1>My Wishlist</h1>
    <p>Your favourite sacred items, saved for later.</p>
  </div>
</section>

<!-- ═══════════════ ACCOUNT SHELL ═══════════════ -->
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
.ac-flash{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;line-height:1.55}
.ac-flash.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ac-flash.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.ac-flash.info{background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE}

.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__head a{font-size:12.5px;font-weight:600;color:#B76146;text-decoration:none}
.ac-card__head a:hover{color:#9B4C34}

/* ── Wishlist grid ─────────────────────────────── */
.wish-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
@media(max-width:1024px){.wish-grid{grid-template-columns:repeat(2,1fr);gap:18px}}
@media(max-width:600px){.wish-grid{grid-template-columns:1fr;gap:16px}}

.wish-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  display:flex;flex-direction:column;box-shadow:0 1px 3px rgba(53,27,47,.05);
  transition:transform .22s,box-shadow .22s,border-color .22s}
.wish-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(53,27,47,.10);
  border-color:rgba(183,97,70,.28)}
.wish-card__media{position:relative;aspect-ratio:4/3;overflow:hidden;background:#F3F4F6}
.wish-card__media img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
.wish-card:hover .wish-card__media img{transform:scale(1.05)}
.wish-card__badge{position:absolute;top:12px;left:12px;padding:5px 12px;
  background:linear-gradient(135deg,#F5D76E,#D4AF37);color:#4A2C0F;
  font-size:10.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;
  border-radius:99px;box-shadow:0 3px 10px rgba(0,0,0,.18);z-index:2}
.wish-card__remove{position:absolute;top:12px;right:12px;width:34px;height:34px;border-radius:50%;
  background:rgba(255,255,255,.96);border:1px solid #EFE6D9;display:grid;place-items:center;
  cursor:pointer;z-index:2;color:#DC2626;transition:all .18s}
.wish-card__remove:hover{background:#FEF2F2;transform:scale(1.08)}
.wish-card__remove svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.2}
.wish-card__body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:10px;flex:1}
.wish-card__name{font-family:var(--font-serif,Georgia,serif);font-size:17px;line-height:1.35;
  color:#351B2F;margin:0;font-weight:600}
.wish-card__name a{color:inherit;text-decoration:none}
.wish-card__name a:hover{color:#B76146}
.wish-card__desc{color:#6B7280;font-size:13px;line-height:1.55;margin:0;flex:1;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.wish-card__price{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;
  padding-top:12px;border-top:1px solid #F3EEE6}
.wish-card__now{font-size:18px;font-weight:700;color:#351B2F}
.wish-card__was{font-size:12.5px;color:#9CA3AF;text-decoration:line-through}
.wish-card__actions{display:flex;gap:8px;margin-top:6px}
.wish-card__btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;
  flex:1;padding:10px 16px;font-size:12.5px;font-weight:600;border-radius:9px;
  border:1px solid transparent;cursor:pointer;font-family:inherit;transition:all .18s;text-decoration:none}
.wish-card__btn--cart{background:#B76146;color:#fff;border-color:#B76146}
.wish-card__btn--cart:hover{background:#9B4C34;color:#fff}
.wish-card__btn--view{background:#fff;color:#351B2F;border-color:#E5E7EB}
.wish-card__btn--view:hover{border-color:#B76146;color:#B76146}

.ac-empty{padding:60px 24px;text-align:center;color:#6B7280}
.ac-empty .ico{font-size:52px;color:#D4AF37;margin-bottom:14px;line-height:1}
.ac-empty h4{font-family:var(--font-serif,Georgia,serif);font-size:20px;color:#351B2F;margin:0 0 8px}
.ac-empty p{font-size:13.5px;margin:0 0 22px;line-height:1.6}
.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:11px 22px;font-size:13px;font-weight:600;border-radius:10px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ac-btn-primary:hover{background:#9B4C34;color:#fff}
</style>

<div class="ac-shell">
  <div class="ac-layout">

    <!-- ── SIDEBAR (inline) ───────────────────────────── -->
    <aside class="ac-nav">
      <a href="<?= url('account.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>" class="active"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <!-- ── CONTENT ─────────────────────────────────────── -->
    <div class="ac-content">
      <h1 class="ac-h1">My Wishlist</h1>
      <p class="ac-sub">
        Showing <b><?= $totalCount ?></b> item<?= $totalCount === 1 ? '' : 's' ?>.
      </p>

      <?php foreach (flash_get() as $f): ?>
        <div class="ac-flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <?php if (empty($items)): ?>

        <div class="ac-card">
          <div class="ac-empty">
            <div class="ico">💖</div>
            <h4>Your wishlist is empty</h4>
            <p>Explore our sacred store and save your favourites.</p>
            <a href="<?= url('shop.php') ?>" class="ac-btn ac-btn-primary">Browse Store</a>
          </div>
        </div>

      <?php else: ?>

        <div class="wish-grid">
          <?php foreach ($items as $p): ?>
            <?php
            $imgSrc = wish_img_url($p['image_url']);
            $off = 0;
            if (!empty($p['mrp']) && $p['mrp'] > $p['price']) {
                $off = (int) round((($p['mrp'] - $p['price']) / $p['mrp']) * 100);
            }
            ?>
            <article class="wish-card">

              <div class="wish-card__media">
                <?php if ($imgSrc): ?>
                  <img src="<?= e($imgSrc) ?>" alt="<?= e($p['name']) ?>"
                       loading="lazy"
                       onerror="this.style.display='none'">
                <?php else: ?>
                  <div style="background:#F3E8D8;display:grid;place-items:center;height:100%;color:#9A8B7C;font-size:32px">
                    <i class="bi bi-image"></i>
                  </div>
                <?php endif; ?>

                <?php if (!empty($p['badge'])): ?>
                  <span class="wish-card__badge"><?= e($p['badge']) ?></span>
                <?php endif; ?>

                <form method="POST" style="margin:0;position:absolute;top:0;right:0;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove_wish">
                  <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" class="wish-card__remove"
                          aria-label="Remove from wishlist"
                          onclick="return confirm('Remove this item from your wishlist?');">
                    <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                  </button>
                </form>
              </div>

              <div class="wish-card__body">
                <h3 class="wish-card__name">
                  <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
                    <?= e($p['name']) ?>
                  </a>
                </h3>

                <?php if (!empty($p['short_desc'])): ?>
                  <p class="wish-card__desc"><?= e($p['short_desc']) ?></p>
                <?php endif; ?>

                <div class="wish-card__price">
                  <span class="wish-card__now"><?= inr($p['price']) ?></span>
                  <?php if (!empty($p['mrp']) && $p['mrp'] > $p['price']): ?>
                    <span class="wish-card__was"><?= inr($p['mrp']) ?></span>
                  <?php endif; ?>
                  <?php if ($off): ?>
                    <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;
                               background:#FEF2F2;color:#DC2626"><?= $off ?>% OFF</span>
                  <?php endif; ?>
                </div>

                <div class="wish-card__actions">
                  <button class="wish-card__btn wish-card__btn--cart"
                          data-add-id="<?= (int) $p['id'] ?>"
                          data-add-name="<?= e($p['name']) ?>"
                          data-add-price="<?= (int) $p['price'] ?>"
                          data-add-img="<?= e($imgSrc) ?>"
                          type="button">
                    <i class="bi bi-bag-plus"></i> Add to Cart
                  </button>

                  <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"
                     class="wish-card__btn wish-card__btn--view">
                    View
                  </a>
                </div>
              </div>

            </article>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>

  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>