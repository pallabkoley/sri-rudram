<?php
$adminPageTitle = $adminPageTitle ?? 'Dashboard';
$adminPageSub   = $adminPageSub   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($adminPageTitle) ?> — <?= e(APP_NAME) ?> Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

<!-- DataTables (Bootstrap 5 theme) + Responsive -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- Admin styles -->
<link rel="stylesheet" href="<?= asset('admin/assets/css/admin.css') ?>">

<!-- jQuery (required by DataTables — loaded in head so page scripts can use it) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<div class="sb-backdrop" id="sbBackdrop"></div>

<?php require __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="topbar-title">
        <h1><?= e($adminPageTitle) ?></h1>
        <?php if ($adminPageSub): ?><p><?= e($adminPageSub) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="topbar-right">
      <a href="<?= url('index.php') ?>" target="_blank" class="btn-view-site">
        <i class="bi bi-globe2"></i> <span>View Site</span>
      </a>

      <div class="dropdown">
        <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="ava"><?= e(strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1))) ?></span>
          <span class="u-info">
            <b><?= e($_SESSION['admin_name'] ?? 'Admin') ?></b>
            <span>Administrator</span>
          </span>
          <i class="bi bi-chevron-down" style="font-size:12px;color:var(--muted)"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="<?= url('admin/dashboard.php') ?>">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="<?= url('admin/modules/settings/index.php') ?>">
              <i class="bi bi-gear"></i> Settings
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="<?= url('admin/logout.php') ?>">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="content-area">
    <?php foreach (flash_get() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>">
        <i class="bi bi-<?= $f['type'] === 'success' ? 'check-circle' : ($f['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <?= e($f['msg']) ?>
      </div>
    <?php endforeach; ?>