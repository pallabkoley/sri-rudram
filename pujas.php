<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  SAFE IMAGE HELPERS (self-contained — no external dependency)
 * ================================================================== */
if (!function_exists('safe_img_url')) {
    function safe_img_url(?string $stored): string {
        if (!$stored) return '';
        $stored = ltrim(trim($stored), '/');
        if (preg_match('#^https?://#i', $stored)) return $stored;
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . $stored;
    }
}

if (!function_exists('safe_img')) {
    function safe_img(?string $stored, string $alt = '', string $class = '', bool $lazy = true): string {
        $src = safe_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($class ?: 'img-ph', ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;'
                 . 'color:#9A8B7C;min-height:200px">'
                 . '<i class="bi bi-image" style="font-size:32px"></i></div>';
        }
        $lazyAttr = $lazy ? ' loading="lazy"' : '';
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="'
             . htmlspecialchars($alt, ENT_QUOTES) . '"'
             . ($class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '')
             . $lazyAttr . '>';
    }
}

/* ==================================================================
 |  Data fetch
 * ================================================================== */
$templeFilter = trim((string)($_GET['temple'] ?? ''));

$filterTemples = $pdo->query("
    SELECT DISTINCT t.id, t.name, t.slug
    FROM temples t
    INNER JOIN pujas p ON p.temple_id = t.id AND p.is_active = 1
    ORDER BY t.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($templeFilter !== '') {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.slug, p.short_desc, p.price, p.badge, p.badge_style,
               p.image_url, p.duration_mins, p.temple_id,
               t.name AS temple_name, t.slug AS temple_slug,
               t.city AS temple_city, t.state AS temple_state
        FROM pujas p
        LEFT JOIN temples t ON t.id = p.temple_id
        WHERE p.is_active = 1 AND t.slug = ?
        ORDER BY p.sort_order ASC, p.id DESC
    ");
    $stmt->execute([$templeFilter]);
    $pujas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pujas = $pdo->query("
        SELECT p.id, p.name, p.slug, p.short_desc, p.price, p.badge, p.badge_style,
               p.image_url, p.duration_mins, p.temple_id,
               t.name AS temple_name, t.slug AS temple_slug,
               t.city AS temple_city, t.state AS temple_state
        FROM pujas p
        LEFT JOIN temples t ON t.id = p.temple_id
        WHERE p.is_active = 1
        ORDER BY p.sort_order ASC, p.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$totalCount = count($pujas);

$pageTitle = 'Puja Services — Book Authentic Hindu Pujas Online';
$pageDesc  = 'Book authentic rituals performed by experienced priests at sacred temples across Bharat. Receive prasad at your home.';

require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ REDESIGNED PUJA CARD STYLES ═══════════════ -->
<style>
  /* ---- Grid ---- */
  .puja-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
  }
  @media (max-width: 1199px) {
    .puja-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  }
  @media (max-width: 900px) {
    .puja-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  }
  @media (max-width: 560px) {
    .puja-grid { grid-template-columns: 1fr; gap: 14px; }
  }

  /* ---- Card ---- */
  .puja-card {
    background: #fff;
    border: 1px solid #ECE7E1;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    box-shadow: 0 1px 2px rgba(53, 27, 47, .04);
  }
  .puja-card:hover {
    transform: translateY(-2px);
    border-color: #E0D6CC;
    box-shadow: 0 4px 10px rgba(53, 27, 47, .06);
  }

  /* ---- Media ---- */
  .pj-media {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    background: #F5F1EC;
  }
  .pj-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .pj-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
    background: #FFF7E0;
    color: #8A6A08;
    border: 1px solid rgba(146, 112, 12, .18);
  }

  .pj-loc {
    position: absolute;
    bottom: 10px;
    left: 10px;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 600;
    background: rgba(255, 255, 255, .94);
    color: #351B2F;
    backdrop-filter: blur(2px);
  }

  /* ---- Body ---- */
  .pj-body {
    padding: 14px 14px 12px;
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 6px;
  }

  .pj-temple {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #B76146;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .pj-title {
    font-family: var(--font-serif, serif);
    font-size: 15px;
    line-height: 1.35;
    font-weight: 600;
    color: #351B2F;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 40px;
  }

  .pj-desc {
    margin: 0;
    color: #6B7280;
    font-size: 12.5px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 37px;
  }

  /* ---- Footer ---- */
  .pj-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-top: 10px;
    margin-top: auto;
    border-top: 1px solid #F2EDE7;
  }

  .pj-price {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
  }
  .pj-price .lbl {
    font-size: 9.5px;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: #9CA3AF;
  }
  .pj-price .amt {
    font-size: 16px;
    font-weight: 700;
    color: #351B2F;
    margin-top: 2px;
  }

  .pj-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 7px 13px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 8px;
    background: #B76146;
    color: #fff;
    text-decoration: none;
    white-space: nowrap;
    border: 1px solid #B76146;
    transition: background .16s ease, border-color .16s ease;
  }
  .pj-btn:hover {
    background: #9F4E36;
    border-color: #9F4E36;
    color: #fff;
  }

  /* ---- Filter tabs (unchanged look) ---- */
  .pj-filter {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 26px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border, #E5E7EB);
  }
  .pj-tab {
    display: inline-flex;
    align-items: center;
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all .16s ease;
    border: 1px solid #E5E7EB;
    background: #fff;
    color: #1F2937;
  }
  .pj-tab.is-active {
    background: #351B2F;
    color: #fff;
    border-color: #351B2F;
  }
  .pj-tab .cnt {
    margin-left: 7px;
    font-size: 10.5px;
    opacity: .75;
  }
</style>

<!-- ═══════════════ PAGE HERO ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb" style="margin-bottom:12px">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Puja Services</span>
    </nav>
    <h1 style="font-family:var(--font-serif);font-size:44px;line-height:1.15;margin:0 0 12px">
      Puja Services
    </h1>
    <p style="max-width:640px;color:var(--muted);font-size:16px;line-height:1.65">
      Book authentic rituals performed by experienced priests at sacred temples across
      Bharat. Receive prasad at your home.
    </p>
  </div>
</section>

<!-- ═══════════════ FILTER TABS + PUJA GRID ═══════════════ -->
<section class="section">
  <div class="container">

    <!-- Filter tabs -->
    <div class="pj-filter">
      <a href="<?= url('pujas.php') ?>"
         class="pj-tab <?= $templeFilter === '' ? 'is-active' : '' ?>">
        All Pujas
        <span class="cnt"><?= $totalCount ?></span>
      </a>

      <?php foreach ($filterTemples as $ft): ?>
        <a href="<?= url('pujas.php?temple=' . urlencode($ft['slug'])) ?>"
           class="pj-tab <?= $templeFilter === $ft['slug'] ? 'is-active' : '' ?>">
          <?= e($ft['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Puja grid -->
    <?php if (empty($pujas)): ?>
      <div style="text-align:center;padding:80px 20px;color:var(--muted)">
        <div style="font-size:48px;margin-bottom:16px">🛕</div>
        <h3 style="font-family:var(--font-serif);font-size:22px;color:var(--ink);margin-bottom:8px">
          No pujas found
        </h3>
        <p>Try selecting a different temple filter.</p>
        <a href="<?= url('pujas.php') ?>" class="btn btn-terra" style="margin-top:16px">
          View All Pujas
        </a>
      </div>
    <?php else: ?>

      <div class="puja-grid">
        <?php foreach ($pujas as $p): ?>
          <article class="puja-card">

            <!-- Media -->
            <div class="pj-media">
              <?= safe_img($p['image_url'], $p['name'], '', true) ?>

              <?php if (!empty($p['badge'])): ?>
                <span class="pj-badge"><?= e($p['badge']) ?></span>
              <?php endif; ?>

              <?php if (!empty($p['temple_city'])): ?>
                <span class="pj-loc">📍 <?= e($p['temple_city']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="pj-body">

              <?php if (!empty($p['temple_name'])): ?>
                <div class="pj-temple"><?= e($p['temple_name']) ?></div>
              <?php endif; ?>

              <h3 class="pj-title"><?= e($p['name']) ?></h3>

              <p class="pj-desc"><?= e($p['short_desc']) ?></p>

              <!-- Footer: price + button -->
              <div class="pj-foot">
                <div class="pj-price">
                  <span class="lbl">Starting at</span>
                  <span class="amt"><?= inr($p['price']) ?></span>
                </div>

                <a href="<?= url('puja.php?slug=' . urlencode($p['slug'])) ?>"
                   class="pj-btn">
                  Book Puja →
                </a>
              </div>

            </div>
          </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <!-- Count line -->
    <?php if (!empty($pujas)): ?>
      <p style="text-align:center;margin-top:36px;color:var(--muted);
                font-size:13px;letter-spacing:.4px">
        Showing <b style="color:var(--ink)"><?= $totalCount ?></b>
        <?= $totalCount === 1 ? 'puja' : 'pujas' ?>
        <?= $templeFilter !== '' ? 'from this temple' : 'available for booking' ?>
      </p>
    <?php endif; ?>

  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>