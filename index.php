<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  HOMEPAGE IMAGE HELPERS
 * ================================================================== */
if (!function_exists('safe_img_url')) {
    function safe_img_url(?string $stored): string {
        if (!$stored) return '';
        $stored = ltrim(trim((string) $stored), '/');
        if (preg_match('#^https?://#i', $stored)) return $stored;
        if (strpos($stored, 'assets/img/') === 0) {
            $stored = substr($stored, strlen('assets/img/'));
        }
        $marker = 'assets/img/';
        $pos = strpos($stored, $marker);
        if ($pos !== false) $stored = substr($stored, $pos + strlen($marker));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}
if (!function_exists('safe_img')) {
    function safe_img(?string $stored, string $alt = '', string $class = '', bool $lazy = true): string {
        $src = safe_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($class ?: 'img-ph', ENT_QUOTES) . '" '
                 . 'style="background:#F6E8CF;display:grid;place-items:center;'
                 . 'color:#806D5D;min-height:120px;width:100%;height:100%">'
                 . '<i class="bi bi-image"></i></div>';
        }
        $lazyAttr = $lazy ? ' loading="lazy"' : '';
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="'
             . htmlspecialchars($alt, ENT_QUOTES) . '"'
             . ($class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '')
             . $lazyAttr . '>';
    }
}

/* ==================================================================
 |  TEMPLE TIMING HELPERS
 * ================================================================== */
if (!function_exists('tp_time_fmt')) {
    function tp_time_fmt(?string $hhmm): string {
        if (!$hhmm) return '';
        $t = strtotime($hhmm);
        return $t ? date('g:i A', $t) : $hhmm;
    }
}
if (!function_exists('tp_timing_data')) {
    function tp_timing_data(array $t): array {
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
 |  AJAX handlers
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require BASE_PATH . '/includes/handlers/cart-handler.php';
    require BASE_PATH . '/includes/handlers/wishlist-handler.php';
    require BASE_PATH . '/includes/handlers/newsletter-handler.php';
}

/* ==================================================================
 |  Page data
 * ================================================================== */
$templesLight = get_temples_light();
$templesDark  = get_temples_dark();
$pujas        = get_popular_pujas(8);
$products     = get_featured_products(12);
$articles     = get_featured_articles(8);
$trending     = get_trending_articles(4);
$festivals    = get_upcoming_festivals(6);
$testimonials = get_testimonials();
$faqs         = get_faqs();
$gyanCats     = get_gyan_categories();

$pageTitle = 'Divya Aastha — Puja Booking, Siddha Store & Dharmik Gyan';
$pageDesc  = 'Book authentic Hindu pujas, receive sacred prasad at home, shop rudraksha & consecrated tools.';

require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   PREMIUM THEME
   ============================================================ */
:root{
  --card-r:14px;
  --card-bd:#DCC49A;
  --card-bd-h:#D6A83E;
  --card-sh:0 1px 3px rgba(49,5,8,.05);
  --card-sh-h:0 8px 18px rgba(49,5,8,.10);
  --pm-maroon:#4B080C;
  --pm-burgundy:#310508;
  --pm-gold:#D6A83E;
  --pm-gold-lt:#E8C66A;
  --pm-cream:#F6E8CF;
  --pm-ivory:#FFF9F0;
  --pm-text:#432B26;
  --pm-muted:#806D5D;
  --pm-border:#DCC49A;
}

.carousel-nav{position:relative;z-index:5}
.c-nav{cursor:pointer;pointer-events:auto;position:relative;z-index:5}
.carousel-track{
  overflow-x:auto;overflow-y:hidden;scroll-behavior:smooth;
  -webkit-overflow-scrolling:touch;scrollbar-width:none;-ms-overflow-style:none;
}
.carousel-track::-webkit-scrollbar{display:none}

#temples.section.sand{margin-top:0!important;padding-top:48px!important}
@media (max-width:768px){
  #temples.section.sand{ padding-top:32px!important; }
}

/* FAQ */
.faq-q{
  cursor:pointer;width:100%;display:flex;align-items:center;
  justify-content:space-between;gap:20px;background:none;border:none;
  text-align:left;font:inherit;
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16.6px;font-weight:600;color:var(--pm-maroon);
  padding:24px 8px;transition:color .25s;
}
.faq-q:hover{color:var(--pm-gold)}
.faq-q .chev{
  width:32px;height:32px;border-radius:50%;flex:0 0 auto;
  display:grid;place-items:center;
  border:1px solid rgba(214,168,62,.4);color:var(--pm-gold);
  transition:all .4s cubic-bezier(.22,.68,.32,1);
  transform:rotate(0deg);
}
.faq-item.open .faq-q .chev{
  background:var(--pm-maroon);border-color:transparent;color:var(--pm-gold-lt);
  transform:rotate(180deg);
}
.faq-a{max-height:0;overflow:hidden;transition:max-height .48s cubic-bezier(.22,.68,.32,1)}
.faq-a p{
  padding:0 8px 26px;font-size:13.8px;color:var(--pm-muted);
  line-height:1.8;max-width:780px;margin:0;
}
.faq-item.open{background:var(--pm-cream)}

/* ═══════════════ TEMPLE CARDS (Compact) ═══════════════ */
.temple-card{
  background:var(--pm-ivory)!important;border:1px solid var(--card-bd)!important;
  border-radius:var(--card-r)!important;overflow:hidden!important;
  display:flex!important;flex-direction:column!important;
  box-shadow:var(--card-sh)!important;
  transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease!important;
}
.temple-card:hover{
  transform:translateY(-3px)!important;
  border-color:var(--card-bd-h)!important;
  box-shadow:var(--card-sh-h)!important;
}
.temple-media{
  position:relative!important;
  aspect-ratio:16/11!important;
  overflow:hidden!important;background:var(--pm-cream)!important;
}
.temple-media img{
  width:100%!important;height:100%!important;
  object-fit:cover!important;display:block!important;
  transition:transform .35s ease!important;
}
.temple-card:hover .temple-media img{transform:scale(1.05)!important}
.temple-type{
  position:absolute!important;top:12px!important;left:12px!important;
  padding:5px 12px!important;border-radius:8px!important;
  font-size:10.5px!important;font-weight:700!important;
  letter-spacing:.5px!important;text-transform:uppercase!important;
  background:var(--pm-gold)!important;color:var(--pm-burgundy)!important;
  border:1px solid var(--pm-gold)!important;box-shadow:none!important;
}
.temple-loc{
  position:absolute!important;bottom:12px!important;left:12px!important;
  padding:5px 12px!important;border-radius:8px!important;
  font-size:12px!important;font-weight:600!important;
  background:rgba(255,249,240,.95)!important;color:var(--pm-maroon)!important;
  box-shadow:none!important;
}
.temple-body{
  padding:16px 18px 16px!important;
  display:flex!important;flex-direction:column!important;
  flex:1!important;gap:8px!important;
  background:var(--pm-ivory)!important;
}
.temple-body h3{
  font-family:var(--font-serif,serif)!important;
  font-size:17px!important;line-height:1.3!important;
  font-weight:600!important;color:var(--pm-maroon)!important;margin:0!important;
  white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;
}

/* ✅ FIXED — description clamp to exactly 2 lines */
.temple-body p{
  margin:0!important;
  color:var(--pm-muted)!important;
  font-size:13px!important;
  line-height:1.5!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;
  -webkit-box-orient:vertical!important;
  overflow:hidden!important;
  text-overflow:ellipsis!important;
  height:39px!important;
  max-height:39px!important;
  flex:0 0 auto!important;
}

.temple-card .temple-body .btn,
.temple-card .temple-body a.btn{
  align-self:stretch!important;width:100%!important;
  padding:11px 18px!important;font-size:13.5px!important;
  font-weight:600!important;border-radius:10px!important;
  margin-top:auto!important;text-align:center!important;justify-content:center!important;
  background:var(--pm-maroon)!important;color:var(--pm-ivory)!important;
  border:1px solid var(--pm-maroon)!important;
}
.temple-card .temple-body .btn:hover{
  background:var(--pm-burgundy)!important;color:var(--pm-gold-lt)!important;
  border-color:var(--pm-burgundy)!important;
}

/* ═══════════════ TEMPLE TIMINGS ═══════════════ */
.temple-timing{
  display:flex;flex-direction:column;gap:4px;
  padding:8px 11px;margin:0;
  background:linear-gradient(135deg,#FBF3E1,#F6E8CF);
  border:1px solid rgba(214,168,62,.32);
  border-radius:9px;
  font-size:11.5px;color:#6B4A3E;line-height:1.4;
}
.temple-timing__row{display:flex;align-items:flex-start;gap:6px}
.temple-timing__row i{flex-shrink:0;margin-top:1px;color:#B58A2B;font-size:12px}
.temple-timing__row span{flex:1;font-weight:500}
.temple-timing.is-closed-today{
  background:linear-gradient(135deg,#FDEEEB,#F9E0DB);
  border-color:rgba(183,58,34,.38);color:#8B2A1C;
}
.temple-timing.is-closed-today i{color:#B73A22}
.temple-timing__badge{
  align-self:flex-start;margin-top:2px;padding:2px 9px;
  font-size:9.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;
  background:#B73A22;color:#FFF3D2;border-radius:99px;
}

.dark-temple-card .temple-timing{
  background:rgba(214,168,62,.10);
  border-color:rgba(214,168,62,.30);
  color:rgba(246,232,207,.85);
}
.dark-temple-card .temple-timing__row i{color:#D6A83E}
.dark-temple-card .temple-timing.is-closed-today{
  background:rgba(183,58,34,.20);
  border-color:rgba(183,58,34,.50);
  color:#FFB3A0;
}
.dark-temple-card .temple-timing.is-closed-today i{color:#FF6B4A}

/* ═══════════════ PUJA CARDS ═══════════════ */
.puja-card{
  background:var(--pm-ivory)!important;border:1px solid var(--card-bd)!important;
  border-radius:var(--card-r)!important;overflow:hidden!important;
  display:flex!important;flex-direction:column!important;
  box-shadow:var(--card-sh)!important;
  transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease!important;
}
.puja-card:hover{
  transform:translateY(-3px)!important;
  border-color:var(--card-bd-h)!important;
  box-shadow:var(--card-sh-h)!important;
}
.pj-media{
  position:relative!important;aspect-ratio:5/4!important;
  overflow:hidden!important;background:var(--pm-cream)!important;
}
.pj-media img{
  width:100%!important;height:100%!important;
  object-fit:cover!important;display:block!important;
  transition:transform .35s ease!important;
}
.puja-card:hover .pj-media img{transform:scale(1.05)!important}
.pj-badge{
  position:absolute!important;top:16px!important;left:16px!important;
  padding:6px 14px!important;border-radius:8px!important;
  font-size:11px!important;font-weight:700!important;
  letter-spacing:.5px!important;text-transform:uppercase!important;
  background:var(--pm-gold)!important;color:var(--pm-burgundy)!important;
  border:1px solid var(--pm-gold)!important;box-shadow:none!important;
}
.pj-loc{
  position:absolute!important;bottom:16px!important;left:16px!important;
  padding:6px 14px!important;border-radius:8px!important;
  font-size:13px!important;font-weight:600!important;
  background:rgba(255,249,240,.95)!important;color:var(--pm-maroon)!important;
  box-shadow:none!important;
}
.pj-body{
  padding:24px 24px 22px!important;
  display:flex!important;flex-direction:column!important;
  flex:1!important;gap:12px!important;
  background:var(--pm-ivory)!important;
}
.pj-body h3{
  font-family:var(--font-serif,serif)!important;
  font-size:19px!important;line-height:1.35!important;
  font-weight:600!important;color:var(--pm-maroon)!important;margin:0!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;min-height:52px!important;
}
.pj-body p{
  margin:0!important;color:var(--pm-muted)!important;
  font-size:14px!important;line-height:1.6!important;
  display:-webkit-box!important;
  -webkit-line-clamp:3!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;min-height:66px!important;
}
.pj-foot{
  display:flex!important;align-items:center!important;
  justify-content:space-between!important;gap:14px!important;
  padding-top:16px!important;margin-top:auto!important;
  border-top:1px solid var(--pm-border)!important;
}
.pj-price{display:flex!important;flex-direction:column!important;line-height:1.15!important}
.pj-price span{
  font-size:11px!important;color:var(--pm-muted)!important;
  letter-spacing:.6px!important;text-transform:uppercase!important;
}
.pj-price b{
  font-size:22px!important;font-weight:700!important;
  color:var(--pm-maroon)!important;margin-top:2px!important;
}
.puja-card .pj-foot .btn,
.puja-card .pj-foot a.btn{
  padding:12px 22px!important;font-size:14px!important;
  font-weight:600!important;border-radius:10px!important;
  white-space:nowrap!important;width:auto!important;
  background:var(--pm-maroon)!important;color:var(--pm-ivory)!important;
  border:1px solid var(--pm-maroon)!important;
}
.puja-card .pj-foot .btn:hover{
  background:var(--pm-burgundy)!important;color:var(--pm-gold-lt)!important;
  border-color:var(--pm-burgundy)!important;
}

/* ═══════════════ SHOP CARDS ═══════════════ */
.shop-card{
  background:var(--pm-ivory)!important;border:1px solid var(--card-bd)!important;
  border-radius:var(--card-r)!important;overflow:hidden!important;
  display:flex!important;flex-direction:column!important;
  box-shadow:var(--card-sh)!important;
  transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease!important;
}
.shop-card:hover{
  transform:translateY(-3px)!important;
  border-color:var(--card-bd-h)!important;
  box-shadow:var(--card-sh-h)!important;
}
.shop-media{
  position:relative!important;aspect-ratio:1/1!important;
  overflow:hidden!important;background:var(--pm-cream)!important;
}
.shop-media img{
  width:100%!important;height:100%!important;
  object-fit:cover!important;display:block!important;
  transition:transform .35s ease!important;
}
.shop-card:hover .shop-media img{transform:scale(1.05)!important}
.shop-badge{
  position:absolute!important;top:16px!important;left:16px!important;
  padding:6px 14px!important;border-radius:8px!important;
  font-size:11px!important;font-weight:700!important;
  letter-spacing:.5px!important;text-transform:uppercase!important;
  background:var(--pm-gold)!important;color:var(--pm-burgundy)!important;
  box-shadow:none!important;
}
.wish-btn{
  position:absolute!important;top:16px!important;right:16px!important;
  width:40px!important;height:40px!important;
  border-radius:10px!important;background:rgba(255,249,240,.95)!important;
  box-shadow:none!important;color:var(--pm-maroon)!important;
}
.shop-body{
  padding:24px 24px 22px!important;
  display:flex!important;flex-direction:column!important;
  flex:1!important;gap:12px!important;
  background:var(--pm-ivory)!important;
}
.shop-body h3{
  font-family:var(--font-serif,serif)!important;
  font-size:18px!important;line-height:1.35!important;
  font-weight:600!important;color:var(--pm-maroon)!important;margin:0!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;min-height:50px!important;
}
.shop-body p{
  margin:0!important;color:var(--pm-muted)!important;
  font-size:14px!important;line-height:1.6!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;min-height:45px!important;
}
.shop-price{
  display:flex!important;align-items:baseline!important;
  gap:8px!important;flex-wrap:wrap!important;margin-top:auto!important;
}
.shop-price .now{font-size:21px!important;font-weight:700!important;color:var(--pm-maroon)!important}
.shop-price .was{font-size:14px!important;color:var(--pm-muted)!important;text-decoration:line-through!important}
.shop-price .off{font-size:12px!important;font-weight:700!important;color:var(--pm-gold)!important}
.shop-body .btn{
  padding:13px 20px!important;font-size:14.5px!important;
  font-weight:600!important;border-radius:10px!important;margin-top:8px!important;
  background:var(--pm-maroon)!important;color:var(--pm-ivory)!important;
  border:1px solid var(--pm-maroon)!important;
}
.shop-body .btn:hover{
  background:var(--pm-burgundy)!important;color:var(--pm-gold-lt)!important;
}

/* ═══════════════ DARK TEMPLES ═══════════════ */
.dark-temple-card{
  background:rgba(255,249,240,.03)!important;
  border:1px solid rgba(214,168,62,.20)!important;
  border-radius:var(--card-r)!important;overflow:hidden!important;
  display:flex!important;flex-direction:column!important;
  box-shadow:none!important;
  transition:transform .18s ease,border-color .18s ease!important;
}
.dark-temple-card:hover{
  transform:translateY(-3px)!important;
  border-color:rgba(214,168,62,.55)!important;
}
.dt-media{
  position:relative!important;aspect-ratio:16/11!important;
  overflow:hidden!important;background:var(--pm-burgundy)!important;
}
.dt-media img{
  width:100%!important;height:100%!important;
  object-fit:cover!important;display:block!important;
  transition:transform .35s ease!important;
}
.dark-temple-card:hover .dt-media img{transform:scale(1.05)!important}
.dt-cat{
  position:absolute!important;top:12px!important;left:12px!important;
  padding:5px 12px!important;border-radius:8px!important;
  font-size:10.5px!important;font-weight:700!important;
  letter-spacing:.5px!important;text-transform:uppercase!important;
  background:rgba(214,168,62,.20)!important;color:var(--pm-gold-lt)!important;
  border:1px solid rgba(214,168,62,.45)!important;
}
.dt-loc{
  position:absolute!important;bottom:12px!important;left:12px!important;
  padding:5px 12px!important;border-radius:8px!important;
  font-size:12px!important;font-weight:600!important;
  background:rgba(49,5,8,.85)!important;color:var(--pm-cream)!important;
}
.dt-body{
  padding:16px 18px 16px!important;
  display:flex!important;flex-direction:column!important;
  flex:1!important;gap:8px!important;
}
.dt-body h3{
  font-family:var(--font-serif,serif)!important;
  font-size:17px!important;line-height:1.3!important;
  font-weight:600!important;color:var(--pm-cream)!important;margin:0!important;
  white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;
}

/* ✅ FIXED — dark description clamp to exactly 2 lines */
.dt-body p{
  margin:0!important;
  color:rgba(246,232,207,.70)!important;
  font-size:13px!important;
  line-height:1.5!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;
  -webkit-box-orient:vertical!important;
  overflow:hidden!important;
  text-overflow:ellipsis!important;
  height:39px!important;
  max-height:39px!important;
  flex:0 0 auto!important;
}

.dark-temple-card .dt-body .btn{
  align-self:stretch!important;width:100%!important;
  padding:11px 18px!important;font-size:13.5px!important;
  font-weight:600!important;border-radius:10px!important;
  margin-top:auto!important;text-align:center!important;justify-content:center!important;
  background:var(--pm-gold)!important;color:var(--pm-burgundy)!important;
  border:1px solid var(--pm-gold)!important;
}
.dark-temple-card .dt-body .btn:hover{
  background:var(--pm-gold-lt)!important;color:var(--pm-burgundy)!important;
}

/* ═══════════════ ARTICLE CARDS ═══════════════ */
.article-card{
  background:var(--pm-ivory)!important;border:1px solid var(--card-bd)!important;
  border-radius:var(--card-r)!important;overflow:hidden!important;
  display:flex!important;flex-direction:column!important;
  box-shadow:var(--card-sh)!important;
  transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease!important;
}
.article-card:hover{
  transform:translateY(-3px)!important;
  border-color:var(--card-bd-h)!important;
  box-shadow:var(--card-sh-h)!important;
}
.ar-media{
  position:relative!important;aspect-ratio:5/4!important;
  overflow:hidden!important;background:var(--pm-cream)!important;
}
.ar-media img{
  width:100%!important;height:100%!important;
  object-fit:cover!important;display:block!important;
  transition:transform .35s ease!important;
}
.article-card:hover .ar-media img{transform:scale(1.05)!important}
.ar-cat{
  position:absolute!important;top:16px!important;left:16px!important;
  padding:6px 14px!important;border-radius:8px!important;
  font-size:11px!important;font-weight:700!important;
  letter-spacing:.5px!important;text-transform:uppercase!important;
  background:var(--pm-gold)!important;color:var(--pm-burgundy)!important;
  border:1px solid var(--pm-gold)!important;
}
.ar-body{
  padding:24px 24px 22px!important;
  display:flex!important;flex-direction:column!important;
  flex:1!important;gap:12px!important;
  background:var(--pm-ivory)!important;
}
.ar-body h3{
  font-family:var(--font-serif,serif)!important;
  font-size:18.5px!important;line-height:1.35!important;
  font-weight:600!important;color:var(--pm-maroon)!important;margin:0!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;min-height:52px!important;
}
.ar-body h3 a{color:inherit!important;text-decoration:none!important}
.ar-body h3 a:hover{color:var(--pm-gold)!important}
.ar-body p{
  margin:0!important;color:var(--pm-muted)!important;
  font-size:14px!important;line-height:1.6!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;min-height:45px!important;
}
.ar-meta{
  display:flex!important;align-items:center!important;gap:8px!important;
  color:var(--pm-muted)!important;font-size:12.5px!important;
  margin-top:auto!important;padding-top:8px!important;
}
.ar-meta .dot{
  width:3px!important;height:3px!important;border-radius:50%!important;
  background:var(--pm-border)!important;display:inline-block!important;
}
.ar-body .btn-ghost{
  font-size:13.5px!important;font-weight:600!important;
  color:var(--pm-gold)!important;text-decoration:none!important;margin-top:8px!important;
}
.ar-body .btn-ghost:hover{color:var(--pm-maroon)!important}

/* ═══════════════ FESTIVAL CARDS ═══════════════ */
.fest-card{
  position:relative;
  border-radius:var(--card-r)!important;
  overflow:hidden!important;
  min-height:250px;
  display:flex;
  flex-direction:column;
  justify-content:flex-end;
  background:linear-gradient(160deg,#4B080C 0%,#310508 100%)!important;
  border:1px solid rgba(214,168,62,.25)!important;
  transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease!important;
}
.fest-card:hover{
  transform:translateY(-3px)!important;
  border-color:rgba(214,168,62,.60)!important;
  box-shadow:0 14px 32px -18px rgba(49,5,8,.55)!important;
}
.fest-card::before{
  content:"";
  position:absolute;
  top:-50px;right:-50px;
  width:180px;height:180px;
  border-radius:50%;
  background:radial-gradient(circle,rgba(214,168,62,.25) 0%,transparent 70%);
  pointer-events:none;
}
.fest-card::after{
  content:"";
  position:absolute;
  inset:10px;
  border:1px solid rgba(214,168,62,.20);
  border-radius:10px;
  pointer-events:none;
}
.fest-inner{
  position:relative;
  z-index:2;
  padding:26px 24px 22px!important;
  display:flex;
  flex-direction:column;
  gap:10px;
}
.fest-date{
  display:inline-flex;
  align-items:center;
  gap:7px;
  align-self:flex-start;
  padding:5px 12px;
  border-radius:99px;
  font-size:10.5px;
  font-weight:700;
  letter-spacing:.8px;
  text-transform:uppercase;
  color:var(--pm-gold-lt);
  background:rgba(214,168,62,.15);
  border:1px solid rgba(214,168,62,.40);
  margin-bottom:2px;
}
.fest-inner h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  color:var(--pm-cream)!important;
  font-size:20px!important;
  margin:0!important;
  line-height:1.25;
  font-weight:600;
}
.fest-inner p{
  color:rgba(246,232,207,.75)!important;
  font-size:13px!important;
  line-height:1.6!important;
  margin:0 0 6px!important;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}
.fest-inner .btn-ghost{
  color:var(--pm-gold)!important;
  font-size:12.5px!important;
  font-weight:600;
  padding:0;
}
.fest-inner .btn-ghost:hover{color:var(--pm-gold-lt)!important}

/* ═══════════════ TRENDING SMALL ═══════════════ */
.trend-small{
  background:var(--pm-ivory)!important;border:1px solid var(--card-bd)!important;
  border-radius:var(--card-r)!important;overflow:hidden!important;
  box-shadow:var(--card-sh)!important;
  transition:transform .18s ease,border-color .18s ease!important;
}
.trend-small:hover{
  transform:translateY(-3px)!important;border-color:var(--card-bd-h)!important;
}
.ts-media{aspect-ratio:5/4!important;overflow:hidden!important;background:var(--pm-cream)!important}
.ts-media img{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important}
.ts-body{padding:18px!important;background:var(--pm-ivory)!important}
.ts-body h4{
  font-size:16px!important;line-height:1.4!important;
  display:-webkit-box!important;
  -webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;
  overflow:hidden!important;
  color:var(--pm-maroon)!important;
}
.ts-body h4 a{color:inherit!important;text-decoration:none!important}
.ts-body h4 a:hover{color:var(--pm-gold)!important}
.ts-cat{color:var(--pm-gold)!important}
</style>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero" id="home">
  <div class="hero-mandala"></div>
  <div class="container hero-grid">
    <div class="hero-content">
      <span class="hero-pill">
        <span class="avatars"><i>R</i><i>S</i><i>A</i></span>
        <?= e(setting('devotee_count', '10 Lakh+')) ?> Happy Devotees
      </span>
      <h1>
        EXPERIENCE HINDU<br>
        <span class="accent">Pujas &amp; Rituals</span><br>
        WITH SHRADDHA
        <span class="sub">Authentic temples. Verified priests. Divine blessings.</span>
      </h1>
      <div class="hero-rule"><span>ॐ</span></div>
      <p class="hero-lead">Offer Puja, watch Darshan videos, receive Holy Prasad at your home from the most auspicious temples across India.</p>

      <div class="hero-trust">
        <span class="trust-chip"><span class="stars">★★★★★</span> <?= e(setting('rating', '4.8')) ?> / 5 Rating</span>
        <span class="trust-chip">Authentic Prasad</span>
        <span class="trust-chip">Verified Priests</span>
      </div>

      <div class="hero-cta">
        <a href="<?= url('pujas.php') ?>" class="btn btn-terra">Participate in Puja →</a>
        <a href="<?= url('shop.php') ?>" class="btn btn-outline">Explore Sacred Store</a>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-collage">
        <figure class="hc-item hc-main">
          <?= safe_img('Pageindex/3img.jpg.jpeg', 'Havan & Yagna — Divya Aastha', '', false) ?>
          <figcaption class="hc-cap">Divya Aastha · Bhakti in Every Ritual</figcaption>
        </figure>
        <figure class="hc-item hc-circle">
          <?= safe_img('Pageindex/4img.jpg.jpeg', 'Shivling Abhishek', '', false) ?>
        </figure>
        <figure class="hc-item hc-a">
          <?= safe_img('Pageindex/5img.jpg.jpeg', 'Puja Offering', '', false) ?>
        </figure>
        <figure class="hc-item hc-b">
          <?= safe_img('Pageindex/6img.jpg.jpeg', 'Temple Blessings', '', false) ?>
        </figure>
        <div class="hc-chip">
          <span class="fc-ico">🕉</span>
          <span><b>Live Darshan</b><span>12 temples online</span></span>
        </div>
        <div class="hc-chip-2">
          <span class="dot"></span>
          Prasad shipped across Bharat
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ FEATURED ON ═══════════════ -->
<section class="featured-on">
  <div class="container">
    <p class="fo-title">Featured On</p>
    <div class="fo-row">
      <?php foreach (['Economic Times','Times of India','Hindustan Times','India Today','YourStory','NDTV'] as $outlet): ?>
        <span class="fo-item"><?= e($outlet) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ TEMPLES CAROUSEL ═══════════════ -->
<section class="section sand" id="temples">
  <div class="container">
    <div class="carousel-head">
      <div class="section-head left">
        <span class="eyebrow">Divine Destinations</span>
        <h2>Sacred Temples of Bharat</h2>
        <p>Offer pujas at these revered shrines and receive divine blessings.</p>
      </div>
      <div class="carousel-nav">
        <button class="c-nav" id="templePrev" type="button" aria-label="Previous">‹</button>
        <button class="c-nav" id="templeNext" type="button" aria-label="Next">›</button>
      </div>
    </div>

    <div class="carousel-track" id="templeTrack">
      <?php foreach ($templesLight as $t): ?>
        <?php $tt = tp_timing_data($t); ?>
        <article class="temple-card">
          <div class="temple-media">
            <?= safe_img($t['image_url'], $t['name']) ?>
            <?php if (!empty($t['type'])): ?>
              <span class="temple-type"><?= e($t['type']) ?></span>
            <?php endif; ?>
            <?php if (!empty($t['city']) || !empty($t['state'])): ?>
              <span class="temple-loc">📍 <?= e(implode(', ', array_filter([$t['city'], $t['state']]))) ?></span>
            <?php endif; ?>
          </div>
          <div class="temple-body">
            <h3><?= e($t['name']) ?></h3>
            <p><?= e($t['short_desc']) ?></p>

            <?php if ($tt['has_any']): ?>
              <div class="temple-timing<?= $tt['closed_today'] ? ' is-closed-today' : '' ?>">
                <?php if ($tt['has_time']): ?>
                  <div class="temple-timing__row">
                    <i class="bi bi-clock"></i>
                    <span><?= e(tp_time_fmt($tt['open_time'])) ?> – <?= e(tp_time_fmt($tt['close_time'])) ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($tt['closed_label']): ?>
                  <div class="temple-timing__row">
                    <i class="bi bi-calendar-week"></i>
                    <span><?= e($tt['closed_label']) ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($tt['closure_note']): ?>
                  <div class="temple-timing__row">
                    <i class="bi bi-info-circle"></i>
                    <span><?= e($tt['closure_note']) ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($tt['closed_today']): ?>
                  <span class="temple-timing__badge">Closed Today</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <a href="<?= url('temple.php?slug=' . urlencode($t['slug'])) ?>" class="btn btn-terra btn-sm">
              Book Puja →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="progress-bar"><div class="pb-fill" id="templeProgress"></div></div>
  </div>
</section>

<!-- ═══════════════ STORE ═══════════════ -->
<section class="section" id="store">
  <div class="container">
    <div class="carousel-head">
      <div class="section-head left">
        <span class="eyebrow">Siddha Store</span>
        <h2>Siddha Store — Sacred Gemstones &amp; Consecrated Tools</h2>
        <p>Authentic rudraksha, healing crystals, malas and sacred tools handpicked for your spiritual well-being.</p>
      </div>
      <div class="carousel-nav">
        <button class="c-nav" id="shopPrev" type="button" aria-label="Previous">‹</button>
        <button class="c-nav" id="shopNext" type="button" aria-label="Next">›</button>
      </div>
    </div>

    <div class="carousel-track" id="shopTrack">
      <?php foreach ($products as $p): ?>
        <?php $off = function_exists('off_percent') ? off_percent($p) : 0; ?>
        <article class="shop-card">
          <div class="shop-media">
            <?= safe_img($p['image_url'], $p['name']) ?>
            <?php if (!empty($p['badge'])): ?>
              <span class="shop-badge <?= e($p['badge_style'] ?? 'plum') ?>"><?= e($p['badge']) ?></span>
            <?php endif; ?>
            <button class="wish-btn" data-wish-id="<?= (int) $p['id'] ?>" aria-label="Wishlist">
              <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
            </button>
          </div>
          <div class="shop-body">
            <h3><?= e($p['name']) ?></h3>
            <p><?= e($p['short_desc']) ?></p>
            <div class="shop-price">
              <span class="now"><?= inr($p['price']) ?></span>
              <?php if (!empty($p['mrp']) && $p['mrp'] > $p['price']): ?>
                <span class="was"><?= inr($p['mrp']) ?></span>
              <?php endif; ?>
              <?php if ($off): ?><span class="off"><?= $off ?>% OFF</span><?php endif; ?>
            </div>
            <button class="btn btn-plum" data-add-to-cart data-product-id="<?= (int) $p['id'] ?>">Buy Now</button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="progress-bar"><div class="pb-fill" id="shopProgress"></div></div>
  </div>
</section>

<!-- ═══════════════ HOW TO BOOK ═══════════════ -->
<section class="section sand">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Simple &amp; Sacred</span>
      <h2>How to Book Puja on <?= e(APP_NAME) ?></h2>
      <p>Simple process — register, receive puja proof, get prasad at home.</p>
    </div>
    <div class="steps">
      <?php
      $steps = [
        ['Choose Your Puja',        'Select the puja according to your spiritual needs.'],
        ['Choose Temple & Date',    'Select your preferred temple and available puja date.'],
        ['Complete Booking',        'Enter your details and complete secure payment.'],
        ['Receive Blessings & Prasad', 'The puja is performed and sacred prasad is delivered.'],
      ];
      foreach ($steps as $i => [$t, $d]): ?>
        <article class="step">
          <div class="step-num"><?= str_pad((string) ($i+1), 2, '0', STR_PAD_LEFT) ?></div>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ YATRA / GYAN TILES ═══════════════ -->
<section class="section" id="gyan">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Begin Your Journey</span>
      <h2>Your Dharmik Yatra with <?= e(APP_NAME) ?></h2>
      <p>A seamless journey through puja, prasadam, knowledge and spiritual experiences.</p>
    </div>
    <div class="yatra-grid">
      <?php foreach (array_slice($gyanCats, 0, 5) as $cat): ?>
        <article class="yatra-card">
          <div class="yatra-ico">
            <svg viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M6 8c3 0 6-2 6-5 0 3 3 5 6 5"/></svg>
          </div>
          <h3><?= e($cat['name']) ?></h3>
          <p><?= e($cat['description']) ?></p>
          <a href="<?= url('gyan.php?cat=' . urlencode($cat['slug'])) ?>" class="btn-ghost">Explore →</a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ TESTIMONIALS ═══════════════ -->
<section class="section sand">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Devotee Voices</span>
      <h2>What Devotees Say</h2>
      <p>Devotees rate <?= e(APP_NAME) ?> highly for authentic puja &amp; prasad delivery.</p>
    </div>
    <div class="testi-grid">
      <?php foreach ($testimonials as $t): ?>
        <article class="testi-card">
          <div class="testi-stars"><?= str_repeat('★', (int) $t['rating']) ?></div>
          <p><?= e($t['message']) ?></p>
          <div class="testi-user">
            <span class="testi-avatar"><?= e($t['initials']) ?></span>
            <span><b><?= e($t['name']) ?></b><span><?= e($t['city']) ?></span></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ GYAN TILES ═══════════════ -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Dharmik Gyan</span>
      <h2>Collection of Dharmik Gyan</h2>
      <p>Mantra, Stotra, Chalisa, Aarti &amp; sacred wisdom for every devotee.</p>
    </div>
    <div class="gyan-grid">
      <?php foreach ($gyanCats as $g): ?>
        <a href="<?= url('gyan.php?cat=' . urlencode($g['slug'])) ?>" class="gyan-tile">
          <div class="gyan-ico">
            <svg viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M6 8c3 0 6-2 6-5 0 3 3 5 6 5"/></svg>
          </div>
          <h3><?= e($g['name']) ?></h3>
          <p><?= e($g['description']) ?></p>
          <span class="cat-link">Explore →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ ARTICLES ═══════════════ -->
<section class="section sand">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Spiritual Knowledge</span>
      <h2>Read, Learn &amp; Grow</h2>
      <p>Deeply researched articles on rituals, mantras and Hindu philosophy.</p>
    </div>
    <div class="article-grid">
      <?php foreach ($articles as $a): ?>
        <article class="article-card">
          <div class="ar-media">
            <?= safe_img($a['image_url'], $a['title']) ?>
            <span class="ar-cat"><?= e($a['category_name'] ?? 'Gyan') ?></span>
          </div>
          <div class="ar-body">
            <h3><a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a></h3>
            <p><?= e($a['excerpt']) ?></p>
            <div class="ar-meta">
              <span><?= (int) $a['read_minutes'] ?> min read</span>
              <span class="dot"></span>
              <span><?= $a['published_at'] ? date('d M Y', strtotime($a['published_at'])) : '' ?></span>
            </div>
            <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="btn-ghost">Read Article →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ TRENDING ═══════════════ -->
<?php if (!empty($trending)): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Trending Now</span>
      <h2>Trending Topics</h2>
      <p>Latest articles on festivals, rituals, traditions and spiritual guidance.</p>
    </div>
    <div class="trend-grid">
      <?php $feature = array_shift($trending); ?>
      <article class="trend-feature">
        <?= safe_img($feature['image_url'], $feature['title']) ?>
        <div class="tf-content">
          <span class="tag"><?= e($feature['category_name'] ?? 'Featured') ?></span>
          <h3><?= e($feature['title']) ?></h3>
          <p><?= e($feature['excerpt']) ?></p>
          <a href="<?= url('article.php?slug=' . urlencode($feature['slug'])) ?>" class="read">Read More →</a>
        </div>
      </article>

      <?php foreach ($trending as $t): ?>
        <article class="trend-small">
          <div class="ts-media"><?= safe_img($t['image_url'], $t['title']) ?></div>
          <div class="ts-body">
            <span class="ts-cat"><?= e($t['category_name'] ?? 'Gyan') ?></span>
            <h4><a href="<?= url('article.php?slug=' . urlencode($t['slug'])) ?>"><?= e($t['title']) ?></a></h4>
            <span><?= (int) $t['read_minutes'] ?> min read</span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ FESTIVALS ═══════════════ -->
<section class="section sand" id="festivals">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Tithi &amp; Muhurat</span>
      <h2>Upcoming Festivals</h2>
      <p>Plan your puja, vrat and celebrations with our festival calendar.</p>
    </div>
    <div class="fest-grid">
      <?php foreach ($festivals as $f): ?>
        <article class="fest-card">
          <div class="fest-inner">
            <span class="fest-date">📅 <?= date('d M Y', strtotime($f['festival_date'])) ?></span>
            <h3><?= e($f['name']) ?></h3>
            <p><?= e($f['short_desc']) ?></p>
            <a href="<?= url('festival.php?slug=' . urlencode($f['slug'])) ?>" class="btn-ghost">Explore →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ POPULAR PUJAS ═══════════════ -->
<section class="section" id="puja">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Book A Ritual</span>
      <h2>Popular Puja Services</h2>
      <p>Book authentic rituals performed by experienced priests at sacred temples.</p>
    </div>
    <div class="puja-grid">
      <?php foreach ($pujas as $p): ?>
        <article class="puja-card">
          <div class="pj-media">
            <?= safe_img($p['image_url'], $p['name']) ?>
            <?php if (!empty($p['badge'])): ?>
              <span class="pj-badge <?= e($p['badge_style'] ?? 'gold') ?>"><?= e($p['badge']) ?></span>
            <?php endif; ?>
            <?php if (!empty($p['temple_city'])): ?>
              <span class="pj-loc">📍 <?= e($p['temple_city']) ?></span>
            <?php elseif (!empty($p['temple_name'])): ?>
              <span class="pj-loc">🛕 <?= e($p['temple_name']) ?></span>
            <?php elseif (!empty($p['custom_temple_name'])): ?>
              <span class="pj-loc">🛕 <?= e($p['custom_temple_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="pj-body">
            <h3><?= e($p['name']) ?></h3>
            <p><?= e($p['short_desc']) ?></p>
            <div class="pj-foot">
              <div class="pj-price">
                <span>Starting at</span>
                <b><?= inr($p['price']) ?></b>
              </div>
              <a href="<?= url('puja.php?slug=' . urlencode($p['slug'])) ?>" class="btn btn-terra btn-sm">
                Book Puja →
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ DARK TEMPLES ═══════════════ -->
<?php if (!empty($templesDark)): ?>
<section class="section dark-temples">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Divine Destinations</span>
      <h2>Sacred Temples of Bharat</h2>
      <p>Explore sacred temples and experience divine blessings from India's most revered spiritual destinations.</p>
    </div>
    <div class="dark-temple-grid">
      <?php foreach ($templesDark as $t): ?>
        <?php $tt = tp_timing_data($t); ?>
        <article class="dark-temple-card">
          <div class="dt-media">
            <?= safe_img($t['image_url'], $t['name']) ?>
            <?php if (!empty($t['type'])): ?>
              <span class="dt-cat"><?= e($t['type']) ?></span>
            <?php endif; ?>
            <?php if (!empty($t['city'])): ?>
              <span class="dt-loc">📍 <?= e($t['city']) ?></span>
            <?php endif; ?>
          </div>
          <div class="dt-body">
            <h3><?= e($t['name']) ?></h3>
            <p><?= e($t['short_desc']) ?></p>

            <?php if ($tt['has_any']): ?>
              <div class="temple-timing<?= $tt['closed_today'] ? ' is-closed-today' : '' ?>">
                <?php if ($tt['has_time']): ?>
                  <div class="temple-timing__row">
                    <i class="bi bi-clock"></i>
                    <span><?= e(tp_time_fmt($tt['open_time'])) ?> – <?= e(tp_time_fmt($tt['close_time'])) ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($tt['closed_label']): ?>
                  <div class="temple-timing__row">
                    <i class="bi bi-calendar-week"></i>
                    <span><?= e($tt['closed_label']) ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($tt['closure_note']): ?>
                  <div class="temple-timing__row">
                    <i class="bi bi-info-circle"></i>
                    <span><?= e($tt['closure_note']) ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($tt['closed_today']): ?>
                  <span class="temple-timing__badge">Closed Today</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <a href="<?= url('temple.php?slug=' . urlencode($t['slug'])) ?>" class="btn btn-champagne btn-sm">
              Book Puja →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ FAQ ═══════════════ -->
<section class="section" id="help">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow center">Need Help?</span>
      <h2>Frequently Asked Questions</h2>
      <p>Everything you need to know about puja, prasad delivery and our services.</p>
    </div>
    <div class="faq-wrap">
      <?php foreach ($faqs as $f): ?>
        <div class="faq-item">
          <button class="faq-q" type="button" aria-expanded="false">
            <span><?= e($f['question']) ?></span>
            <span class="chev" aria-hidden="true">▾</span>
          </button>
          <div class="faq-a">
            <p><?= nl2br(e($f['answer'])) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ NEWSLETTER ═══════════════ -->
<section class="section tight">
  <div class="container">
    <div class="newsletter">
      <div class="newsletter-mandala"></div>
      <div>
        <span class="eyebrow no-line">Divine Inbox</span>
        <h2>Stay Connected With Divine Wisdom</h2>
        <p>Receive festival updates, spiritual articles, puja offers and sacred insights directly in your inbox.</p>
      </div>
      <form class="nl-form" id="newsletterForm" novalidate>
        <?= csrf_field() ?>
        <div class="nl-row">
          <input type="email" id="newsletterEmail" name="email" placeholder="Enter your email address" autocomplete="email">
          <button type="submit" class="btn">Subscribe</button>
        </div>
        <p class="nl-msg" id="newsletterMsg"></p>
        <p class="nl-note">We respect your privacy. Unsubscribe anytime.</p>
      </form>
    </div>
  </div>
</section>

<script>
(function () {
  'use strict';

  function initCarousel(opts) {
    var track    = document.getElementById(opts.track);
    var prevBtn  = document.getElementById(opts.prev);
    var nextBtn  = document.getElementById(opts.next);
    var progress = opts.progress ? document.getElementById(opts.progress) : null;

    if (!track || !prevBtn || !nextBtn) return;
    if (track.dataset.carouselBound === '1') return;
    track.dataset.carouselBound = '1';

    function getStep() {
      var first = track.firstElementChild;
      if (!first) return 320;
      var cardW = first.getBoundingClientRect().width;
      var styles = window.getComputedStyle(track);
      var gap = parseFloat(styles.columnGap || styles.gap) || 0;
      return cardW + gap;
    }

    function slide(dir) {
      var step = getStep() * dir;
      if (typeof track.scrollBy === 'function') {
        track.scrollBy({ left: step, behavior: 'smooth' });
      } else {
        track.scrollLeft += step;
      }
    }

    function updateProgress() {
      if (!progress) return;
      var max = track.scrollWidth - track.clientWidth;
      if (max <= 0) { progress.style.width = '100%'; return; }
      var pct = (track.scrollLeft / max) * 100;
      progress.style.width = Math.max(8, Math.min(100, pct)) + '%';
    }

    prevBtn.addEventListener('click', function (e) { e.preventDefault(); slide(-1); });
    nextBtn.addEventListener('click', function (e) { e.preventDefault(); slide(1); });

    track.addEventListener('scroll', function () {
      window.requestAnimationFrame(updateProgress);
    }, { passive: true });

    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(updateProgress, 120);
    });

    updateProgress();
  }

  function bootCarousels() {
    initCarousel({ track: 'templeTrack', prev: 'templePrev', next: 'templeNext', progress: 'templeProgress' });
    initCarousel({ track: 'shopTrack',   prev: 'shopPrev',   next: 'shopNext',   progress: 'shopProgress'   });
  }

  function bootFaq() {
    var faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(function (item) {
      var btn = item.querySelector('.faq-q');
      var ans = item.querySelector('.faq-a');
      if (!btn || !ans) return;

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = item.classList.contains('open');

        faqItems.forEach(function (other) {
          if (other !== item && other.classList.contains('open')) {
            other.classList.remove('open');
            var oBtn = other.querySelector('.faq-q');
            var oAns = other.querySelector('.faq-a');
            if (oBtn) oBtn.setAttribute('aria-expanded', 'false');
            if (oAns) oAns.style.maxHeight = '0px';
          }
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
  }

  function showToast(msg, type) {
    type = type || 'success';
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText =
      'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
      'z-index:99999;padding:14px 22px;border-radius:12px;' +
      'font:600 14px/1.4 system-ui,-apple-system,sans-serif;' +
      'color:#FFF9F0;box-shadow:0 12px 32px rgba(0,0,0,.25);' +
      'opacity:0;transition:opacity .25s ease;pointer-events:none;' +
      (type === 'error' ? 'background:#DC2626;' : 'background:#4B080C;');
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.style.opacity = '1'; });
    setTimeout(function () {
      t.style.opacity = '0';
      setTimeout(function () { t.remove(); }, 300);
    }, 2400);
  }

  function updateCartBadge(count) {
    var el = document.getElementById('cartCount');
    if (!el) return;
    var n = parseInt(count, 10);
    if (isNaN(n)) n = parseInt(el.textContent || '0', 10) + 1;
    el.textContent = String(n);
  }

  function getCsrfToken() {
    var input = document.querySelector('input[name="_csrf"]');
    if (input && input.value) return input.value;
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content') || '';
    var body = document.body;
    if (body && body.dataset && body.dataset.csrf) return body.dataset.csrf;
    return '';
  }

  function handleAddToCart(btn) {
    var pid = btn.getAttribute('data-product-id');
    if (!pid) return;

    if (btn.dataset.loading === '1') return;
    btn.dataset.loading = '1';
    var original = btn.innerHTML;
    btn.innerHTML = 'Adding…';

    var payload = new URLSearchParams();
    payload.append('action',     'cart_add');
    payload.append('product_id', pid);
    payload.append('qty',        '1');

    var token = getCsrfToken();
    if (token) payload.append('_csrf', token);

    fetch(window.location.pathname + window.location.search, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: payload.toString()
    })
    .then(function (r) { return r.text(); })
    .then(function (raw) {
      btn.dataset.loading = '0';
      btn.innerHTML = original;

      var data = null;
      try { data = JSON.parse(raw); } catch (e) { data = null; }

      if (data && data.ok === true) {
        showToast(data.message || 'Added to cart');
        updateCartBadge(data.cart_count);
        try { sessionStorage.setItem('openCartAfterReload', '1'); } catch (e) {}
        setTimeout(function () { window.location.reload(); }, 700);
      } else if (data && data.error) {
        showToast(data.error, 'error');
      } else {
        showToast('Could not add to cart', 'error');
      }
    })
    .catch(function (err) {
      btn.dataset.loading = '0';
      btn.innerHTML = original;
      console.error('[cart]', err);
      showToast('Could not add to cart', 'error');
    });
  }

  function bootCartButtons() {
    document.querySelectorAll('[data-add-to-cart]').forEach(function (btn) {
      if (btn.dataset.cartBound === '1') return;
      btn.dataset.cartBound = '1';
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        handleAddToCart(btn);
      });
    });
  }

  function boot() {
    bootCarousels();
    bootFaq();
    bootCartButtons();

    try {
      if (sessionStorage.getItem('openCartAfterReload') === '1') {
        sessionStorage.removeItem('openCartAfterReload');
        setTimeout(function () {
          var cartBtn = document.getElementById('cartBtn');
          if (cartBtn) cartBtn.click();
        }, 300);
      }
    } catch (e) {}
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('load', function () {
    setTimeout(function () {
      ['templeTrack','shopTrack'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.scrollLeft = 0;
      });
      bootCartButtons();
    }, 150);
  });
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>