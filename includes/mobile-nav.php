<?php
$nav = [
    ['label' => 'Home',            'href' => 'index.php'],
    ['label' => 'Puja',            'href' => 'pujas.php'],
    ['label' => 'Bhet',            'href' => 'bhet.php'],
    ['label' => 'Panchang',        'href' => 'panchang.php'],
    ['label' => 'Rashifal',        'href' => 'rashifal.php'],
    ['label' => 'Gyan',            'href' => 'gyan.php'],
    ['label' => 'Spiritual Store', 'href' => 'shop.php'],
];
$current = current_page();
?>
<div class="mobile-nav" id="mobileNav" aria-hidden="true">
  <div class="mn-backdrop" data-close-mobile></div>
  <aside class="mn-panel" role="dialog" aria-label="Mobile navigation">
    <div class="mn-head">
      <a href="<?= url('index.php') ?>" class="brand">
        <span class="brand-mark">ॐ</span>
        <span class="brand-text"><strong>Divya</strong><em>Aastha</em></span>
      </a>
      <button class="mn-close" data-close-mobile aria-label="Close">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <nav class="mn-links">
      <?php foreach ($nav as $item): ?>
        <a href="<?= url($item['href']) ?>" class="<?= $current === $item['href'] ? 'active' : '' ?>">
          <?= e($item['label']) ?> <span class="arw">→</span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="mn-foot">
      <a href="<?= url('pujas.php') ?>" class="btn btn-terra btn-block">Book Puja</a>
      <a href="<?= url('shop.php') ?>" class="btn btn-outline btn-block" style="margin-top:10px">Explore Store</a>
    </div>
  </aside>
</div>