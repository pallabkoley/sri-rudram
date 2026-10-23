<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  SAFE IMAGE HELPER
 * ================================================================== */
if (!function_exists('gyan_img_url')) {
    function gyan_img_url(?string $stored): string {
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

if (!function_exists('gyan_img')) {
    function gyan_img(?string $stored, string $alt = '', string $class = ''): string {
        $src = gyan_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($class ?: 'ar-ph', ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;'
                 . 'color:#9A8B7C;width:100%;height:100%;font-size:32px">'
                 . '<i class="bi bi-journal-text"></i></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" '
             . 'alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" '
             . ($class !== '' ? 'class="' . htmlspecialchars($class, ENT_QUOTES) . '" ' : '')
             . 'loading="lazy">';
    }
}

if (!function_exists('gyan_cat_icon')) {
    function gyan_cat_icon(string $slug, string $name = ''): string {
        $key = strtolower(trim($slug ?: $name));
        $map = [
            'mantra'=>'🕉️','stotra'=>'📜','chalisa'=>'📖','aarti'=>'🪔',
            'katha'=>'📚','festival'=>'🎉','temple'=>'🛕','ritual'=>'🙏',
            'astrology'=>'✨','jyotish'=>'✨','gyan'=>'📘','rudraksha'=>'📿',
        ];
        if (isset($map[$key])) return $map[$key];
        $lower = strtolower($name);
        if (strpos($lower,'mantra')   !== false) return '🕉️';
        if (strpos($lower,'stotra')   !== false) return '📜';
        if (strpos($lower,'chalisa')  !== false) return '📖';
        if (strpos($lower,'aarti')    !== false) return '🪔';
        if (strpos($lower,'katha')    !== false) return '📚';
        if (strpos($lower,'festival') !== false) return '🎉';
        if (strpos($lower,'temple')   !== false) return '🛕';
        if (strpos($lower,'ritual')   !== false) return '🙏';
        if (strpos($lower,'rudraksha')!== false) return '📿';
        return '📘';
    }
}

/* ==================================================================
 |  Input
 * ================================================================== */
$catSlug = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
$searchQ = isset($_GET['q'])   ? trim((string) $_GET['q'])   : '';

/* ==================================================================
 |  Load categories + their article counts (fully dynamic)
 * ================================================================== */
$gyanCategories = [];
try {
    $gyanCategories = $pdo->query(
        "SELECT id, name, slug, description
         FROM gyan_categories
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $gyanCategories = [];
}

$articleCategories = [];
try {
    $articleCategories = $pdo->query(
        "SELECT id, name, slug, sort_order
         FROM article_categories
         ORDER BY sort_order ASC, name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $articleCategories = [];
}

/* Count articles per gyan category */
$gyanCounts = [];
foreach ($gyanCategories as $gc) {
    $slug = $gc['slug'];
    $name = $gc['name'];
    $cnt  = 0;
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM articles a
             LEFT JOIN article_categories c ON c.id = a.category_id
             WHERE a.is_active = 1
               AND (c.slug = ? OR LOWER(c.name) = LOWER(?)
                    OR a.title LIKE ? OR a.excerpt LIKE ?)"
        );
        $like = '%' . $name . '%';
        $stmt->execute([$slug, $name, $like, $like]);
        $cnt = (int) $stmt->fetchColumn();
    } catch (Throwable $e) { $cnt = 0; }
    $gyanCounts[$slug] = $cnt;
}

/* Count per article category */
$articleCatCounts = [];
foreach ($articleCategories as $ac) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM articles WHERE is_active = 1 AND category_id = ?"
        );
        $stmt->execute([$ac['id']]);
        $articleCatCounts[$ac['slug']] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) { $articleCatCounts[$ac['slug']] = 0; }
}

/* ==================================================================
 |  Resolve clicked category
 * ================================================================== */
$matchedGyanCat = null;
if ($catSlug !== '') {
    foreach ($gyanCategories as $gc) {
        if (($gc['slug'] ?? '') === $catSlug) { $matchedGyanCat = $gc; break; }
    }
    if (!$matchedGyanCat) {
        foreach ($articleCategories as $ac) {
            if (($ac['slug'] ?? '') === $catSlug) { $matchedGyanCat = $ac; break; }
        }
    }
}

$activeCatLabel = 'All Articles';
if ($matchedGyanCat) $activeCatLabel = $matchedGyanCat['name'];

/* ==================================================================
 |  Articles query
 * ================================================================== */
try {
    $where  = ['a.is_active = 1'];
    $params = [];

    if ($catSlug !== '') {
        $name = $matchedGyanCat['name'] ?? '';
        $like = '%' . $name . '%';
        $where[] = '(
            c.slug = ?
            OR LOWER(c.name) = LOWER(?)
            OR a.title   LIKE ?
            OR a.excerpt LIKE ?
        )';
        $params[] = $catSlug;
        $params[] = $name;
        $params[] = $like;
        $params[] = $like;
    }

    if ($searchQ !== '') {
        $like     = '%' . $searchQ . '%';
        $where[]  = '(a.title LIKE ? OR a.excerpt LIKE ? OR a.body LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql = "SELECT a.*, c.name AS category_name, c.slug AS category_slug
            FROM articles a
            LEFT JOIN article_categories c ON c.id = a.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.published_at DESC, a.id DESC";

    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $articles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('[gyan] ' . $e->getMessage());
    $articles = [];
}

/* ---------- Featured / Latest (dynamic) ---------- */
$featuredArticles = [];
try {
    $featuredArticles = $pdo->query(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active = 1 AND a.is_featured = 1
         ORDER BY a.published_at DESC, a.id DESC
         LIMIT 4"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (count($featuredArticles) < 4) {
        $need    = 4 - count($featuredArticles);
        $exclude = array_column($featuredArticles, 'id');
        $exList  = !empty($exclude) ? implode(',', array_map('intval', $exclude)) : '0';
        $fill = $pdo->query(
            "SELECT a.*, c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN article_categories c ON c.id = a.category_id
             WHERE a.is_active = 1 AND a.id NOT IN ($exList)
             ORDER BY a.published_at DESC, a.id DESC
             LIMIT " . (int) $need
        )->fetchAll(PDO::FETCH_ASSOC);
        $featuredArticles = array_merge($featuredArticles, $fill);
    }
} catch (Throwable $e) { $featuredArticles = []; }

/* ---------- Festival (dynamic) ---------- */
$festivalArticles = [];
try {
    $festivalArticles = $pdo->query(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active = 1
           AND (c.slug = 'festival' OR LOWER(c.name) = 'festival')
         ORDER BY a.published_at DESC, a.id DESC
         LIMIT 3"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $festivalArticles = []; }

/* ---------- Temple (dynamic) ---------- */
$templeArticles = [];
try {
    $templeArticles = $pdo->query(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active = 1
           AND (c.slug = 'temple' OR LOWER(c.name) = 'temple')
         ORDER BY a.published_at DESC, a.id DESC
         LIMIT 3"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $templeArticles = []; }

/* ---------- Astrology (graceful) ---------- */
$astrologyArticles = [];
try {
    $astrologyArticles = $pdo->query(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active = 1
           AND (c.slug IN ('astrology','jyotish','rashifal')
                OR c.name LIKE '%Astrolog%')
         ORDER BY a.published_at DESC, a.id DESC
         LIMIT 3"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $astrologyArticles = []; }

/* ---------- Trending (dynamic) ---------- */
$trendingArticles = [];
try {
    $trendingArticles = $pdo->query(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active = 1 AND a.is_trending = 1
         ORDER BY a.published_at DESC, a.id DESC
         LIMIT 4"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $trendingArticles = []; }

$totalCount = count($articles);

$pageTitle = 'Dharmik Gyan — Articles, Mantras & Rituals';
$pageDesc  = 'Deeply researched articles on rituals, mantras and Hindu philosophy.';
require BASE_PATH . '/includes/header.php';
?>

<style>
.gy-page{
  --gy-plum:#2B1220; --gy-plum-2:#3A1A2D;
  --gy-gold:#D4AF37; --gy-gold-2:#C4A060;
  --gy-cream:#FAF6EF; --gy-card:#FFFFFF;
  --gy-line:#EFE6D9; --gy-text:#351B2F;
  --gy-muted:#6B7280; --gy-muted-2:#9CA3AF;
  background:var(--gy-cream);
}
.gy-hero{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,#2B1220 0%,#3A1A2D 55%,#2B1220 100%);
  padding:76px 0 84px;text-align:center;
}
.gy-hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(circle at 15% 30%,rgba(212,175,55,.14) 0%,transparent 45%),
    radial-gradient(circle at 85% 70%,rgba(183,97,70,.22) 0%,transparent 50%);
}
.gy-hero::after{
  content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent);
}
.gy-hero__inner{position:relative;z-index:2;max-width:900px;margin:0 auto;padding:0 24px}
.gy-hero__eyebrow{
  display:inline-block;padding:6px 18px;font-size:11px;font-weight:700;
  letter-spacing:2.4px;text-transform:uppercase;color:var(--gy-gold);
  background:rgba(212,175,55,.10);border:1px solid rgba(212,175,55,.32);
  border-radius:99px;margin-bottom:22px;backdrop-filter:blur(4px);
}
.gy-hero h1{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(2rem,4.5vw,3.4rem);line-height:1.12;letter-spacing:-.8px;
  color:#F7EBDD;margin:0 0 20px;font-weight:600;
  text-shadow:0 2px 22px rgba(0,0,0,.35);
}
.gy-hero h1 em{font-style:italic;color:var(--gy-gold);font-weight:500}
.gy-hero__desc{
  color:rgba(247,235,221,.82);font-size:16px;line-height:1.75;
  max-width:700px;margin:0 auto 30px;
}
.gy-search{max-width:620px;margin:0 auto;position:relative}
.gy-search__form{
  display:flex;align-items:center;gap:10px;
  background:#FCF8F2;border-radius:999px;padding:8px 8px 8px 22px;
  border:1px solid rgba(212,175,55,.35);
  box-shadow:0 18px 40px -18px rgba(0,0,0,.6);
}
.gy-search__form svg{width:18px;height:18px;flex:0 0 auto;stroke:#B76146;fill:none;stroke-width:2}
.gy-search__form input{
  flex:1;min-width:0;border:none;outline:none;background:transparent;
  font-size:14.5px;color:#342823;padding:10px 0;
}
.gy-search__form input::placeholder{color:#9A8B7C}
.gy-search__form button{
  flex:0 0 auto;padding:11px 24px;font-size:13.5px;font-weight:600;
  border-radius:999px;border:none;cursor:pointer;
  background:#B76146;color:#FCF8F2;transition:background .2s ease;
}
.gy-search__form button:hover{background:#9B4C34}
@media(max-width:640px){
  .gy-hero{padding:56px 0 62px}
  .gy-hero__desc{font-size:14px;margin-bottom:24px}
  .gy-search__form{padding:6px 6px 6px 16px}
  .gy-search__form input{font-size:14px}
  .gy-search__form button{padding:10px 18px;font-size:13px}
}

.gy-section{padding:68px 0;background:var(--gy-cream)}
.gy-section.alt{background:#F5EEE4}
.gy-container{max-width:1240px;margin:0 auto;padding:0 22px}

.gy-head{max-width:760px;margin:0 auto 44px;text-align:center}
.gy-head.left{margin-left:0;text-align:left}
.gy-head__eyebrow{
  display:inline-flex;align-items:center;gap:10px;
  font-size:11px;font-weight:700;letter-spacing:2.4px;
  text-transform:uppercase;color:#B76146;margin-bottom:12px;
}
.gy-head__eyebrow::before,
.gy-head__eyebrow::after{content:'';display:inline-block;width:22px;height:1px;background:#D4AF37}
.gy-head__eyebrow.left::before{display:none}
.gy-head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(1.5rem,3vw,2.3rem);line-height:1.2;letter-spacing:-.4px;
  color:var(--gy-text);margin:0 0 14px;font-weight:600;
}
.gy-head h2 em{font-style:italic;color:#B76146;font-weight:500}
.gy-head p{color:var(--gy-muted);font-size:15px;line-height:1.72;max-width:640px;margin:0 auto}
.gy-head.left p{margin-left:0}
.gy-rule{width:60px;height:2px;border-radius:2px;background:linear-gradient(90deg,#D4AF37,#B76146);margin:16px auto 0}
.gy-head.left .gy-rule{margin-left:0}

/* Category cards */
.gy-cats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
@media(max-width:1024px){.gy-cats{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:768px){.gy-cats{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}}
@media(max-width:480px){.gy-cats{grid-template-columns:1fr}}

.gy-cat{
  display:flex;flex-direction:column;align-items:flex-start;
  background:#fff;border:1px solid var(--gy-line);
  border-radius:14px;padding:22px 20px 20px;
  text-decoration:none;color:inherit;
  transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
  box-shadow:0 1px 2px rgba(53,27,47,.04);
  position:relative;
}
.gy-cat:hover{transform:translateY(-3px);border-color:rgba(212,175,55,.5);box-shadow:0 10px 24px -12px rgba(53,27,47,.18)}
.gy-cat__count{
  position:absolute;top:14px;right:14px;
  padding:3px 9px;border-radius:99px;
  font-size:10.5px;font-weight:700;letter-spacing:.4px;
  background:#FAF3E6;color:#8A6A08;
  border:1px solid rgba(212,175,55,.35);
}
.gy-cat__count.empty{background:#F3F4F6;color:#9CA3AF;border-color:#E5E7EB}
.gy-cat__icon{
  width:52px;height:52px;border-radius:14px;
  display:grid;place-items:center;
  background:#FAF3E6;border:1px solid rgba(212,175,55,.32);
  font-size:24px;line-height:1;margin-bottom:14px;
}
.gy-cat__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:17px;line-height:1.3;color:var(--gy-text);
  margin:0 0 6px;font-weight:600;
}
.gy-cat__desc{
  color:var(--gy-muted);font-size:12.5px;line-height:1.55;
  margin:0 0 12px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.gy-cat__more{
  margin-top:auto;font-size:11.5px;font-weight:700;letter-spacing:.5px;
  text-transform:uppercase;color:#B76146;
  display:inline-flex;align-items:center;gap:5px;
}
.gy-cat:hover .gy-cat__more{color:#9B4C34}

/* Filters */
.gy-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:30px;padding-bottom:22px;border-bottom:1px solid var(--gy-line)}
.gy-chip{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 18px;border-radius:99px;font-size:13px;font-weight:600;
  text-decoration:none;transition:all .18s;
  background:#fff;color:#1F2937;border:1px solid #E5E7EB;white-space:nowrap;
}
.gy-chip:hover{border-color:#B76146;color:#B76146}
.gy-chip.active{background:var(--gy-plum);color:#fff;border-color:var(--gy-plum)}
.gy-chip .cnt{font-size:11px;opacity:.75;font-weight:700}
.gy-count{margin-left:auto;font-size:13px;color:var(--gy-muted);font-weight:600}

/* Article grid */
.gy-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}
.gy-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:1024px){
  .gy-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
  .gy-grid.cols-3{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:768px){
  .gy-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  .gy-grid.cols-3{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:520px){.gy-grid,.gy-grid.cols-3{grid-template-columns:1fr;gap:14px}}

.gy-card{
  background:#fff;border-radius:14px;overflow:hidden;
  border:1px solid var(--gy-line);
  box-shadow:0 1px 3px rgba(53,27,47,.04);
  display:flex;flex-direction:column;
  transition:transform .2s,border-color .2s,box-shadow .2s;
}
.gy-card:hover{transform:translateY(-3px);border-color:rgba(183,97,70,.32);box-shadow:0 12px 28px -14px rgba(53,27,47,.20)}
.gy-card__media{position:relative;aspect-ratio:4/3;overflow:hidden;background:#F5F1EC}
.gy-card__media img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
.gy-card:hover .gy-card__media img{transform:scale(1.04)}
.gy-card__cat{
  position:absolute;top:12px;left:12px;padding:4px 11px;
  background:rgba(53,27,47,.88);backdrop-filter:blur(4px);
  color:#F7EBDD;font-size:10.5px;font-weight:700;
  letter-spacing:.5px;text-transform:uppercase;border-radius:99px;z-index:2;
}
.gy-card__body{padding:16px;display:flex;flex-direction:column;gap:8px;flex:1}
.gy-card__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:16px;line-height:1.35;color:var(--gy-text);
  margin:0;font-weight:600;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px;
}
.gy-card__title a{color:inherit;text-decoration:none}
.gy-card__title a:hover{color:#B76146}
.gy-card__excerpt{
  color:var(--gy-muted);font-size:12.5px;line-height:1.55;
  margin:0;flex:1;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:39px;
}
.gy-card__meta{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  font-size:11px;color:var(--gy-muted-2);font-weight:500;
  padding-top:10px;border-top:1px solid #F3EEE6;margin-top:auto;
}
.gy-card__meta .dot{width:3px;height:3px;border-radius:50%;background:#D1D5DB}
.gy-card__cta{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#B76146;text-decoration:none;letter-spacing:.3px}
.gy-card__cta:hover{color:#9B4C34}

.gy-more{text-align:center;margin-top:44px}
.gy-more a{
  display:inline-flex;align-items:center;gap:9px;
  padding:14px 32px;font-size:14px;font-weight:600;
  background:#B76146;color:#fff;border-radius:999px;text-decoration:none;
  box-shadow:0 12px 26px -14px rgba(183,97,70,.85);
  transition:background .2s ease,transform .2s ease;
}
.gy-more a:hover{background:#9B4C34;transform:translateY(-2px)}
.gy-more a svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.2}

.gy-empty{
  text-align:center;padding:80px 20px;color:var(--gy-muted);
  background:#fff;border:1px solid var(--gy-line);border-radius:16px;
}
.gy-empty .ico{font-size:56px;color:#D4AF37;margin-bottom:16px;line-height:1}
.gy-empty h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:22px;color:var(--gy-text);margin:0 0 8px;
}
.gy-empty p{margin:0;font-size:14px;line-height:1.6}
.gy-empty a{
  display:inline-flex;align-items:center;gap:6px;margin-top:20px;
  padding:10px 22px;font-size:13px;font-weight:600;border-radius:10px;
  background:#B76146;color:#fff;text-decoration:none;
}
.gy-empty a:hover{background:#9B4C34}
</style>

<div class="gy-page">

<!-- ═══════════════ HERO ═══════════════ -->
<section class="gy-hero">
  <div class="gy-hero__inner">
    <span class="gy-hero__eyebrow">Dharmik Gyan · Sacred Wisdom</span>
    <h1>Explore Dharmik Gyan<br>&amp; <em>Sacred Wisdom</em></h1>
    <p class="gy-hero__desc">
      Dive into authentic Mantras, Stotras, Chalisas, Aartis, Kathas, temple
      knowledge, festivals and rituals — curated for every devotee seeking
      spiritual growth and divine connection.
    </p>
    <div class="gy-search">
      <form class="gy-search__form" method="get" action="<?= url('gyan.php') ?>" role="search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
        <input type="search" name="q" value="<?= e($searchQ) ?>"
               placeholder="Search mantras, festivals, temples, rituals…"
               aria-label="Search Gyan articles">
        <?php if ($catSlug !== ''): ?>
          <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
        <?php endif; ?>
        <button type="submit">Explore</button>
      </form>
    </div>
  </div>
</section>

<!-- ═══════════════ COLLECTION OF DHARMIK GYAN ═══════════════ -->
<?php if (!empty($gyanCategories)): ?>
<section class="gy-section">
  <div class="gy-container">
    <div class="gy-head">
      <span class="gy-head__eyebrow">Sacred Categories</span>
      <h2>Collection of <em>Dharmik Gyan</em></h2>
      <p>Mantra, Stotra, Chalisa, Aarti &amp; sacred wisdom for every devotee.</p>
      <div class="gy-rule"></div>
    </div>

    <div class="gy-cats">
      <?php foreach ($gyanCategories as $gc):
        $icon  = gyan_cat_icon($gc['slug'] ?? '', $gc['name'] ?? '');
        $desc  = $gc['description'] ?? '';
        if ($desc === '') {
            $desc = 'Explore sacred ' . strtolower($gc['name']) . ' knowledge and devotional wisdom.';
        }
        $slug  = !empty($gc['slug']) ? $gc['slug'] : strtolower(str_replace(' ', '-', $gc['name']));
        $link  = url('gyan.php?cat=' . urlencode($slug));
        $count = $gyanCounts[$slug] ?? 0;
      ?>
        <a href="<?= htmlspecialchars($link, ENT_QUOTES) ?>" class="gy-cat">
          <span class="gy-cat__count<?= $count === 0 ? ' empty' : '' ?>">
            <?= (int) $count ?> <?= $count === 1 ? 'article' : 'articles' ?>
          </span>
          <span class="gy-cat__icon" aria-hidden="true"><?= $icon ?></span>
          <h3 class="gy-cat__title"><?= e($gc['name']) ?></h3>
          <p class="gy-cat__desc"><?= e($desc) ?></p>
          <span class="gy-cat__more">Explore →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ FEATURED / LATEST GYAN ═══════════════ -->
<?php if (!empty($featuredArticles)): ?>
<section class="gy-section alt">
  <div class="gy-container">
    <div class="gy-head">
      <span class="gy-head__eyebrow">Featured &amp; Latest</span>
      <h2>Featured <em>Gyan</em></h2>
      <p>Handpicked spiritual readings on rituals, mantras and Sanatan wisdom.</p>
      <div class="gy-rule"></div>
    </div>

    <div class="gy-grid">
      <?php foreach ($featuredArticles as $a): ?>
        <article class="gy-card">
          <div class="gy-card__media">
            <?= gyan_img($a['image_url'], $a['title']) ?>
            <?php if (!empty($a['category_name'])): ?>
              <span class="gy-card__cat"><?= e($a['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="gy-card__body">
            <h3 class="gy-card__title">
              <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a>
            </h3>
            <?php if (!empty($a['excerpt'])): ?>
              <p class="gy-card__excerpt"><?= e($a['excerpt']) ?></p>
            <?php endif; ?>
            <div class="gy-card__meta">
              <span><?= (int) ($a['read_minutes'] ?: 5) ?> min read</span>
              <?php if (!empty($a['published_at'])): ?>
                <span class="dot"></span>
                <span><?= date('d M Y', strtotime($a['published_at'])) ?></span>
              <?php endif; ?>
            </div>
            <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="gy-card__cta">Read Article →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="gy-more">
      <a href="#all-gyan">Explore All Gyan
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ ALL GYAN LISTING ═══════════════ -->
<section class="gy-section" id="all-gyan">
  <div class="gy-container">

    <?php if ($searchQ !== ''): ?>
      <div class="gy-head left">
        <span class="gy-head__eyebrow left">Search Results</span>
        <h2>Results for <em><?= e($searchQ) ?></em></h2>
        <p><?= (int) $totalCount ?> matching article<?= $totalCount === 1 ? '' : 's' ?> found.</p>
      </div>
    <?php elseif ($catSlug !== ''): ?>
      <div class="gy-head left">
        <span class="gy-head__eyebrow left">Category</span>
        <h2><?= e($activeCatLabel) ?></h2>
        <p>
          <?php if ($totalCount === 0): ?>
            No articles published in this category yet — check back soon.
          <?php else: ?>
            <?= (int) $totalCount ?> article<?= $totalCount === 1 ? '' : 's' ?> in this category.
          <?php endif; ?>
        </p>
      </div>
    <?php else: ?>
      <div class="gy-head">
        <span class="gy-head__eyebrow">All Articles</span>
        <h2>Dharmik <em>Gyan</em></h2>
        <p>Browse our complete library of devotional and philosophical articles.</p>
        <div class="gy-rule"></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($articleCategories)): ?>
      <div class="gy-filters">
        <a href="<?= url('gyan.php') ?>" class="gy-chip<?= $catSlug === '' ? ' active' : '' ?>">
          All Articles
          <span class="cnt"><?= array_sum($articleCatCounts) ?></span>
        </a>
        <?php foreach ($articleCategories as $c):
          $cnt = $articleCatCounts[$c['slug']] ?? 0;
          if ($cnt === 0) continue; // skip empty
        ?>
          <a href="<?= url('gyan.php?cat=' . urlencode($c['slug'])) ?>"
             class="gy-chip<?= $catSlug === $c['slug'] ? ' active' : '' ?>">
            <?= e($c['name']) ?>
            <span class="cnt"><?= (int) $cnt ?></span>
          </a>
        <?php endforeach; ?>
        <span class="gy-count">
          Showing <b><?= (int) $totalCount ?></b>
        </span>
      </div>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
      <div class="gy-empty">
        <div class="ico">📖</div>
        <h3>
          <?php if ($searchQ !== ''): ?>
            No articles found for "<?= e($searchQ) ?>"
          <?php elseif ($catSlug !== ''): ?>
            No articles in <?= e($activeCatLabel) ?> yet
          <?php else: ?>
            No articles yet
          <?php endif; ?>
        </h3>
        <p>New spiritual articles are being added. Please check back soon.</p>
        <a href="<?= url('gyan.php') ?>">← View All Articles</a>
      </div>
    <?php else: ?>
      <div class="gy-grid">
        <?php foreach ($articles as $a): ?>
          <article class="gy-card">
            <div class="gy-card__media">
              <?= gyan_img($a['image_url'], $a['title']) ?>
              <?php if (!empty($a['category_name'])): ?>
                <span class="gy-card__cat"><?= e($a['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="gy-card__body">
              <h3 class="gy-card__title">
                <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a>
              </h3>
              <?php if (!empty($a['excerpt'])): ?>
                <p class="gy-card__excerpt"><?= e($a['excerpt']) ?></p>
              <?php endif; ?>
              <div class="gy-card__meta">
                <span><?= (int) ($a['read_minutes'] ?: 5) ?> min read</span>
                <?php if (!empty($a['published_at'])): ?>
                  <span class="dot"></span>
                  <span><?= date('d M Y', strtotime($a['published_at'])) ?></span>
                <?php endif; ?>
              </div>
              <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="gy-card__cta">Read Article →</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- ═══════════════ FESTIVALS ═══════════════ -->
<?php if (!empty($festivalArticles)): ?>
<section class="gy-section alt">
  <div class="gy-container">
    <div class="gy-head">
      <span class="gy-head__eyebrow">Utsav &amp; Parv</span>
      <h2><em>Festivals</em> of Sanatan Dharma</h2>
      <p>Sacred wisdom and devotional content behind every festival.</p>
      <div class="gy-rule"></div>
    </div>
    <div class="gy-grid cols-3">
      <?php foreach ($festivalArticles as $a): ?>
        <article class="gy-card">
          <div class="gy-card__media">
            <?= gyan_img($a['image_url'], $a['title']) ?>
            <?php if (!empty($a['category_name'])): ?>
              <span class="gy-card__cat"><?= e($a['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="gy-card__body">
            <h3 class="gy-card__title">
              <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a>
            </h3>
            <?php if (!empty($a['excerpt'])): ?>
              <p class="gy-card__excerpt"><?= e($a['excerpt']) ?></p>
            <?php endif; ?>
            <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="gy-card__cta">Read Article →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ TEMPLES OF BHARAT ═══════════════ -->
<?php if (!empty($templeArticles)): ?>
<section class="gy-section">
  <div class="gy-container">
    <div class="gy-head">
      <span class="gy-head__eyebrow">Divya Sthal</span>
      <h2><em>Temples</em> of Bharat</h2>
      <p>Sacred wisdom and devotional content from India's most revered shrines.</p>
      <div class="gy-rule"></div>
    </div>
    <div class="gy-grid cols-3">
      <?php foreach ($templeArticles as $a): ?>
        <article class="gy-card">
          <div class="gy-card__media">
            <?= gyan_img($a['image_url'], $a['title']) ?>
            <?php if (!empty($a['category_name'])): ?>
              <span class="gy-card__cat"><?= e($a['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="gy-card__body">
            <h3 class="gy-card__title">
              <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a>
            </h3>
            <?php if (!empty($a['excerpt'])): ?>
              <p class="gy-card__excerpt"><?= e($a['excerpt']) ?></p>
            <?php endif; ?>
            <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="gy-card__cta">Read Article →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ ASTROLOGY ═══════════════ -->
<?php if (!empty($astrologyArticles)): ?>
<section class="gy-section alt">
  <div class="gy-container">
    <div class="gy-head">
      <span class="gy-head__eyebrow">Jyotish</span>
      <h2><em>Astrology</em> &amp; Cosmic Wisdom</h2>
      <p>Sacred wisdom and devotional content from the stars.</p>
      <div class="gy-rule"></div>
    </div>
    <div class="gy-grid cols-3">
      <?php foreach ($astrologyArticles as $a): ?>
        <article class="gy-card">
          <div class="gy-card__media">
            <?= gyan_img($a['image_url'], $a['title']) ?>
            <?php if (!empty($a['category_name'])): ?>
              <span class="gy-card__cat"><?= e($a['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="gy-card__body">
            <h3 class="gy-card__title">
              <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a>
            </h3>
            <?php if (!empty($a['excerpt'])): ?>
              <p class="gy-card__excerpt"><?= e($a['excerpt']) ?></p>
            <?php endif; ?>
            <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="gy-card__cta">Read Article →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════ TRENDING TOPICS ═══════════════ -->
<?php if (!empty($trendingArticles)): ?>
<section class="gy-section">
  <div class="gy-container">
    <div class="gy-head">
      <span class="gy-head__eyebrow">Trending Now</span>
      <h2>Trending <em>Topics</em></h2>
      <p>Latest articles on rituals, temples, festivals and spiritual guidance.</p>
      <div class="gy-rule"></div>
    </div>
    <div class="gy-grid">
      <?php foreach ($trendingArticles as $a): ?>
        <article class="gy-card">
          <div class="gy-card__media">
            <?= gyan_img($a['image_url'], $a['title']) ?>
            <?php if (!empty($a['category_name'])): ?>
              <span class="gy-card__cat"><?= e($a['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="gy-card__body">
            <h3 class="gy-card__title">
              <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a>
            </h3>
            <?php if (!empty($a['excerpt'])): ?>
              <p class="gy-card__excerpt"><?= e($a['excerpt']) ?></p>
            <?php endif; ?>
            <div class="gy-card__meta">
              <span><?= (int) ($a['read_minutes'] ?: 5) ?> min read</span>
              <?php if (!empty($a['published_at'])): ?>
                <span class="dot"></span>
                <span><?= date('d M Y', strtotime($a['published_at'])) ?></span>
              <?php endif; ?>
            </div>
            <a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>" class="gy-card__cta">Read Article →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>