<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  TEMPLE IMAGE HELPER
 * ================================================================== */
if (!function_exists('tpl_list_img_url')) {
    function tpl_list_img_url(?string $stored): string {
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

/* ==================================================================
 |  TIME + CLOSURE HELPERS
 * ================================================================== */
if (!function_exists('tpl_time_fmt')) {
    function tpl_time_fmt(?string $hhmm): string {
        if (!$hhmm) return '';
        $t = strtotime($hhmm);
        return $t ? date('g:i A', $t) : $hhmm;
    }
}
if (!function_exists('tpl_timing_data')) {
    function tpl_timing_data(array $t): array {
        $closedDays  = trim((string)($t['closed_days']  ?? ''));
        $openTime    = trim((string)($t['open_time']    ?? ''));
        $closeTime   = trim((string)($t['close_time']   ?? ''));
        $closureNote = trim((string)($t['closure_note'] ?? ''));

        $hasTime = ($openTime !== '' && $closeTime !== '');

        $closedList = $closedDays
            ? array_map('trim', explode(',', strtolower($closedDays)))
            : [];

        $weekNames = [
            'mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu',
            'fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'
        ];
        $closedLabel = '';
        if (!empty($closedList)) {
            $names = array_map(fn($k)=>$weekNames[$k] ?? ucfirst($k), $closedList);
            if (count($names) === 1)      $closedLabel = 'Closed: ' . $names[0];
            elseif (count($names) === 2)  $closedLabel = 'Closed: ' . $names[0] . ' & ' . $names[1];
            else {
                $last = array_pop($names);
                $closedLabel = 'Closed: ' . implode(', ', $names) . ' & ' . $last;
            }
        }

        $closedToday = false;
        if (!empty($closedList)) {
            $todayKey = strtolower(substr(date('D'), 0, 3));
            if (in_array($todayKey, $closedList, true)) $closedToday = true;
        }

        $hasAny = ($hasTime || $closedLabel !== '' || $closureNote !== '');

        return [
            'has_time'      => $hasTime,
            'open_time'     => $openTime,
            'close_time'    => $closeTime,
            'closed_label'  => $closedLabel,
            'closure_note'  => $closureNote,
            'closed_today'  => $closedToday,
            'has_any'       => $hasAny,
        ];
    }
}

/* ==================================================================
 |  Query params
 * ================================================================== */
$typeFilter  = trim((string)($_GET['type']  ?? ''));
$stateFilter = trim((string)($_GET['state'] ?? ''));
$searchQ     = trim((string)($_GET['q']     ?? ''));

/* ==================================================================
 |  Build temples query
 * ================================================================== */
try {
    $where  = ['is_active = 1'];
    $params = [];

    if ($typeFilter !== '') {
        $where[]  = 'type = ?';
        $params[] = $typeFilter;
    }
    if ($stateFilter !== '') {
        $where[]  = 'state = ?';
        $params[] = $stateFilter;
    }
    if ($searchQ !== '') {
        $where[]  = '(name LIKE ? OR city LIKE ? OR state LIKE ? OR short_desc LIKE ?)';
        $like     = '%' . $searchQ . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql = 'SELECT id, name, slug, type, city, state, image_url, short_desc,
                   closed_days, open_time, close_time, closure_note,
                   is_featured, is_dark_featured, sort_order
            FROM temples
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY is_featured DESC, sort_order ASC, id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $temples = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('[temples.list] ' . $e->getMessage());
    $temples = [];
}

/* ==================================================================
 |  Filter source data
 * ================================================================== */
try {
    $filterTypes = $pdo->query(
        "SELECT DISTINCT type FROM temples
         WHERE is_active = 1 AND type IS NOT NULL AND type <> ''
         ORDER BY type ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $filterTypes = [];
}

try {
    $filterStates = $pdo->query(
        "SELECT DISTINCT state FROM temples
         WHERE is_active = 1 AND state IS NOT NULL AND state <> ''
         ORDER BY state ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $filterStates = [];
}

$totalCount  = count($temples);
$activeLabel = 'All Temples';
if ($typeFilter !== '')  $activeLabel = $typeFilter;
if ($stateFilter !== '') $activeLabel = $stateFilter . ($typeFilter ? ' · ' . $typeFilter : '');
if ($searchQ !== '')     $activeLabel = 'Search: ' . $searchQ;

$pageTitle = 'Sacred Temples of Bharat — Divya Aastha';
$pageDesc  = 'Discover revered temples across Bharat and book sacred pujas.';

require BASE_PATH . '/includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   TEMPLES LISTING PAGE
   ═══════════════════════════════════════════════════════════ */
:root{
  --tl-burgundy:#3A0E12;
  --tl-burgundy-2:#2B0A0D;
  --tl-maroon:#4B080C;
  --tl-gold:#D6A83E;
  --tl-gold-lt:#E8C66A;
  --tl-gold-dk:#B58A2B;
  --tl-cream:#F6E8CF;
  --tl-ivory:#FFF9F0;
  --tl-warm:#FAF6EF;
  --tl-text:#351B2F;
  --tl-muted:#6B7280;
  --tl-muted-2:#9CA3AF;
  --tl-line:#ECE7E1;
  --tl-line-gold:rgba(214,168,62,.32);
  --tl-terra:#B76146;
}

/* ---------- HERO — site pattern ---------- */
.page-hero{
  position:relative;
  overflow:hidden;
  background:
    radial-gradient(circle at 50% 0%, rgba(214,168,62,.10) 0%, transparent 55%),
    linear-gradient(180deg, #FBF3E1 0%, #F6E8CF 60%, #FBF3E1 100%) !important;
  border-bottom:1px solid rgba(214,168,62,.22);
}
.page-hero .page-hero-mandala{
  animation: tl-mandala-spin 120s linear infinite;
  transform-origin: center center;
  will-change: transform;
  opacity:.55;
}
@keyframes tl-mandala-spin{
  from{ transform: rotate(0deg); }
  to  { transform: rotate(360deg); }
}
@media (prefers-reduced-motion: reduce){
  .page-hero .page-hero-mandala{ animation: none; }
}

/* ---------- SEARCH ---------- */
.tl-search-wrap{
  max-width:720px;
  margin:22px auto 0;
  position:relative;
}
.tl-search{
  display:flex;
  align-items:center;
  gap:10px;
  background:var(--tl-ivory);
  border:1px solid rgba(214,168,62,.32);
  border-radius:99px;
  padding:6px 6px 6px 22px;
  box-shadow:0 10px 28px -14px rgba(53,27,47,.20);
  transition:border-color .25s, box-shadow .25s;
}
.tl-search:focus-within{
  border-color:var(--tl-gold);
  box-shadow:0 12px 32px -14px rgba(214,168,62,.45);
}
.tl-search svg{
  width:18px;height:18px;flex-shrink:0;
  stroke:var(--tl-terra);fill:none;stroke-width:2;
}
.tl-search input{
  flex:1;min-width:0;
  border:none;outline:none;background:transparent;
  font-size:14.5px;color:var(--tl-text);
  padding:10px 0;
  font-family:inherit;
}
.tl-search input::placeholder{color:var(--tl-muted-2)}
.tl-search button{
  flex-shrink:0;
  padding:11px 24px;
  font-size:13.5px;font-weight:700;
  letter-spacing:.6px;text-transform:uppercase;
  border-radius:99px;
  border:1px solid var(--tl-maroon);
  background:linear-gradient(135deg, var(--tl-maroon), var(--tl-burgundy-2));
  color:var(--tl-cream);
  cursor:pointer;
  font-family:inherit;
  transition:all .22s;
}
.tl-search button:hover{
  background:linear-gradient(135deg, var(--tl-burgundy-2), var(--tl-maroon));
  box-shadow:0 6px 18px -6px rgba(75,8,12,.55);
}
@media(max-width:520px){
  .tl-search{padding:5px 5px 5px 16px}
  .tl-search input{font-size:14px}
  .tl-search button{padding:10px 18px;font-size:12.5px}
}

/* ---------- SECTION WRAP ---------- */
.tl-wrap{
  max-width:1240px;
  margin:0 auto;
  padding:0 22px;
}
.tl-section{
  padding:44px 0 90px;
  background:var(--tl-warm);
}

/* ---------- FILTERS ---------- */
.tl-filters{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-bottom:26px;
  padding-bottom:22px;
  border-bottom:1px solid rgba(214,168,62,.18);
}
.tl-chip{
  display:inline-flex;
  align-items:center;
  padding:8px 18px;
  border-radius:99px;
  font-size:12.5px;
  font-weight:600;
  letter-spacing:.2px;
  text-decoration:none;
  white-space:nowrap;
  transition:all .22s ease;
  background:var(--tl-ivory);
  color:var(--tl-text);
  border:1px solid var(--tl-line);
}
.tl-chip:hover{
  border-color:var(--tl-gold);
  color:var(--tl-gold-dk);
  transform:translateY(-1px);
  box-shadow:0 4px 12px -4px rgba(214,168,62,.35);
}
.tl-chip.active{
  background:linear-gradient(135deg, var(--tl-maroon), var(--tl-burgundy-2));
  color:var(--tl-cream);
  border-color:var(--tl-maroon);
  box-shadow:0 6px 16px -6px rgba(75,8,12,.55);
}
.tl-chip.active::before{
  content:"";
  width:6px;height:6px;
  background:var(--tl-gold);
  border-radius:50%;
  margin-right:8px;
  box-shadow:0 0 6px rgba(214,168,62,.9);
}

/* ---------- HEAD ---------- */
.tl-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  flex-wrap:wrap;
  gap:14px;
  margin-bottom:26px;
}
.tl-head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:24px;
  color:var(--tl-text);
  margin:0;
  font-weight:600;
  letter-spacing:-.2px;
  position:relative;
  padding-left:16px;
}
.tl-head h2::before{
  content:"";
  position:absolute;
  left:0;top:50%;
  transform:translateY(-50%);
  width:4px;height:22px;
  background:linear-gradient(180deg, var(--tl-gold), var(--tl-terra));
  border-radius:2px;
}
.tl-head .count{
  color:var(--tl-muted);
  font-size:12.5px;
  padding-bottom:6px;
  letter-spacing:.2px;
}
.tl-head .count b{color:var(--tl-maroon);font-weight:700}

/* ---------- GRID ---------- */
.tl-grid{
  display:grid;
  grid-template-columns:repeat(3, minmax(0,1fr));
  gap:26px;
  align-items:stretch;
}
@media(max-width:1024px){
  .tl-grid{grid-template-columns:repeat(2, minmax(0,1fr));gap:20px}
}
@media(max-width:640px){
  .tl-grid{grid-template-columns:1fr;gap:16px}
  .tl-section{padding:36px 0 60px}
  .tl-head h2{font-size:19px}
}

/* ---------- TEMPLE CARD ---------- */
.tl-card{
  background:var(--tl-ivory);
  border:1px solid var(--tl-line);
  border-radius:16px;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  height:100%;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
  transition:transform .32s cubic-bezier(.4,0,.2,1),
             box-shadow .32s cubic-bezier(.4,0,.2,1),
             border-color .32s cubic-bezier(.4,0,.2,1);
  position:relative;
}
.tl-card::before{
  content:"";
  position:absolute;
  top:0;left:20%;right:20%;
  height:2px;
  background:linear-gradient(90deg, transparent, var(--tl-gold), transparent);
  opacity:0;
  transition:opacity .35s ease;
  z-index:3;
}
.tl-card:hover{
  transform:translateY(-6px);
  border-color:var(--tl-line-gold);
  box-shadow:0 20px 42px -20px rgba(53,27,47,.32),
             0 0 0 1px rgba(214,168,62,.25);
}
.tl-card:hover::before{opacity:1}

/* Media 16:10 */
.tl-media{
  position:relative;
  width:100%;
  aspect-ratio:16/10;
  overflow:hidden;
  background:linear-gradient(135deg, var(--tl-cream), #EFDDB9);
  flex-shrink:0;
}
.tl-media img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
  transition:transform .6s cubic-bezier(.4,0,.2,1);
}
.tl-card:hover .tl-media img{transform:scale(1.06)}
.tl-media::after{
  content:"";
  position:absolute;
  top:0;left:0;right:0;height:70px;
  background:linear-gradient(180deg, rgba(49,5,8,.22), transparent);
  pointer-events:none;
  z-index:1;
}
.tl-media .ph{
  position:absolute;inset:0;
  display:grid;place-items:center;
  background:linear-gradient(135deg, var(--tl-cream), #EFDDB9);
  color:var(--tl-terra);font-size:44px;z-index:0;
}

/* Badges */
.tl-type{
  position:absolute;
  top:12px;left:12px;
  z-index:2;
  padding:5px 11px;
  background:linear-gradient(135deg, var(--tl-gold), var(--tl-gold-dk));
  color:var(--tl-burgundy-2);
  font-size:9.5px;
  font-weight:800;
  letter-spacing:.7px;
  text-transform:uppercase;
  border-radius:99px;
  border:1px solid #8E6A1F;
  box-shadow:0 4px 10px -2px rgba(49,5,8,.32);
}
.tl-loc{
  position:absolute;
  bottom:12px;left:12px;
  z-index:2;
  padding:5px 11px;
  background:rgba(255,249,240,.96);
  color:var(--tl-maroon);
  font-size:11px;
  font-weight:600;
  border-radius:6px;
  border:1px solid rgba(214,168,62,.30);
  display:inline-flex;
  align-items:center;
  gap:5px;
}
.tl-featured{
  position:absolute;
  top:12px;right:12px;
  z-index:2;
  padding:5px 11px;
  background:linear-gradient(135deg, var(--tl-maroon), var(--tl-burgundy-2));
  color:var(--tl-cream);
  font-size:9.5px;
  font-weight:800;
  letter-spacing:.7px;
  text-transform:uppercase;
  border-radius:99px;
  border:1px solid rgba(214,168,62,.55);
  box-shadow:0 4px 10px -2px rgba(49,5,8,.45);
}

/* Body */
.tl-body{
  padding:18px;
  display:flex;
  flex-direction:column;
  gap:9px;
  flex:1;
}
.tl-name{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:17px;
  line-height:1.32;
  color:var(--tl-text);
  margin:0;
  font-weight:600;
  height:45px;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  text-overflow:ellipsis;
  flex-shrink:0;
}
.tl-name a{
  color:inherit;
  text-decoration:none;
  transition:color .22s;
}
.tl-name a:hover{color:var(--tl-terra)}

.tl-desc{
  color:var(--tl-muted);
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

/* Timings block */
.tl-timing{
  display:flex;
  flex-direction:column;
  gap:4px;
  padding:8px 11px;
  margin-top:2px;
  background:linear-gradient(135deg, #FBF3E1, #F6E8CF);
  border:1px solid rgba(214,168,62,.32);
  border-radius:9px;
  font-size:11.5px;
  color:#6B4A3E;
  line-height:1.42;
  flex-shrink:0;
}
.tl-timing__row{
  display:flex;
  align-items:flex-start;
  gap:6px;
}
.tl-timing__row svg{
  flex-shrink:0;
  margin-top:1px;
  width:12px;height:12px;
  color:var(--tl-gold-dk);
  fill:none;stroke:currentColor;stroke-width:2;
  stroke-linecap:round;stroke-linejoin:round;
}
.tl-timing__row span{flex:1;font-weight:500}
.tl-timing.is-closed-today{
  background:linear-gradient(135deg, #FDEEEB, #F9E0DB);
  border-color:rgba(183,58,34,.38);
  color:#8B2A1C;
}
.tl-timing.is-closed-today svg{color:#B73A22}
.tl-timing__badge{
  align-self:flex-start;
  margin-top:2px;
  padding:2px 9px;
  font-size:9.5px;
  font-weight:800;
  letter-spacing:.5px;
  text-transform:uppercase;
  background:#B73A22;
  color:#FFF3D2;
  border-radius:99px;
}

/* CTA */
.tl-foot{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  padding-top:14px;
  margin-top:auto;
  border-top:1px solid rgba(214,168,62,.22);
  flex-shrink:0;
}
.tl-pujas{
  display:flex;
  flex-direction:column;
  line-height:1.1;
  min-width:0;
}
.tl-pujas .lbl{
  font-size:9.5px;
  color:var(--tl-muted-2);
  letter-spacing:.9px;
  text-transform:uppercase;
  font-weight:700;
}
.tl-pujas .val{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16px;
  font-weight:700;
  color:var(--tl-maroon);
  margin-top:3px;
}

.tl-btn{
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
  background:linear-gradient(135deg, var(--tl-maroon), var(--tl-burgundy-2));
  color:var(--tl-cream);
  text-decoration:none;
  border:1px solid var(--tl-maroon);
  white-space:nowrap;
  overflow:hidden;
  transition:all .28s cubic-bezier(.4,0,.2,1);
  box-shadow:0 4px 12px -6px rgba(49,5,8,.5);
  font-family:inherit;
}
.tl-btn::before{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(135deg, rgba(214,168,62,.40), rgba(214,168,62,.15));
  opacity:0;
  transition:opacity .28s;
}
.tl-btn > *{position:relative;z-index:1}
.tl-btn:hover{
  color:#FFF3D2;
  border-color:var(--tl-gold);
  box-shadow:0 8px 22px -8px rgba(214,168,62,.60),
             inset 0 0 0 1px rgba(214,168,62,.40);
  transform:translateY(-1px);
}
.tl-btn:hover::before{opacity:1}
.tl-btn svg{
  width:13px;height:13px;
  transition:transform .28s cubic-bezier(.4,0,.2,1);
  fill:none;stroke:currentColor;stroke-width:2.4;
  stroke-linecap:round;stroke-linejoin:round;
}
.tl-btn:hover svg{transform:translateX(3px)}

/* ---------- EMPTY STATE ---------- */
.tl-empty{
  text-align:center;
  padding:80px 22px;
  background:var(--tl-ivory);
  border:1px solid var(--tl-line);
  border-radius:16px;
}
.tl-empty .ico{
  font-size:56px;
  color:var(--tl-gold);
  margin-bottom:16px;
  line-height:1;
  filter:grayscale(.2);
}
.tl-empty h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:22px;
  color:var(--tl-text);
  margin:0 0 8px;
  font-weight:600;
}
.tl-empty p{margin:0 0 22px;font-size:14px;color:var(--tl-muted);line-height:1.65}
.tl-empty a{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:11px 22px;
  font-size:12.5px;
  font-weight:700;
  letter-spacing:.7px;
  text-transform:uppercase;
  border-radius:9px;
  background:linear-gradient(135deg, var(--tl-maroon), var(--tl-burgundy-2));
  color:var(--tl-cream);
  text-decoration:none;
  border:1px solid var(--tl-maroon);
}

/* ---------- RESPONSIVE ---------- */
@media(max-width:520px){
  .tl-body{padding:14px;gap:8px}
  .tl-name{font-size:15px;height:42px}
  .tl-desc{font-size:12px;height:38px}
  .tl-foot{padding-top:12px;gap:8px}
  .tl-btn{padding:9px 13px;font-size:11px}
}
</style>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Temples</span>
    </nav>
    <span class="eyebrow">Divine Destinations</span>
    <h1>Sacred Temples of Bharat</h1>
    <p>Explore revered shrines across India and book authentic pujas performed by verified priests.</p>

    <form class="tl-search-wrap" method="get" action="<?= url('temples.php') ?>">
      <div class="tl-search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
        <input type="search" name="q" value="<?= e($searchQ) ?>"
               placeholder="Search temples by name, city or state…"
               aria-label="Search temples">
        <?php if ($typeFilter !== ''): ?>
          <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
        <?php endif; ?>
        <?php if ($stateFilter !== ''): ?>
          <input type="hidden" name="state" value="<?= e($stateFilter) ?>">
        <?php endif; ?>
        <button type="submit">Search</button>
      </div>
    </form>
  </div>
</section>

<!-- ═══════════════ LISTING ═══════════════ -->
<section class="tl-section">
  <div class="tl-wrap">

    <!-- Filter chips -->
    <?php if (!empty($filterTypes) || !empty($filterStates)): ?>
      <div class="tl-filters">

        <a href="<?= url('temples.php') ?>"
           class="tl-chip<?= $typeFilter === '' && $stateFilter === '' && $searchQ === '' ? ' active' : '' ?>">
          All Temples
        </a>

        <?php foreach ($filterTypes as $t): ?>
          <?php
            $params = ['type' => $t];
            if ($stateFilter !== '') $params['state'] = $stateFilter;
            if ($searchQ !== '')     $params['q']     = $searchQ;
          ?>
          <a href="<?= url('temples.php?' . http_build_query($params)) ?>"
             class="tl-chip<?= $typeFilter === $t ? ' active' : '' ?>">
            <?= e($t) ?>
          </a>
        <?php endforeach; ?>

        <?php if ($stateFilter !== ''): ?>
          <?php
            $clearParams = [];
            if ($typeFilter !== '') $clearParams['type'] = $typeFilter;
            if ($searchQ !== '')    $clearParams['q']    = $searchQ;
            $clearUrl = 'temples.php' . (!empty($clearParams) ? '?' . http_build_query($clearParams) : '');
          ?>
          <a href="<?= url($clearUrl) ?>" class="tl-chip active">
            <?= e($stateFilter) ?> ✕
          </a>
        <?php endif; ?>

      </div>
    <?php endif; ?>

    <!-- Head -->
    <?php if ($totalCount > 0): ?>
      <div class="tl-head">
        <h2><?= e($activeLabel) ?></h2>
        <span class="count">
          Showing <b><?= (int) $totalCount ?></b>
          <?= $totalCount === 1 ? 'temple' : 'temples' ?>
        </span>
      </div>
    <?php endif; ?>

    <!-- Grid / Empty -->
    <?php if (empty($temples)): ?>

      <div class="tl-empty">
        <div class="ico">🛕</div>
        <h3>No Temples Found</h3>
        <p>
          <?php if ($searchQ !== ''): ?>
            No temples matched "<b><?= e($searchQ) ?></b>". Try a different search.
          <?php else: ?>
            No temples match your filters right now.
          <?php endif; ?>
        </p>
        <a href="<?= url('temples.php') ?>">View All Temples →</a>
      </div>

    <?php else: ?>

      <div class="tl-grid">
        <?php foreach ($temples as $t): ?>
          <?php
            $tt = tpl_timing_data($t);
            $tImg = tpl_list_img_url($t['image_url']);
            $loc  = implode(', ', array_filter([$t['city'], $t['state']]));
          ?>
          <article class="tl-card">

            <div class="tl-media">
              <?php if ($tImg): ?>
                <img src="<?= e($tImg) ?>" alt="<?= e($t['name']) ?>" loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                <div class="ph" style="display:none">🛕</div>
              <?php else: ?>
                <div class="ph">🛕</div>
              <?php endif; ?>

              <?php if (!empty($t['type'])): ?>
                <span class="tl-type"><?= e($t['type']) ?></span>
              <?php endif; ?>

              <?php if (!empty($t['is_featured'])): ?>
                <span class="tl-featured">★ Featured</span>
              <?php endif; ?>

              <?php if ($loc !== ''): ?>
                <span class="tl-loc">
                  <svg viewBox="0 0 24 24" width="11" height="11" fill="none"
                       stroke="currentColor" stroke-width="2.2"
                       stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                  <?= e($loc) ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="tl-body">
              <h3 class="tl-name">
                <a href="<?= url('temple.php?slug=' . urlencode($t['slug'])) ?>">
                  <?= e($t['name']) ?>
                </a>
              </h3>

              <?php if (!empty($t['short_desc'])): ?>
                <p class="tl-desc"><?= e($t['short_desc']) ?></p>
              <?php endif; ?>

              <?php if ($tt['has_any']): ?>
                <div class="tl-timing<?= $tt['closed_today'] ? ' is-closed-today' : '' ?>">
                  <?php if ($tt['has_time']): ?>
                    <div class="tl-timing__row">
                      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      <span><?= e(tpl_time_fmt($tt['open_time'])) ?> – <?= e(tpl_time_fmt($tt['close_time'])) ?></span>
                    </div>
                  <?php endif; ?>

                  <?php if ($tt['closed_label']): ?>
                    <div class="tl-timing__row">
                      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                      <span><?= e($tt['closed_label']) ?></span>
                    </div>
                  <?php endif; ?>

                  <?php if ($tt['closure_note']): ?>
                    <div class="tl-timing__row">
                      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                      <span><?= e($tt['closure_note']) ?></span>
                    </div>
                  <?php endif; ?>

                  <?php if ($tt['closed_today']): ?>
                    <span class="tl-timing__badge">Closed Today</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="tl-foot">
                <div class="tl-pujas">
                  <span class="lbl">Explore</span>
                  <span class="val">Book Puja</span>
                </div>
                <a href="<?= url('temple.php?slug=' . urlencode($t['slug'])) ?>"
                   class="tl-btn">
                  <span>View Temple</span>
                  <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
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