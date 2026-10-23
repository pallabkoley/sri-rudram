<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  SAFE IMAGE HELPERS
 * ================================================================== */
if (!function_exists('safe_img_url')) {
    function safe_img_url(?string $stored): string {
        if (!$stored) return '';
        $stored = ltrim(trim((string)$stored), '/');
        if (preg_match('#^https?://#i', $stored)) return $stored;
        if (strpos($stored, 'assets/img/') === 0) {
            $stored = substr($stored, strlen('assets/img/'));
        }
        $marker = 'assets/img/';
        $pos = strpos($stored, $marker);
        if ($pos !== false) $stored = substr($stored, $pos + strlen($marker));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . $stored;
    }
}
if (!function_exists('safe_img')) {
    function safe_img(?string $stored, string $alt = '', string $class = '', bool $lazy = true): string {
        $src = safe_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($class ?: 'img-ph', ENT_QUOTES) . '" '
                 . 'style="background:linear-gradient(135deg,#F6E8CF,#EFDDB9);display:grid;'
                 . 'place-items:center;color:#B76146;min-height:200px;font-size:42px">'
                 . '<i class="bi bi-flower1"></i></div>';
        }
        $lazyAttr = $lazy ? ' loading="lazy"' : '';
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="'
             . htmlspecialchars($alt, ENT_QUOTES) . '"'
             . ($class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '')
             . $lazyAttr . '>';
    }
}

/* ==================================================================
 |  AJAX
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require BASE_PATH . '/includes/handlers/cart-handler.php';
    require BASE_PATH . '/includes/handlers/puja-book-handler.php';
}

/* ==================================================================
 |  Fetch puja
 * ================================================================== */
$slug = trim((string)($_GET['slug'] ?? ''));
$puja = get_puja_by_slug($slug);

if (!$puja) {
    http_response_code(404);
    require BASE_PATH . '/404.php';
    exit;
}

$pujaId = (int)$puja['id'];

/* ==================================================================
 |  Load all related data
 * ================================================================== */
$temple = null;
if (!empty($puja['temple_id'])) {
    try {
        $st = $pdo->prepare('SELECT * FROM temples WHERE id = ? AND is_active = 1 LIMIT 1');
        $st->execute([(int)$puja['temple_id']]);
        $temple = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {}
}

$benefits = [];
try {
    $st = $pdo->prepare('SELECT id, title, description, icon FROM puja_benefits
                         WHERE puja_id = ? AND is_active = 1
                         ORDER BY sort_order ASC, id ASC');
    $st->execute([$pujaId]);
    $benefits = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$procedures = [];
try {
    $st = $pdo->prepare('SELECT id, step_number, title, description, image_url FROM puja_procedures
                         WHERE puja_id = ? AND is_active = 1
                         ORDER BY step_number ASC, id ASC');
    $st->execute([$pujaId]);
    $procedures = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$packages = [];
try {
    $st = $pdo->prepare('SELECT id, name, slug, short_desc, price, compare_price,
                                included_items, badge, is_popular
                         FROM puja_packages
                         WHERE puja_id = ? AND is_active = 1
                         ORDER BY is_popular DESC, sort_order ASC, id ASC');
    $st->execute([$pujaId]);
    $packages = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$faqs = [];
try {
    $st = $pdo->prepare('SELECT id, question, answer FROM puja_faqs
                         WHERE puja_id = ? AND is_active = 1
                         ORDER BY sort_order ASC, id ASC');
    $st->execute([$pujaId]);
    $faqs = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$reviews = [];
$avgRating = 0.0;
$reviewCount = 0;
try {
    $st = $pdo->prepare('SELECT id, reviewer_name, rating, review, created_at
                         FROM puja_reviews
                         WHERE puja_id = ? AND is_approved = 1
                         ORDER BY created_at DESC
                         LIMIT 12');
    $st->execute([$pujaId]);
    $reviews = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare('SELECT AVG(rating) AS avg_r, COUNT(*) AS c
                         FROM puja_reviews WHERE puja_id = ? AND is_approved = 1');
    $st->execute([$pujaId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['c'] > 0) {
        $avgRating = round((float)$row['avg_r'], 1);
        $reviewCount = (int)$row['c'];
    }
} catch (Throwable $e) {}

$relatedPujas = [];
try {
    $st = $pdo->prepare(
        'SELECT p.id, p.name, p.slug, p.short_desc, p.price, p.image_url,
                t.name AS temple_name
         FROM puja_related pr
         INNER JOIN pujas p ON p.id = pr.related_puja_id AND p.is_active = 1
         LEFT JOIN temples t ON t.id = p.temple_id
         WHERE pr.puja_id = ?
         ORDER BY pr.sort_order ASC
         LIMIT 4'
    );
    $st->execute([$pujaId]);
    $relatedPujas = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

if (empty($relatedPujas)) {
    try {
        $st = $pdo->prepare(
            'SELECT p.id, p.name, p.slug, p.short_desc, p.price, p.image_url,
                    t.name AS temple_name
             FROM pujas p
             LEFT JOIN temples t ON t.id = p.temple_id
             WHERE p.is_active = 1 AND p.id <> ?
             ORDER BY CASE WHEN p.temple_id = ? THEN 0 ELSE 1 END,
                      p.is_popular DESC, p.sort_order ASC
             LIMIT 4'
        );
        $st->execute([$pujaId, (int)($puja['temple_id'] ?? 0)]);
        $relatedPujas = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

/* Baseline price */
$basePrice        = (float)$puja['price'];
$defaultPrice     = $basePrice;
$defaultPackageId = 0;
if (!empty($packages)) {
    foreach ($packages as $pk) {
        if (!empty($pk['is_popular'])) {
            $defaultPrice     = (float)$pk['price'];
            $defaultPackageId = (int)$pk['id'];
            break;
        }
    }
    if ($defaultPackageId === 0 && !empty($packages[0]['id'])) {
        $defaultPrice     = (float)$packages[0]['price'];
        $defaultPackageId = (int)$packages[0]['id'];
    }
}

/* Hero BG */
$heroImg   = safe_img_url($puja['image_url'] ?? '');
$heroStyle = $heroImg !== '' ? "background-image:url('" . htmlspecialchars($heroImg, ENT_QUOTES) . "');" : '';

/* Build tab list (only tabs with content) */
$tabs = [];
if (!empty($puja['description'])) {
    $tabs['about'] = 'About Puja';
}
if ($temple) {
    $tabs['temple'] = 'Temple Details';
}
if (!empty($benefits)) {
    $tabs['benefits'] = 'Benefits';
}
if (!empty($procedures)) {
    $tabs['process'] = 'Process';
}
if (!empty($packages)) {
    $tabs['packages'] = 'Packages';
}
if (!empty($reviews)) {
    $tabs['reviews'] = 'Reviews';
}
if (!empty($faqs)) {
    $tabs['faqs'] = 'FAQs';
}
$tabs['book'] = 'Book Now';   // Always available

/* Default tab — first available */
$defaultTab = array_key_first($tabs);

$pageTitle = $puja['name'] . ' — Book Online Puja';
$pageDesc  = $puja['short_desc'] ?? '';
require BASE_PATH . '/includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   PUJA DETAIL — Tab-based content switching
   ═══════════════════════════════════════════════════════════ */
:root{
  --pd-maroon:#4B080C;
  --pd-burgundy:#310508;
  --pd-gold:#D6A83E;
  --pd-gold-lt:#E8C66A;
  --pd-gold-dk:#B58A2B;
  --pd-cream:#F6E8CF;
  --pd-ivory:#FFF9F0;
  --pd-warm:#FAF6EF;
  --pd-text:#351B2F;
  --pd-muted:#6B7280;
  --pd-muted-2:#9CA3AF;
  --pd-line:#EFE6D9;
  --pd-line-gold:rgba(214,168,62,.32);
  --pd-terra:#B76146;
}

/* ───────── HERO ───────── */
.pd-hero{
  position:relative;overflow:hidden;
  background-color:#2B1220;background-size:cover;background-position:center;
  background-repeat:no-repeat;min-height:220px;display:flex;align-items:center;
}
.pd-hero__overlay{
  position:absolute;inset:0;pointer-events:none;
  background:
    linear-gradient(90deg,
      rgba(43,18,32,.94) 0%,rgba(43,18,32,.82) 35%,
      rgba(43,18,32,.55) 65%,rgba(43,18,32,.30) 100%),
    radial-gradient(circle at 20% 60%,rgba(183,97,70,.20) 0%,transparent 55%);
}
.pd-hero__gold{
  position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,
    rgba(212,175,55,.55) 20%,rgba(212,175,55,.95) 50%,
    rgba(212,175,55,.55) 80%,transparent);
}
.pd-hero__inner{position:relative;z-index:2;width:100%;padding:42px 0 38px}
.pd-hero .breadcrumb{margin:0;color:rgba(247,235,221,.85);font-size:14px}
.pd-hero .breadcrumb a{color:rgba(247,235,221,.90);text-decoration:none;transition:color .15s}
.pd-hero .breadcrumb a:hover{color:var(--pd-gold)}
.pd-hero .breadcrumb .sep{color:rgba(247,235,221,.45);margin:0 10px}
.pd-hero .breadcrumb .current{color:#F7EBDD;font-weight:600}
@media(max-width:768px){
  .pd-hero{min-height:150px;background-position:center 30%}
  .pd-hero__inner{padding:28px 0 24px}
  .pd-hero .breadcrumb{font-size:12.5px}
}

/* ───────── LAYOUT ───────── */
.pd-wrap{max-width:1200px;margin:0 auto;padding:0 22px}
.pd-section{padding:60px 0}
.pd-section--white{background:#fff}
.pd-section--warm{background:var(--pd-warm)}

/* ───────── MAIN — Two Column ───────── */
.pd-main{padding:44px 0 16px;background:var(--pd-warm)}
.pd-main-grid{display:grid;grid-template-columns:1.05fr 1fr;gap:38px;align-items:flex-start}
@media(max-width:900px){.pd-main-grid{grid-template-columns:1fr;gap:24px}}

.pd-media{
  position:relative;border-radius:18px;overflow:hidden;
  background:linear-gradient(135deg,var(--pd-cream),#EFDDB9);
  aspect-ratio:4/3;
  border:1px solid rgba(214,168,62,.32);
  box-shadow:0 16px 44px -20px rgba(53,27,47,.32);
}
.pd-media img{width:100%;height:100%;object-fit:cover;display:block}
.pd-media .pd-badge{
  position:absolute;top:16px;left:16px;
  padding:7px 14px;font-size:10.5px;font-weight:800;letter-spacing:.8px;
  text-transform:uppercase;border-radius:99px;
  background:linear-gradient(135deg,var(--pd-gold),var(--pd-gold-dk));
  color:var(--pd-burgundy);border:1px solid #8E6A1F;
  box-shadow:0 4px 12px -3px rgba(49,5,8,.35);
}

.pd-info{display:flex;flex-direction:column;gap:14px}
.pd-cat-badge{
  display:inline-flex;align-items:center;gap:6px;align-self:flex-start;
  padding:5px 13px;font-size:10.5px;font-weight:800;letter-spacing:1.2px;
  text-transform:uppercase;border-radius:99px;
  background:linear-gradient(135deg,#FFF3D2,#F6E8CF);
  color:#8A6A18;border:1px solid rgba(214,168,62,.45);
}
.pd-title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(24px,3.2vw,34px);line-height:1.18;
  color:var(--pd-text);margin:0;font-weight:600;letter-spacing:-.3px;
}
.pd-meta{display:flex;flex-wrap:wrap;gap:8px}
.pd-meta span{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 13px;font-size:12.5px;
  color:var(--pd-text);background:var(--pd-ivory);
  border:1px solid var(--pd-line);border-radius:99px;
}
.pd-meta span svg{width:13px;height:13px;color:var(--pd-gold-dk);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.pd-meta span b{color:var(--pd-maroon)}
.pd-short{color:var(--pd-muted);font-size:14.5px;line-height:1.7;margin:0}

.pd-price-block{
  display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;
  padding:16px 18px;
  background:linear-gradient(135deg,#FFF9F0,#FBF3E1);
  border:1px solid var(--pd-line-gold);border-radius:14px;
}
.pd-price-label{font-size:10px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:var(--pd-gold-dk)}
.pd-price-value{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:32px;font-weight:700;color:var(--pd-maroon);line-height:1;letter-spacing:-.5px;
}
.pd-price-value .cur{font-size:22px;margin-right:2px}
.pd-price-note{font-size:12px;color:var(--pd-muted);margin-left:auto;text-align:right}
.pd-price-note b{color:var(--pd-maroon)}

.pd-includes{display:flex;flex-direction:column;gap:8px}
.pd-includes h4{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:15px;color:var(--pd-text);margin:0;font-weight:600;
}
.pd-includes ul{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:8px}
.pd-includes li{
  display:flex;align-items:flex-start;gap:10px;
  font-size:13px;color:#4B5563;line-height:1.55;
}
.pd-includes li::before{
  content:"✓";display:grid;place-items:center;
  flex-shrink:0;width:18px;height:18px;margin-top:1px;
  background:linear-gradient(135deg,var(--pd-gold),var(--pd-gold-dk));
  color:#fff;font-size:11px;font-weight:900;border-radius:50%;
}

.pd-cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:6px}
.pd-cta .pd-btn{flex:1;min-width:180px}
.pd-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:14px 22px;font-size:14px;font-weight:700;letter-spacing:.4px;
  border-radius:11px;text-decoration:none;cursor:pointer;
  font-family:inherit;transition:all .25s cubic-bezier(.4,0,.2,1);
  border:1px solid transparent;
}
.pd-btn-primary{
  background:linear-gradient(135deg,var(--pd-maroon),var(--pd-burgundy));
  color:var(--pd-cream);border-color:var(--pd-maroon);
  box-shadow:0 8px 22px -10px rgba(75,8,12,.6);
}
.pd-btn-primary:hover{
  color:#FFF3D2;border-color:var(--pd-gold);
  box-shadow:0 10px 26px -8px rgba(214,168,62,.55);
  transform:translateY(-2px);
}
.pd-btn-ghost{
  background:transparent;color:var(--pd-maroon);
  border:1.5px solid rgba(75,8,12,.35);
}
.pd-btn-ghost:hover{border-color:var(--pd-gold);color:var(--pd-gold-dk);background:rgba(214,168,62,.08)}
@media(max-width:520px){.pd-cta{flex-direction:column}.pd-cta .pd-btn{width:100%;flex:none}}

.pd-trust{
  display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;
  padding-top:14px;border-top:1px solid var(--pd-line);
}
.pd-trust span{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--pd-muted);font-weight:600}
.pd-trust svg{width:14px;height:14px;color:var(--pd-gold-dk);flex-shrink:0}
.pd-trust svg *{fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

/* ═══════════════════════════════════════════
   TAB BAR — sticky tab navigation
   ═══════════════════════════════════════════ */
.pd-tabs{
  position:sticky;
  top:0;
  z-index:40;
  background:var(--pd-ivory);
  border-top:1px solid var(--pd-line);
  border-bottom:1px solid var(--pd-line);
  box-shadow:0 2px 8px -4px rgba(53,27,47,.08);
}
.pd-tabs__inner{
  max-width:1200px;margin:0 auto;padding:0 22px;
  display:flex;gap:4px;overflow-x:auto;
  scrollbar-width:none;-ms-overflow-style:none;
}
.pd-tabs__inner::-webkit-scrollbar{display:none}

.pd-tab{
  flex-shrink:0;
  display:inline-flex;align-items:center;gap:6px;
  padding:16px 18px;font-size:13.5px;font-weight:600;
  color:var(--pd-muted);text-decoration:none;
  background:transparent;border:none;cursor:pointer;
  border-bottom:2px solid transparent;
  transition:all .2s ease;white-space:nowrap;
  font-family:inherit;
  position:relative;
}
.pd-tab:hover{color:var(--pd-terra);background:rgba(214,168,62,.05)}
.pd-tab:focus-visible{
  outline:2px solid var(--pd-gold);
  outline-offset:-4px;
  border-radius:6px;
}
.pd-tab[aria-selected="true"]{
  color:var(--pd-terra);
  border-bottom-color:var(--pd-terra);
  font-weight:700;
}
@media(max-width:640px){
  .pd-tabs__inner{padding:0 12px}
  .pd-tab{padding:14px 12px;font-size:12.5px}
}

/* ═══════════════════════════════════════════
   TAB PANELS — only one visible at a time
   ═══════════════════════════════════════════ */
.pd-panel{display:none}
.pd-panel.is-active{display:block}

.pd-panel .pd-section{padding:60px 0}

/* ───────── SECTION HEAD ───────── */
.pd-head{text-align:center;max-width:720px;margin:0 auto 40px}
.pd-head .eyebrow{
  display:inline-flex;align-items:center;gap:10px;
  font-size:11px;font-weight:700;letter-spacing:2.4px;
  text-transform:uppercase;color:var(--pd-terra);margin-bottom:12px;
}
.pd-head .eyebrow::before,
.pd-head .eyebrow::after{content:"";display:inline-block;width:22px;height:1px;background:var(--pd-gold)}
.pd-head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(22px,2.8vw,30px);line-height:1.2;
  color:var(--pd-text);margin:0 0 12px;font-weight:600;letter-spacing:-.3px;
}
.pd-head .line{
  width:70px;height:2px;margin:0 auto 14px;
  background:linear-gradient(90deg,transparent,var(--pd-gold),var(--pd-terra),transparent);
}
.pd-head p{color:var(--pd-muted);font-size:14.5px;line-height:1.7;margin:0}

/* ───────── ABOUT PUJA ───────── */
.pd-about-wrap{
  max-width:900px;margin:0 auto;
  background:#fff;border:1px solid var(--pd-line);
  border-radius:18px;padding:32px 38px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
}
@media(max-width:640px){.pd-about-wrap{padding:22px 20px;border-radius:14px}}
.pd-about-wrap p{color:#4B5563;font-size:15px;line-height:1.85;margin:0 0 14px}
.pd-about-wrap p:last-child{margin-bottom:0}
.pd-about-wrap h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;color:var(--pd-text);margin:20px 0 10px;font-weight:600;
}
.pd-about-wrap h3:first-child{margin-top:0}
.pd-readmore-wrap{
  position:relative;max-height:260px;overflow:hidden;
  transition:max-height .5s cubic-bezier(.4,0,.2,1);
}
.pd-readmore-wrap::after{
  content:"";position:absolute;left:0;right:0;bottom:0;height:80px;
  background:linear-gradient(180deg,transparent,var(--pd-warm) 85%);
  pointer-events:none;transition:opacity .3s;
}
.pd-readmore-wrap.expanded{max-height:5000px}
.pd-readmore-wrap.expanded::after{opacity:0}
.pd-readmore-btn{
  display:inline-flex;align-items:center;gap:6px;
  margin-top:14px;padding:10px 20px;
  font-size:13px;font-weight:700;letter-spacing:.4px;
  color:var(--pd-terra);background:transparent;
  border:1.5px solid var(--pd-terra);border-radius:99px;
  cursor:pointer;transition:all .22s;font-family:inherit;
}
.pd-readmore-btn:hover{background:var(--pd-terra);color:#fff}

/* ───────── TEMPLE ───────── */
.pd-temple-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:38px;align-items:center}
@media(max-width:820px){.pd-temple-grid{grid-template-columns:1fr;gap:24px}}
.pd-temple-media{
  position:relative;border-radius:16px;overflow:hidden;
  aspect-ratio:4/3;background:linear-gradient(135deg,var(--pd-cream),#EFDDB9);
  border:1px solid var(--pd-line-gold);
  box-shadow:0 14px 36px -20px rgba(53,27,47,.30);
}
.pd-temple-media img{width:100%;height:100%;object-fit:cover;display:block}
.pd-temple-info h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:24px;color:var(--pd-text);margin:0 0 8px;font-weight:600;
}
.pd-temple-loc{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--pd-muted);margin-bottom:14px}
.pd-temple-loc svg{width:14px;height:14px;color:var(--pd-gold-dk);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.pd-temple-desc{color:#4B5563;font-size:14.5px;line-height:1.78;margin:0 0 16px}
.pd-temple-timing{
  display:flex;flex-direction:column;gap:6px;
  padding:14px 16px;margin-top:6px;
  background:linear-gradient(135deg,#FBF3E1,#F6E8CF);
  border:1px solid rgba(214,168,62,.32);border-radius:10px;
  font-size:12.5px;color:#6B4A3E;line-height:1.45;
}
.pd-temple-timing__row{display:flex;align-items:flex-start;gap:8px}
.pd-temple-timing__row svg{
  width:13px;height:13px;flex-shrink:0;margin-top:2px;
  color:var(--pd-gold-dk);fill:none;stroke:currentColor;
  stroke-width:2;stroke-linecap:round;stroke-linejoin:round;
}

/* ───────── BENEFITS ───────── */
.pd-benefits{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
@media(max-width:900px){.pd-benefits{grid-template-columns:repeat(2,1fr);gap:16px}}
@media(max-width:520px){.pd-benefits{grid-template-columns:1fr;gap:14px}}
.pd-benefit{
  background:var(--pd-ivory);border:1px solid var(--pd-line);
  border-radius:14px;padding:24px 22px;
  display:flex;flex-direction:column;gap:12px;
  transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease;
  position:relative;overflow:hidden;
}
.pd-benefit::before{
  content:"";position:absolute;top:0;left:20%;right:20%;height:2px;
  background:linear-gradient(90deg,transparent,var(--pd-gold),transparent);
  opacity:0;transition:opacity .3s;
}
.pd-benefit:hover{transform:translateY(-4px);border-color:var(--pd-line-gold);box-shadow:0 14px 32px -18px rgba(53,27,47,.22)}
.pd-benefit:hover::before{opacity:1}
.pd-benefit__ic{
  width:48px;height:48px;border-radius:12px;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#FBF3E1,#EFDDB9);
  border:1px solid rgba(214,168,62,.32);
  color:var(--pd-gold-dk);font-size:22px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.6);
}
.pd-benefit__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16px;color:var(--pd-text);margin:0;font-weight:600;line-height:1.35;
}
.pd-benefit__desc{color:var(--pd-muted);font-size:13px;line-height:1.62;margin:0}

/* ───────── PROCEDURE ───────── */
.pd-steps{max-width:840px;margin:0 auto;position:relative}
.pd-steps::before{
  content:"";position:absolute;left:22px;top:16px;bottom:16px;width:2px;
  background:linear-gradient(180deg,var(--pd-gold),var(--pd-terra));
  opacity:.35;
}
@media(max-width:640px){.pd-steps::before{left:18px}}
.pd-step{position:relative;padding-left:70px;margin-bottom:24px}
.pd-step:last-child{margin-bottom:0}
@media(max-width:640px){.pd-step{padding-left:56px}}
.pd-step__num{
  position:absolute;left:0;top:0;
  width:46px;height:46px;border-radius:50%;
  display:grid;place-items:center;
  font-family:var(--font-serif,Georgia,serif);
  font-weight:700;font-size:17px;color:var(--pd-cream);
  background:linear-gradient(135deg,var(--pd-maroon),var(--pd-burgundy));
  border:2px solid var(--pd-gold);
  box-shadow:0 6px 16px -6px rgba(75,8,12,.5);
  z-index:2;
}
@media(max-width:640px){.pd-step__num{width:38px;height:38px;font-size:14px}}
.pd-step__body{
  background:var(--pd-ivory);border:1px solid var(--pd-line);
  border-radius:14px;padding:20px 22px;
  transition:border-color .25s ease,box-shadow .25s ease,transform .25s ease;
}
.pd-step:hover .pd-step__body{
  border-color:var(--pd-line-gold);
  box-shadow:0 12px 26px -14px rgba(53,27,47,.22);
  transform:translateX(4px);
}
.pd-step__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16px;color:var(--pd-text);margin:0 0 8px;font-weight:600;
}
.pd-step__desc{color:var(--pd-muted);font-size:13.5px;line-height:1.68;margin:0}
.pd-step__img{margin-top:14px;border-radius:10px;overflow:hidden;aspect-ratio:16/9;background:#F3E8D8}
.pd-step__img img{width:100%;height:100%;object-fit:cover;display:block}

/* ───────── PACKAGES ───────── */
.pd-packages{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
@media(max-width:900px){.pd-packages{grid-template-columns:repeat(2,1fr);gap:18px}}
@media(max-width:560px){.pd-packages{grid-template-columns:1fr;gap:16px}}
.pd-pkg{
  position:relative;background:var(--pd-ivory);
  border:2px solid var(--pd-line);border-radius:16px;
  padding:24px 22px;cursor:pointer;
  transition:all .25s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;gap:12px;
}
.pd-pkg:hover{border-color:var(--pd-line-gold);transform:translateY(-3px)}
.pd-pkg.is-selected{
  border-color:var(--pd-gold);
  box-shadow:0 14px 32px -16px rgba(214,168,62,.55);
  background:linear-gradient(180deg,#FFF9F0,#FBF3E1);
}
.pd-pkg input[type="radio"]{position:absolute;opacity:0;pointer-events:none}
.pd-pkg__top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding-right:24px}
.pd-pkg__name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:17px;color:var(--pd-text);margin:0;font-weight:600;line-height:1.3;
}
.pd-pkg__badge{
  padding:4px 10px;font-size:9.5px;font-weight:800;letter-spacing:.6px;
  text-transform:uppercase;border-radius:99px;white-space:nowrap;
  background:linear-gradient(135deg,var(--pd-gold),var(--pd-gold-dk));
  color:var(--pd-burgundy);border:1px solid #8E6A1F;
}
.pd-pkg__desc{color:var(--pd-muted);font-size:13px;line-height:1.55;margin:0;min-height:40px}
.pd-pkg__price{
  display:flex;align-items:baseline;gap:8px;
  padding-top:12px;border-top:1px solid rgba(214,168,62,.22);
}
.pd-pkg__price .now{font-family:var(--font-serif,Georgia,serif);font-size:24px;font-weight:700;color:var(--pd-maroon)}
.pd-pkg__price .was{font-size:13px;color:var(--pd-muted-2);text-decoration:line-through}
.pd-pkg__check{
  position:absolute;top:14px;right:14px;
  width:24px;height:24px;border-radius:50%;
  border:2px solid var(--pd-line);
  display:grid;place-items:center;
  transition:all .2s ease;
}
.pd-pkg.is-selected .pd-pkg__check{
  border-color:var(--pd-gold);
  background:linear-gradient(135deg,var(--pd-gold),var(--pd-gold-dk));
}
.pd-pkg__check::after{
  content:"";width:10px;height:5px;
  border-left:2px solid white;border-bottom:2px solid white;
  transform:rotate(-45deg) translateY(-1px);
  opacity:0;transition:opacity .2s;
}
.pd-pkg.is-selected .pd-pkg__check::after{opacity:1}

/* ───────── REVIEWS ───────── */
.pd-reviews-summary{
  display:flex;flex-wrap:wrap;align-items:center;gap:22px;
  padding:20px 24px;margin:0 auto 30px;max-width:720px;
  background:linear-gradient(135deg,#FFF9F0,#FBF3E1);
  border:1px solid var(--pd-line-gold);border-radius:14px;
}
.pd-reviews-summary__score{
  display:flex;flex-direction:column;align-items:center;gap:4px;
  padding-right:22px;border-right:1px solid rgba(214,168,62,.28);
}
.pd-reviews-summary__score b{
  font-family:var(--font-serif,Georgia,serif);
  font-size:34px;font-weight:700;color:var(--pd-maroon);line-height:1;
}
.pd-reviews-summary__stars{display:flex;gap:2px;color:var(--pd-gold);font-size:14px}
.pd-reviews-summary__meta{font-size:12px;color:var(--pd-muted)}
.pd-reviews-summary__text{flex:1;font-size:14px;color:var(--pd-text);line-height:1.6;min-width:200px}

.pd-reviews{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
@media(max-width:900px){.pd-reviews{grid-template-columns:repeat(2,1fr);gap:14px}}
@media(max-width:560px){.pd-reviews{grid-template-columns:1fr;gap:12px}}
.pd-review{
  background:var(--pd-ivory);border:1px solid var(--pd-line);
  border-radius:14px;padding:18px;
  display:flex;flex-direction:column;gap:10px;
}
.pd-review__top{display:flex;justify-content:space-between;align-items:center;gap:10px}
.pd-review__stars{display:flex;gap:2px;color:var(--pd-gold);font-size:13px}
.pd-review__name{font-size:13px;font-weight:700;color:var(--pd-text);margin:0}
.pd-review__text{
  color:#4B5563;font-size:13px;line-height:1.65;margin:0;
  display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;overflow:hidden;
}
.pd-review__date{font-size:11px;color:var(--pd-muted-2);margin-top:auto}

/* ───────── FAQ ───────── */
.pd-faqs{max-width:820px;margin:0 auto}
.pd-faq{
  background:var(--pd-ivory);border:1px solid var(--pd-line);
  border-radius:12px;margin-bottom:10px;overflow:hidden;
  transition:border-color .2s ease,box-shadow .2s ease;
}
.pd-faq.open{border-color:var(--pd-line-gold);box-shadow:0 10px 24px -16px rgba(53,27,47,.20)}
.pd-faq__q{
  cursor:pointer;width:100%;display:flex;align-items:center;justify-content:space-between;
  gap:16px;padding:18px 22px;background:none;border:none;text-align:left;
  font-family:inherit;font-weight:700;font-size:14.5px;color:var(--pd-text);
  transition:color .2s;
}
.pd-faq__q:hover{color:var(--pd-gold-dk)}
.pd-faq__q:focus-visible{outline:2px solid var(--pd-gold);outline-offset:-3px}
.pd-faq__chev{
  width:28px;height:28px;border-radius:50%;
  display:grid;place-items:center;flex-shrink:0;
  border:1px solid rgba(214,168,62,.40);color:var(--pd-gold-dk);
  transition:all .35s cubic-bezier(.4,0,.2,1);font-size:12px;
}
.pd-faq.open .pd-faq__chev{
  background:var(--pd-maroon);border-color:var(--pd-maroon);
  color:var(--pd-gold-lt);transform:rotate(180deg);
}
.pd-faq__a{max-height:0;overflow:hidden;transition:max-height .4s cubic-bezier(.22,.68,.32,1)}
.pd-faq__a p{padding:0 22px 20px;margin:0;font-size:13.5px;line-height:1.78;color:var(--pd-muted)}

/* ───────── RELATED (kept inside "About" fallback / below tabs) ───────── */
.pd-related{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
@media(max-width:1024px){.pd-related{grid-template-columns:repeat(3,1fr);gap:16px}}
@media(max-width:800px){.pd-related{grid-template-columns:repeat(2,1fr);gap:14px}}
@media(max-width:520px){.pd-related{grid-template-columns:1fr}}
.pd-rel{
  background:var(--pd-ivory);border:1px solid var(--pd-line);
  border-radius:14px;overflow:hidden;display:flex;flex-direction:column;
  text-decoration:none;color:inherit;
  transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease;
}
.pd-rel:hover{transform:translateY(-4px);border-color:var(--pd-line-gold);box-shadow:0 14px 32px -18px rgba(53,27,47,.25)}
.pd-rel__media{position:relative;aspect-ratio:4/3;overflow:hidden;background:#F3E8D8}
.pd-rel__media img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s}
.pd-rel:hover .pd-rel__media img{transform:scale(1.05)}
.pd-rel__body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:6px;flex:1}
.pd-rel__temple{font-size:9.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--pd-gold-dk);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pd-rel__name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:14.5px;line-height:1.32;color:var(--pd-text);margin:0;font-weight:600;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:38px;
}
.pd-rel__price{
  margin-top:auto;font-family:var(--font-serif,Georgia,serif);
  font-size:16px;font-weight:700;color:var(--pd-maroon);padding-top:8px;
  border-top:1px solid rgba(214,168,62,.20);
}

/* ───────── BOOKING FORM ───────── */
.pd-book{
  max-width:840px;margin:0 auto;
  background:var(--pd-ivory);border:1px solid var(--pd-line-gold);
  border-radius:18px;padding:36px;
  box-shadow:0 14px 40px -22px rgba(53,27,47,.25);
}
@media(max-width:640px){.pd-book{padding:24px 20px;border-radius:14px}}
.pd-book h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:24px;color:var(--pd-text);margin:0 0 8px;font-weight:600;
}
.pd-book .pd-book__sub{color:var(--pd-muted);font-size:13.5px;line-height:1.6;margin:0 0 24px}
.pd-form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px}
@media(max-width:560px){.pd-form-row{grid-template-columns:1fr;gap:12px}}
.pd-field{display:flex;flex-direction:column;gap:6px}
.pd-field label{font-size:12px;font-weight:700;letter-spacing:.3px;color:var(--pd-text);text-transform:uppercase}
.pd-field input,
.pd-field select,
.pd-field textarea{
  width:100%;padding:11px 14px;font-size:14px;
  border:1px solid var(--pd-line);border-radius:10px;
  background:#fff;color:var(--pd-text);font-family:inherit;
  transition:border-color .2s,box-shadow .2s;
}
.pd-field input:focus,
.pd-field select:focus,
.pd-field textarea:focus{outline:none;border-color:var(--pd-gold);box-shadow:0 0 0 3px rgba(214,168,62,.15)}
.pd-submit-wrap{
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:14px;margin-top:22px;padding-top:22px;
  border-top:1px solid var(--pd-line);
}
.pd-total{display:flex;flex-direction:column;line-height:1.15}
.pd-total .lbl{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--pd-muted-2)}
.pd-total .amt{font-family:var(--font-serif,Georgia,serif);font-size:26px;font-weight:700;color:var(--pd-maroon);margin-top:2px}
.pd-submit-wrap .pd-btn{min-width:220px}
@media(max-width:520px){.pd-submit-wrap .pd-btn{width:100%;min-width:0}}

/* ───────── STICKY MOBILE CTA ───────── */
.pd-sticky{display:none}
@media(max-width:820px){
  .pd-sticky{
    display:flex;align-items:center;gap:12px;
    position:fixed;bottom:0;left:0;right:0;z-index:900;
    padding:12px 16px calc(12px + env(safe-area-inset-bottom));
    background:rgba(43,18,32,.97);
    backdrop-filter:blur(10px);
    border-top:1px solid rgba(212,175,55,.35);
    box-shadow:0 -6px 20px rgba(0,0,0,.20);
  }
  .pd-sticky__price{display:flex;flex-direction:column;line-height:1.1;min-width:0;flex-shrink:0}
  .pd-sticky__price .lbl{font-size:9.5px;color:rgba(246,232,207,.6);letter-spacing:.8px;text-transform:uppercase;font-weight:700}
  .pd-sticky__price .amt{font-family:var(--font-serif,Georgia,serif);font-size:20px;font-weight:700;color:var(--pd-gold-lt);margin-top:2px}
  .pd-sticky__btn{
    flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:13px 20px;font-size:14px;font-weight:700;letter-spacing:.4px;
    border-radius:11px;text-decoration:none;
    background:linear-gradient(135deg,var(--pd-gold),var(--pd-gold-dk));
    color:var(--pd-burgundy);border:1px solid #8E6A1F;white-space:nowrap;
  }
  .pd-page-bottom-pad{padding-bottom:100px}
}
</style>

<div class="pd-page-bottom-pad">

<!-- ═══════════ HERO ═══════════ -->
<section class="pd-hero" style="<?= $heroStyle ?>">
  <div class="pd-hero__overlay"></div>
  <div class="pd-hero__gold"></div>
  <div class="pd-wrap pd-hero__inner">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('pujas.php') ?>">Puja</a>
      <span class="sep">›</span>
      <span class="current"><?= e($puja['name']) ?></span>
    </nav>
  </div>
</section>

<!-- ═══════════ MAIN — Two Column ═══════════ -->
<section class="pd-main">
  <div class="pd-wrap">
    <div class="pd-main-grid">

      <div class="pd-media">
        <?= safe_img($puja['image_url'], $puja['name'], '', false) ?>
        <?php if (!empty($puja['badge'])): ?>
          <span class="pd-badge"><?= e($puja['badge']) ?></span>
        <?php endif; ?>
      </div>

      <div class="pd-info">
        <?php if (!empty($puja['badge'])): ?>
          <span class="pd-cat-badge">✦ <?= e($puja['badge']) ?></span>
        <?php endif; ?>

        <h1 class="pd-title"><?= e($puja['name']) ?></h1>

        <div class="pd-meta">
          <?php if (!empty($temple['name'])): ?>
            <span><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg><b><?= e($temple['name']) ?></b></span>
          <?php elseif (!empty($puja['custom_temple_name'])): ?>
            <span><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg><b><?= e($puja['custom_temple_name']) ?></b></span>
          <?php endif; ?>
          <?php if (!empty($temple['city']) || !empty($temple['state'])): ?>
            <span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><?= e(implode(', ', array_filter([$temple['city'] ?? '', $temple['state'] ?? '']))) ?></span>
          <?php endif; ?>
          <?php if (!empty($puja['duration_mins'])): ?>
            <span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><b><?= (int) $puja['duration_mins'] ?> min</b> ritual</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($puja['short_desc'])): ?>
          <p class="pd-short"><?= e($puja['short_desc']) ?></p>
        <?php endif; ?>

        <div class="pd-price-block" id="pd-price-block">
          <div>
            <div class="pd-price-label">Starting from</div>
            <div class="pd-price-value" id="pd-price-display">
              <span class="cur">₹</span><?= number_format($defaultPrice, 0) ?>
            </div>
          </div>
          <div class="pd-price-note"><b>Prasad included</b><br>Home delivery</div>
        </div>

        <div class="pd-includes">
          <h4>What's Included</h4>
          <ul>
            <li>Performed by verified Vedic pandits</li>
            <li>Sankalp with your name &amp; gotra</li>
            <li>Video proof of the puja sent to you</li>
            <li>Prasad shipped to your home address</li>
          </ul>
        </div>

        <div class="pd-cta">
          <button type="button" class="pd-btn pd-btn-primary"
                  data-switch-tab="book">
            <i class="bi bi-flower1"></i> Book This Puja
          </button>
          <button type="button" class="pd-btn pd-btn-ghost"
                  data-add-id="<?= (int) $puja['id'] ?>"
                  data-add-name="<?= e($puja['name']) ?>"
                  data-add-price="<?= (int) $defaultPrice ?>"
                  data-add-img="<?= e(safe_img_url($puja['image_url'])) ?>">
            <i class="bi bi-cart-plus"></i> Add to Cart
          </button>
        </div>

        <div class="pd-trust">
          <span><svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6z"/><path d="m9 12 2 2 4-4"/></svg>Verified Priests</span>
          <span><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>Prasad Delivery</span>
          <span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>Video Proof</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════
     TAB BAR — proper tab system
     ═══════════════════════════════════════════════════ -->
<div class="pd-tabs">
  <div class="pd-tabs__inner" role="tablist" aria-label="Puja detail sections">
    <?php $first = true; foreach ($tabs as $key => $label): ?>
      <button type="button"
              class="pd-tab<?= $key === $defaultTab ? ' is-active' : '' ?>"
              id="tab-<?= e($key) ?>"
              role="tab"
              aria-selected="<?= $key === $defaultTab ? 'true' : 'false' ?>"
              aria-controls="panel-<?= e($key) ?>"
              tabindex="<?= $key === $defaultTab ? '0' : '-1' ?>"
              data-tab="<?= e($key) ?>">
        <?= e($label) ?>
      </button>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     TAB PANELS — only one visible at a time
     ═══════════════════════════════════════════════════ -->

<!-- ─────────── ABOUT ─────────── -->
<?php if (!empty($puja['description'])): ?>
<div class="pd-panel<?= $defaultTab === 'about' ? ' is-active' : '' ?>"
     id="panel-about" role="tabpanel" aria-labelledby="tab-about" tabindex="0">
  <section class="pd-section pd-section--warm">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">About This Puja</span>
        <h2>Discover <?= e($puja['name']) ?></h2>
        <div class="line"></div>
      </div>

      <div class="pd-about-wrap">
        <div class="pd-readmore-wrap" id="pd-about-content">
          <?php
            $desc = $puja['description'];
            if (strip_tags($desc) === $desc) {
                $paras = preg_split('/\r\n|\r|\n/', $desc);
                foreach ($paras as $para) {
                    $para = trim($para);
                    if ($para !== '') echo '<p>' . e($para) . '</p>';
                }
            } else {
                echo $desc;
            }
          ?>
        </div>
        <button type="button" class="pd-readmore-btn" id="pd-readmore-btn" aria-expanded="false">
          See More <span aria-hidden="true">▾</span>
        </button>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── TEMPLE DETAILS ─────────── -->
<?php if ($temple): ?>
<div class="pd-panel<?= $defaultTab === 'temple' ? ' is-active' : '' ?>"
     id="panel-temple" role="tabpanel" aria-labelledby="tab-temple" tabindex="0">
  <section class="pd-section pd-section--white">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Divine Destination</span>
        <h2>Temple Details</h2>
        <div class="line"></div>
        <p>Where this sacred ritual is performed by our verified pandits.</p>
      </div>
      <div class="pd-temple-grid">
        <div class="pd-temple-media">
          <?= safe_img($temple['image_url'], $temple['name']) ?>
        </div>
        <div class="pd-temple-info">
          <h3><?= e($temple['name']) ?></h3>
          <div class="pd-temple-loc">
            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e(implode(', ', array_filter([$temple['city'] ?? '', $temple['state'] ?? ''])) ?: 'India') ?>
            <?php if (!empty($temple['type'])): ?> · <?= e($temple['type']) ?><?php endif; ?>
          </div>

          <?php if (!empty($temple['short_desc'])): ?>
            <p class="pd-temple-desc"><?= e($temple['short_desc']) ?></p>
          <?php endif; ?>

          <?php
            $tOpen   = trim((string)($temple['open_time']    ?? ''));
            $tClose  = trim((string)($temple['close_time']   ?? ''));
            $tClosed = trim((string)($temple['closed_days']  ?? ''));
            $tNote   = trim((string)($temple['closure_note'] ?? ''));
            $tHasTime = ($tOpen !== '' && $tClose !== '');
            $tClosedLabel = '';
            if ($tClosed !== '') {
                $list = array_map('trim', explode(',', strtolower($tClosed)));
                $map  = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'];
                $names = array_map(fn($k)=>$map[$k] ?? ucfirst($k), $list);
                $tClosedLabel = 'Closed on ' . implode(', ', $names);
            }
          ?>

          <?php if ($tHasTime || $tClosedLabel || $tNote): ?>
            <div class="pd-temple-timing">
              <?php if ($tHasTime): ?>
                <div class="pd-temple-timing__row">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  <span><?= e(date('g:i A', strtotime($tOpen))) ?> – <?= e(date('g:i A', strtotime($tClose))) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($tClosedLabel): ?>
                <div class="pd-temple-timing__row">
                  <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  <span><?= e($tClosedLabel) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($tNote): ?>
                <div class="pd-temple-timing__row">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                  <span><?= e($tNote) ?></span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── BENEFITS ─────────── -->
<?php if (!empty($benefits)): ?>
<div class="pd-panel<?= $defaultTab === 'benefits' ? ' is-active' : '' ?>"
     id="panel-benefits" role="tabpanel" aria-labelledby="tab-benefits" tabindex="0">
  <section class="pd-section pd-section--warm">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Divine Benefits</span>
        <h2>Benefits of This Puja</h2>
        <div class="line"></div>
        <p>Traditional blessings and spiritual significance of performing this sacred ritual.</p>
      </div>
      <div class="pd-benefits">
        <?php foreach ($benefits as $b): ?>
          <article class="pd-benefit">
            <div class="pd-benefit__ic">
              <?= !empty($b['icon']) ? $b['icon'] : '<i class="bi bi-flower2"></i>' ?>
            </div>
            <h3 class="pd-benefit__title"><?= e($b['title']) ?></h3>
            <?php if (!empty($b['description'])): ?>
              <p class="pd-benefit__desc"><?= e($b['description']) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── PROCESS ─────────── -->
<?php if (!empty($procedures)): ?>
<div class="pd-panel<?= $defaultTab === 'process' ? ' is-active' : '' ?>"
     id="panel-process" role="tabpanel" aria-labelledby="tab-process" tabindex="0">
  <section class="pd-section pd-section--white">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Sacred Procedure</span>
        <h2>How This Puja Is Performed</h2>
        <div class="line"></div>
        <p>Step-by-step Vedic procedure followed by our verified pandits.</p>
      </div>
      <div class="pd-steps">
        <?php foreach ($procedures as $i => $step): ?>
          <?php $stepImg = safe_img_url($step['image_url'] ?? ''); ?>
          <div class="pd-step">
            <div class="pd-step__num"><?= (int)($step['step_number'] ?: ($i + 1)) ?></div>
            <div class="pd-step__body">
              <h3 class="pd-step__title"><?= e($step['title']) ?></h3>
              <?php if (!empty($step['description'])): ?>
                <p class="pd-step__desc"><?= nl2br(e($step['description'])) ?></p>
              <?php endif; ?>
              <?php if ($stepImg): ?>
                <div class="pd-step__img">
                  <img src="<?= e($stepImg) ?>" alt="<?= e($step['title']) ?>" loading="lazy">
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── PACKAGES ─────────── -->
<?php if (!empty($packages)): ?>
<div class="pd-panel<?= $defaultTab === 'packages' ? ' is-active' : '' ?>"
     id="panel-packages" role="tabpanel" aria-labelledby="tab-packages" tabindex="0">
  <section class="pd-section pd-section--warm">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Choose Package</span>
        <h2>Select Your Puja Package</h2>
        <div class="line"></div>
        <p>Pick the offering that matches your prayer and intent.</p>
      </div>
      <div class="pd-packages">
        <?php foreach ($packages as $pk): ?>
          <?php
            $pkgId    = (int)$pk['id'];
            $pkgPrice = (float)$pk['price'];
            $isSel    = ($pkgId === $defaultPackageId);
          ?>
          <label class="pd-pkg<?= $isSel ? ' is-selected' : '' ?>"
                 for="pd-pkg-<?= $pkgId ?>"
                 data-pkg-id="<?= $pkgId ?>"
                 data-pkg-price="<?= $pkgPrice ?>">
            <input type="radio" id="pd-pkg-<?= $pkgId ?>"
                   name="pd_pkg" value="<?= $pkgId ?>"
                   data-price="<?= $pkgPrice ?>"
                   <?= $isSel ? 'checked' : '' ?>>
            <span class="pd-pkg__check"></span>
            <div class="pd-pkg__top">
              <h3 class="pd-pkg__name"><?= e($pk['name']) ?></h3>
              <?php if (!empty($pk['badge'])): ?>
                <span class="pd-pkg__badge"><?= e($pk['badge']) ?></span>
              <?php endif; ?>
            </div>
            <?php if (!empty($pk['short_desc'])): ?>
              <p class="pd-pkg__desc"><?= e($pk['short_desc']) ?></p>
            <?php endif; ?>
            <div class="pd-pkg__price">
              <span class="now"><?= inr($pkgPrice) ?></span>
              <?php if (!empty($pk['compare_price']) && (float)$pk['compare_price'] > $pkgPrice): ?>
                <span class="was"><?= inr((float)$pk['compare_price']) ?></span>
              <?php endif; ?>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── REVIEWS ─────────── -->
<?php if (!empty($reviews)): ?>
<div class="pd-panel<?= $defaultTab === 'reviews' ? ' is-active' : '' ?>"
     id="panel-reviews" role="tabpanel" aria-labelledby="tab-reviews" tabindex="0">
  <section class="pd-section pd-section--white">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Devotee Voices</span>
        <h2>Reviews &amp; Ratings</h2>
        <div class="line"></div>
      </div>

      <div class="pd-reviews-summary">
        <div class="pd-reviews-summary__score">
          <b><?= number_format($avgRating, 1) ?></b>
          <div class="pd-reviews-summary__stars">
            <?php
              $full = (int)floor($avgRating);
              $half = ($avgRating - $full) >= 0.5;
              for ($i = 0; $i < $full; $i++) echo '★';
              if ($half) echo '☆';
            ?>
          </div>
          <div class="pd-reviews-summary__meta"><?= (int)$reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?></div>
        </div>
        <div class="pd-reviews-summary__text">
          Devotees have rated this puja <b><?= number_format($avgRating,1) ?> / 5</b> based on verified bookings.
        </div>
      </div>

      <div class="pd-reviews">
        <?php foreach ($reviews as $rv): ?>
          <article class="pd-review">
            <div class="pd-review__top">
              <h4 class="pd-review__name"><?= e($rv['reviewer_name']) ?></h4>
              <div class="pd-review__stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <?= $i <= (int)$rv['rating'] ? '★' : '☆' ?>
                <?php endfor; ?>
              </div>
            </div>
            <p class="pd-review__text"><?= nl2br(e($rv['review'])) ?></p>
            <?php if (!empty($rv['created_at'])): ?>
              <div class="pd-review__date"><?= e(date('d M Y', strtotime($rv['created_at']))) ?></div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── FAQS ─────────── -->
<?php if (!empty($faqs)): ?>
<div class="pd-panel<?= $defaultTab === 'faqs' ? ' is-active' : '' ?>"
     id="panel-faqs" role="tabpanel" aria-labelledby="tab-faqs" tabindex="0">
  <section class="pd-section pd-section--warm">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Have Questions?</span>
        <h2>Frequently Asked Questions</h2>
        <div class="line"></div>
      </div>
      <div class="pd-faqs">
        <?php foreach ($faqs as $fq): ?>
          <div class="pd-faq">
            <button type="button" class="pd-faq__q" aria-expanded="false">
              <span><?= e($fq['question']) ?></span>
              <span class="pd-faq__chev" aria-hidden="true">▾</span>
            </button>
            <div class="pd-faq__a">
              <p><?= nl2br(e($fq['answer'])) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>

<!-- ─────────── BOOK NOW ─────────── -->
<div class="pd-panel<?= $defaultTab === 'book' ? ' is-active' : '' ?>"
     id="panel-book" role="tabpanel" aria-labelledby="tab-book" tabindex="0">
  <section class="pd-section pd-section--warm">
    <div class="pd-wrap">
      <div class="pd-head">
        <span class="eyebrow">Reserve Your Puja</span>
        <h2>Book This Puja</h2>
        <div class="line"></div>
        <p>Fill the form below to book <b><?= e($puja['name']) ?></b>. Our team will contact you to confirm the muhurat and date.</p>
      </div>

      <div class="pd-book">
        <?php if (is_logged_in()): ?>
          <form method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="puja_book">
            <input type="hidden" name="puja_id" value="<?= (int) $puja['id'] ?>">
            <input type="hidden" name="puja_package_id" id="pd-selected-package" value="<?= (int)$defaultPackageId ?>">
            <input type="hidden" name="amount" id="pd-selected-amount" value="<?= number_format($defaultPrice, 2, '.', '') ?>">

            <div class="pd-form-row">
              <div class="pd-field">
                <label for="pd-name">Devotee Name</label>
                <input type="text" id="pd-name" name="devotee_name" required value="<?= e(current_user_name()) ?>">
              </div>
              <div class="pd-field">
                <label for="pd-gotra">Gotra <small style="opacity:.6">(optional)</small></label>
                <input type="text" id="pd-gotra" name="gotra" placeholder="e.g. Kashyap">
              </div>
            </div>

            <div class="pd-form-row">
              <div class="pd-field">
                <label for="pd-date">Preferred Date</label>
                <input type="date" id="pd-date" name="booking_date" required min="<?= date('Y-m-d') ?>">
              </div>
              <div class="pd-field">
                <label for="pd-sankalp">Sankalp / Wish <small style="opacity:.6">(optional)</small></label>
                <input type="text" id="pd-sankalp" name="sankalp" placeholder="What do you wish to pray for?">
              </div>
            </div>

            <div class="pd-submit-wrap">
              <div class="pd-total">
                <span class="lbl">Total Amount</span>
                <span class="amt" id="pd-form-amount"><?= inr($defaultPrice) ?></span>
              </div>
              <button type="submit" class="pd-btn pd-btn-primary">
                <i class="bi bi-check2-circle"></i> Confirm Booking
              </button>
            </div>
          </form>
        <?php else: ?>
          <div class="pd-includes" style="background:#FBF3E1;padding:18px;border-radius:12px;border:1px solid var(--pd-line-gold)">
            <h4>Login Required</h4>
            <p style="margin:0;color:var(--pd-muted);font-size:13.5px;line-height:1.6">
              Please login to your Divya Aastha account to book this puja.
            </p>
          </div>
          <div style="margin-top:18px">
            <a href="<?= url('login.php') ?>" class="pd-btn pd-btn-primary">
              <i class="bi bi-box-arrow-in-right"></i> Login to Continue
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- ═══════════ RELATED — always visible below tabs ═══════════ -->
<?php if (!empty($relatedPujas)): ?>
<section class="pd-section pd-section--white">
  <div class="pd-wrap">
    <div class="pd-head">
      <span class="eyebrow">You May Also Book</span>
      <h2>Related Pujas</h2>
      <div class="line"></div>
    </div>
    <div class="pd-related">
      <?php foreach ($relatedPujas as $rp): ?>
        <a href="<?= url('puja.php?slug=' . urlencode($rp['slug'])) ?>" class="pd-rel">
          <div class="pd-rel__media">
            <?= safe_img($rp['image_url'], $rp['name']) ?>
          </div>
          <div class="pd-rel__body">
            <?php if (!empty($rp['temple_name'])): ?>
              <span class="pd-rel__temple"><?= e($rp['temple_name']) ?></span>
            <?php endif; ?>
            <h3 class="pd-rel__name"><?= e($rp['name']) ?></h3>
            <div class="pd-rel__price"><?= inr($rp['price']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

</div>

<!-- ═══════════ STICKY MOBILE CTA ═══════════ -->
<div class="pd-sticky">
  <div class="pd-sticky__price">
    <span class="lbl">Total</span>
    <span class="amt" id="pd-sticky-price"><?= inr($defaultPrice) ?></span>
  </div>
  <button type="button" class="pd-sticky__btn" data-switch-tab="book">
    <i class="bi bi-flower1"></i> Book This Puja
  </button>
</div>

<script>
(function () {
  'use strict';

  /* ══════════ TAB SYSTEM ══════════ */
  var tabs     = document.querySelectorAll('.pd-tab');
  var panels   = document.querySelectorAll('.pd-panel');
  var tabBar   = document.querySelector('.pd-tabs__inner');

  function activateTab(tabKey) {
    // Update tab buttons
    tabs.forEach(function (t) {
      var isMatch = t.getAttribute('data-tab') === tabKey;
      t.classList.toggle('is-active', isMatch);
      t.setAttribute('aria-selected', isMatch ? 'true' : 'false');
      t.setAttribute('tabindex', isMatch ? '0' : '-1');
    });

    // Update panels
    panels.forEach(function (p) {
      var isMatch = p.id === 'panel-' + tabKey;
      p.classList.toggle('is-active', isMatch);
    });

    // Persist tab in URL hash (no scroll)
    try {
      history.replaceState(null, '', '#' + tabKey);
    } catch (err) {}

    // Scroll window to the tab bar so the user sees the new content from the top
    // (only if the tab bar is above the current viewport)
    if (tabBar) {
      var rect = tabBar.getBoundingClientRect();
      if (rect.top < 0) {
        var navBar = document.querySelector('.pd-tabs');
        var navH = navBar ? navBar.offsetHeight : 0;
        var mainHeader = document.querySelector('.site-header') || document.querySelector('header');
        var headerH = 0;
        if (mainHeader) {
          var pos = getComputedStyle(mainHeader).position;
          if (pos === 'sticky' || pos === 'fixed') headerH = mainHeader.offsetHeight;
        }
        var top = (navBar.getBoundingClientRect().top + window.pageYOffset) - (headerH + 8);
        window.scrollTo({ top: top < 0 ? 0 : top, behavior: 'smooth' });
      }
    }
  }

  // Click handlers on tabs
  tabs.forEach(function (t) {
    t.addEventListener('click', function (e) {
      e.preventDefault();
      activateTab(t.getAttribute('data-tab'));
    });
  });

  // Keyboard navigation for tabs (arrow keys, Home, End)
  if (tabBar) {
    tabBar.addEventListener('keydown', function (e) {
      var idx = -1;
      tabs.forEach(function (t, i) { if (t.classList.contains('is-active')) idx = i; });
      if (idx < 0) return;

      var newIdx = idx;
      if (e.key === 'ArrowRight') newIdx = (idx + 1) % tabs.length;
      else if (e.key === 'ArrowLeft') newIdx = (idx - 1 + tabs.length) % tabs.length;
      else if (e.key === 'Home') newIdx = 0;
      else if (e.key === 'End') newIdx = tabs.length - 1;
      else return;

      e.preventDefault();
      var newTab = tabs[newIdx];
      activateTab(newTab.getAttribute('data-tab'));
      newTab.focus();
    });
  }

  /* ══════════ Buttons that switch to a specific tab (Book Now CTA) ══════════ */
  document.querySelectorAll('[data-switch-tab]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      activateTab(btn.getAttribute('data-switch-tab'));
    });
  });

  /* ══════════ Restore tab from URL hash on load ══════════ */
  if (window.location.hash) {
    var hashKey = window.location.hash.replace('#', '');
    var exists = false;
    tabs.forEach(function (t) { if (t.getAttribute('data-tab') === hashKey) exists = true; });
    if (exists) {
      activateTab(hashKey);
    }
  }

  /* ══════════ Package selection ══════════ */
  var pkgCards = document.querySelectorAll('.pd-pkg');
  var priceDisplay = document.getElementById('pd-price-display');
  var stickyPrice  = document.getElementById('pd-sticky-price');
  var formAmount   = document.getElementById('pd-form-amount');
  var hiddenPkg    = document.getElementById('pd-selected-package');
  var hiddenAmount = document.getElementById('pd-selected-amount');

  function fmtINR(n) {
    n = Math.round(Number(n) || 0);
    return '₹' + n.toLocaleString('en-IN');
  }

  function selectPkg(card) {
    if (!card) return;
    var price = parseFloat(card.getAttribute('data-pkg-price')) || 0;
    var pkgId = card.getAttribute('data-pkg-id') || '0';

    pkgCards.forEach(function (c) { c.classList.remove('is-selected'); });
    card.classList.add('is-selected');

    var radio = card.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    if (priceDisplay) {
      priceDisplay.innerHTML = '<span class="cur">₹</span>' + Math.round(price).toLocaleString('en-IN');
    }
    if (stickyPrice)  stickyPrice.textContent = fmtINR(price);
    if (formAmount)   formAmount.textContent  = fmtINR(price);
    if (hiddenPkg)    hiddenPkg.value = pkgId;
    if (hiddenAmount) hiddenAmount.value = price.toFixed(2);

    var addBtn = document.querySelector('[data-add-id]');
    if (addBtn) addBtn.setAttribute('data-add-price', Math.round(price));
  }

  pkgCards.forEach(function (card) {
    card.addEventListener('click', function (e) {
      e.preventDefault();
      selectPkg(card);
    });
  });

  var preselected = document.querySelector('.pd-pkg.is-selected');
  if (preselected) selectPkg(preselected);

  /* ══════════ FAQ accordion ══════════ */
  document.querySelectorAll('.pd-faq').forEach(function (item) {
    var btn = item.querySelector('.pd-faq__q');
    var ans = item.querySelector('.pd-faq__a');
    if (!btn || !ans) return;

    btn.addEventListener('click', function () {
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.pd-faq.open').forEach(function (other) {
        if (other === item) return;
        other.classList.remove('open');
        var ob = other.querySelector('.pd-faq__q');
        var oa = other.querySelector('.pd-faq__a');
        if (ob) ob.setAttribute('aria-expanded', 'false');
        if (oa) oa.style.maxHeight = '0px';
      });
      if (isOpen) {
        item.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        ans.style.maxHeight = '0px';
      } else {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        ans.style.maxHeight = ans.scrollHeight + 'px';
      }
    });
  });

  /* ══════════ About "Read More" ══════════ */
  var aboutWrap = document.getElementById('pd-about-content');
  var readMoreBtn = document.getElementById('pd-readmore-btn');
  if (aboutWrap && readMoreBtn) {
    if (aboutWrap.scrollHeight <= 280) {
      readMoreBtn.style.display = 'none';
      aboutWrap.classList.add('expanded');
    } else {
      readMoreBtn.addEventListener('click', function () {
        var isExpanded = aboutWrap.classList.toggle('expanded');
        readMoreBtn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        readMoreBtn.innerHTML = isExpanded
          ? 'Show Less <span aria-hidden="true">▴</span>'
          : 'See More <span aria-hidden="true">▾</span>';
      });
    }
  }

})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>