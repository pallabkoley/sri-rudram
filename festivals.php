<?php
require_once __DIR__ . '/config/config.php';
$festivals = get_upcoming_festivals(20);
$pageTitle = 'Upcoming Hindu Festivals 2026 — Tithi & Muhurat';
$pageDesc  = 'Plan your puja, vrat and celebrations with our festival calendar.';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Festivals</span>
    </nav>
    <span class="eyebrow">Tithi &amp; Muhurat</span>
    <h1>Upcoming Festivals</h1>
    <p>Plan your puja, vrat and celebrations with our festival calendar.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fest-grid">
      <?php foreach ($festivals as $f): ?>
        <article class="fest-card reveal">
          <?= img_tag($f['image_url'], $f['name']) ?>
          <div class="fest-inner">
            <span class="fest-date">📅 <?= date('d M Y', strtotime($f['festival_date'])) ?></span>
            <h3><?= e($f['name']) ?></h3>
            <p><?= e($f['short_desc']) ?></p>
            <a href="<?= url('festival.php?slug=' . $f['slug']) ?>" class="btn-ghost">Explore →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>