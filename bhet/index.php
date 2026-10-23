<?php
require_once dirname(__DIR__) . '/config/config.php';

/* ==================================================================
 |  FRONTEND BHET IMAGE HELPER — always correct, no dependency
 * ================================================================== */
if (!function_exists('bhet_front_img_url')) {
    function bhet_front_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';

        // Full URL — use as-is
        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }

        $stored = ltrim($stored, '/');

        // Strip accidental "assets/img/" prefix
        if (strpos($stored, 'assets/img/') === 0) {
            $stored = substr($stored, strlen('assets/img/'));
        }

        // If "assets/img/" appears anywhere, keep what's after it
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
            return '<div class="' . htmlspecialchars($class ?: 'bhet-thumb-ph', ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;'
                 . 'color:#9A8B7C;min-height:220px;font-size:32px">'
                 . '<i class="bi bi-image"></i></div>';
        }

        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" '
             . 'alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" '
             . ($class !== '' ? 'class="' . htmlspecialchars($class, ENT_QUOTES) . '" ' : '')
             . 'loading="lazy">';
    }
}

/* ==================================================================
 |  Data fetch
 * ================================================================== */
try {
    $bhets = $pdo->query(
        'SELECT b.id, b.name, b.slug, b.short_desc, b.description, b.price,
                b.image_url, b.is_featured, b.sort_order,
                t.name  AS type_name, t.slug AS type_slug,
                tp.name AS temple_name, tp.city AS temple_city, tp.state AS temple_state
         FROM bhets b
         LEFT JOIN bhet_types t  ON t.id  = b.bhet_type_id
         LEFT JOIN temples    tp ON tp.id = b.temple_id
         WHERE b.is_active = 1
         ORDER BY b.sort_order ASC, b.id DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[bhet/index] ' . $e->getMessage());
    $bhets = [];
}

$totalCount = count($bhets);

$pageTitle = 'Temple Bhet & Daan Sevas — Divya Aastha';
$pageDesc  = 'Offer sacred bhet and daan at revered temples across Bharat with complete transparency and temple acknowledgment.';

require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   BHET PRIMARY PAGE — 3-column card grid
   ============================================================ */
.bhet-hero {
    position: relative;
    overflow: hidden;
    background:
      linear-gradient(135deg, #2B1220 0%, #351B2F 50%, #4A2440 100%);
    padding: 72px 0 64px;
    text-align: center;
}
.bhet-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
      radial-gradient(circle at 15% 30%, rgba(183,97,70,.28) 0%, transparent 50%),
      radial-gradient(circle at 85% 70%, rgba(212,175,55,.16) 0%, transparent 55%);
    pointer-events: none;
}
.bhet-hero::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(212,175,55,.9), transparent);
}
.bhet-hero__inner {
    position: relative;
    z-index: 2;
    max-width: 760px;
    margin: 0 auto;
    padding: 0 20px;
}
.bhet-hero__eyebrow {
    display: inline-block;
    padding: 6px 18px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #D4AF37;
    background: rgba(212,175,55,.12);
    border: 1px solid rgba(212,175,55,.35);
    border-radius: 99px;
    margin-bottom: 22px;
}
.bhet-hero h1 {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 46px;
    line-height: 1.15;
    color: #F7EBDD;
    margin: 0 0 16px;
    font-weight: 600;
}
.bhet-hero p {
    color: rgba(247,235,221,.75);
    font-size: 16px;
    line-height: 1.7;
    margin: 0;
    max-width: 620px;
    margin-left: auto;
    margin-right: auto;
}
@media (max-width: 768px) {
    .bhet-hero { padding: 48px 0 42px; }
    .bhet-hero h1 { font-size: 30px; }
    .bhet-hero p { font-size: 14px; }
    .bhet-hero__eyebrow { font-size: 10px; margin-bottom: 16px; }
}

/* ── Grid ──────────────────────────────────────────────── */
.bhet-section {
    padding: 56px 0 80px;
    background: #FAF6EF;
}
.bhet-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 22px;
}
.bhet-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 32px;
}
.bhet-head h2 {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 28px;
    color: #351B2F;
    margin: 0;
    font-weight: 600;
}
.bhet-head .count {
    color: #6B7280;
    font-size: 13px;
    padding-bottom: 6px;
}

.bhet-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 26px;
}
@media (max-width: 1024px) {
    .bhet-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
}
@media (max-width: 640px) {
    .bhet-grid { grid-template-columns: 1fr; gap: 18px; }
    .bhet-section { padding: 36px 0 56px; }
    .bhet-head h2 { font-size: 22px; }
}

/* ── Card ──────────────────────────────────────────────── */
.bhet-card {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #EFE6D9;
    box-shadow: 0 1px 3px rgba(53,27,47,.05);
    display: flex;
    flex-direction: column;
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.bhet-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 32px rgba(53,27,47,.12);
    border-color: rgba(183,97,70,.28);
}

.bhet-media {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
    background: #F3F4F6;
}
.bhet-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .35s ease;
}
.bhet-card:hover .bhet-media img {
    transform: scale(1.05);
}

.bhet-media__badge {
    position: absolute;
    top: 14px;
    left: 14px;
    padding: 5px 12px;
    background: linear-gradient(135deg, #F5D76E, #D4AF37);
    color: #4A2C0F;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    border-radius: 99px;
    box-shadow: 0 3px 10px rgba(0,0,0,.18);
    z-index: 2;
}
.bhet-media__type {
    position: absolute;
    bottom: 12px;
    left: 12px;
    padding: 5px 12px;
    background: rgba(53,27,47,.85);
    backdrop-filter: blur(4px);
    color: #F7EBDD;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .4px;
    border-radius: 99px;
    z-index: 2;
}
.bhet-media__loc {
    position: absolute;
    bottom: 12px;
    right: 12px;
    padding: 5px 11px;
    background: rgba(255,255,255,.95);
    color: #351B2F;
    font-size: 11px;
    font-weight: 600;
    border-radius: 99px;
    box-shadow: 0 2px 6px rgba(0,0,0,.10);
    z-index: 2;
}

.bhet-body {
    padding: 20px 22px 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}
.bhet-temple {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    color: #B76146;
}
.bhet-title {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 19px;
    line-height: 1.32;
    color: #351B2F;
    margin: 0;
    font-weight: 600;
}
.bhet-title a {
    color: inherit;
    text-decoration: none;
}
.bhet-title a:hover { color: #B76146; }

.bhet-desc {
    color: #6B7280;
    font-size: 13.5px;
    line-height: 1.62;
    margin: 0;
    flex: 1;

    /* 3 lines max, ellipsis */
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.bhet-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-top: 14px;
    margin-top: 6px;
    border-top: 1px solid #F3EEE6;
}
.bhet-price {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.bhet-price .from {
    font-size: 10.5px;
    color: #9CA3AF;
    letter-spacing: .5px;
    text-transform: uppercase;
    font-weight: 600;
}
.bhet-price .amt {
    font-size: 19px;
    font-weight: 700;
    color: #351B2F;
    line-height: 1.2;
}

.bhet-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
    background: #B76146;
    color: #fff;
    text-decoration: none;
    border: 1px solid #B76146;
    white-space: nowrap;
    transition: background .18s, transform .18s;
}
.bhet-btn:hover {
    background: #9B4C34;
    color: #fff;
    transform: translateX(2px);
}
.bhet-btn svg {
    width: 14px;
    height: 14px;
}

/* ── Empty state ──────────────────────────────────────── */
.bhet-empty {
    text-align: center;
    padding: 80px 20px;
    color: #6B7280;
}
.bhet-empty .ico {
    font-size: 56px;
    color: #D4AF37;
    margin-bottom: 16px;
    line-height: 1;
}
.bhet-empty h3 {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 22px;
    color: #351B2F;
    margin: 0 0 8px;
}
.bhet-empty p { margin: 0; font-size: 14px; }
</style>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="bhet-hero">
  <div class="bhet-hero__inner">
    <span class="bhet-hero__eyebrow">Sacred Offerings</span>
    <h1>Temple Bhet &amp; Daan Sevas</h1>
    <p>
      Offer sacred bhet and daan at revered temples across Bharat with complete
      transparency and temple acknowledgment. Every seva is performed by verified
      priests and delivered with divine blessings.
    </p>
  </div>
</section>

<!-- ═══════════════ GRID ═══════════════ -->
<section class="bhet-section">
  <div class="bhet-container">

    <?php if (empty($bhets)): ?>

      <div class="bhet-empty">
        <div class="ico">🛕</div>
        <h3>No Bhets Available Yet</h3>
        <p>New sacred offerings are being added. Please check back soon.</p>
      </div>

    <?php else: ?>

      <div class="bhet-head">
        <h2>All Bhet &amp; Daan Sevas</h2>
        <span class="count">
          Showing <b><?= (int) $totalCount ?></b>
          <?= $totalCount === 1 ? 'seva' : 'sevas' ?>
        </span>
      </div>

      <div class="bhet-grid">

        <?php foreach ($bhets as $b): ?>
          <article class="bhet-card">

            <!-- Image -->
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

            <!-- Body -->
            <div class="bhet-body">

              <?php if (!empty($b['temple_name'])): ?>
                <div class="bhet-temple"><?= e($b['temple_name']) ?></div>
              <?php endif; ?>

              <h3 class="bhet-title">
                <a href="<?= url('bhet/detail.php?slug=' . urlencode($b['slug'])) ?>">
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

                <a href="<?= url('bhet/detail.php?slug=' . urlencode($b['slug'])) ?>"
                   class="bhet-btn">
                  Offer Bhet
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