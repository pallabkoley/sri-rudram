<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  Fetch article
 * ================================================================== */
$slug    = trim((string) ($_GET['slug'] ?? ''));
$article = $slug !== '' ? get_article_by_slug($slug) : null;

if (!$article) {
    http_response_code(404);
    require BASE_PATH . '/404.php';
    exit;
}

/* ==================================================================
 |  Article image — self-contained helper with proper fallback
 * ================================================================== */
if (!function_exists('article_img_url')) {
    function article_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';

        // Full external URL (Unsplash etc.) — use as-is
        if (preg_match('#^https?://#i', $stored)) return $stored;

        // Local path — build full URL
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

/* ---------- Fallback cover (locally stored) ---------- */
$fallbackCover = 'https://divyaaastha.xinkpayroll.co.in/assets/img/articles/rudraksha-cover.jpg';

$articleImageSrc = article_img_url($article['image_url'] ?? '');

/* If image URL is empty, use fallback */
if ($articleImageSrc === '') {
    $articleImageSrc = $fallbackCover;
}

/* ==================================================================
 |  Related articles (same category, excluding current)
 * ================================================================== */
$relatedArticles = [];
try {
    $catId = (int) ($article['category_id'] ?? 0);
    if ($catId > 0) {
        $stmt = $pdo->prepare(
            "SELECT a.id, a.title, a.slug, a.excerpt, a.image_url,
                    a.read_minutes, a.published_at,
                    c.name AS category_name
             FROM articles a
             LEFT JOIN article_categories c ON c.id = a.category_id
             WHERE a.is_active = 1
               AND a.category_id = ?
               AND a.id <> ?
             ORDER BY a.published_at DESC
             LIMIT 3"
        );
        $stmt->execute([$catId, (int) $article['id']]);
        $relatedArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $relatedArticles = [];
}

$pageTitle = $article['title'];
$pageDesc  = $article['excerpt'] ?? '';
require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   ARTICLE PAGE — scoped styles (ar-*)
   ============================================================ */
.ar-page{background:#FBF7F0;padding:0 0 80px;min-height:70vh}
.ar-wrap{max-width:1180px;margin:0 auto;padding:0 22px}

/* ---------- HERO ---------- */
.ar-hero{
  background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
  position:relative;overflow:hidden;
  padding:48px 0 72px;
}
.ar-hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(circle at 15% 30%,rgba(183,97,70,.28) 0%,transparent 55%),
    radial-gradient(circle at 85% 70%,rgba(212,175,55,.18) 0%,transparent 55%);
}
.ar-hero::after{
  content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent);
}
.ar-hero .ar-wrap{position:relative;z-index:2}

.ar-breadcrumb{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  font-size:12.5px;color:rgba(247,235,221,.72);
  margin-bottom:24px;
}
.ar-breadcrumb a{color:rgba(247,235,221,.9);text-decoration:none}
.ar-breadcrumb a:hover{color:#D4AF37}
.ar-breadcrumb .sep{color:rgba(247,235,221,.35)}
.ar-breadcrumb .current{color:#F7EBDD;font-weight:600}

.ar-hero__grid{
  display:grid;grid-template-columns:1.1fr 1fr;gap:44px;align-items:center;
}
@media(max-width:900px){
  .ar-hero{padding:32px 0 48px}
  .ar-hero__grid{grid-template-columns:1fr;gap:28px}
}

.ar-eyebrow{
  display:inline-block;
  font-size:11px;font-weight:700;letter-spacing:2.4px;
  text-transform:uppercase;color:#D4AF37;
  padding:6px 14px;
  background:rgba(212,175,55,.12);
  border:1px solid rgba(212,175,55,.35);
  border-radius:99px;
  margin-bottom:18px;
}

.ar-hero h1{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:clamp(1.9rem,4vw,2.9rem);
  line-height:1.15;color:#F7EBDD;
  margin:0 0 18px;font-weight:600;
  letter-spacing:-.5px;
  text-shadow:0 2px 22px rgba(0,0,0,.35);
}

.ar-hero__meta{
  display:flex;flex-wrap:wrap;gap:14px;
  font-size:13px;color:rgba(247,235,221,.8);
}
.ar-hero__meta span{
  display:inline-flex;align-items:center;gap:6px;
}
.ar-hero__meta svg{
  width:14px;height:14px;
  stroke:#D4AF37;fill:none;
  stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;
}

/* Hero cover image */
.ar-hero__media{
  position:relative;
  aspect-ratio:4/3;
  border-radius:18px;
  overflow:hidden;
  background:#3A1F2F;
  border:1px solid rgba(212,175,55,.28);
  box-shadow:0 24px 60px -24px rgba(0,0,0,.55);
}
.ar-hero__media img{
  width:100%;height:100%;object-fit:cover;display:block;
}
.ar-hero__media .ar-hero__ph{
  position:absolute;inset:0;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#3A1A2D,#1B1019);
  color:#D4AF37;
  font-family:'Playfair Display',Georgia,serif;
  font-size:88px;
  line-height:1;
}
@media(max-width:900px){
  .ar-hero__media{aspect-ratio:16/9;max-width:520px;margin:0 auto}
}

/* ---------- ARTICLE LAYOUT ---------- */
.ar-main{
  max-width:1180px;margin:0 auto;padding:0 22px;
  display:grid;grid-template-columns:minmax(0,1fr) 280px;
  gap:44px;
  margin-top:-40px;
  position:relative;z-index:3;
}
@media(max-width:960px){
  .ar-main{grid-template-columns:1fr;gap:32px;margin-top:-28px}
}

/* ---------- ARTICLE BODY ---------- */
.ar-article{
  background:#fff;
  border:1px solid #EFE6D9;
  border-radius:18px;
  padding:40px 44px 44px;
  box-shadow:0 8px 32px -20px rgba(53,27,47,.12);
}
@media(max-width:640px){.ar-article{padding:28px 22px 32px}}

.ar-excerpt{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:17px;line-height:1.7;color:#4A3F38;
  font-style:italic;
  margin:0 0 28px;
  padding:0 0 24px;
  border-bottom:1px solid #F3EEE6;
}

.ar-body{
  font-size:16px;line-height:1.85;color:#3A2E28;
}
.ar-body h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:24px;line-height:1.3;color:#351B2F;
  margin:36px 0 16px;font-weight:600;
  letter-spacing:-.3px;
}
.ar-body h2:first-child{margin-top:0}
.ar-body h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;line-height:1.35;color:#351B2F;
  margin:28px 0 12px;font-weight:600;
}
.ar-body p{margin:0 0 18px;color:#4A3F38}
.ar-body p:last-child{margin-bottom:0}
.ar-body ul,.ar-body ol{
  margin:14px 0 20px;padding-left:24px;
}
.ar-body li{margin-bottom:8px;color:#4A3F38;line-height:1.7}
.ar-body blockquote{
  border-left:3px solid #D4AF37;
  background:#FBF7F0;
  padding:16px 22px;margin:24px 0;
  border-radius:0 12px 12px 0;
  font-style:italic;color:#351B2F;
}
.ar-body img{
  max-width:100%;height:auto;
  border-radius:12px;margin:20px 0;
  display:block;
}
.ar-body a{color:#B76146;text-decoration:underline}
.ar-body a:hover{color:#9B4C34}

.ar-placeholder{
  text-align:center;padding:40px 20px;
  background:#FBF7F0;border-radius:12px;
  color:#6B7280;font-size:15px;
}

/* ---------- SIDEBAR ---------- */
.ar-sidebar{
  display:flex;flex-direction:column;gap:20px;
  position:sticky;top:96px;align-self:start;
}
@media(max-width:960px){.ar-sidebar{position:static}}

.ar-side-card{
  background:#fff;border:1px solid #EFE6D9;
  border-radius:14px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
}
.ar-side-head{
  padding:14px 18px;
  border-bottom:1px solid #F3EEE6;
  background:linear-gradient(180deg,#FCF8F2,#FBF5EC);
}
.ar-side-head h4{
  margin:0;
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:14px;color:#351B2F;font-weight:600;
  letter-spacing:.2px;
}
.ar-side-body{padding:14px 16px}

.ar-share{
  display:flex;gap:8px;flex-wrap:wrap;
}
.ar-share a{
  width:36px;height:36px;border-radius:9px;
  display:grid;place-items:center;
  background:#FBF7F0;color:#B76146;
  border:1px solid #EFE6D9;
  text-decoration:none;
  transition:all .18s ease;
}
.ar-share a:hover{
  background:#B76146;color:#fff;border-color:#B76146;
  transform:translateY(-2px);
}
.ar-share svg{
  width:16px;height:16px;
  stroke:currentColor;fill:none;
  stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;
}

.ar-related{display:flex;flex-direction:column;gap:12px}
.ar-rel-item{
  display:grid;grid-template-columns:60px 1fr;
  gap:12px;align-items:center;
  text-decoration:none;color:inherit;
  padding:6px;border-radius:10px;
  transition:background .18s ease;
}
.ar-rel-item:hover{background:#FBF7F0}
.ar-rel-item__media{
  position:relative;
  width:60px;height:60px;border-radius:10px;
  overflow:hidden;background:#F5F1EC;
  border:1px solid #EFE6D9;
}
.ar-rel-item__media img{
  width:100%;height:100%;object-fit:cover;display:block;
}
.ar-rel-item__title{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:13px;line-height:1.35;color:#351B2F;
  margin:0 0 4px;font-weight:600;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;
  overflow:hidden;
}
.ar-rel-item:hover .ar-rel-item__title{color:#B76146}
.ar-rel-item__meta{
  font-size:10.5px;color:#9CA3AF;
}

/* ---------- BACK CTA ---------- */
.ar-back{
  text-align:center;margin-top:36px;
}
.ar-back a{
  display:inline-flex;align-items:center;gap:8px;
  padding:13px 28px;font-size:13.5px;font-weight:600;
  border-radius:10px;
  background:#fff;color:#351B2F;
  border:1px solid #E5DDD2;
  text-decoration:none;
  transition:all .2s ease;
}
.ar-back a:hover{
  border-color:#B76146;color:#B76146;background:#FFF7E0;
}
.ar-back a svg{
  width:15px;height:15px;
  stroke:currentColor;fill:none;
  stroke-width:2;stroke-linecap:round;stroke-linejoin:round;
}

/* ---------- Responsive tweaks ---------- */
@media(max-width:640px){
  .ar-main{padding:0 16px;margin-top:-22px}
  .ar-article{padding:24px 18px}
  .ar-body{font-size:15.5px}
  .ar-body h2{font-size:21px}
  .ar-body h3{font-size:17.5px}
  .ar-hero h1{font-size:1.75rem}
}
</style>

<div class="ar-page">

  <!-- ═══════════════════════════════════════════════════
       HERO
       ═══════════════════════════════════════════════════ -->
  <section class="ar-hero">
    <div class="ar-wrap">

      <nav class="ar-breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span class="sep">›</span>
        <a href="<?= url('gyan.php') ?>">Gyan</a>
        <span class="sep">›</span>
        <span class="current"><?= e($article['category_name'] ?? 'Article') ?></span>
      </nav>

      <div class="ar-hero__grid">

        <div>
          <span class="ar-eyebrow">
            <?= e($article['category_name'] ?? 'Dharmik Gyan') ?>
          </span>

          <h1><?= e($article['title']) ?></h1>

          <div class="ar-hero__meta">
            <?php if (!empty($article['published_at'])): ?>
              <span>
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?= date('d M Y', strtotime($article['published_at'])) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($article['read_minutes'])): ?>
              <span>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <?= (int) $article['read_minutes'] ?> min read
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="ar-hero__media">
          <?php if ($articleImageSrc): ?>
            <img src="<?= htmlspecialchars($articleImageSrc, ENT_QUOTES) ?>"
                 alt="<?= htmlspecialchars($article['title'], ENT_QUOTES) ?>"
                 fetchpriority="high"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
            <div class="ar-hero__ph" style="display:none">ॐ</div>
          <?php else: ?>
            <div class="ar-hero__ph">ॐ</div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════
       MAIN
       ═══════════════════════════════════════════════════ -->
  <div class="ar-main">

    <!-- ---------- ARTICLE BODY ---------- -->
    <article class="ar-article">

      <?php if (!empty($article['excerpt'])): ?>
        <p class="ar-excerpt"><?= e($article['excerpt']) ?></p>
      <?php endif; ?>

      <div class="ar-body">
        <?php if (!empty($article['body'])): ?>
          <?= $article['body'] ?>
        <?php else: ?>
          <div class="ar-placeholder">
            <p>Full article coming soon. Please check back shortly.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="ar-back">
        <a href="<?= url('gyan.php') ?>">
          <svg viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
          Back to Gyan
        </a>
      </div>

    </article>

    <!-- ---------- SIDEBAR ---------- -->
    <aside class="ar-sidebar">

      <!-- Share -->
      <div class="ar-side-card">
        <div class="ar-side-head"><h4>Share this article</h4></div>
        <div class="ar-side-body">
          <div class="ar-share">
            <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['title']) ?>+<?= urlencode(url('article.php?slug=' . $article['slug'])) ?>"
               target="_blank" rel="noopener" aria-label="Share on WhatsApp">
              <svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.1s-.8 1-.9 1.2c-.2.2-.3.2-.6.1a8.2 8.2 0 0 1-2.4-1.5 9 9 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.6-.7c.2-.2.2-.3.3-.5s0-.4 0-.5l-.9-2.1c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.8.4A3.4 3.4 0 0 0 6 9.2a6 6 0 0 0 1.3 3.2 12.4 12.4 0 0 0 5.3 4.7c2.6 1 2.6.7 3.1.6a3.1 3.1 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.2-.3-.3-.4-.5z"/></svg>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(url('article.php?slug=' . $article['slug'])) ?>"
               target="_blank" rel="noopener" aria-label="Share on Facebook">
              <svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode(url('article.php?slug=' . $article['slug'])) ?>"
               target="_blank" rel="noopener" aria-label="Share on Twitter">
              <svg viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(url('article.php?slug=' . $article['slug'])) ?>"
               target="_blank" rel="noopener" aria-label="Share on LinkedIn">
              <svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Related articles -->
      <?php if (!empty($relatedArticles)): ?>
        <div class="ar-side-card">
          <div class="ar-side-head"><h4>Related Articles</h4></div>
          <div class="ar-side-body">
            <div class="ar-related">
              <?php foreach ($relatedArticles as $r): ?>
                <?php
                  $rImg = article_img_url($r['image_url'] ?? '');
                  if ($rImg === '') $rImg = $fallbackCover;
                ?>
                <a href="<?= url('article.php?slug=' . urlencode($r['slug'])) ?>"
                   class="ar-rel-item">
                  <div class="ar-rel-item__media">
                    <img src="<?= htmlspecialchars($rImg, ENT_QUOTES) ?>"
                         alt="<?= htmlspecialchars($r['title'], ENT_QUOTES) ?>"
                         loading="lazy">
                  </div>
                  <div>
                    <h5 class="ar-rel-item__title"><?= e($r['title']) ?></h5>
                    <span class="ar-rel-item__meta">
                      <?= (int) ($r['read_minutes'] ?? 5) ?> min read
                    </span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </aside>

  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>