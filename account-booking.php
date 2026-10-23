<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account-bookings.php';
    redirect('login.php');
}

$userId    = current_user_id();
$bookingId = (int) ($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    flash_set('error', 'Invalid booking.');
    redirect('account-bookings.php');
}

/* Fetch the booking with puja and temple details */
$stmt = $pdo->prepare(
    'SELECT b.*, p.name AS puja_name, p.slug AS puja_slug, p.image_url AS puja_image,
            t.name AS temple_name, t.city AS temple_city
     FROM puja_bookings b
     LEFT JOIN pujas p ON p.id = b.puja_id
     LEFT JOIN temples t ON t.id = b.temple_id
     WHERE b.id = ? AND b.user_id = ?
     LIMIT 1'
);
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    flash_set('error', 'Booking not found.');
    redirect('account-bookings.php');
}

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

$imgSrc = ac_img_url($booking['puja_image']);

$pageTitle = 'Booking ' . $booking['booking_ref'] . ' — Divya Aastha';
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
      <a href="<?= url('account-bookings.php') ?>">Bookings</a>
      <span class="sep">›</span>
      <span class="current"><?= e($booking['booking_ref']) ?></span>
    </nav>
    <span class="eyebrow">Booking Details</span>
    <h1><?= e($booking['puja_name'] ?: 'Puja') ?></h1>
    <p>Booking Reference: <b><?= e($booking['booking_ref']) ?></b></p>
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
.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__body{padding:22px}
.ac-pill{display:inline-flex;padding:4px 11px;font-size:10.5px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.ac-pill.green{background:#ECFDF5;color:#059669}
.ac-pill.amber{background:#FFFBEB;color:#D97706}
.ac-pill.red{background:#FEF2F2;color:#DC2626}
.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:9px 18px;font-size:12.5px;font-weight:600;border-radius:9px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.ac-btn-outline:hover{border-color:#B76146;color:#B76146}
.ac-row{display:grid;grid-template-columns:280px 1fr;gap:24px;margin-bottom:20px}
@media(max-width:700px){.ac-row{grid-template-columns:1fr}}
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
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:20px">
        <div>
          <h1 class="ac-h1"><?= e($booking['puja_name'] ?: 'Puja') ?></h1>
          <p class="ac-sub" style="margin-bottom:0">
            Status: <span class="ac-pill <?= $booking['status'] === 'completed' ? 'green' : ($booking['status'] === 'cancelled' ? 'red' : 'amber') ?>"><?= e($booking['status']) ?></span>
          </p>
        </div>
        <a href="<?= url('account-bookings.php') ?>" class="ac-btn ac-btn-outline">← Back</a>
      </div>

      <div class="ac-row">
        <?php if ($imgSrc): ?>
          <div style="border-radius:16px;overflow:hidden;border:1px solid #EFE6D9;background:#F3F4F6;aspect-ratio:1/1">
            <img src="<?= e($imgSrc) ?>" alt="<?= e($booking['puja_name']) ?>"
                 style="width:100%;height:100%;object-fit:cover;display:block"
                 onerror="this.style.display='none'">
          </div>
        <?php else: ?>
          <div style="border-radius:16px;background:#F3E8D8;aspect-ratio:1/1;display:grid;place-items:center;color:#9A8B7C;font-size:48px">
            <i class="bi bi-flower1"></i>
          </div>
        <?php endif; ?>

        <div class="ac-card" style="margin-bottom:0">
          <div class="ac-card__head"><h3>Booking Details</h3></div>
          <div class="ac-card__body">
            <table style="width:100%;font-size:13.5px;color:#4B5563">
              <tr><td style="padding:7px 0">Reference</td><td style="text-align:right;font-weight:600;color:#351B2F"><?= e($booking['booking_ref']) ?></td></tr>
              <?php if ($booking['temple_name']): ?>
                <tr><td style="padding:7px 0">Temple</td><td style="text-align:right"><?= e($booking['temple_name']) ?><?= $booking['temple_city'] ? ', ' . e($booking['temple_city']) : '' ?></td></tr>
              <?php endif; ?>
              <tr><td style="padding:7px 0">Booking Date</td><td style="text-align:right"><?= date('d M Y', strtotime($booking['booking_date'])) ?></td></tr>
              <?php if ($booking['muhurat_time']): ?>
                <tr><td style="padding:7px 0">Muhurat</td><td style="text-align:right"><?= date('h:i A', strtotime($booking['muhurat_time'])) ?></td></tr>
              <?php endif; ?>
              <tr><td style="padding:7px 0">Amount</td><td style="text-align:right;font-weight:700;color:#351B2F"><?= inr($booking['amount']) ?></td></tr>
            </table>
          </div>
        </div>
      </div>

      <div class="ac-card">
        <div class="ac-card__head"><h3>Sankalp Details</h3></div>
        <div class="ac-card__body" style="font-size:13.5px;color:#4B5563;line-height:1.8">
          <b style="color:#351B2F">Devotee:</b> <?= e($booking['devotee_name']) ?><br>
          <?php if (!empty($booking['gotra'])): ?><b style="color:#351B2F">Gotra:</b> <?= e($booking['gotra']) ?><br><?php endif; ?>
          <?php if (!empty($booking['sankalp'])): ?><b style="color:#351B2F">Sankalp:</b> <?= e($booking['sankalp']) ?><?php endif; ?>
        </div>
      </div>

      <div class="ac-card">
        <div class="ac-card__head"><h3>Updates</h3></div>
        <div class="ac-card__body" style="font-size:13.5px;color:#6B7280;line-height:1.7">
          <?php if (!empty($booking['video_url']) || !empty($booking['prasad_tracking'])): ?>
            <?php if (!empty($booking['video_url'])): ?>
              <b style="color:#351B2F">Puja Video:</b>
              <a href="<?= e($booking['video_url']) ?>" target="_blank" rel="noopener" style="color:#B76146;font-weight:600">Watch →</a><br>
            <?php endif; ?>
            <?php if (!empty($booking['prasad_tracking'])): ?>
              <b style="color:#351B2F">Prasad Tracking:</b> <?= e($booking['prasad_tracking']) ?>
            <?php endif; ?>
          <?php else: ?>
            Your puja video and prasad tracking details will appear here once the puja is performed.
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>