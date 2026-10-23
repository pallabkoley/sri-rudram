<?php
if (!defined('BASE_PATH')) require_once __DIR__ . '/config/config.php';
http_response_code(404);
$pageTitle = 'Page Not Found — ' . APP_NAME;
require BASE_PATH . '/includes/header.php';
?>

<section class="section" style="min-height:60vh;display:flex;align-items:center">
  <div class="container">
    <div class="coming-soon">
      <div class="om">ॐ</div>
      <h2>404 — Page Not Found</h2>
      <p>The page you are looking for does not exist or has been moved.</p>
      <a href="<?= url('index.php') ?>" class="btn btn-terra">Back to Home</a>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>