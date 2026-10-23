<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account-bookings.php';
    flash_set('info', 'Please login to view your bookings.');
    redirect('login.php');
}

$userId = current_user_id();

/* Fetch bookings with puja and temple details */
$stmt = $pdo->prepare(
    'SELECT b.*, p.name AS puja_name, p.image_url AS puja_image,
            t.name AS temple_name, t.city AS temple_city
     FROM puja_bookings b
     LEFT JOIN pujas p ON p.id = b.puja_id
     LEFT JOIN temples t ON t.id = b.temple_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC'
);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Image helper (inline) */
if (!function_exists('ac_img_url')) {
    function ac_img_url(?string $s): string {
        $s = trim((string) $s);
        if ($s === '') return '';
        if (preg_match('#^https?://#i', $s)) return $s;
        $s = ltrim($s, '/');
        if (strpos($s, 'assets/img/') === 0) $s = substr($s, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($s, $m);
        if ($pos !== false) $s = substr($s, $pos + strlen($m));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($s, '/');
    }
}

$pageTitle = 'My Puja Bookings — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('account.php') ?>">My Account</a>
      <span class="sep">›</span>
      <span class="current">Puja Bookings</span>
    </nav>
    <span class="eyebrow">Sacred Rituals</span>
    <h1>My Puja Bookings</h1>
    <p>All your puja bookings, prasad updates and video proofs.</p>
  </div>
</section>

<style>
.ac-shell{max-width:1200px;margin:0 auto;padding:36px 22px 80px;background:#FAF6EF}
.ac-layout{display:grid;grid-template-columns:240px 1fr;gap:26px}
@media(max-width:900px){.ac-layout{grid-template-columns:1fr;gap:18px}}
.ac-nav{background:#fff;border:1px solid #EFE6D9;border-radius:16px;padding:14px;height:fit-content;
  position:sticky;top:90px;box-shadow:0 1px 3px rgba(53,27,47,.05)}
@media(max-width:900px){.ac-nav{position:static;display:flex;overflow-x:auto;gap:8px;padding:10px}}
.ac-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;
  font-size:13.5px;font-weight:600;color:#4B5563;text-decoration:none;transition:all .15s;margin-bottom:4px}
@media(max-width:900px){.ac-nav a{flex-shrink:0;margin-bottom:0;white-space:nowrap}}
.ac-nav a:hover{background:#FAF6EF;color:#B76146}
.ac-nav a.active{background:#351B2F;color:#fff}
.ac-nav a i{font-size:16px;width:18px;text-align:center}
.ac-nav a.logout{color:#DC2626;margin-top:8px;border-top:1px solid #F3EEE6;padding-top:16px}
.ac-nav a.logout:hover{background:#FEF2F2;color:#DC2626}
.ac-content{min-width:0}
.ac-h1{font-family:var(--font-serif,Georgia,serif);font-size:26px;color:#351B2F;margin:0 0 6px;font-weight:600}
.ac-sub{color:#6B7280;font-size:13.5px;margin:0 0 24px;line-height:1.6}
.ac-flash{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;line-height:1.55}
.ac-flash.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ac-flash.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-list{list-style:none;padding:0;margin:0}
.ac-list li{padding:18px 22px;border-bottom:1px solid #F3EEE6;display:grid;
  grid-template-columns:80px 1fr auto;gap:16px;align-items:center}
.ac-list li:last-child{border-bottom:0}
@media(max-width:640px){
  .ac-list li{grid-template-columns:1fr;gap:12px;padding:16px}
}
.ac-list__thumb{
  width:80px;height:80px;border-radius:12px;object-fit:cover;
  background:#F3F4F6;border:1px solid #EFE6D9;display:block;
}
@media(max-width:640px){.ac-list__thumb{width:100%;height:140px;border-radius:10px}}
.ac-list__main{min-width:0}
.ac-list__title{font-weight:700;color:#351B2F;font-size:15px;line-height:1.35;margin-bottom:6px;
  font-family:var(--font-serif,Georgia,serif)}
.ac-list__meta{font-size:12.5px;color:#6B7280;line-height:1.65}
.ac-list__meta b{color:#351B2F;font-weight:600}
.ac-list__right{text-align:right}
@media(max-width:640px){.ac-list__right{text-align:left}}
.ac-list__amount{font-weight:700;color:#351B2F;font-size:16px;margin-bottom:8px}
.ac-pill{display:inline-flex;padding:4px 11px;font-size:10.5px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.ac-pill.green{background:#ECFDF5;color:#059669}
.ac-pill.amber{background:#FFFBEB;color:#D97706}
.ac-pill.red{background:#FEF2F2;color:#DC2626}
.ac-pill.blue{background:#EFF6FF;color:#2563EB}
.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:9px 18px;font-size:12.5px;font-weight:600;border-radius:9px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ac-btn-primary:hover{background:#9B4C34;color:#fff}
.ac-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.ac-btn-outline:hover{border-color:#B76146;color:#B76146}
.ac-empty{padding:60px 24px;text-align:center;color:#6B7280}
.ac-empty .ico{font-size:52px;color:#D4AF37;margin-bottom:14px;line-height:1}
.ac-empty h4{font-family:var(--font-serif,Georgia,serif);font-size:20px;color:#351B2F;margin:0 0 8px}
.ac-empty p{font-size:13.5px;margin:0 0 22px;line-height:1.6}
</style>

<div class="ac-shell">
  <div class="ac-layout">
    <aside class="ac-nav">
      <a href="<?= url('account.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>" class="active"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <div class="ac-content">
      <h1 class="ac-h1">My Puja Bookings</h1>
      <p class="ac-sub">Showing <b><?= count($bookings) ?></b> booking<?= count($bookings) === 1 ? '' : 's' ?>.</p>

      <?php foreach (flash_get() as $f): ?>
        <div class="ac-flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <div class="ac-card">
        <?php if (empty($bookings)): ?>
          <div class="ac-empty">
            <div class="ico">🕉️</div>
            <h4>No Bookings Yet</h4>
            <p>Book a sacred puja at a revered temple and receive prasad at home.</p>
            <a href="<?= url('pujas.php') ?>" class="ac-btn ac-btn-primary">Browse Pujas</a>
          </div>
        <?php else: ?>
          <ul class="ac-list">
            <?php foreach ($bookings as $b): ?>
              <?php
              $imgSrc = ac_img_url($b['puja_image']);
              $statusClass = 'amber';
              if ($b['status'] === 'completed' || $b['status'] === 'confirmed') $statusClass = 'green';
              elseif ($b['status'] === 'cancelled') $statusClass = 'red';
              ?>
              <li>
                <?php if ($imgSrc): ?>
                  <img src="<?= e($imgSrc) ?>" alt="<?= e($b['puja_name']) ?>"
                       class="ac-list__thumb" loading="lazy"
                       onerror="this.style.background='#F3E8D8';this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%239A8B7C%22><path d=%22M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 4a3 3 0 110 6 3 3 0 010-6z%22/></svg>';">
                <?php else: ?>
                  <div class="ac-list__thumb" style="background:#F3E8D8;display:grid;place-items:center;color:#9A8B7C;font-size:28px">
                    <i class="bi bi-flower1"></i>
                  </div>
                <?php endif; ?>

                <div class="ac-list__main">
                  <div class="ac-list__title">
                    <?= e($b['puja_name'] ?: 'Puja') ?>
                    <span class="ac-pill <?= $statusClass ?>" style="margin-left:8px"><?= e($b['status']) ?></span>
                  </div>
                  <div class="ac-list__meta">
                    Ref: <b><?= e($b['booking_ref']) ?></b>
                    <?php if (!empty($b['temple_name'])): ?>
                      <br>🛕 <b><?= e($b['temple_name']) ?></b><?= !empty($b['temple_city']) ? ', ' . e($b['temple_city']) : '' ?>
                    <?php endif; ?>
                    <br>📅 <?= date('d M Y', strtotime($b['booking_date'])) ?>
                    <?php if (!empty($b['muhurat_time'])): ?>
                      · ⏱ <?= date('h:i A', strtotime($b['muhurat_time'])) ?>
                    <?php endif; ?>
                    <?php if (!empty($b['devotee_name'])): ?>
                      <br>👤 <?= e($b['devotee_name']) ?>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="ac-list__right">
                  <div class="ac-list__amount"><?= inr($b['amount']) ?></div>
                  <a href="<?= url('account-booking.php?id=' . (int) $b['id']) ?>"
                     class="ac-btn ac-btn-outline">
                    View Details →
                  </a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>