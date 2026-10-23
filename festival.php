<?php
require_once __DIR__ . '/config/config.php';
$slug = $_GET['slug'] ?? '';
$festival = get_festival_by_slug($slug);

if (!$festival) { http_response_code(404); require BASE_PATH . '/404.php'; exit; }

$pageTitle = $festival['name'] . ' — ' . date('Y', strtotime($festival['festival_date']));
$pageDesc  = $festival['short_desc'];
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('festivals.php') ?>">Festivals</a>
      <span class="sep">›</span>
      <span class="current"><?= e($festival['name']) ?></span>
    </nav>
    <span class="eyebrow"><?= date('d M Y', strtotime($festival['festival_date'])) ?></span>
    <h1><?= e($festival['name']) ?></h1>
    <p><?= e($festival['short_desc']) ?></p>
  </div>
</section>

<article class="container">
  <div class="detail-media" style="aspect-ratio:16/7;margin-top:40px;border-radius:var(--r-lg)">
    <?= img_tag($festival['image_url'], $festival['name'], '', false) ?>
  </div>

  <div class="article-body">
    <?php if (!empty($festival['description'])): ?>
      <?= nl2br(e($festival['description'])) ?>
    <?php else: ?>
      <p>Detailed information about this festival is coming soon.</p>
    <?php endif; ?>
  </div>

  <div class="center-cta">
    <a href="<?= url('pujas.php') ?>" class="btn btn-terra">Book Festival Puja</a>
  </div>
</article>

<?php require BASE_PATH . '/includes/footer.php'; ?>