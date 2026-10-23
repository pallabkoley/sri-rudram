<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  BHET IMAGE HELPER
 * ================================================================== */
if (!function_exists('bhet_front_img_url')) {
    function bhet_front_img_url(?string $stored): string {
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
if (!function_exists('bhet_front_img')) {
    function bhet_front_img(?string $stored, string $alt = '', string $class = ''): string {
        $src = bhet_front_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($class ?: 'bhet-media-ph', ENT_QUOTES) . '" '
                 . 'style="background:linear-gradient(135deg,#F6E8CF,#EFDDB9);display:grid;'
                 . 'place-items:center;color:#B76146;width:100%;height:100%;font-size:42px">'
                 . '<i class="bi bi-image"></i></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" '
             . 'alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" '
             . ($class !== '' ? 'class="' . htmlspecialchars($class, ENT_QUOTES) . '" ' : '')
             . 'loading="lazy">';
    }
}

/* ==================================================================
 |  Data
 * ================================================================== */
$typeFilter = trim((string)($_GET['type'] ?? ''));

try {
    if ($typeFilter !== '') {
        $stmt = $pdo->prepare(
            'SELECT b.id, b.name, b.slug, b.short_desc, b.price, b.image_url,
                    b.is_featured, b.sort_order,
                    t.name AS type_name, t.slug AS type_slug,
                    tp.name AS temple_name, tp.city AS temple_city, tp.state AS temple_state
             FROM bhets b
             LEFT JOIN bhet_types t  ON t.id  = b.bhet_type_id
             LEFT JOIN temples    tp ON tp.id = b.temple_id
             WHERE b.is_active = 1 AND t.slug = ?
             ORDER BY b.sort_order ASC, b.id DESC'
        );
        $stmt->execute([$typeFilter]);
        $bhets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $bhets = $pdo->query(
            'SELECT b.id, b.name, b.slug, b.short_desc, b.price, b.image_url,
                    b.is_featured, b.sort_order,
                    t.name AS type_name, t.slug AS type_slug,
                    tp.name AS temple_name, tp.city AS temple_city, tp.state AS temple_state
             FROM bhets b
             LEFT JOIN bhet_types t  ON t.id  = b.bhet_type_id
             LEFT JOIN temples    tp ON tp.id = b.temple_id
             WHERE b.is_active = 1
             ORDER BY b.sort_order ASC, b.id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    $filterTypes = $pdo->query(
        "SELECT DISTINCT t.id, t.name, t.slug
         FROM bhet_types t
         INNER JOIN bhets b ON b.bhet_type_id = t.id AND b.is_active = 1
         ORDER BY t.sort_order ASC, t.name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('[bhet] ' . $e->getMessage());
    $bhets = [];
    $filterTypes = [];
}

$totalCount = count($bhets);
$filterLabel = 'All Bhet & Daan Sevas';
if ($typeFilter !== '') {
    foreach ($filterTypes as $ft) {
        if ($ft['slug'] === $typeFilter) { $filterLabel = $ft['name']; break; }
    }
}

$pageTitle = 'Temple Bhet & Daan Sevas — Divya Aastha';
$pageDesc  = 'Offer sacred bhet and daan at revered temples across Bharat.';

require BASE_PATH . '/includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   BHET & DAAN SEVAS — Luxury Temple Marketplace
   ═══════════════════════════════════════════════════════════ */
:root{
  --bh-burgundy:#3A0E12;
  --bh-burgundy-2:#2B0A0D;
  --bh-maroon:#4B080C;
  --bh-gold:#D6A83E;
  --bh-gold-lt:#E8C66A;
  --bh-gold-dk:#B58A2B;
  --bh-cream:#F6E8CF;
  --bh-ivory:#FFF9F0;
  --bh-warm:#FAF6EF;
  --bh-text:#351B2F;
  --bh-muted:#6B7280;
  --bh-muted-2:#9CA3AF;
  --bh-line:#ECE7E1;
  --bh-line-gold:rgba(214,168,62,.32);
  --bh-terra:#B76146;
}

/* ============================================================
   HERO — subtle gold gradient + mandala watermark
   ============================================================ */
.page-hero{
  position:relative;
  overflow:hidden;
  background:
    radial-gradient(circle at 50% 0%, rgba(214,168,62,.10) 0%, transparent 55%),
    linear-gradient(180deg, #FBF3E1 0%, #F6E8CF 60%, #FBF3E1 100%) !important;
  border-bottom:1px solid rgba(214,168,62,.22);
}
.page-hero .page-hero-mandala{
  animation: bhet-mandala-spin 120s linear infinite;
  transform-origin: center center;
  will-change: transform;
  opacity:.55;
}
@keyframes bhet-mandala-spin{
  from{ transform: rotate(0deg); }
  to  { transform: rotate(360deg); }
}
@media (prefers-reduced-motion: reduce){
  .page-hero .page-hero-mandala{ animation: none; }
}
/* Ornamental gold hairline under hero */
.page-hero::after{
  content:"";
  position:absolute;
  left:12%;right:12%;bottom:-1px;
  height:1px;
  background:linear-gradient(90deg, transparent, rgba(214,168,62,.65), transparent);
  pointer-events:none;
}

/* ============================================================
   TYPES OF SEVA
   ============================================================ */
.seva-types{
  background:var(--bh-warm);
  padding:60px 0 52px;
  border-bottom:1px solid rgba(214,168,62,.14);
}
.seva-types__inner{
  max-width:1240px;
  margin:0 auto;
  padding:0 22px;
  text-align:center;
}
.seva-types__eyebrow{
  display:inline-flex;
  align-items:center;
  gap:10px;
  font-size:11px;
  font-weight:700;
  letter-spacing:2.4px;
  text-transform:uppercase;
  color:var(--bh-terra);
  margin-bottom:12px;
}
.seva-types__eyebrow::before,
.seva-types__eyebrow::after{
  content:"";
  width:22px;height:1px;
  background:var(--bh-gold);
}
.seva-types__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(1.5rem, 3.2vw, 2.4rem);
  line-height:1.2;
  color:var(--bh-text);
  margin:0 0 14px;
  font-weight:600;
  letter-spacing:-.3px;
}
.seva-types__title em{
  font-style:italic;
  color:var(--bh-terra);
  font-weight:500;
}
.seva-types__subtitle{
  color:var(--bh-muted);
  font-size:15px;
  line-height:1.65;
  max-width:620px;
  margin:0 auto 18px;
}
.seva-types__rule{
  width:70px;
  height:2px;
  border-radius:2px;
  background:linear-gradient(90deg, transparent, var(--bh-gold), var(--bh-terra), transparent);
  margin:0 auto 42px;
}
.seva-types__grid{
  display:grid;
  grid-template-columns:repeat(3, minmax(0,1fr));
  gap:20px;
  text-align:left;
}
@media(max-width:900px){
  .seva-types__grid{grid-template-columns:repeat(2, minmax(0,1fr));gap:16px}
}
@media(max-width:620px){
  .seva-types{padding:44px 0 36px}
  .seva-types__subtitle{font-size:14px}
  .seva-types__grid{grid-template-columns:1fr;gap:14px}
  .seva-types__rule{margin-bottom:28px}
}

.seva-card{
  background:var(--bh-ivory);
  border:1px solid var(--bh-line);
  border-radius:16px;
  padding:26px 22px 24px;
  transition:transform .28s cubic-bezier(.4,0,.2,1),
             border-color .28s ease,
             box-shadow .28s ease;
  box-shadow:0 1px 2px rgba(53,27,47,.04);
  display:flex;
  flex-direction:column;
  min-height:180px;
  position:relative;
  overflow:hidden;
}
.seva-card::before{
  content:"";
  position:absolute;
  top:0;left:20%;right:20%;
  height:2px;
  background:linear-gradient(90deg, transparent, var(--bh-gold), transparent);
  opacity:0;
  transition:opacity .3s;
}
.seva-card:hover{
  transform:translateY(-5px);
  border-color:var(--bh-line-gold);
  box-shadow:0 14px 32px -16px rgba(53,27,47,.22),
             0 0 0 1px rgba(214,168,62,.18);
}
.seva-card:hover::before{opacity:1}

.seva-card__icon{
  width:54px;
  height:54px;
  border-radius:14px;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg, #FBF3E1, #EFDDB9);
  border:1px solid rgba(214,168,62,.35);
  font-size:24px;
  line-height:1;
  margin-bottom:16px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.7),
             0 2px 6px -2px rgba(214,168,62,.20);
  color:var(--bh-gold-dk);
}
.seva-card__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;
  line-height:1.3;
  color:var(--bh-text);
  margin:0 0 8px;
  font-weight:600;
}
.seva-card__desc{
  color:var(--bh-muted);
  font-size:13.5px;
  line-height:1.65;
  margin:0;
  flex:1;
}

/* ============================================================
   INTRO BLOCK
   ============================================================ */
.bhet-intro{
  background:var(--bh-warm);
  padding:56px 0 44px;
  text-align:center;
}
.bhet-intro__inner{
  max-width:1240px;
  margin:0 auto;
  padding:0 22px;
}
.bhet-intro__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(1.5rem, 3vw, 2.3rem);
  line-height:1.22;
  letter-spacing:.5px;
  color:var(--bh-text);
  margin:0 0 20px;
  font-weight:600;
  text-transform:uppercase;
}
.bhet-intro__title .accent{
  color:var(--bh-gold);
  font-weight:600;
}
.bhet-intro__desc{
  color:var(--bh-muted);
  font-size:15px;
  line-height:1.82;
  max-width:840px;
  margin:0 auto 26px;
}
.bhet-intro__rule{
  width:70px;
  height:2px;
  border-radius:2px;
  background:linear-gradient(90deg, transparent, var(--bh-gold), var(--bh-terra), transparent);
  margin:0 auto;
}
@media(max-width:768px){
  .bhet-intro{padding:36px 0 30px}
  .bhet-intro__title{font-size:20px;letter-spacing:.3px;margin-bottom:14px}
  .bhet-intro__desc{font-size:13.5px;line-height:1.72;margin-bottom:20px}
}

/* ============================================================
   LISTING SECTION
   ============================================================ */
.bhet-section{
  padding:0 0 90px;
  background:var(--bh-warm);
  position:relative;
}
.bhet-container{
  max-width:1240px;
  margin:0 auto;
  padding:0 22px;
}

.bhet-filters{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-bottom:26px;
  padding-bottom:22px;
  border-bottom:1px solid rgba(214,168,62,.18);
}
.bhet-chip{
  display:inline-flex;
  align-items:center;
  padding:8px 18px;
  border-radius:99px;
  font-size:12.5px;
  font-weight:600;
  letter-spacing:.2px;
  text-decoration:none;
  transition:all .22s ease;
  background:var(--bh-ivory);
  color:var(--bh-text);
  border:1px solid var(--bh-line);
}
.bhet-chip:hover{
  border-color:var(--bh-gold);
  color:var(--bh-gold-dk);
  transform:translateY(-1px);
  box-shadow:0 4px 12px -4px rgba(214,168,62,.35);
}
.bhet-chip.active{
  background:linear-gradient(135deg, var(--bh-maroon) 0%, var(--bh-burgundy-2) 100%);
  color:var(--bh-cream);
  border-color:var(--bh-maroon);
  box-shadow:0 6px 16px -6px rgba(75,8,12,.55);
}
.bhet-chip.active::before{
  content:"";
  width:6px;height:6px;
  background:var(--bh-gold);
  border-radius:50%;
  margin-right:8px;
  box-shadow:0 0 6px rgba(214,168,62,.9);
}

.bhet-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  flex-wrap:wrap;
  gap:14px;
  margin-bottom:26px;
}
.bhet-head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:24px;
  color:var(--bh-text);
  margin:0;
  font-weight:600;
  letter-spacing:-.2px;
  position:relative;
  padding-left:16px;
}
.bhet-head h2::before{
  content:"";
  position:absolute;
  left:0;top:50%;
  transform:translateY(-50%);
  width:4px;height:22px;
  background:linear-gradient(180deg, var(--bh-gold), var(--bh-terra));
  border-radius:2px;
}
.bhet-head .count{
  color:var(--bh-muted);
  font-size:12.5px;
  padding-bottom:6px;
  letter-spacing:.2px;
}
.bhet-head .count b{color:var(--bh-maroon);font-weight:700}

/* ============================================================
   GRID
   ============================================================ */
.bhet-grid{
  display:grid;
  grid-template-columns:repeat(3, minmax(0,1fr));
  gap:26px;
  align-items:stretch;
}
@media(max-width:1024px){
  .bhet-grid{grid-template-columns:repeat(2, minmax(0,1fr));gap:20px}
}
@media(max-width:640px){
  .bhet-grid{grid-template-columns:1fr;gap:16px}
  .bhet-section{padding:0 0 60px}
  .bhet-head h2{font-size:19px}
}

/* ============================================================
   SEVA CARD — Premium
   ============================================================ */
.bhet-card{
  background:var(--bh-ivory);
  border:1px solid var(--bh-line);
  border-radius:16px;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  height:100%;
  box-shadow:0 1px 3px rgba(53,27,47,.05),
             0 1px 2px rgba(53,27,47,.03);
  transition:transform .32s cubic-bezier(.4,0,.2,1),
             box-shadow .32s cubic-bezier(.4,0,.2,1),
             border-color .32s cubic-bezier(.4,0,.2,1);
  position:relative;
}
.bhet-card::before{
  content:"";
  position:absolute;
  top:0;left:20%;right:20%;
  height:2px;
  background:linear-gradient(90deg, transparent, var(--bh-gold), transparent);
  opacity:0;
  transition:opacity .35s ease;
  z-index:3;
}
.bhet-card:hover{
  transform:translateY(-6px);
  border-color:var(--bh-line-gold);
  box-shadow:0 20px 42px -20px rgba(53,27,47,.32),
             0 0 0 1px rgba(214,168,62,.25);
}
.bhet-card:hover::before{opacity:1}

/* ---------- Media 16:9 ---------- */
.bhet-media{
  position:relative;
  width:100%;
  aspect-ratio:16/9;
  overflow:hidden;
  background:linear-gradient(135deg, var(--bh-cream), #EFDDB9);
  flex-shrink:0;
}
.bhet-media img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
  transition:transform .6s cubic-bezier(.4,0,.2,1);
}
.bhet-card:hover .bhet-media img{transform:scale(1.06)}

/* Soft dark overlay at top for badge readability */
.bhet-media::after{
  content:"";
  position:absolute;
  top:0;left:0;right:0;height:70px;
  background:linear-gradient(180deg, rgba(49,5,8,.22), transparent);
  pointer-events:none;
  z-index:1;
}

/* Featured badge — gold gradient */
.bhet-media__badge{
  position:absolute;
  top:12px;left:12px;
  z-index:2;
  display:inline-flex;
  align-items:center;
  gap:5px;
  padding:5px 11px;
  background:linear-gradient(135deg, var(--bh-gold), var(--bh-gold-dk));
  color:var(--bh-burgundy-2);
  font-size:9.5px;
  font-weight:800;
  letter-spacing:.7px;
  text-transform:uppercase;
  border-radius:99px;
  border:1px solid #8E6A1F;
  box-shadow:0 4px 10px -2px rgba(49,5,8,.32);
}

/* Type badge — burgundy */
.bhet-media__type{
  position:absolute;
  bottom:12px;left:12px;
  z-index:2;
  padding:5px 11px;
  background:rgba(58,14,18,.90);
  backdrop-filter:blur(6px);
  color:var(--bh-cream);
  font-size:10px;
  font-weight:700;
  letter-spacing:.6px;
  text-transform:uppercase;
  border-radius:6px;
  border:1px solid rgba(214,168,62,.40);
}

/* Location badge — ivory */
.bhet-media__loc{
  position:absolute;
  bottom:12px;right:12px;
  z-index:2;
  padding:5px 11px;
  background:rgba(255,249,240,.96);
  color:var(--bh-maroon);
  font-size:11px;
  font-weight:600;
  border-radius:6px;
  border:1px solid rgba(214,168,62,.30);
}

/* ---------- Body ---------- */
.bhet-body{
  padding:18px 18px 18px;
  display:flex;
  flex-direction:column;
  gap:9px;
  flex:1;
}

.bhet-temple{
  font-size:10px;
  font-weight:700;
  letter-spacing:1.3px;
  text-transform:uppercase;
  color:var(--bh-gold-dk);
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  flex-shrink:0;
}

/* Title — fixed 2 lines */
.bhet-title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16px;
  line-height:1.36;
  color:var(--bh-text);
  margin:0;
  font-weight:600;
  height:44px;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  text-overflow:ellipsis;
  flex-shrink:0;
}
.bhet-title a{
  color:inherit;
  text-decoration:none;
  transition:color .22s;
}
.bhet-title a:hover{color:var(--bh-terra)}

/* Description — fixed 2 lines */
.bhet-desc{
  color:var(--bh-muted);
  font-size:12.5px;
  line-height:1.58;
  margin:0;
  height:40px;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  text-overflow:ellipsis;
  flex-shrink:0;
}

/* Footer — price + CTA */
.bhet-foot{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  padding-top:14px;
  margin-top:auto;
  border-top:1px solid rgba(214,168,62,.22);
  flex-shrink:0;
}
.bhet-price{
  display:flex;
  flex-direction:column;
  line-height:1.1;
  min-width:0;
}
.bhet-price .from{
  font-size:9.5px;
  color:var(--bh-muted-2);
  letter-spacing:.9px;
  text-transform:uppercase;
  font-weight:700;
}
.bhet-price .amt{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:21px;
  font-weight:700;
  color:var(--bh-maroon);
  margin-top:3px;
  letter-spacing:-.3px;
}

/* CTA Button */
.bhet-btn{
  position:relative;
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:10px 16px;
  font-size:11.5px;
  font-weight:700;
  letter-spacing:.7px;
  text-transform:uppercase;
  border-radius:9px;
  background:linear-gradient(135deg, var(--bh-maroon) 0%, var(--bh-burgundy-2) 100%);
  color:var(--bh-cream);
  text-decoration:none;
  border:1px solid var(--bh-maroon);
  white-space:nowrap;
  overflow:hidden;
  transition:all .28s cubic-bezier(.4,0,.2,1);
  box-shadow:0 4px 12px -6px rgba(49,5,8,.5);
  font-family:inherit;
}
.bhet-btn::before{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(135deg, rgba(214,168,62,.40), rgba(214,168,62,.15));
  opacity:0;
  transition:opacity .28s;
}
.bhet-btn > *{position:relative;z-index:1}
.bhet-btn:hover{
  color:#FFF3D2;
  border-color:var(--bh-gold);
  box-shadow:0 8px 22px -8px rgba(214,168,62,.60),
             inset 0 0 0 1px rgba(214,168,62,.40);
  transform:translateY(-1px);
}
.bhet-btn:hover::before{opacity:1}
.bhet-btn svg{
  width:13px;height:13px;
  transition:transform .28s cubic-bezier(.4,0,.2,1);
}
.bhet-btn:hover svg{transform:translateX(3px)}

/* ============================================================
   EMPTY STATE
   ============================================================ */
.bhet-empty{
  text-align:center;
  padding:80px 22px;
  background:var(--bh-ivory);
  border:1px solid var(--bh-line);
  border-radius:16px;
}
.bhet-empty .ico{
  font-size:56px;
  color:var(--bh-gold);
  margin-bottom:16px;
  line-height:1;
  filter:grayscale(.2);
}
.bhet-empty h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:22px;
  color:var(--bh-text);
  margin:0 0 8px;
  font-weight:600;
}
.bhet-empty p{margin:0 0 20px;font-size:14px;color:var(--bh-muted);line-height:1.6}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media(max-width:520px){
  .bhet-body{padding:14px 14px 14px;gap:7px}
  .bhet-title{font-size:15px;height:42px}
  .bhet-desc{font-size:12px;height:38px}
  .bhet-price .amt{font-size:18px}
  .bhet-btn{padding:9px 13px;font-size:11px}
  .bhet-foot{padding-top:12px;gap:8px}
}
</style>

<!-- ═══════════════ HERO — site common pattern ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Bhet &amp; Daan</span>
    </nav>
    <span class="eyebrow">Sacred Offerings</span>
    <h1>Temple Bhet &amp; Daan Sevas</h1>
    <p>Offer sacred bhet and daan at revered temples across Bharat with complete transparency and temple acknowledgment. Every seva is performed by verified priests and delivered with divine blessings.</p>
  </div>
</section>

<!-- ═══════════════ TYPES OF SEVA ═══════════════ -->
<section class="seva-types">
  <div class="seva-types__inner">
    <span class="seva-types__eyebrow">Sacred Categories</span>
    <h2 class="seva-types__title">Types of <em>Seva</em></h2>
    <p class="seva-types__subtitle">
      Gau Seva, Anna Daan, and sacred temple offerings for spiritual merit.
    </p>
    <div class="seva-types__rule" aria-hidden="true"></div>

    <div class="seva-types__grid">
      <article class="seva-card">
        <div class="seva-card__icon" aria-hidden="true">🙏</div>
        <h3 class="seva-card__title">Gau Seva</h3>
        <p class="seva-card__desc">
          Serve the divine cow and earn blessings through this sacred act of
          selfless giving.
        </p>
      </article>

      <article class="seva-card">
        <div class="seva-card__icon" aria-hidden="true">🙏</div>
        <h3 class="seva-card__title">Anna Daan</h3>
        <p class="seva-card__desc">
          Offer food to the needy and temples — one of the highest forms of
          charity in Sanatan Dharma.
        </p>
      </article>

      <article class="seva-card">
        <div class="seva-card__icon" aria-hidden="true">🙏</div>
        <h3 class="seva-card__title">Temple Offerings</h3>
        <p class="seva-card__desc">
          Send flowers, sweets, abhishek items and sacred offerings to temples
          on your behalf.
        </p>
      </article>
    </div>
  </div>
</section>

<!-- ═══════════════ INTRO BLOCK ═══════════════ -->
<section class="bhet-intro">
  <div class="bhet-intro__inner">
    <h2 class="bhet-intro__title">
      Temple <span class="accent">Bhet</span> &amp;
      <span class="accent">Daan</span> Sevas
    </h2>
    <p class="bhet-intro__desc">
      Send sacred offerings (Bhet &amp; Daan) directly to your Isht Devta or top
      temples across India through Srirudram's authentic Vedic rituals. Choose
      from a wide range of spiritual offerings like flowers, sweets, abhishek
      items, gold, chunri, and more — all aligned with your specific life
      problems such as health, finances, relationships, or career growth.
      Schedule offerings on auspicious tithis and festivals.
    </p>
    <div class="bhet-intro__rule" aria-hidden="true"></div>
  </div>
</section>

<!-- ═══════════════ LISTING ═══════════════ -->
<section class="bhet-section">
  <div class="bhet-container">

    <?php if (!empty($filterTypes)): ?>
      <div class="bhet-filters">
        <a href="<?= url('bhet.php') ?>"
           class="bhet-chip<?= $typeFilter === '' ? ' active' : '' ?>">
          All Sevas
        </a>
        <?php foreach ($filterTypes as $ft): ?>
          <a href="<?= url('bhet.php?type=' . urlencode($ft['slug'])) ?>"
             class="bhet-chip<?= $typeFilter === $ft['slug'] ? ' active' : '' ?>">
            <?= e($ft['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (empty($bhets)): ?>
      <div class="bhet-empty">
        <div class="ico">🛕</div>
        <h3>No Bhets Available<?= $typeFilter !== '' ? ' in this category' : ' Yet' ?></h3>
        <p>New sacred offerings are being added. Please check back soon.</p>
        <?php if ($typeFilter !== ''): ?>
          <a href="<?= url('bhet.php') ?>" class="bhet-btn" style="display:inline-flex">
            <span>View All Bhets</span>
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="bhet-head">
        <h2><?= $typeFilter !== '' ? e($filterLabel) : 'Available Sevas' ?></h2>
        <span class="count">
          Showing <b><?= (int) $totalCount ?></b>
          <?= $totalCount === 1 ? 'seva' : 'sevas' ?>
        </span>
      </div>

      <div class="bhet-grid">
        <?php foreach ($bhets as $b): ?>
          <article class="bhet-card">
            <div class="bhet-media">
              <?= bhet_front_img($b['image_url'], $b['name']) ?>

              <?php if (!empty($b['is_featured'])): ?>
                <span class="bhet-media__badge">★ Featured</span>
              <?php endif; ?>

              <?php if (!empty($b['type_name'])): ?>
                <span class="bhet-media__type"><?= e($b['type_name']) ?></span>
              <?php endif; ?>

              <?php if (!empty($b['temple_city'])): ?>
                <span class="bhet-media__loc">📍 <?= e($b['temple_city']) ?></span>
              <?php endif; ?>
            </div>

            <div class="bhet-body">
              <?php if (!empty($b['temple_name'])): ?>
                <div class="bhet-temple"><?= e($b['temple_name']) ?></div>
              <?php endif; ?>

              <h3 class="bhet-title">
                <a href="<?= url('bhet-detail.php?slug=' . urlencode($b['slug'])) ?>">
                  <?= e($b['name']) ?>
                </a>
              </h3>

              <?php if (!empty($b['short_desc'])): ?>
                <p class="bhet-desc"><?= e($b['short_desc']) ?></p>
              <?php endif; ?>

              <div class="bhet-foot">
                <div class="bhet-price">
                  <span class="from">From</span>
                  <span class="amt"><?= inr($b['price']) ?></span>
                </div>
                <a href="<?= url('bhet-detail.php?slug=' . urlencode($b['slug'])) ?>"
                   class="bhet-btn">
                  <span>Offer Bhet</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                  </svg>
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>