<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  HELPERS
 * ================================================================== */
if (!function_exists('pb_img_url')) {
    function pb_img_url(?string $s): string {
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

if (!function_exists('pb_generate_ref')) {
    function pb_generate_ref(PDO $pdo): string {
        for ($i = 0; $i < 10; $i++) {
            $ref = 'DA-PJ-' . strtoupper(bin2hex(random_bytes(4)));
            $s = $pdo->prepare('SELECT COUNT(*) FROM puja_bookings WHERE booking_ref = ?');
            $s->execute([$ref]);
            if ((int) $s->fetchColumn() === 0) return $ref;
        }
        return 'DA-PJ-' . strtoupper(bin2hex(random_bytes(4))) . substr(time(), -4);
    }
}

/* ==================================================================
 |  SUCCESS PAGE (PRG — never re-posted)
 * ================================================================== */
$isSuccess = (int) ($_GET['success'] ?? 0) === 1;
$bookingId = (int) ($_GET['id'] ?? 0);

if ($isSuccess && $bookingId > 0 && is_logged_in()) {
    $userId = current_user_id();
    $bs = $pdo->prepare(
        'SELECT b.*, p.name AS puja_name, t.name AS temple_name, t.city AS temple_city
         FROM puja_bookings b
         LEFT JOIN pujas p   ON p.id = b.puja_id
         LEFT JOIN temples t ON t.id = b.temple_id
         WHERE b.id = ? AND b.user_id = ? LIMIT 1'
    );
    $bs->execute([$bookingId, $userId]);
    $confirmed = $bs->fetch(PDO::FETCH_ASSOC);

    if ($confirmed) {
        $pageTitle = 'Booking Confirmed — Divya Aastha';
        require BASE_PATH . '/includes/header.php';
        ?>
        <style>
        .pbc-page{background:#FAF6EF;padding:56px 22px 80px;min-height:70vh}
        .pbc-card{max-width:680px;margin:0 auto;background:#fff;border-radius:20px;
          border:1px solid #EFE6D9;box-shadow:0 12px 40px rgba(53,27,47,.08);overflow:hidden}
        .pbc-head{background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
          padding:40px 32px;text-align:center;position:relative;overflow:hidden}
        .pbc-head::after{content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
          background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent)}
        .pbc-check{width:76px;height:76px;border-radius:50%;margin:0 auto 18px;
          background:linear-gradient(135deg,#10B981,#059669);color:#fff;
          display:grid;place-items:center;font-size:36px;
          box-shadow:0 12px 30px rgba(16,185,129,.35)}
        .pbc-head h1{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
          color:#F7EBDD;margin:0 0 8px;font-size:28px;font-weight:600}
        .pbc-head p{color:rgba(247,235,221,.85);margin:0;font-size:14.5px;line-height:1.6}
        .pbc-ref{display:inline-block;margin-top:18px;padding:8px 18px;
          background:rgba(212,175,55,.15);border:1px dashed rgba(212,175,55,.55);
          border-radius:99px;color:#D4AF37;font-size:13px;font-weight:700;
          letter-spacing:1.2px;font-family:monospace}
        .pbc-body{padding:32px}
        .pbc-row{display:flex;justify-content:space-between;padding:14px 0;
          border-bottom:1px solid #F3EEE6;gap:12px}
        .pbc-row:last-of-type{border-bottom:0}
        .pbc-row .lbl{font-size:12.5px;color:#6B7280;font-weight:600;
          text-transform:uppercase;letter-spacing:.6px}
        .pbc-row .val{font-size:14.5px;color:#351B2F;font-weight:600;text-align:right;max-width:60%}
        .pbc-total{background:#FAF6EF;border-radius:12px;padding:18px 22px;margin-top:14px;
          display:flex;justify-content:space-between;align-items:center}
        .pbc-total .lbl{font-size:12.5px;color:#6B7280;font-weight:700;
          text-transform:uppercase;letter-spacing:.8px}
        .pbc-total .amt{font-family:var(--font-serif,Georgia,serif);
          font-size:26px;font-weight:700;color:#B76146}
        .pbc-badge{display:inline-flex;padding:5px 12px;border-radius:99px;
          font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
        .pbc-badge.pending{background:#FFFBEB;color:#D97706}
        .pbc-badge.confirmed{background:#ECFDF5;color:#059669}
        .pbc-note{margin-top:16px;padding:14px 16px;background:#EFF6FF;
          border:1px solid #BFDBFE;border-radius:10px;font-size:13px;
          color:#1E40AF;line-height:1.6}
        .pbc-actions{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap}
        @media(max-width:600px){.pbc-actions{flex-direction:column}.pbc-actions a{width:100%}}
        .pbc-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
          flex:1;padding:14px 22px;font-size:14px;font-weight:600;border-radius:12px;
          text-decoration:none;border:1px solid transparent;transition:all .18s;white-space:nowrap}
        .pbc-btn-primary{background:linear-gradient(135deg,#C9704F,#B76146);color:#fff;
          box-shadow:0 8px 22px rgba(183,97,70,.30)}
        .pbc-btn-primary:hover{background:linear-gradient(135deg,#B76146,#9B4C34);color:#fff}
        .pbc-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
        .pbc-btn-outline:hover{border-color:#B76146;color:#B76146}
        </style>

        <div class="pbc-page">
          <div class="pbc-card">
            <div class="pbc-head">
              <div class="pbc-check"><i class="bi bi-check-lg"></i></div>
              <h1>Booking Received</h1>
              <p>Your sacred puja booking has been received. Our team will contact you shortly.</p>
              <div class="pbc-ref">Ref: <?= e($confirmed['booking_ref']) ?></div>
            </div>
            <div class="pbc-body">
              <div class="pbc-row"><span class="lbl">Puja</span>
                <span class="val"><?= e($confirmed['puja_name'] ?: 'Puja') ?></span></div>
              <?php if (!empty($confirmed['temple_name'])): ?>
              <div class="pbc-row"><span class="lbl">Temple</span>
                <span class="val"><?= e($confirmed['temple_name']) ?><?= $confirmed['temple_city'] ? ' · ' . e($confirmed['temple_city']) : '' ?></span></div>
              <?php endif; ?>
              <div class="pbc-row"><span class="lbl">Devotee</span>
                <span class="val"><?= e($confirmed['devotee_name']) ?></span></div>
              <?php if (!empty($confirmed['gotra'])): ?>
              <div class="pbc-row"><span class="lbl">Gotra</span>
                <span class="val"><?= e($confirmed['gotra']) ?></span></div>
              <?php endif; ?>
              <div class="pbc-row"><span class="lbl">Booking Date</span>
                <span class="val"><?= date('d M Y', strtotime($confirmed['booking_date'])) ?></span></div>
              <?php if (!empty($confirmed['muhurat_time'])): ?>
              <div class="pbc-row"><span class="lbl">Muhurat</span>
                <span class="val"><?= date('h:i A', strtotime($confirmed['muhurat_time'])) ?></span></div>
              <?php endif; ?>
              <div class="pbc-row"><span class="lbl">Status</span>
                <span class="val">
                  <span class="pbc-badge <?= $confirmed['status'] === 'confirmed' ? 'confirmed' : 'pending' ?>">
                    <?= e($confirmed['status']) ?>
                  </span>
                </span></div>
              <div class="pbc-total">
                <span class="lbl">Total Amount</span>
                <span class="amt"><?= inr($confirmed['amount']) ?></span>
              </div>
              <div class="pbc-note">
                <i class="bi bi-info-circle"></i>
                You can track this booking from your account dashboard. Our team will contact you for confirmation and prasad delivery updates.
              </div>
              <div class="pbc-actions">
                <a href="<?= url('account-booking.php?id=' . (int)$confirmed['id']) ?>" class="pbc-btn pbc-btn-primary">
                  <i class="bi bi-receipt"></i> View My Booking
                </a>
                <a href="<?= url('account-bookings.php') ?>" class="pbc-btn pbc-btn-outline">
                  <i class="bi bi-list-ul"></i> My Bookings
                </a>
                <a href="<?= url('index.php') ?>" class="pbc-btn pbc-btn-outline">
                  <i class="bi bi-house"></i> Back to Home
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php
        require BASE_PATH . '/includes/footer.php';
        exit;
    }
}

/* ==================================================================
 |  DETERMINE MODE:  SELECTION  vs  BOOKING
 * ================================================================== */
$pujaId      = (int)  ($_GET['puja_id']   ?? 0);
$slug        = trim((string) ($_GET['slug'] ?? ''));
$urlTempleId = (int)  ($_GET['temple_id'] ?? 0);

$puja   = null;
$temple = null;
$mode   = 'booking';

if ($pujaId > 0 || $slug !== '') {
    try {
        if ($pujaId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM pujas WHERE id = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$pujaId]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM pujas WHERE slug = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$slug]);
        }
        $puja = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('[puja-book:load] ' . $e->getMessage());
    }
    if (!$puja) {
        flash_set('error', 'The selected puja was not found or is currently unavailable.');
        redirect('pujas.php');
    }
} else {
    $mode = 'selection';
}

if ($mode === 'booking') {
    if (!empty($puja['temple_id'])) {
        $ts = $pdo->prepare('SELECT * FROM temples WHERE id = ? AND is_active = 1 LIMIT 1');
        $ts->execute([(int) $puja['temple_id']]);
        $temple = $ts->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$temple && $urlTempleId > 0) {
        $ts = $pdo->prepare('SELECT * FROM temples WHERE id = ? AND is_active = 1 LIMIT 1');
        $ts->execute([$urlTempleId]);
        $temple = $ts->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} else {
    if ($urlTempleId > 0) {
        $ts = $pdo->prepare('SELECT * FROM temples WHERE id = ? AND is_active = 1 LIMIT 1');
        $ts->execute([$urlTempleId]);
        $temple = $ts->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$resolvedTempleId = $temple ? (int) $temple['id'] : null;

/* ==================================================================
 |  SELECTION MODE
 * ================================================================== */
if ($mode === 'selection') {

    $selectPujas = [];
    $contextLabel = 'Available Pujas';

    try {
        if ($resolvedTempleId) {
            $ps = $pdo->prepare(
                'SELECT id, name, slug, short_desc, price, badge, image_url, duration_mins
                 FROM pujas WHERE temple_id = ? AND is_active = 1
                 ORDER BY sort_order ASC, id ASC'
            );
            $ps->execute([$resolvedTempleId]);
            $selectPujas = $ps->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($selectPujas)) {
                $contextLabel = 'Pujas at ' . $temple['name'];
            }
        }

        if (empty($selectPujas)) {
            $ps = $pdo->query(
                'SELECT id, name, slug, short_desc, price, badge, image_url, duration_mins
                 FROM pujas WHERE is_active = 1
                 ORDER BY sort_order ASC, id ASC'
            );
            $selectPujas = $ps->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $contextLabel = 'Choose a Sacred Puja';
        }
    } catch (Throwable $e) {
        error_log('[puja-book:selection] ' . $e->getMessage());
        $selectPujas = [];
    }

    $pageTitle = 'Book a Puja — Divya Aastha';
    require BASE_PATH . '/includes/header.php';
    ?>

    <style>
    .pbs-page{background:#FAF6EF;padding:32px 0 60px}
    .pbs-wrap{max-width:1200px;margin:0 auto;padding:0 22px}
    @media(max-width:640px){.pbs-page{padding:20px 0 40px}.pbs-wrap{padding:0 16px}}

    .pbs-back{display:inline-flex;align-items:center;gap:6px;font-size:13px;
      font-weight:600;color:#B76146;text-decoration:none;margin-bottom:20px}
    .pbs-back:hover{color:#9B4C34}

    .pbs-head{text-align:center;margin-bottom:36px}
    .pbs-head .eyebrow{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;
      text-transform:uppercase;color:#B76146;margin-bottom:10px}
    .pbs-head h1{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
      font-size:32px;line-height:1.2;color:#351B2F;margin:0 0 10px;font-weight:600}
    @media(max-width:640px){.pbs-head h1{font-size:24px}}
    .pbs-head .line{width:70px;height:2px;margin:0 auto 12px;
      background:linear-gradient(90deg,transparent,#D4AF37,transparent)}
    .pbs-head p{color:#6B7280;font-size:14px;line-height:1.6;margin:0}

    .pbs-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
    @media(max-width:1024px){.pbs-grid{grid-template-columns:repeat(2,1fr);gap:18px}}
    @media(max-width:640px){.pbs-grid{grid-template-columns:1fr;gap:16px}}

    .pbs-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;
      overflow:hidden;display:flex;flex-direction:column;
      box-shadow:0 1px 3px rgba(53,27,47,.05);
      transition:transform .22s,box-shadow .22s,border-color .22s}
    .pbs-card:hover{transform:translateY(-4px);box-shadow:0 14px 32px rgba(53,27,47,.11);
      border-color:rgba(183,97,70,.30)}
    .pbs-media{position:relative;aspect-ratio:4/3;overflow:hidden;background:#F3F4F6}
    .pbs-media img{width:100%;height:100%;object-fit:cover;transition:transform .35s}
    .pbs-card:hover .pbs-media img{transform:scale(1.05)}
    .pbs-media .ph{position:absolute;inset:0;display:grid;place-items:center;
      background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;font-size:44px}
    .pbs-badge{position:absolute;top:12px;left:12px;padding:5px 12px;
      background:linear-gradient(135deg,#F5D76E,#D4AF37);color:#4A2C0F;
      font-size:10.5px;font-weight:800;letter-spacing:.5px;
      text-transform:uppercase;border-radius:99px;
      box-shadow:0 3px 10px rgba(0,0,0,.18);z-index:2}
    .pbs-body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:10px;flex:1}
    .pbs-name{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
      font-size:17px;line-height:1.3;color:#351B2F;margin:0;font-weight:600}
    .pbs-desc{color:#6B7280;font-size:13px;line-height:1.6;margin:0;flex:1;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .pbs-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;
      padding-top:12px;border-top:1px solid #F3EEE6;margin-top:4px}
    .pbs-price{display:flex;flex-direction:column;gap:1px}
    .pbs-price .from{font-size:10px;color:#9CA3AF;letter-spacing:.4px;
      text-transform:uppercase;font-weight:600}
    .pbs-price .amt{font-family:var(--font-serif,Georgia,serif);
      font-size:19px;font-weight:700;color:#351B2F}
    .pbs-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;
      font-size:12.5px;font-weight:600;border-radius:9px;background:#B76146;color:#fff;
      border:1px solid #B76146;text-decoration:none;white-space:nowrap;transition:all .18s}
    .pbs-btn:hover{background:#9B4C34;color:#fff}
    .pbs-btn svg{width:13px;height:13px}
    .pbs-empty{max-width:600px;margin:0 auto;text-align:center;padding:52px 30px;
      background:#fff;border-radius:18px;border:1px dashed #EFE6D9}
    .pbs-empty .ico{font-size:52px;color:#D4AF37;margin-bottom:14px;line-height:1}
    .pbs-empty h3{font-family:var(--font-serif,Georgia,serif);font-size:20px;
      color:#351B2F;margin:0 0 8px;font-weight:600}
    .pbs-empty p{color:#6B7280;font-size:13.5px;margin:0 0 20px;line-height:1.6}
    </style>

    <div class="pbs-page">
      <div class="pbs-wrap">

        <a href="<?= $temple ? url('temple.php?slug=' . urlencode($temple['slug'])) : url('index.php') ?>" class="pbs-back">
          <i class="bi bi-arrow-left"></i> Back
        </a>

        <div class="pbs-head">
          <span class="eyebrow">Select a Puja</span>
          <h1><?= e($contextLabel) ?></h1>
          <div class="line"></div>
          <p>
            <?php if ($temple && !empty($selectPujas)): ?>
              Choose a puja to book at <?= e($temple['name']) ?>.
            <?php else: ?>
              Choose a sacred puja to book online and receive divine blessings.
            <?php endif; ?>
          </p>
        </div>

        <?php if (empty($selectPujas)): ?>

          <div class="pbs-empty">
            <div class="ico">🛕</div>
            <h3>No Pujas Available</h3>
            <p>There are no pujas available for booking at the moment. Please check back soon.</p>
            <a href="<?= url('index.php') ?>" class="pbs-btn">
              <i class="bi bi-house"></i> Back to Home
            </a>
          </div>

        <?php else: ?>

          <div class="pbs-grid">
            <?php foreach ($selectPujas as $p): ?>
              <?php $pImg = pb_img_url($p['image_url']); ?>
              <article class="pbs-card">
                <div class="pbs-media">
                  <?php if ($pImg): ?>
                    <img src="<?= e($pImg) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                    <div class="ph" style="display:none"><i class="bi bi-flower1"></i></div>
                  <?php else: ?>
                    <div class="ph"><i class="bi bi-flower1"></i></div>
                  <?php endif; ?>
                  <?php if (!empty($p['badge'])): ?>
                    <span class="pbs-badge"><?= e($p['badge']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="pbs-body">
                  <h3 class="pbs-name"><?= e($p['name']) ?></h3>
                  <?php if (!empty($p['short_desc'])): ?>
                    <p class="pbs-desc"><?= e($p['short_desc']) ?></p>
                  <?php endif; ?>
                  <div class="pbs-foot">
                    <div class="pbs-price">
                      <span class="from">From</span>
                      <span class="amt"><?= inr($p['price']) ?></span>
                    </div>
                    <a href="<?= url('puja-book.php?puja_id=' . (int)$p['id'] . ($resolvedTempleId ? '&temple_id=' . $resolvedTempleId : '')) ?>"
                       class="pbs-btn">
                      Book Now
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                           stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                      </svg>
                    </a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>

      </div>
    </div>

    <?php
    require BASE_PATH . '/includes/footer.php';
    exit;
}

/* ==================================================================
 |  BOOKING MODE — login required
 * ================================================================== */
if (!is_logged_in()) {
    $back = 'puja-book.php?puja_id=' . (int) $puja['id'];
    if ($resolvedTempleId) $back .= '&temple_id=' . $resolvedTempleId;

    $_SESSION['redirect_after_login'] = $back;
    flash_set('info', 'Please login to book this puja.');
    redirect('login.php');
}

$userId = current_user_id();

$us = $pdo->prepare('SELECT name, email, phone, gotra FROM users WHERE id = ? LIMIT 1');
$us->execute([$userId]);
$user = $us->fetch(PDO::FETCH_ASSOC) ?: [];

/* ==================================================================
 |  POST HANDLER
 * ================================================================== */
$errors = [];
$form = [
    'devotee_name' => (string) ($user['name']  ?? ''),
    'email'        => (string) ($user['email'] ?? ''),
    'phone'        => (string) ($user['phone'] ?? ''),
    'gotra'        => (string) ($user['gotra'] ?? ''),
    'booking_date' => '',
    'muhurat_time' => '',
    'sankalp'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $form['devotee_name'] = trim((string) post('devotee_name', ''));
    $form['email']        = trim((string) post('email', ''));
    $form['phone']        = trim((string) post('phone', ''));
    $form['gotra']        = trim((string) post('gotra', ''));
    $form['booking_date'] = trim((string) post('booking_date', ''));
    $form['muhurat_time'] = trim((string) post('muhurat_time', ''));
    $form['sankalp']      = trim((string) post('sankalp', ''));

    $postPujaId   = (int) post('puja_id', 0);
    $postTempleId = (int) post('temple_id', 0);

    if ($form['devotee_name'] === '')  $errors['devotee_name'] = 'Devotee name is required.';
    if ($form['email'] === '')         $errors['email'] = 'Email is required.';
    elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if ($form['phone'] === '')         $errors['phone'] = 'Phone number is required.';
    elseif (!preg_match('/^[\d+\-\s()]{7,20}$/', $form['phone'])) $errors['phone'] = 'Enter a valid phone.';
    if ($form['booking_date'] === '')  $errors['booking_date'] = 'Booking date is required.';
    elseif (strtotime($form['booking_date']) < strtotime(date('Y-m-d'))) $errors['booking_date'] = 'Booking date cannot be in the past.';
    if ($form['muhurat_time'] === '')  $errors['muhurat_time'] = 'Please select a muhurat time.';

    if ($postPujaId !== (int) $puja['id']) {
        $errors['_'] = 'Puja selection is invalid.';
    }

    $serverPrice = 0.0;
    if (!$errors) {
        try {
            $q = $pdo->prepare('SELECT id, price, is_active FROM pujas WHERE id = ? LIMIT 1');
            $q->execute([$postPujaId]);
            $sp = $q->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { $sp = null; }

        if (!$sp || (int) $sp['is_active'] !== 1) {
            $errors['_'] = 'The selected puja is no longer available.';
        } else {
            $serverPrice = (float) $sp['price'];
            if ($serverPrice <= 0) $errors['_'] = 'Puja price is not configured.';
        }
    }

    $insertTempleId = null;
    if (!$errors && $postTempleId > 0) {
        $tv = $pdo->prepare('SELECT id FROM temples WHERE id = ? AND is_active = 1 LIMIT 1');
        $tv->execute([$postTempleId]);
        if ($tv->fetchColumn()) $insertTempleId = (int) $postTempleId;
    }
    if (!$errors && $insertTempleId === null && $resolvedTempleId) {
        $insertTempleId = $resolvedTempleId;
    }

    if (!$errors) {
        try {
            $ref = pb_generate_ref($pdo);
            $pdo->prepare(
                'INSERT INTO puja_bookings
                   (booking_ref, user_id, puja_id, temple_id, booking_date,
                    muhurat_time, devotee_name, gotra, sankalp, amount, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?, "pending")'
            )->execute([
                $ref, $userId, (int) $puja['id'], $insertTempleId,
                $form['booking_date'], $form['muhurat_time'],
                $form['devotee_name'], $form['gotra'] ?: null,
                $form['sankalp'] ?: null, $serverPrice,
            ]);

            redirect('puja-book.php?success=1&id=' . (int) $pdo->lastInsertId());

        } catch (PDOException $e) {
            error_log('[puja-book:insert] ' . $e->getMessage());
            $errors['_'] = 'We could not complete your booking. Please try again.';
        }
    }
}

/* ==================================================================
 |  RENDER BOOKING FORM
 * ================================================================== */
$pageTitle = 'Book ' . $puja['name'] . ' — Divya Aastha';
require BASE_PATH . '/includes/header.php';

$pujaImg = pb_img_url($puja['image_url'] ?? '');

$muhurats = [
    '06:00' => '06:00 AM — Pratah Kaal',
    '07:30' => '07:30 AM — Pratah Kaal',
    '09:00' => '09:00 AM — Madhyahn',
    '10:30' => '10:30 AM — Madhyahn',
    '12:00' => '12:00 PM — Abhijit Muhurat',
    '16:00' => '04:00 PM — Sayankal',
    '17:30' => '05:30 PM — Sayankal',
    '18:30' => '06:30 PM — Sandhya',
];
?>

<style>
.pb-page{background:#FAF6EF;padding:32px 0 60px}
.pb-wrap{max-width:1200px;margin:0 auto;padding:0 22px}
@media(max-width:640px){.pb-page{padding:20px 0 40px}.pb-wrap{padding:0 16px}}

.pb-back{display:inline-flex;align-items:center;gap:6px;font-size:13px;
  font-weight:600;color:#B76146;text-decoration:none;margin-bottom:20px}
.pb-back:hover{color:#9B4C34}

.pb-head{text-align:center;margin-bottom:32px}
.pb-head .eyebrow{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;
  text-transform:uppercase;color:#B76146;margin-bottom:10px}
.pb-head h1{font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:32px;line-height:1.2;color:#351B2F;margin:0 0 10px;font-weight:600}
@media(max-width:640px){.pb-head h1{font-size:24px}}
.pb-head .line{width:70px;height:2px;margin:0 auto 12px;
  background:linear-gradient(90deg,transparent,#D4AF37,transparent)}
.pb-head p{color:#6B7280;font-size:14px;line-height:1.6;margin:0}

.pb-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:26px;align-items:flex-start}
@media(max-width:900px){.pb-grid{grid-template-columns:1fr;gap:20px}}

.pb-card{background:#fff;border:1px solid #EFE6D9;border-radius:18px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);overflow:hidden}
.pb-card__head{padding:20px 24px;border-bottom:1px solid #EFE6D9}
.pb-card__head h2{font-family:var(--font-serif,Georgia,serif);font-size:20px;
  color:#351B2F;margin:0;font-weight:600}
.pb-card__body{padding:24px}

.pb-sect{margin-bottom:22px}
.pb-sect__title{font-size:11px;font-weight:700;letter-spacing:1.5px;
  text-transform:uppercase;color:#B76146;margin-bottom:14px;
  padding-bottom:8px;border-bottom:1px solid #F3EEE6}

.pb-field{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.pb-field label{font-size:12px;font-weight:700;letter-spacing:.4px;
  text-transform:uppercase;color:#6B7280}
.pb-field label .req{color:#DC2626}
.pb-field input,.pb-field select,.pb-field textarea{
  width:100%;padding:12px 14px;border:1px solid #E5E7EB;border-radius:10px;
  font-family:inherit;font-size:14px;color:#1F2937;background:#fff;
  transition:border-color .18s,box-shadow .18s}
.pb-field input:focus,.pb-field select:focus,.pb-field textarea:focus{
  outline:none;border-color:#B76146;box-shadow:0 0 0 3px rgba(183,97,70,.12)}
.pb-field textarea{resize:vertical;min-height:90px}
.pb-field .err{color:#DC2626;font-size:12px;margin-top:2px}

.pb-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.pb-row{grid-template-columns:1fr}}

.pb-ro{display:flex;gap:14px;align-items:center;padding:14px;
  background:#FAF6EF;border:1px solid #EFE6D9;border-radius:12px}
.pb-ro__img{width:60px;height:60px;border-radius:10px;object-fit:cover;
  background:#F3E8D8;flex-shrink:0;display:grid;place-items:center;
  color:#B76146;font-size:24px;overflow:hidden}
.pb-ro__img img{width:100%;height:100%;object-fit:cover}
.pb-ro__info{flex:1;min-width:0}
.pb-ro__label{font-size:10.5px;font-weight:700;letter-spacing:1px;
  text-transform:uppercase;color:#B76146;margin-bottom:2px}
.pb-ro__title{font-family:var(--font-serif,Georgia,serif);font-size:16px;
  font-weight:600;color:#351B2F;line-height:1.35}
.pb-ro__sub{font-size:12px;color:#6B7280;margin-top:2px}

.pb-alert{padding:14px 18px;border-radius:12px;font-size:13.5px;
  margin-bottom:18px;line-height:1.55;display:flex;gap:10px;align-items:flex-start}
.pb-alert.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}

.pb-submit{display:inline-flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:16px 28px;font-size:15px;font-weight:700;border-radius:12px;
  background:linear-gradient(135deg,#C9704F,#B76146);color:#fff;
  border:1px solid #B76146;cursor:pointer;font-family:inherit;
  transition:all .18s;box-shadow:0 8px 22px rgba(183,97,70,.30);margin-top:8px}
.pb-submit:hover{background:linear-gradient(135deg,#B76146,#9B4C34);
  transform:translateY(-1px);box-shadow:0 12px 28px rgba(183,97,70,.40)}
.pb-submit:disabled{opacity:.65;cursor:not-allowed;transform:none}

.pb-summary{background:#fff;border:1px solid #EFE6D9;border-radius:18px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);overflow:hidden;position:sticky;top:90px}
@media(max-width:900px){.pb-summary{position:static}}
.pb-summary__head{background:linear-gradient(135deg,#351B2F 0%,#4A2440 100%);
  padding:18px 22px;position:relative;overflow:hidden}
.pb-summary__head::after{content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent)}
.pb-summary__head h3{font-family:var(--font-serif,Georgia,serif);color:#F7EBDD;
  margin:0;font-size:18px;font-weight:600}
.pb-summary__body{padding:22px}
.pb-sum-row{display:flex;justify-content:space-between;gap:12px;
  padding:10px 0;font-size:13.5px;border-bottom:1px dashed #F3EEE6}
.pb-sum-row:last-of-type{border-bottom:0}
.pb-sum-row .lbl{color:#6B7280;font-weight:500}
.pb-sum-row .val{color:#351B2F;font-weight:600;text-align:right;max-width:60%;
  word-break:break-word}
.pb-sum-total{background:#FAF6EF;border-radius:12px;padding:16px 20px;
  margin-top:14px;display:flex;justify-content:space-between;align-items:center}
.pb-sum-total .lbl{font-size:11.5px;color:#6B7280;font-weight:700;
  text-transform:uppercase;letter-spacing:.8px}
.pb-sum-total .amt{font-family:var(--font-serif,Georgia,serif);
  font-size:24px;font-weight:700;color:#B76146}
.pb-secure{display:flex;gap:10px;align-items:flex-start;margin-top:18px;
  padding:14px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;
  font-size:12px;line-height:1.5;color:#065F46}
.pb-secure i{font-size:16px;flex-shrink:0;margin-top:1px}
</style>

<div class="pb-page">
  <div class="pb-wrap">

    <a href="<?= $temple ? url('temple.php?slug=' . urlencode($temple['slug'])) : url('pujas.php') ?>" class="pb-back">
      <i class="bi bi-arrow-left"></i> Back
    </a>

    <div class="pb-head">
      <span class="eyebrow">Complete Your Booking</span>
      <h1>Book This Puja</h1>
      <div class="line"></div>
      <p>Fill in your details to complete the sacred puja booking.</p>
    </div>

    <div class="pb-grid">

      <div class="pb-card">
        <div class="pb-card__head"><h2>Booking Details</h2></div>
        <div class="pb-card__body">

          <?php if (!empty($errors['_'])): ?>
            <div class="pb-alert error">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <span><?= e($errors['_']) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" novalidate autocomplete="off" id="pbForm">
            <?= csrf_field() ?>
            <input type="hidden" name="puja_id"   value="<?= (int) $puja['id'] ?>">
            <input type="hidden" name="temple_id" value="<?= $resolvedTempleId ? (int)$resolvedTempleId : 0 ?>">

            <div class="pb-sect">
              <div class="pb-sect__title">Selected Puja</div>
              <div class="pb-ro">
                <div class="pb-ro__img">
                  <?php if ($pujaImg): ?>
                    <img src="<?= e($pujaImg) ?>" alt="<?= e($puja['name']) ?>"
                         onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'bi bi-flower1\'></i>';">
                  <?php else: ?>
                    <i class="bi bi-flower1"></i>
                  <?php endif; ?>
                </div>
                <div class="pb-ro__info">
                  <div class="pb-ro__label">Puja</div>
                  <div class="pb-ro__title"><?= e($puja['name']) ?></div>
                  <?php if (!empty($puja['short_desc'])): ?>
                    <div class="pb-ro__sub"><?= e(excerpt($puja['short_desc'], 80)) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <?php if ($temple): ?>
              <div class="pb-sect">
                <div class="pb-sect__title">Temple</div>
                <div class="pb-ro">
                  <div class="pb-ro__img"><i class="bi bi-building"></i></div>
                  <div class="pb-ro__info">
                    <div class="pb-ro__label">Temple</div>
                    <div class="pb-ro__title"><?= e($temple['name']) ?></div>
                    <div class="pb-ro__sub"><?= e(implode(', ', array_filter([$temple['city'], $temple['state']])) ?: 'India') ?></div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <div class="pb-sect">
              <div class="pb-sect__title">Devotee Details</div>

              <div class="pb-field">
                <label>Devotee Name <span class="req">*</span></label>
                <input type="text" name="devotee_name" required
                       value="<?= e($form['devotee_name']) ?>"
                       placeholder="Full name for sankalp">
                <?php if (!empty($errors['devotee_name'])): ?>
                  <div class="err"><?= e($errors['devotee_name']) ?></div>
                <?php endif; ?>
              </div>

              <div class="pb-row">
                <div class="pb-field">
                  <label>Email <span class="req">*</span></label>
                  <input type="email" name="email" required
                         value="<?= e($form['email']) ?>"
                         placeholder="you@example.com">
                  <?php if (!empty($errors['email'])): ?>
                    <div class="err"><?= e($errors['email']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="pb-field">
                  <label>Phone <span class="req">*</span></label>
                  <input type="tel" name="phone" required
                         value="<?= e($form['phone']) ?>"
                         placeholder="+91 ...">
                  <?php if (!empty($errors['phone'])): ?>
                    <div class="err"><?= e($errors['phone']) ?></div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="pb-field">
                <label>Gotra <small style="font-weight:500;text-transform:none;color:#9CA3AF">(optional)</small></label>
                <input type="text" name="gotra" value="<?= e($form['gotra']) ?>" placeholder="e.g. Kashyap">
              </div>
            </div>

            <div class="pb-sect">
              <div class="pb-sect__title">Schedule</div>

              <div class="pb-row">
                <div class="pb-field">
                  <label>Booking Date <span class="req">*</span></label>
                  <input type="date" name="booking_date" required
                         min="<?= date('Y-m-d') ?>"
                         value="<?= e($form['booking_date']) ?>"
                         id="pbDate">
                  <?php if (!empty($errors['booking_date'])): ?>
                    <div class="err"><?= e($errors['booking_date']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="pb-field">
                  <label>Muhurat Time <span class="req">*</span></label>
                  <select name="muhurat_time" required id="pbMuhurat">
                    <option value="">— Select muhurat —</option>
                    <?php foreach ($muhurats as $val => $label): ?>
                      <option value="<?= e($val) ?>" <?= $form['muhurat_time'] === $val ? 'selected' : '' ?>>
                        <?= e($label) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (!empty($errors['muhurat_time'])): ?>
                    <div class="err"><?= e($errors['muhurat_time']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="pb-sect" style="margin-bottom:0">
              <div class="pb-sect__title">Sankalp / Special Request</div>
              <div class="pb-field" style="margin-bottom:0">
                <textarea name="sankalp" placeholder="Share your prayer intention (optional)…"><?= e($form['sankalp']) ?></textarea>
              </div>
            </div>

            <button type="submit" class="pb-submit" id="pbSubmit">
              <i class="bi bi-shield-check"></i>
              Confirm &amp; Book Puja — <?= inr($puja['price']) ?>
            </button>
          </form>
        </div>
      </div>

      <aside class="pb-summary">
        <div class="pb-summary__head"><h3>Booking Summary</h3></div>
        <div class="pb-summary__body">
          <div class="pb-sum-row"><span class="lbl">Puja</span>
            <span class="val"><?= e($puja['name']) ?></span></div>
          <div class="pb-sum-row"><span class="lbl">Temple</span>
            <span class="val"><?= $temple ? e($temple['name']) : '—' ?></span></div>
          <div class="pb-sum-row"><span class="lbl">Devotee</span>
            <span class="val" id="sumDevotee"><?= e($form['devotee_name']) ?: '—' ?></span></div>
          <div class="pb-sum-row"><span class="lbl">Date</span>
            <span class="val" id="sumDate"><?= $form['booking_date'] ? date('d M Y', strtotime($form['booking_date'])) : '—' ?></span></div>
          <div class="pb-sum-row"><span class="lbl">Muhurat</span>
            <span class="val" id="sumMuhurat"><?= $form['muhurat_time'] ? date('h:i A', strtotime($form['muhurat_time'])) : '—' ?></span></div>
          <div class="pb-sum-total">
            <span class="lbl">Total Amount</span>
            <span class="amt"><?= inr($puja['price']) ?></span>
          </div>
          <div class="pb-secure">
            <i class="bi bi-shield-check"></i>
            <span>Amount is fetched from the official puja price — never editable by the browser.</span>
          </div>
        </div>
      </aside>

    </div>

  </div>
</div>

<script>
(function () {
  var date = document.getElementById('pbDate');
  var muh  = document.getElementById('pbMuhurat');
  var dev  = document.querySelector('input[name="devotee_name"]');
  var form = document.getElementById('pbForm');
  var btn  = document.getElementById('pbSubmit');

  var sDev = document.getElementById('sumDevotee');
  var sDate = document.getElementById('sumDate');
  var sMuh = document.getElementById('sumMuhurat');

  function fmtDate(v) {
    if (!v) return '—';
    var d = new Date(v + 'T00:00:00');
    if (isNaN(d)) return v;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }
  function fmtTime(v) {
    if (!v) return '—';
    var p = v.split(':'), h = parseInt(p[0], 10), m = p[1] || '00';
    var ap = h >= 12 ? 'PM' : 'AM', h12 = h % 12; if (h12 === 0) h12 = 12;
    return h12 + ':' + m + ' ' + ap;
  }

  if (dev) dev.addEventListener('input', function () { sDev.textContent = dev.value.trim() || '—'; });
  if (date) date.addEventListener('change', function () { sDate.textContent = fmtDate(date.value); });
  if (muh) muh.addEventListener('change', function () { sMuh.textContent = fmtTime(muh.value); });

  if (form && btn) {
    var submitted = false;
    form.addEventListener('submit', function (e) {
      if (submitted) { e.preventDefault(); return; }
      submitted = true;
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Booking…';
    });
  }
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>