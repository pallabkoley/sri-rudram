<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require BASE_PATH . '/includes/handlers/cart-handler.php';
    require BASE_PATH . '/includes/handlers/wishlist-handler.php';
}

/* ==================================================================
 |  INLINE HELPERS
 * ================================================================== */
if (!function_exists('shop_img_url')) {
    function shop_img_url(?string $stored): string {
        return upload_url($stored);
    }
}
if (!function_exists('shop_off')) {
    function shop_off(array $p): int {
        if (empty($p['mrp']) || $p['mrp'] <= $p['price']) return 0;
        return (int) round((($p['mrp'] - $p['price']) / $p['mrp']) * 100);
    }
}

/* ==================================================================
 |  Wishlist state for logged-in user
 * ================================================================== */
$wishlistedIds = [];
if (is_logged_in()) {
    try {
        $wstmt = $pdo->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
        $wstmt->execute([(int) current_user_id()]);
        while ($row = $wstmt->fetch(PDO::FETCH_ASSOC)) {
            $wishlistedIds[(int)$row['product_id']] = true;
        }
    } catch (Throwable $e) {}
}

/* ==================================================================
 |  Data — direct DB
 * ================================================================== */
$catSlug   = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
$shopDebug = '';

try {
    if ($catSlug !== '') {
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND c.slug = ?
             ORDER BY p.sort_order ASC, p.id DESC"
        );
        $stmt->execute([$catSlug]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $products = $pdo->query(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             WHERE p.is_active = 1
             ORDER BY p.sort_order ASC, p.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('[shop] ' . $e->getMessage());
    $products  = [];
    $shopDebug = 'DB error: ' . $e->getMessage();
}

try {
    $categories = $pdo->query(
        "SELECT id, name, slug FROM product_categories ORDER BY sort_order ASC, name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

$pageTitle = 'Siddha Store — Sacred Gemstones & Consecrated Tools';
$pageDesc  = 'Shop authentic rudraksha, healing crystals, malas and sacred tools.';
require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════ PRODUCT GRID — Luxury Boutique Cards ═══════════ -->
<style>
/* ---------- Design tokens ---------- */
:root{
  --sx-burgundy:#4B080C;
  --sx-burgundy-dark:#310508;
  --sx-gold:#D6A83E;
  --sx-gold-soft:#E5C97A;
  --sx-cream:#F6E8CF;
  --sx-ivory:#FFF9F0;
  --sx-muted:#8B6A5D;
  --sx-line:rgba(214,168,62,.22);
  --sx-line-strong:rgba(214,168,62,.45);
}

/* ============================================================
   HERO — rotating mandala animation
   ============================================================ */
.page-hero .page-hero-mandala{
  animation: sx-mandala-spin 90s linear infinite;
  transform-origin: center center;
  will-change: transform;
}
@keyframes sx-mandala-spin{
  from{ transform: rotate(0deg); }
  to  { transform: rotate(360deg); }
}
@media (prefers-reduced-motion: reduce){
  .page-hero .page-hero-mandala{ animation: none; }
}

/* ============================================================
   FILTERS — pill chips
   ============================================================ */
.sx-filters{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  align-items:center;
  margin:0 0 30px;
  padding-bottom:22px;
  border-bottom:1px solid var(--line-gold, var(--sx-line));
}
.sx-chip{
  display:inline-flex;
  align-items:center;
  padding:8px 18px;
  border-radius:99px;
  font-size:12.5px;
  font-weight:600;
  letter-spacing:.2px;
  text-decoration:none;
  white-space:nowrap;
  color:var(--sx-burgundy);
  background:rgba(255,249,240,.85);
  border:1px solid var(--sx-line);
  transition:all .22s cubic-bezier(.4,0,.2,1);
}
.sx-chip:hover{
  border-color:var(--sx-gold);
  color:var(--sx-gold);
  transform:translateY(-1px);
  box-shadow:0 4px 12px -4px rgba(214,168,62,.35);
}
.sx-chip.active{
  background:linear-gradient(135deg, var(--sx-burgundy) 0%, var(--sx-burgundy-dark) 100%);
  color:var(--sx-cream);
  border-color:var(--sx-burgundy);
  box-shadow:0 6px 16px -6px rgba(75,8,12,.55), inset 0 1px 0 rgba(214,168,62,.25);
}
.sx-chip.active::before{
  content:"";
  width:6px;height:6px;
  background:var(--sx-gold);
  border-radius:50%;
  margin-right:8px;
  box-shadow:0 0 6px rgba(214,168,62,.9);
}
.sx-count{
  margin-left:auto;
  font-size:12.5px;
  color:var(--sx-muted);
  font-weight:600;
  letter-spacing:.3px;
}
.sx-count b{color:var(--sx-burgundy)}

/* ============================================================
   GRID — 4 cols desktop, 3 mid, 2 tablet, 1 mobile
   ============================================================ */
.sx-grid{
  display:grid;
  grid-template-columns:repeat(4, minmax(0, 1fr));
  grid-auto-rows:1fr;
  gap:18px;
  align-items:stretch;
}
@media(max-width:980px){
  .sx-grid{grid-template-columns:repeat(3, minmax(0,1fr));gap:16px}
}
@media(max-width:760px){
  .sx-grid{grid-template-columns:repeat(2, minmax(0,1fr));gap:14px}
}
@media(max-width:480px){
  .sx-grid{grid-template-columns:1fr;gap:14px}
}

/* ============================================================
   CARD
   ============================================================ */
.sx-card{
  position:relative;
  background:linear-gradient(180deg, #FFF9F0 0%, #FBF3E1 100%);
  border-radius:14px;
  overflow:hidden;
  border:1px solid var(--sx-line);
  box-shadow:
    0 1px 2px rgba(49,5,8,.04),
    0 4px 14px -8px rgba(49,5,8,.12);
  display:flex;
  flex-direction:column;
  height:100%;
  min-height:410px;
  transition:transform .3s cubic-bezier(.4,0,.2,1),
             box-shadow .3s cubic-bezier(.4,0,.2,1),
             border-color .3s cubic-bezier(.4,0,.2,1);
}
.sx-card:hover{
  transform:translateY(-5px);
  border-color:var(--sx-gold);
  box-shadow:
    0 12px 32px -12px rgba(49,5,8,.35),
    0 0 0 1px rgba(214,168,62,.35);
}
.sx-card::before{
  content:"";
  position:absolute;top:0;left:20%;right:20%;
  height:2px;
  background:linear-gradient(90deg, transparent, var(--sx-gold), transparent);
  opacity:0;
  transition:opacity .35s ease;
  z-index:3;
}
.sx-card:hover::before{opacity:1}

/* ---------- Media ---------- */
.sx-card__media{
  position:relative;
  aspect-ratio:4/3;
  width:100%;
  overflow:hidden;
  background:linear-gradient(135deg, #F6E8CF, #EFDDB9);
  flex-shrink:0;
}
.sx-card__media img{
  width:100%;height:100%;
  object-fit:cover;
  display:block;
  transition:transform .5s cubic-bezier(.4,0,.2,1);
}
.sx-card:hover .sx-card__media img{transform:scale(1.06)}
.sx-card__media::after{
  content:"";
  position:absolute;top:0;left:0;right:0;height:60px;
  background:linear-gradient(180deg, rgba(49,5,8,.18), transparent);
  pointer-events:none;
  z-index:1;
}

/* ---------- Badge ---------- */
.sx-badge{
  position:absolute;
  top:12px;left:12px;
  z-index:2;
  display:inline-flex;
  align-items:center;
  gap:5px;
  padding:5px 11px;
  font-size:9.5px;
  font-weight:800;
  letter-spacing:1px;
  text-transform:uppercase;
  color:var(--sx-cream);
  background:linear-gradient(135deg, var(--sx-burgundy) 0%, var(--sx-burgundy-dark) 100%);
  border:1px solid var(--sx-gold);
  border-radius:99px;
  box-shadow:0 4px 12px -3px rgba(49,5,8,.45);
}
.sx-badge::before{
  content:"";
  width:5px;height:5px;
  background:var(--sx-gold);
  border-radius:50%;
  box-shadow:0 0 5px rgba(214,168,62,.9);
}
.sx-badge--gold{
  background:linear-gradient(135deg, var(--sx-gold) 0%, #B58A2B 100%);
  color:var(--sx-burgundy-dark);
  border-color:#8E6A1F;
}
.sx-badge--gold::before{background:var(--sx-burgundy-dark)}
.sx-badge--sage{
  background:linear-gradient(135deg, #6E7A5A 0%, #556349 100%);
  border-color:#889A72;
}
.sx-badge--terra{
  background:linear-gradient(135deg, #8B3A22 0%, #6B2A18 100%);
  border-color:var(--sx-gold-soft);
}

/* ---------- Wishlist ---------- */
.sx-wish{
  position:absolute;
  top:12px;right:12px;
  z-index:2;
  width:34px;height:34px;
  border-radius:50%;
  background:rgba(255,249,240,.94);
  border:1px solid var(--sx-line);
  display:grid;place-items:center;
  cursor:pointer;
  color:var(--sx-burgundy);
  transition:all .22s cubic-bezier(.4,0,.2,1);
  backdrop-filter:blur(4px);
}
.sx-wish:hover{
  background:var(--sx-ivory);
  color:var(--sx-gold);
  border-color:var(--sx-gold);
  transform:scale(1.1);
  box-shadow:0 6px 14px -4px rgba(214,168,62,.55);
}
.sx-wish svg{
  width:15px;height:15px;
  stroke:currentColor;
  fill:none;
  stroke-width:2;
  stroke-linecap:round;stroke-linejoin:round;
  transition:all .22s ease;
}
.sx-wish.active{
  background:linear-gradient(135deg, var(--sx-burgundy), var(--sx-burgundy-dark));
  border-color:var(--sx-gold);
  color:var(--sx-gold);
}
.sx-wish.active svg{fill:currentColor}
.sx-wish[data-wish-busy="1"]{opacity:.6;pointer-events:none}

/* ---------- Body ---------- */
.sx-card__body{
  padding:14px;
  display:flex;
  flex-direction:column;
  gap:7px;
  flex:1;
}
.sx-card__cat{
  font-size:9px;
  font-weight:700;
  letter-spacing:1.4px;
  text-transform:uppercase;
  color:var(--sx-gold);
  flex-shrink:0;
}
.sx-card__name{
  font-family:'Playfair Display', Georgia, serif;
  font-size:14.5px;
  line-height:1.32;
  color:var(--sx-burgundy);
  margin:0;
  font-weight:600;
  height:39px;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  text-overflow:ellipsis;
  flex-shrink:0;
}
.sx-card__name a{
  color:inherit;
  text-decoration:none;
  transition:color .22s;
}
.sx-card__name a:hover{color:var(--sx-gold)}

.sx-card__desc{
  color:var(--sx-muted);
  font-size:12px;
  line-height:1.5;
  margin:0;
  height:36px;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  text-overflow:ellipsis;
  flex-shrink:0;
}

.sx-card__rule{
  height:1px;
  background:linear-gradient(90deg, transparent, var(--sx-line), transparent);
  margin:6px 0 2px;
  flex-shrink:0;
}

/* ---------- Price ---------- */
.sx-card__price{
  display:flex;
  align-items:baseline;
  gap:8px;
  flex-wrap:wrap;
  margin-top:auto;
  padding-top:6px;
  flex-shrink:0;
}
.sx-now{
  font-family:'Playfair Display', serif;
  font-size:17px;
  font-weight:700;
  color:var(--sx-burgundy);
  letter-spacing:-.2px;
}
.sx-was{
  font-size:11.5px;
  color:var(--sx-muted);
  text-decoration:line-through;
  text-decoration-color:rgba(139,106,93,.55);
}
.sx-off{
  margin-left:auto;
  font-size:9.5px;
  font-weight:800;
  letter-spacing:.6px;
  padding:3px 8px;
  border-radius:4px;
  background:linear-gradient(135deg, rgba(214,168,62,.18), rgba(214,168,62,.08));
  color:#8A6A18;
  border:1px solid rgba(214,168,62,.35);
}

/* ---------- Buy Now Button ---------- */
.sx-card__cta{margin-top:6px;flex-shrink:0}
.sx-btn{
  position:relative;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
  width:100%;
  padding:9px 14px;
  font-size:11.5px;
  font-weight:700;
  letter-spacing:.8px;
  text-transform:uppercase;
  font-family:inherit;
  border-radius:8px;
  background:linear-gradient(135deg, var(--sx-burgundy) 0%, var(--sx-burgundy-dark) 100%);
  color:var(--sx-cream);
  border:1px solid var(--sx-burgundy);
  text-decoration:none;
  cursor:pointer;
  overflow:hidden;
  transition:all .28s cubic-bezier(.4,0,.2,1);
  box-shadow:0 4px 12px -6px rgba(49,5,8,.5);
}
.sx-btn::before{
  content:"";
  position:absolute;inset:0;
  background:linear-gradient(135deg, rgba(214,168,62,.35), rgba(214,168,62,.12));
  opacity:0;
  transition:opacity .28s;
}
.sx-btn > *{position:relative;z-index:1}
.sx-btn:hover{
  color:#FFF3D2;
  border-color:var(--sx-gold);
  box-shadow:0 8px 20px -8px rgba(214,168,62,.55),
             inset 0 0 0 1px rgba(214,168,62,.35);
  transform:translateY(-1px);
}
.sx-btn:hover::before{opacity:1}
.sx-btn:disabled{
  opacity:.55;
  cursor:not-allowed;
  transform:none;
  background:#6B5B58;
  border-color:#6B5B58;
  color:#E8DDD5;
}
.sx-btn:disabled::before{display:none}

/* ============================================================
   EMPTY / DEBUG
   ============================================================ */
.sx-empty{
  text-align:center;
  padding:70px 22px;
  background:var(--sx-ivory);
  border:1px solid var(--sx-line);
  border-radius:16px;
}
.sx-empty .ico{
  font-size:52px;
  margin-bottom:14px;
  line-height:1;
  filter:grayscale(.3);
}
.sx-empty h3{
  font-family:'Playfair Display', serif;
  font-size:22px;
  color:var(--sx-burgundy);
  margin:0 0 8px;
}
.sx-empty p{color:var(--sx-muted);margin:0}
.sx-debug{
  padding:12px 16px;
  background:rgba(139,58,34,.08);
  border:1px solid rgba(139,58,34,.25);
  border-radius:10px;
  color:#8B3A22;
  font-size:12px;
  margin-bottom:20px;
  font-family:ui-monospace, monospace;
}

/* ============================================================
   RESPONSIVE tweaks
   ============================================================ */
@media(max-width:760px){
  .sx-card{min-height:400px}
}
@media(max-width:480px){
  .sx-card{min-height:380px}
}
</style>

<!-- ═══════════════ HERO — site common pattern ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Shop</span>
    </nav>
    <span class="eyebrow">Siddha Store</span>
    <h1>Sacred Gemstones &amp; Consecrated Tools</h1>
    <p>Authentic rudraksha, healing crystals, malas and sacred tools handpicked for your spiritual well-being.</p>
  </div>
</section>

<!-- ═══════════════ PRODUCTS ═══════════════ -->
<section class="section" style="padding:40px 0 80px;background:var(--cream)">
  <div class="container">

    <?php if ($shopDebug !== ''): ?>
      <div class="sx-debug"><?= e($shopDebug) ?></div>
    <?php endif; ?>

    <!-- Filter chips -->
    <?php if (!empty($categories)): ?>
      <div class="sx-filters">
        <a href="<?= url('shop.php') ?>"
           class="sx-chip<?= $catSlug === '' ? ' active' : '' ?>">
          All Products
        </a>
        <?php foreach ($categories as $c): ?>
          <a href="<?= url('shop.php?cat=' . urlencode($c['slug'])) ?>"
             class="sx-chip<?= $catSlug === $c['slug'] ? ' active' : '' ?>">
            <?= e($c['name']) ?>
          </a>
        <?php endforeach; ?>
        <span class="sx-count">
          <b><?= count($products) ?></b> <?= count($products) === 1 ? 'product' : 'products' ?>
        </span>
      </div>
    <?php endif; ?>

    <!-- Grid -->
    <?php if (empty($products)): ?>

      <div class="sx-empty">
        <div class="ico">🛍</div>
        <h3>No Products Available</h3>
        <p>New sacred items are being added. Please check back soon.</p>
      </div>

    <?php else: ?>

      <div class="sx-grid">
        <?php foreach ($products as $p):
          $off      = shop_off($p);
          $pid      = (int) $p['id'];
          $wished   = isset($wishlistedIds[$pid]);
          $imgSrc   = shop_img_url($p['image_url']);
          $inStock  = ((int)($p['stock'] ?? 0)) > 0;
          $badge    = $p['badge'] ?? '';
          $bsClass  = 'sx-badge';
          if (!empty($p['badge_style'])) {
              if ($p['badge_style'] === 'terra') $bsClass .= ' sx-badge--terra';
              elseif ($p['badge_style'] === 'sage') $bsClass .= ' sx-badge--sage';
              elseif ($p['badge_style'] === 'gold') $bsClass .= ' sx-badge--gold';
          }
        ?>
          <article class="sx-card">

            <div class="sx-card__media">
              <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
                <?php if ($imgSrc): ?>
                  <img src="<?= e($imgSrc) ?>"
                       alt="<?= e($p['name']) ?>"
                       loading="lazy"
                       onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                  <div style="display:none;width:100%;height:100%;place-items:center;background:linear-gradient(135deg,#F6E8CF,#EFDDB9);color:#B76146;font-size:32px">🛕</div>
                <?php else: ?>
                  <div style="width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,#F6E8CF,#EFDDB9);color:#B76146;font-size:32px">🛕</div>
                <?php endif; ?>
              </a>

              <?php if ($badge !== ''): ?>
                <span class="<?= e($bsClass) ?>"><?= e($badge) ?></span>
              <?php endif; ?>

              <button class="sx-wish js-wish-toggle<?= $wished ? ' active' : '' ?>"
                      data-product-id="<?= $pid ?>"
                      aria-label="<?= $wished ? 'Remove from wishlist' : 'Add to wishlist' ?>"
                      aria-pressed="<?= $wished ? 'true' : 'false' ?>"
                      type="button">
                <svg viewBox="0 0 24 24">
                  <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/>
                </svg>
              </button>
            </div>

            <div class="sx-card__body">
              <?php if (!empty($p['category_name'])): ?>
                <span class="sx-card__cat"><?= e($p['category_name']) ?></span>
              <?php endif; ?>

              <h3 class="sx-card__name">
                <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
                  <?= e($p['name']) ?>
                </a>
              </h3>

              <?php if (!empty($p['short_desc'])): ?>
                <p class="sx-card__desc"><?= e($p['short_desc']) ?></p>
              <?php endif; ?>

              <div class="sx-card__rule" aria-hidden="true"></div>

              <div class="sx-card__price">
                <span class="sx-now"><?= inr($p['price']) ?></span>
                <?php if (!empty($p['mrp']) && $p['mrp'] > $p['price']): ?>
                  <span class="sx-was"><?= inr($p['mrp']) ?></span>
                <?php endif; ?>
                <?php if ($off): ?>
                  <span class="sx-off"><?= $off ?>% OFF</span>
                <?php endif; ?>
              </div>

              <div class="sx-card__cta">
                <?php if ($inStock): ?>
                  <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"
                     class="sx-btn">
                    <span>Buy Now</span>
                    <span aria-hidden="true">→</span>
                  </a>
                <?php else: ?>
                  <button class="sx-btn" type="button" disabled>
                    <span>Out of Stock</span>
                  </button>
                <?php endif; ?>
              </div>
            </div>

          </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>