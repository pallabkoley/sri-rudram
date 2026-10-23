<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/helpers/puja_helpers.php';

/* ==================================================================
 |  Fetch temple by slug
 * ================================================================== */
$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    flash_set('error', 'Temple not specified.');
    redirect('temples.php');
}

try {
    $stmt = $pdo->prepare('SELECT * FROM temples WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $temple = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[temple] ' . $e->getMessage());
    $temple = null;
}

if (!$temple) {
    flash_set('error', 'Temple not found.');
    redirect('temples.php');
}

$templeId   = (int) $temple['id'];
$templeName = (string) $temple['name'];

/* ==================================================================
 |  Pujas
 * ================================================================== */
$pujas     = get_temple_pujas($pdo, $templeId, $templeName);
$pujaCount = count($pujas);

/* ==================================================================
 |  Related temples
 * ================================================================== */
try {
    $rs = $pdo->prepare(
        'SELECT id, name, slug, type, city, state, image_url
         FROM temples
         WHERE is_active = 1 AND id <> ?
         ORDER BY is_featured DESC, sort_order ASC
         LIMIT 3'
    );
    $rs->execute([$templeId]);
    $relatedTemples = $rs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $relatedTemples = [];
}

/* ==================================================================
 |  Image helper
 * ================================================================== */
if (!function_exists('tpl_img_url')) {
    function tpl_img_url(?string $stored): string {
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

/* ==================================================================
 |  Temple timings
 * ================================================================== */
if (!function_exists('tpl_time_fmt')) {
    function tpl_time_fmt(?string $hhmm): string {
        if (!$hhmm) return '';
        $t = strtotime($hhmm);
        return $t ? date('g:i A', $t) : $hhmm;
    }
}

$tClosedDays  = $temple['closed_days']  ?? '';
$tOpenTime    = $temple['open_time']    ?? '';
$tCloseTime   = $temple['close_time']   ?? '';
$tClosureNote = $temple['closure_note'] ?? '';

$tHasTime = ($tOpenTime && $tCloseTime);

$tClosedList = $tClosedDays
    ? array_map('trim', explode(',', strtolower($tClosedDays)))
    : [];

$weekNames = [
    'mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday',
    'fri'=>'Friday','sat'=>'Saturday','sun'=>'Sunday'
];

$tClosedLabel = '';
if (!empty($tClosedList)) {
    $names = array_map(fn($k)=>$weekNames[$k] ?? ucfirst($k), $tClosedList);
    if (count($names) === 1) {
        $tClosedLabel = 'Closed on ' . $names[0];
    } elseif (count($names) === 2) {
        $tClosedLabel = 'Closed on ' . $names[0] . ' & ' . $names[1];
    } else {
        $last = array_pop($names);
        $tClosedLabel = 'Closed on ' . implode(', ', $names) . ' & ' . $last;
    }
}

$tClosedToday = false;
if (!empty($tClosedList)) {
    $todayKey = strtolower(substr(date('D'), 0, 3));
    if (in_array($todayKey, $tClosedList, true)) $tClosedToday = true;
}

$tHasTiming = ($tHasTime || $tClosedLabel || $tClosureNote);

$templeImg = tpl_img_url($temple['image_url']);

/* ==================================================================
 |  Book Puja target
 * ================================================================== */
if ($pujaCount === 1) {
    $bookPujaUrl   = url('puja-book.php?puja_id=' . (int)$pujas[0]['id'] . '&temple_id=' . $templeId);
    $bookPujaLabel = 'Book Puja Now';
} elseif ($pujaCount > 1) {
    $bookPujaUrl   = '#tp-pujas';
    $bookPujaLabel = 'View ' . $pujaCount . ' Pujas';
} else {
    $bookPujaUrl   = url('pujas.php');
    $bookPujaLabel = 'Explore All Pujas';
}

$pageTitle = $temple['name'] . ' — Divya Aastha';
$pageDesc  = $temple['short_desc'] ?: ('Book a sacred puja at ' . $temple['name']);
require BASE_PATH . '/includes/header.php';
?>

<style>
.tp-page{padding:0 0 60px;background:#FAF6EF;width:100%;overflow-x:hidden}
.tp-page *{box-sizing:border-box}
.tp-page img{max-width:100%;height:auto;display:block}
.tp-page .tp-wrap{max-width:1200px;margin:0 auto;padding:0 22px}

/* Hero */
.tp-hero{
  background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
  padding:44px 0 56px;position:relative;overflow:hidden;
}
.tp-hero::after{
  content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent);
}
.tp-hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(circle at 12% 25%,rgba(183,97,70,.30) 0%,transparent 55%),
    radial-gradient(circle at 88% 75%,rgba(212,175,55,.16) 0%,transparent 55%);
}
.tp-breadcrumb{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  font-size:13px;color:rgba(247,235,221,.75);
  margin-bottom:26px;position:relative;z-index:2;
}
.tp-breadcrumb a{color:rgba(247,235,221,.9);text-decoration:none}
.tp-breadcrumb a:hover{color:#D4AF37}
.tp-breadcrumb .sep{color:rgba(247,235,221,.4)}
.tp-breadcrumb .current{color:#F7EBDD;font-weight:600}
.tp-hero-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:38px;align-items:center;
  position:relative;z-index:2;
}
@media(max-width:900px){
  .tp-hero{padding:28px 0 40px}
  .tp-hero-grid{grid-template-columns:1fr;gap:24px}
}
.tp-hero-media{
  position:relative;border-radius:18px;overflow:hidden;
  aspect-ratio:4/3;background:#F3F4F6;
  box-shadow:0 20px 50px rgba(0,0,0,.28);
  border:1px solid rgba(212,175,55,.20);
}
.tp-hero-media img{width:100%;height:100%;object-fit:cover}
.tp-hero-media .ph{
  width:100%;height:100%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;font-size:64px;
}
.tp-eyebrow{
  display:inline-block;padding:6px 16px;font-size:11px;font-weight:700;
  letter-spacing:2px;text-transform:uppercase;color:#D4AF37;
  background:rgba(212,175,55,.12);border:1px solid rgba(212,175,55,.35);
  border-radius:99px;margin-bottom:16px;
}
.tp-hero-title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:42px;line-height:1.12;color:#F7EBDD;
  margin:0 0 14px;font-weight:600;
  text-shadow:0 2px 20px rgba(0,0,0,.35);
}
@media(max-width:900px){.tp-hero-title{font-size:28px}}
@media(max-width:420px){.tp-hero-title{font-size:24px}}
.tp-meta{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
.tp-meta span{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;font-size:12.5px;
  color:rgba(247,235,221,.9);
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.14);
  border-radius:99px;
}
.tp-meta span i{color:#D4AF37}
.tp-meta span.is-closed{
  background:rgba(183,58,34,.28);
  border-color:rgba(255,123,91,.50);
  color:#FFC9B8;
}
.tp-meta span.is-closed i{color:#FF7B5B}

.tp-hero-desc{
  font-size:15px;line-height:1.7;color:rgba(247,235,221,.82);
  margin:0 0 24px;max-width:560px;
}
.tp-hero-cta{display:flex;flex-wrap:wrap;gap:12px}
@media(max-width:640px){.tp-hero-cta{flex-direction:column}}
@media(max-width:640px){.tp-hero-cta a{width:100%;text-align:center}}
.tp-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:14px 26px;font-size:14px;font-weight:600;border-radius:12px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .2s;white-space:nowrap;
}
.tp-btn-primary{
  background:linear-gradient(135deg,#C9704F,#B76146);
  color:#fff;border-color:#B76146;
  box-shadow:0 8px 24px rgba(183,97,70,.35);
}
.tp-btn-primary:hover{
  background:linear-gradient(135deg,#B76146,#9B4C34);
  color:#fff;transform:translateY(-2px);
  box-shadow:0 12px 30px rgba(183,97,70,.45);
}
.tp-btn-ghost{
  background:rgba(255,255,255,.10);color:#F7EBDD;
  border-color:rgba(247,235,221,.35);
}
.tp-btn-ghost:hover{
  background:rgba(255,255,255,.18);color:#fff;
  border-color:rgba(212,175,55,.6);
}

.tp-info-wrap{padding:42px 0 20px;background:#FAF6EF}
.tp-info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
@media(max-width:900px){.tp-info-grid{grid-template-columns:repeat(2,1fr);gap:12px}}
@media(max-width:420px){.tp-info-grid{grid-template-columns:1fr;gap:10px}}
.tp-info-card{
  background:#fff;border:1px solid #EFE6D9;border-radius:14px;
  padding:18px;box-shadow:0 1px 3px rgba(53,27,47,.05);
}
.tp-info-card.is-closed{
  background:linear-gradient(135deg,#FDF2EF,#FBE4DE);
  border-color:rgba(183,58,34,.30);
}
.tp-info-card .ic{
  width:38px;height:38px;border-radius:10px;display:grid;place-items:center;
  font-size:17px;color:#fff;background:linear-gradient(135deg,#5B3A55,#351B2F);
  margin-bottom:10px;
}
.tp-info-card.is-closed .ic{
  background:linear-gradient(135deg,#D8452A,#B73A22);
}
.tp-info-card .lbl{
  font-size:10.5px;font-weight:700;letter-spacing:1.3px;
  text-transform:uppercase;color:#6B7280;margin-bottom:4px;
}
.tp-info-card .val{
  font-family:var(--font-serif,Georgia,serif);
  font-size:15px;font-weight:600;color:#351B2F;line-height:1.4;
  word-break:break-word;
}
.tp-info-card.is-closed .val{color:#8B2A1C}
.tp-info-card .sublabel{
  font-size:12px;color:#8A6A18;font-style:italic;
  margin-top:6px;line-height:1.5;
}
.tp-info-card.is-closed .sublabel{color:#B73A22}
.tp-info-card .today-badge{
  display:inline-flex;align-items:center;gap:4px;
  margin-top:8px;padding:3px 10px;
  font-size:10px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;
  background:#B73A22;color:#FFF3D2;border-radius:99px;
}

/* Timing strip */
.tp-timing-strip{
  display:grid;grid-template-columns:repeat(3,1fr);gap:14px;
  max-width:960px;margin:0 auto;padding:26px 0 12px;
}
@media(max-width:768px){.tp-timing-strip{grid-template-columns:1fr;gap:10px}}
.tp-timing-item{
  background:linear-gradient(135deg,#FBF3E1,#F6E8CF);
  border:1px solid rgba(214,168,62,.32);
  border-radius:12px;padding:16px 18px;
  display:flex;align-items:flex-start;gap:12px;
}
.tp-timing-item .ti-ic{
  width:36px;height:36px;flex-shrink:0;border-radius:9px;
  display:grid;place-items:center;font-size:16px;
  background:rgba(214,168,62,.22);color:#8A6A18;
}
.tp-timing-item .ti-txt .ti-lbl{
  font-size:10px;font-weight:700;letter-spacing:1.2px;
  text-transform:uppercase;color:#8A6A18;margin-bottom:4px;
}
.tp-timing-item .ti-txt .ti-val{
  font-family:var(--font-serif,Georgia,serif);
  font-size:15px;font-weight:600;color:#4B080C;line-height:1.4;
}
.tp-timing-item.is-closed{
  background:linear-gradient(135deg,#FDEEEB,#F9E0DB);
  border-color:rgba(183,58,34,.35);
}
.tp-timing-item.is-closed .ti-ic{background:rgba(183,58,34,.22);color:#8B2A1C}
.tp-timing-item.is-closed .ti-txt .ti-lbl{color:#8B2A1C}
.tp-timing-item.is-closed .ti-txt .ti-val{color:#8B2A1C}

.tp-section{padding:52px 0}
.tp-section--white{background:#fff}
.tp-head{text-align:center;max-width:720px;margin:0 auto 36px}
.tp-head .eyebrow{
  display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;
  text-transform:uppercase;color:#B76146;margin-bottom:12px;
}
.tp-head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:30px;line-height:1.2;color:#351B2F;margin:0 0 12px;font-weight:600;
}
@media(max-width:768px){.tp-head h2{font-size:22px}}
.tp-head .line{
  width:70px;height:2px;margin:0 auto 14px;
  background:linear-gradient(90deg,transparent,#D4AF37,transparent);
}
.tp-head p{color:#6B7280;font-size:14.5px;line-height:1.7;margin:0}

.tp-puja-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
@media(max-width:1024px){.tp-puja-grid{grid-template-columns:repeat(2,1fr);gap:18px}}
@media(max-width:640px){.tp-puja-grid{grid-template-columns:1fr;gap:16px}}
.tp-puja{
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  overflow:hidden;display:flex;flex-direction:column;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
  transition:transform .22s,box-shadow .22s,border-color .22s;
}
.tp-puja:hover{
  transform:translateY(-4px);box-shadow:0 14px 32px rgba(53,27,47,.11);
  border-color:rgba(183,97,70,.30);
}
.tp-puja-media{position:relative;aspect-ratio:4/3;overflow:hidden;background:#F3F4F6}
.tp-puja-media img{width:100%;height:100%;object-fit:cover;transition:transform .35s ease}
.tp-puja:hover .tp-puja-media img{transform:scale(1.05)}
.tp-puja-media .ph{
  position:absolute;inset:0;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;font-size:44px;
}
.tp-puja-badge{
  position:absolute;top:12px;left:12px;padding:5px 12px;
  background:linear-gradient(135deg,#F5D76E,#D4AF37);color:#4A2C0F;
  font-size:10.5px;font-weight:800;letter-spacing:.5px;
  text-transform:uppercase;border-radius:99px;
  box-shadow:0 3px 10px rgba(0,0,0,.18);z-index:2;
}
.tp-puja-body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:10px;flex:1}
.tp-puja-name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;line-height:1.3;color:#351B2F;margin:0;font-weight:600;
}
.tp-puja-name a{color:inherit;text-decoration:none}
.tp-puja-name a:hover{color:#B76146}
.tp-puja-desc{
  color:#6B7280;font-size:13px;line-height:1.6;margin:0;flex:1;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.tp-puja-foot{
  display:flex;justify-content:space-between;align-items:center;gap:10px;
  padding-top:12px;border-top:1px solid #F3EEE6;margin-top:4px;
}
.tp-puja-price{display:flex;flex-direction:column;gap:1px}
.tp-puja-price .from{
  font-size:10px;color:#9CA3AF;letter-spacing:.4px;
  text-transform:uppercase;font-weight:600;
}
.tp-puja-price .amt{
  font-family:var(--font-serif,Georgia,serif);
  font-size:19px;font-weight:700;color:#351B2F;
}
.tp-puja-btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:10px 18px;font-size:12.5px;font-weight:600;
  border-radius:9px;background:#B76146;color:#fff;
  border:1px solid #B76146;text-decoration:none;
  white-space:nowrap;transition:all .18s;
}
.tp-puja-btn:hover{background:#9B4C34;color:#fff}
.tp-puja-btn svg{width:13px;height:13px}

.tp-empty{
  max-width:600px;margin:0 auto;text-align:center;
  padding:52px 30px;background:#fff;border-radius:18px;
  border:1px dashed #EFE6D9;
}
.tp-empty .ico{font-size:52px;color:#D4AF37;margin-bottom:14px;line-height:1}
.tp-empty h3{
  font-family:var(--font-serif,Georgia,serif);font-size:20px;
  color:#351B2F;margin:0 0 8px;font-weight:600;
}
.tp-empty p{color:#6B7280;font-size:13.5px;margin:0 0 20px;line-height:1.6}

.tp-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:900px){.tp-steps{grid-template-columns:1fr;gap:14px}}
.tp-step{
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  padding:28px 22px;text-align:center;position:relative;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
}
.tp-step .num{
  position:absolute;top:-14px;left:50%;transform:translateX(-50%);
  width:32px;height:32px;border-radius:50%;
  background:linear-gradient(135deg,#C9704F,#B76146);color:#fff;
  display:grid;place-items:center;font-weight:700;font-size:13px;
  box-shadow:0 6px 16px rgba(183,97,70,.35);
}
.tp-step .ic{
  width:56px;height:56px;border-radius:14px;margin:0 auto 14px;
  display:grid;place-items:center;font-size:24px;color:#B76146;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
}
.tp-step h3{
  font-family:var(--font-serif,Georgia,serif);font-size:17px;
  color:#351B2F;margin:0 0 6px;font-weight:600;
}
.tp-step p{color:#6B7280;font-size:13px;line-height:1.6;margin:0}

.tp-desc-inner{
  max-width:820px;margin:0 auto;background:#fff;
  border:1px solid #EFE6D9;border-radius:18px;
  padding:34px 38px;box-shadow:0 1px 3px rgba(53,27,47,.05);
}
@media(max-width:640px){.tp-desc-inner{padding:22px 20px}}
.tp-desc-inner p{color:#4B5563;font-size:15px;line-height:1.85;margin:0 0 14px}
.tp-desc-inner p:last-child{margin-bottom:0}
.tp-desc-inner .lead{
  font-family:var(--font-serif,Georgia,serif);font-size:17px;
  line-height:1.7;color:#351B2F;margin:0 0 18px;font-weight:500;
}

.tp-related{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
@media(max-width:900px){.tp-related{grid-template-columns:repeat(2,1fr);gap:16px}}
@media(max-width:600px){.tp-related{grid-template-columns:1fr;gap:14px}}
.tp-rel{
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  overflow:hidden;display:flex;flex-direction:column;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
  transition:transform .22s,box-shadow .22s,border-color .22s;
}
.tp-rel:hover{
  transform:translateY(-4px);box-shadow:0 14px 28px rgba(53,27,47,.10);
  border-color:rgba(183,97,70,.28);
}
.tp-rel-media{position:relative;aspect-ratio:4/3;overflow:hidden;background:#F3F4F6}
.tp-rel-media img{width:100%;height:100%;object-fit:cover;transition:transform .35s}
.tp-rel:hover .tp-rel-media img{transform:scale(1.05)}
.tp-rel-media .ph{
  width:100%;height:100%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);
  color:#B76146;font-size:40px;
}
.tp-rel-media .cat{
  position:absolute;top:10px;left:10px;padding:4px 10px;
  background:rgba(53,27,47,.88);color:#F7EBDD;
  font-size:10px;font-weight:700;letter-spacing:.4px;
  text-transform:uppercase;border-radius:99px;
}
.tp-rel-body{padding:16px 18px 18px;display:flex;flex-direction:column;gap:6px;flex:1}
.tp-rel-name{
  font-family:var(--font-serif,Georgia,serif);font-size:16px;
  line-height:1.3;color:#351B2F;margin:0;font-weight:600;
}
.tp-rel-loc{font-size:12px;color:#6B7280}
.tp-rel-link{
  display:inline-flex;align-items:center;gap:5px;
  font-size:12px;font-weight:700;color:#B76146;
  text-decoration:none;margin-top:6px;
}
.tp-rel-link:hover{color:#9B4C34}

.tp-sticky{display:none}
@media(max-width:768px){
  .tp-sticky{
    display:block;position:fixed;bottom:0;left:0;right:0;z-index:900;
    padding:12px 16px calc(12px + env(safe-area-inset-bottom));
    background:rgba(53,27,47,.97);backdrop-filter:blur(10px);
    border-top:1px solid rgba(212,175,55,.35);
    box-shadow:0 -6px 20px rgba(0,0,0,.18);
  }
  .tp-sticky a{
    display:flex;align-items:center;justify-content:center;gap:8px;
    width:100%;padding:13px 20px;font-size:14px;font-weight:600;
    border-radius:11px;background:linear-gradient(135deg,#C9704F,#B76146);
    color:#fff;text-decoration:none;
  }
  .tp-page{padding-bottom:88px}
}
</style>

<div class="tp-page">

<section class="tp-hero">
  <div class="tp-wrap">

    <nav class="tp-breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('temples.php') ?>">Temples</a>
      <span class="sep">›</span>
      <span class="current"><?= e($temple['name']) ?></span>
    </nav>

    <div class="tp-hero-grid">

      <div class="tp-hero-media">
        <?php if ($templeImg): ?>
          <img src="<?= e($templeImg) ?>" alt="<?= e($temple['name']) ?>">
        <?php else: ?>
          <div class="ph"><i class="bi bi-building"></i></div>
        <?php endif; ?>
      </div>

      <div>
        <span class="tp-eyebrow">Divine Destination</span>
        <h1 class="tp-hero-title"><?= e($temple['name']) ?></h1>

        <div class="tp-meta">
          <?php if (!empty($temple['city']) || !empty($temple['state'])): ?>
            <span>
              <i class="bi bi-geo-alt-fill"></i>
              <?= e(implode(', ', array_filter([$temple['city'], $temple['state']]))) ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($temple['type'])): ?>
            <span><i class="bi bi-stars"></i> <?= e($temple['type']) ?></span>
          <?php endif; ?>
          <?php if ($tHasTiming && $tClosedLabel): ?>
            <span class="<?= $tClosedToday ? 'is-closed' : '' ?>">
              <i class="bi bi-calendar-week"></i>
              <?= e($tClosedLabel) ?>
            </span>
          <?php endif; ?>
          <?php if ($tClosedToday): ?>
            <span class="is-closed">
              <i class="bi bi-x-octagon-fill"></i> Closed Today
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($temple['short_desc'])): ?>
          <p class="tp-hero-desc"><?= e($temple['short_desc']) ?></p>
        <?php endif; ?>

        <div class="tp-hero-cta">
          <a href="<?= e($bookPujaUrl) ?>" class="tp-btn tp-btn-primary">
            <i class="bi bi-flower1"></i> <?= e($bookPujaLabel) ?>
          </a>
          <a href="#tp-about" class="tp-btn tp-btn-ghost">
            <i class="bi bi-info-circle"></i> Temple Details
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="tp-info-wrap">
  <div class="tp-wrap">
    <div class="tp-info-grid">

      <?php if (!empty($temple['type'])): ?>
        <div class="tp-info-card">
          <div class="ic"><i class="bi bi-stars"></i></div>
          <div class="lbl">Temple Type</div>
          <div class="val"><?= e($temple['type']) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($temple['city'])): ?>
        <div class="tp-info-card">
          <div class="ic"><i class="bi bi-buildings"></i></div>
          <div class="lbl">City</div>
          <div class="val"><?= e($temple['city']) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($temple['state'])): ?>
        <div class="tp-info-card">
          <div class="ic"><i class="bi bi-map"></i></div>
          <div class="lbl">State</div>
          <div class="val"><?= e($temple['state']) ?></div>
        </div>
      <?php endif; ?>

      <div class="tp-info-card">
        <div class="ic"><i class="bi bi-flower2"></i></div>
        <div class="lbl">Available Pujas</div>
        <div class="val"><?= $pujaCount ?> Sacred Ritual<?= $pujaCount === 1 ? '' : 's' ?></div>
      </div>

    </div>
  </div>
</section>

<?php if ($tHasTiming): ?>
<section class="tp-wrap">
  <div class="tp-timing-strip">

    <?php if ($tHasTime): ?>
      <div class="tp-timing-item<?= $tClosedToday ? ' is-closed' : '' ?>">
        <div class="ti-ic"><i class="bi bi-clock"></i></div>
        <div class="ti-txt">
          <div class="ti-lbl">Opening Hours</div>
          <div class="ti-val">
            <?= e(tpl_time_fmt($tOpenTime)) ?> – <?= e(tpl_time_fmt($tCloseTime)) ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="tp-timing-item<?= $tClosedToday ? ' is-closed' : '' ?>">
      <div class="ti-ic"><i class="bi bi-calendar-week"></i></div>
      <div class="ti-txt">
        <div class="ti-lbl">Weekly Closure</div>
        <div class="ti-val"><?= e($tClosedLabel ?: 'Open all days') ?></div>
      </div>
    </div>

    <?php if ($tClosureNote): ?>
      <div class="tp-timing-item">
        <div class="ti-ic"><i class="bi bi-info-circle"></i></div>
        <div class="ti-txt">
          <div class="ti-lbl">Please Note</div>
          <div class="ti-val" style="font-size:13.5px;font-weight:500">
            <?= e($tClosureNote) ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>

<section class="tp-section tp-section--white" id="tp-pujas">
  <div class="tp-wrap">

    <div class="tp-head">
      <span class="eyebrow">Sacred Rituals</span>
      <h2>Pujas Available at This Temple</h2>
      <div class="line"></div>
      <p>Book a sacred puja at <?= e($temple['name']) ?> and receive divine blessings.</p>
    </div>

    <?php if (empty($pujas)): ?>

      <div class="tp-empty">
        <div class="ico">🛕</div>
        <h3>Puja Bookings Coming Soon</h3>
        <p>Puja bookings for this temple will be available soon. Explore pujas available at other revered temples.</p>
        <a href="<?= url('pujas.php') ?>" class="tp-btn tp-btn-primary">
          <i class="bi bi-flower1"></i> Explore All Pujas
        </a>
      </div>

    <?php else: ?>

      <div class="tp-puja-grid">
        <?php foreach ($pujas as $p): ?>
          <?php $pImg = tpl_img_url($p['image_url']); ?>
          <article class="tp-puja">

            <div class="tp-puja-media">
              <?php if ($pImg): ?>
                <img src="<?= e($pImg) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                <div class="ph" style="display:none"><i class="bi bi-flower1"></i></div>
              <?php else: ?>
                <div class="ph"><i class="bi bi-flower1"></i></div>
              <?php endif; ?>
              <?php if (!empty($p['badge'])): ?>
                <span class="tp-puja-badge"><?= e($p['badge']) ?></span>
              <?php endif; ?>
            </div>

            <div class="tp-puja-body">
              <h3 class="tp-puja-name">
                <a href="<?= url('puja.php?slug=' . urlencode($p['slug']) . '&temple_id=' . $templeId) ?>">
                  <?= e($p['name']) ?>
                </a>
              </h3>

              <?php if (!empty($p['short_desc'])): ?>
                <p class="tp-puja-desc"><?= e($p['short_desc']) ?></p>
              <?php endif; ?>

              <div class="tp-puja-foot">
                <div class="tp-puja-price">
                  <span class="from">From</span>
                  <span class="amt"><?= inr($p['price']) ?></span>
                </div>
                <a href="<?= url('puja-book.php?puja_id=' . (int)$p['id'] . '&temple_id=' . $templeId) ?>"
                   class="tp-puja-btn">
                  Book Now
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

<?php if (!empty($pujas)): ?>
<section class="tp-section">
  <div class="tp-wrap">
    <div class="tp-head">
      <span class="eyebrow">Simple &amp; Sacred</span>
      <h2>How Booking Works</h2>
      <div class="line"></div>
      <p>Book a puja in three simple steps.</p>
    </div>

    <div class="tp-steps">
      <div class="tp-step">
        <div class="num">1</div>
        <div class="ic"><i class="bi bi-flower1"></i></div>
        <h3>Choose Puja</h3>
        <p>Pick a puja that matches your prayer and intent.</p>
      </div>
      <div class="tp-step">
        <div class="num">2</div>
        <div class="ic"><i class="bi bi-calendar-heart"></i></div>
        <h3>Select Date &amp; Muhurat</h3>
        <p>Choose an auspicious date and preferred muhurat.</p>
      </div>
      <div class="tp-step">
        <div class="num">3</div>
        <div class="ic"><i class="bi bi-person-check"></i></div>
        <h3>Enter Details &amp; Book</h3>
        <p>Share your name, gotra and sankalp to confirm.</p>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($temple['description'])): ?>
<section class="tp-section tp-section--white" id="tp-about">
  <div class="tp-wrap">
    <div class="tp-head">
      <span class="eyebrow">About This Temple</span>
      <h2><?= e($temple['name']) ?></h2>
      <div class="line"></div>
    </div>

    <div class="tp-desc-inner">
      <?php if (!empty($temple['short_desc'])): ?>
        <p class="lead"><?= e($temple['short_desc']) ?></p>
      <?php endif; ?>
      <?= nl2br(e($temple['description'])) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($relatedTemples)): ?>
<section class="tp-section">
  <div class="tp-wrap">
    <div class="tp-head">
      <span class="eyebrow">Divine Destinations</span>
      <h2>Explore More Temples</h2>
      <div class="line"></div>
      <p>Discover other revered shrines across Bharat.</p>
    </div>

    <div class="tp-related">
      <?php foreach ($relatedTemples as $rt): ?>
        <?php $rtImg = tpl_img_url($rt['image_url']); ?>
        <article class="tp-rel">
          <div class="tp-rel-media">
            <?php if ($rtImg): ?>
              <img src="<?= e($rtImg) ?>" alt="<?= e($rt['name']) ?>" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
              <div class="ph" style="display:none"><i class="bi bi-building"></i></div>
            <?php else: ?>
              <div class="ph"><i class="bi bi-building"></i></div>
            <?php endif; ?>
            <?php if (!empty($rt['type'])): ?>
              <span class="cat"><?= e($rt['type']) ?></span>
            <?php endif; ?>
          </div>

          <div class="tp-rel-body">
            <h3 class="tp-rel-name"><?= e($rt['name']) ?></h3>
            <div class="tp-rel-loc">
              <?= e(implode(', ', array_filter([$rt['city'], $rt['state']])) ?: 'India') ?>
            </div>
            <a href="<?= url('temple.php?slug=' . urlencode($rt['slug'])) ?>"
               class="tp-rel-link">
              View Temple →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

</div>

<div class="tp-sticky">
  <a href="<?= e($bookPujaUrl) ?>">
    <i class="bi bi-flower1"></i> <?= e($bookPujaLabel) ?>
  </a>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>