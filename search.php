<?php
require_once __DIR__ . '/config/config.php';

$q = trim($_GET['q'] ?? '');
$results = ['pujas' => [], 'temples' => [], 'products' => [], 'articles' => []];

if (strlen($q) >= 2) {
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare("SELECT name, slug, image_url, short_desc FROM pujas WHERE is_active=1 AND name LIKE ? LIMIT 10");
    $stmt->execute([$like]);
    $results['pujas'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT name, slug, image_url, city, state FROM temples WHERE is_active=1 AND (name LIKE ? OR city LIKE ?) LIMIT 10");
    $stmt->execute([$like, $like]);
    $results['temples'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT name, slug, image_url, price FROM products WHERE is_active=1 AND name LIKE ? LIMIT 10");
    $stmt->execute([$like]);
    $results['products'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT title, slug, image_url, excerpt FROM articles WHERE is_active=1 AND title LIKE ? LIMIT 10");
    $stmt->execute([$like]);
    $results['articles'] = $stmt->fetchAll();
}

$total = array_sum(array_map('count', $results));

$pageTitle = $q ? "Search: $q" : 'Search';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Search</span>
    </nav>
    <h1>Search Results</h1>
    <p><?= $q ? "Showing results for: <strong>" . e($q) . "</strong>" : "Enter a search term" ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (!$q): ?>
      <div class="coming-soon">
        <div class="om">🔍</div>
        <h2>Search Divya Aastha</h2>
        <p>Find pujas, temples, products and articles.</p>
      </div>
    <?php elseif ($total === 0): ?>
      <div class="coming-soon">
        <div class="om">😔</div>
        <h2>No results found</h2>
        <p>No matches for "<strong><?= e($q) ?></strong>". Try a different search term.</p>
        <a href="<?= url('index.php') ?>" class="btn btn-outline">Back to Home</a>
      </div>
    <?php else: ?>
      <p style="margin-bottom:24px;color:var(--muted)">Found <?= $total ?> results</p>

      <?php if (!empty($results['pujas'])): ?>
        <h2 style="font-size:20px;margin-bottom:18px">Pujas (<?= count($results['pujas']) ?>)</h2>
        <div class="puja-grid" style="margin-bottom:40px">
          <?php foreach ($results['pujas'] as $p): ?>
            <article class="puja-card">
              <div class="pj-media"><?= img_tag($p['image_url'], $p['name']) ?></div>
              <div class="pj-body">
                <h3><?= e($p['name']) ?></h3>
                <p><?= e($p['short_desc']) ?></p>
                <a href="<?= url('puja.php?slug=' . $p['slug']) ?>" class="btn btn-terra btn-sm">View</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['products'])): ?>
        <h2 style="font-size:20px;margin-bottom:18px">Products (<?= count($results['products']) ?>)</h2>
        <div class="puja-grid" style="margin-bottom:40px">
          <?php foreach ($results['products'] as $p): ?>
            <article class="shop-card">
              <div class="shop-media"><?= img_tag($p['image_url'], $p['name']) ?></div>
              <div class="shop-body">
                <h3><a href="<?= url('product.php?slug=' . $p['slug']) ?>"><?= e($p['name']) ?></a></h3>
                <div class="shop-price"><span class="now"><?= inr($p['price']) ?></span></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['temples'])): ?>
        <h2 style="font-size:20px;margin-bottom:18px">Temples (<?= count($results['temples']) ?>)</h2>
        <div class="puja-grid" style="margin-bottom:40px">
          <?php foreach ($results['temples'] as $t): ?>
            <article class="temple-card">
              <div class="temple-media"><?= img_tag($t['image_url'], $t['name']) ?></div>
              <div class="temple-body">
                <h3><?= e($t['name']) ?></h3>
                <a href="<?= url('temple.php?slug=' . $t['slug']) ?>" class="btn btn-terra btn-sm">View</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['articles'])): ?>
        <h2 style="font-size:20px;margin-bottom:18px">Articles (<?= count($results['articles']) ?>)</h2>
        <div class="article-grid">
          <?php foreach ($results['articles'] as $a): ?>
            <article class="article-card">
              <div class="ar-media"><?= img_tag($a['image_url'], $a['title']) ?></div>
              <div class="ar-body">
                <h3><a href="<?= url('article.php?slug=' . $a['slug']) ?>"><?= e($a['title']) ?></a></h3>
                <p><?= e($a['excerpt']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>