<?php
require_once dirname(__DIR__) . '/config/config.php';

/* ==================================================================
 |  FRONTEND IMAGE HELPER (icon — used if types have icon images later)
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

/* ==================================================================
 |  Data fetch
 * ================================================================== */
try {
    $types = $pdo->query(
        'SELECT t.id, t.name, t.slug, t.short_desc, t.icon, t.sort_order,
                (SELECT COUNT(*) FROM bhets b
                 WHERE b.bhet_type_id = t.id AND b.is_active = 1) AS bhet_count
         FROM bhet_types t
         WHERE t.is_active = 1
         ORDER BY t.sort_order ASC, t.name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[bhet/types] ' . $e->getMessage());
    $types = [];
}

$totalTypes = count($types);

$pageTitle = 'Types of Seva — Divya Aastha';
$pageDesc  = 'Explore the different types of sacred seva offered at temples across Bharat — Gau Seva, Anna Daan, Vastra Daan and more.';

require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   TYPES OF SEVA PAGE
   ============================================================ */
.bht-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #2B1220 0%, #351B2F 55%, #4A2440 100%);
    padding: 72px 0 64px;
    text-align: center;
}
.bht-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
      radial-gradient(circle at 20% 40%, rgba(212,175,55,.16) 0%, transparent 55%),
      radial-gradient(circle at 80% 60%, rgba(183,97,70,.22) 0%, transparent 55%);
    pointer-events: none;
}
.bht-hero::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(212,175,55,.9), transparent);
}
.bht-hero__inner {
    position: relative;
    z-index: 2;
    max-width: 720px;
    margin: 0 auto;
    padding: 0 20px;
}
.bht-hero__eyebrow {
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
.bht-hero h1 {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 44px;
    line-height: 1.15;
    color: #F7EBDD;
    margin: 0 0 16px;
    font-weight: 600;
}
.bht-hero p {
    color: rgba(247,235,221,.75);
    font-size: 16px;
    line-height: 1.7;
    margin: 0;
    max-width: 580px;
    margin-left: auto;
    margin-right: auto;
}
@media (max-width: 768px) {
    .bht-hero { padding: 48px 0 42px; }
    .bht-hero h1 { font-size: 28px; }
    .bht-hero p { font-size: 14px; }
}

.bht-section {
    padding: 56px 0 80px;
    background: #FAF6EF;
}
.bht-container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 22px;
}
.bht-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
@media (max-width: 900px) {
    .bht-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .bht-grid { grid-template-columns: 1fr; gap: 16px; }
    .bht-section { padding: 36px 0 56px; }
}

.bht-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px 24px 24px;
    border: 1px solid #EFE6D9;
    box-shadow: 0 1px 3px rgba(53,27,47,.05);
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    position: relative;
    overflow: hidden;
}
.bht-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #B76146, #D4AF37, #B76146);
    opacity: 0;
    transition: opacity .25s;
}
.bht-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 30px rgba(53,27,47,.12);
    border-color: rgba(183,97,70,.28);
}
.bht-card:hover::before { opacity: 1; }

.bht-card__icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-size: 26px;
    background: linear-gradient(135deg, #F4EAF0, #F7EBDD);
    color: #B76146;
    margin-bottom: 4px;
    overflow: hidden;
}
.bht-card__icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.bht-card__name {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 20px;
    font-weight: 600;
    color: #351B2F;
    margin: 0;
    line-height: 1.3;
}
.bht-card__desc {
    color: #6B7280;
    font-size: 13.5px;
    line-height: 1.6;
    margin: 0;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.bht-card__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    margin-top: 4px;
    border-top: 1px solid #F3EEE6;
}
.bht-card__count {
    font-size: 12px;
    color: #6B7280;
    font-weight: 600;
}
.bht-card__count b { color: #B76146; font-weight: 700; }

.bht-card__cta {
    font-size: 12.5px;
    font-weight: 700;
    color: #B76146;
    letter-spacing: .3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.bht-card:hover .bht-card__cta { color: #9B4C34; }

.bht-empty {
    text-align: center;
    padding: 80px 20px;
    color: #6B7280;
}
.bht-empty .ico {
    font-size: 56px;
    color: #D4AF37;
    margin-bottom: 16px;
    line-height: 1;
}
.bht-empty h3 {
    font-family: var(--font-serif, 'Playfair Display', Georgia, serif);
    font-size: 22px;
    color: #351B2F;
    margin: 0 0 8px;
}
</style>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="bht-hero">
  <div class="bht-hero__inner">
    <span class="bht-hero__eyebrow">Sacred Categories</span>
    <h1>Types of Seva</h1>
    <p>
      Discover the many paths of sacred giving — from Anna Daan and Gau Seva
      to Vastra Daan and Vidya Daan. Every offering brings blessings to the
      giver and to the world.
    </p>
  </div>
</section>

<!-- ═══════════════ GRID ═══════════════ -->
<section class="bht-section">
  <div class="bht-container">

    <?php if (empty($types)): ?>

      <div class="bht-empty">
        <div class="ico">🪔</div>
        <h3>No Seva Types Available</h3>
        <p>New seva categories are being added. Please check back soon.</p>
      </div>

    <?php else: ?>

      <div class="bht-grid">

        <?php foreach ($types as $t): ?>
          <a href="<?= url('bhet/index.php?type=' . urlencode($t['slug'])) ?>"
             class="bht-card">

            <div class="bht-card__icon">
              <?php if (!empty($t['icon']) && preg_match('#\.(jpg|jpeg|png|webp|gif|svg)$#i', $t['icon'])): ?>
                <img src="<?= e(bhet_front_img_url($t['icon'])) ?>" alt="<?= e($t['name']) ?>">
              <?php elseif (!empty($t['icon'])): ?>
                <?= e($t['icon']) ?>
              <?php else: ?>
                🪔
              <?php endif; ?>
            </div>

            <h3 class="bht-card__name"><?= e($t['name']) ?></h3>

            <?php if (!empty($t['short_desc'])): ?>
              <p class="bht-card__desc"><?= e($t['short_desc']) ?></p>
            <?php endif; ?>

            <div class="bht-card__foot">
              <span class="bht-card__count">
                <b><?= (int) $t['bhet_count'] ?></b>
                <?= $t['bhet_count'] == 1 ? 'Seva' : 'Sevas' ?>
              </span>
              <span class="bht-card__cta">View Sevas →</span>
            </div>
          </a>
        <?php endforeach; ?>

      </div>
    <?php endif; ?>

  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>