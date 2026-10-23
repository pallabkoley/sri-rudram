<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require BASE_PATH . '/includes/handlers/cart-handler.php';
    require BASE_PATH . '/includes/handlers/wishlist-handler.php';
}

$slug = $_GET['slug'] ?? '';
$product = get_product_by_slug($slug);

if (!$product) {
    http_response_code(404);
    require BASE_PATH . '/404.php';
    exit;
}

$off = off_percent($product);
$pageTitle = $product['name'] . ' — Siddha Store';
$pageDesc  = $product['short_desc'] ?? '';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('shop.php') ?>">Shop</a>
      <span class="sep">›</span>
      <span class="current"><?= e($product['name']) ?></span>
    </nav>
  </div>
</section>

<section class="container">
  <div class="detail-grid">
    <div class="detail-media">
      <?= img_tag($product['image_url'], $product['name'], '', false) ?>
      <?php if (!empty($product['badge'])): ?>
        <span class="badge-overlay"><?= e($product['badge']) ?></span>
      <?php endif; ?>
    </div>

    <div class="detail-info">
      <h1><?= e($product['name']) ?></h1>

      <div class="detail-meta">
        <?php if (!empty($product['category_name'])): ?>
          <span class="m-item">🏷 <b><?= e($product['category_name']) ?></b></span>
        <?php endif; ?>
        <span class="m-item">
          <?= $product['stock'] > 0
              ? '✅ <b>In Stock</b>'
              : '⚠ <b>Out of Stock</b>' ?>
        </span>
      </div>

      <div class="detail-price">
        <span class="now"><?= inr($product['price']) ?></span>
        <?php if (!empty($product['mrp'])): ?>
          <span class="was"><?= inr($product['mrp']) ?></span>
        <?php endif; ?>
        <?php if ($off): ?>
          <span class="off"><?= $off ?>% OFF</span>
        <?php endif; ?>
      </div>

      <div class="detail-desc">
        <p><?= e($product['short_desc']) ?></p>
        <?php if (!empty($product['description'])): ?>
          <h3>Product Details</h3>
          <p><?= nl2br(e($product['description'])) ?></p>
        <?php endif; ?>
      </div>

      <div class="detail-actions">
        <div class="qty-selector">
          <button type="button" onclick="document.getElementById('qty').value=Math.max(1,+document.getElementById('qty').value-1)">−</button>
          <input type="number" id="qty" value="1" min="1" max="<?= (int) $product['stock'] ?>">
          <button type="button" onclick="document.getElementById('qty').value=+document.getElementById('qty').value+1">+</button>
        </div>
        <button class="btn btn-terra" data-add-id="<?= (int) $product['id'] ?>"
                data-add-name="<?= e($product['name']) ?>"
                data-add-price="<?= (int) $product['price'] ?>"
                data-add-img="<?= e(upload_url($product['image_url'])) ?>">
          Add to Cart
        </button>
        <button class="btn btn-outline wish-btn" data-wish-id="<?= (int) $product['id'] ?>" style="position:static">
          ♡ Wishlist
        </button>
      </div>

      <div class="trust-row">
        <span class="trust-item">
          <svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
          100% Authentic
        </span>
        <span class="trust-item">
          <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
          Lab Certified
        </span>
        <span class="trust-item">
          <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7"/></svg>
          Free Shipping
        </span>
      </div>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>