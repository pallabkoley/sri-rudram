<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  BHET IMAGE HELPER
 * ================================================================== */
if (!function_exists('bhet_front_img_url')) {
    function bhet_front_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;
        $stored = ltrim($stored, '/');
        if (strpos($stored, 'assets/img/') === 0) {
            $stored = substr($stored, strlen('assets/img/'));
        }
        $marker = 'assets/img/';
        $pos = strpos($stored, $marker);
        if ($pos !== false) {
            $stored = substr($stored, $pos + strlen($marker));
        }
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}

if (!function_exists('bhet_front_img')) {
    function bhet_front_img(?string $stored, string $alt = '', string $class = ''): string {
        $src = bhet_front_img_url($stored);
        if ($src === '') {
            return '<div class="' . htmlspecialchars($class ?: 'ph', ENT_QUOTES) . '" '
                 . 'style="background:#F3E8D8;display:grid;place-items:center;'
                 . 'color:#9A8B7C;width:100%;height:100%;font-size:48px">'
                 . '<i class="bi bi-image"></i></div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" '
             . 'alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" '
             . ($class !== '' ? 'class="' . htmlspecialchars($class, ENT_QUOTES) . '" ' : '')
             . 'loading="lazy">';
    }
}

/* ==================================================================
 |  Booking form handler
 * ================================================================== */
$bookingSuccess = false;
$bookingError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'bhet_book') {
    csrf_verify(post('_csrf', ''));

    $bhetId      = (int) post('bhet_id', 0);
    $devoteeName = trim((string) post('devotee_name', ''));
    $gotra       = trim((string) post('gotra', ''));
    $phone       = trim((string) post('phone', ''));
    $sankalp     = trim((string) post('sankalp', ''));
    $amount      = (float) post('amount', 0);

    if ($bhetId <= 0 || $devoteeName === '' || $amount <= 0) {
        $bookingError = 'Please fill all required fields correctly.';
    } else {
        try {
            $ref = 'BHET-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Ymd');

            $hasTable = false;
            try {
                $hasTable = (bool) $pdo->query("SHOW TABLES LIKE 'bhet_bookings'")->fetchColumn();
            } catch (Throwable $e) { $hasTable = false; }

            if ($hasTable) {
                $userId = function_exists('current_user_id') ? current_user_id() : null;
                $pdo->prepare(
                    'INSERT INTO bhet_bookings
                       (booking_ref, user_id, bhet_id, devotee_name, gotra,
                        phone, sankalp, amount, status)
                     VALUES (?,?,?,?,?,?,?,?, "pending")'
                )->execute([
                    $ref, $userId, $bhetId, $devoteeName,
                    $gotra ?: null, $phone ?: null, $sankalp ?: null, $amount,
                ]);
            }

            $bookingSuccess = true;

        } catch (Throwable $e) {
            error_log('[bhet-detail:book] ' . $e->getMessage());
            $bookingError = 'Booking failed. Please try again.';
        }
    }
}

/* ==================================================================
 |  Load bhet by slug
 * ================================================================== */
$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug === '') {
    flash_set('error', 'Invalid bhet.');
    redirect('bhet.php');
}

try {
    $stmt = $pdo->prepare(
        'SELECT b.*,
                t.name AS type_name, t.slug AS type_slug,
                tp.name AS temple_name, tp.city AS temple_city,
                tp.state AS temple_state, tp.slug AS temple_slug
         FROM bhets b
         LEFT JOIN bhet_types t  ON t.id  = b.bhet_type_id
         LEFT JOIN temples    tp ON tp.id = b.temple_id
         WHERE b.slug = ? AND b.is_active = 1
         LIMIT 1'
    );
    $stmt->execute([$slug]);
    $bhet = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[bhet-detail] ' . $e->getMessage());
    $bhet = null;
}

if (!$bhet) {
    http_response_code(404);
    flash_set('error', 'Bhet not found.');
    redirect('bhet.php');
}

$pageTitle = $bhet['name'] . ' — Offer Bhet Online';
$pageDesc  = $bhet['short_desc'] ?? 'Offer sacred bhet and daan at this revered temple.';

require BASE_PATH . '/includes/header.php';

$heroImg = bhet_front_img_url($bhet['image_url']);
$heroStyle = $heroImg !== ''
    ? "background-image:url('" . htmlspecialchars($heroImg, ENT_QUOTES) . "');"
    : '';
?>

<style>
/* ═══════════ HERO ═══════════ */
.bhd-hero{
  position:relative;overflow:hidden;background-color:#2B1220;
  background-size:cover;background-position:center;background-repeat:no-repeat;
  min-height:280px;display:flex;align-items:center;
}
.bhd-hero__overlay{
  position:absolute;inset:0;pointer-events:none;
  background:
    linear-gradient(90deg,
      rgba(43,18,32,.94) 0%,rgba(43,18,32,.82) 35%,
      rgba(43,18,32,.55) 65%,rgba(43,18,32,.30) 100%),
    radial-gradient(circle at 20% 60%,rgba(183,97,70,.20) 0%,transparent 55%);
}
.bhd-hero__gold{
  position:absolute;left:0;right:0;bottom:0;height:2px;pointer-events:none;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.95),transparent);
}
.bhd-hero__inner{
  position:relative;z-index:2;width:100%;
  padding-top:64px;padding-bottom:64px;
}
.bhd-hero .breadcrumb{
  margin:0;color:rgba(247,235,221,.85);font-size:14px;letter-spacing:.3px;
}
.bhd-hero .breadcrumb a{color:rgba(247,235,221,.9);text-decoration:none;transition:color .15s}
.bhd-hero .breadcrumb a:hover{color:#D4AF37}
.bhd-hero .breadcrumb .sep{color:rgba(247,235,221,.45);margin:0 10px}
.bhd-hero .breadcrumb .current{color:#F7EBDD;font-weight:600}
@media(max-width:768px){
  .bhd-hero{min-height:200px;background-position:center 30%}
  .bhd-hero__inner{padding-top:44px;padding-bottom:44px}
  .bhd-hero__overlay{
    background:
      linear-gradient(180deg,rgba(43,18,32,.90) 0%,rgba(43,18,32,.78) 100%),
      radial-gradient(circle at 50% 100%,rgba(183,97,70,.18) 0%,transparent 60%);
  }
  .bhd-hero .breadcrumb{font-size:13px}
}

/* ═══════════ BODY ═══════════ */
.bhd-wrap{padding:56px 0 80px;background:#FAF6EF}
.bhd-container{max-width:1140px;margin:0 auto;padding:0 22px}
.bhd-grid{
  display:grid;grid-template-columns:1.05fr 1fr;gap:48px;align-items:start;
}
@media(max-width:900px){
  .bhd-grid{grid-template-columns:1fr;gap:32px}
  .bhd-wrap{padding:36px 0 56px}
}
.bhd-media{
  position:relative;border-radius:20px;overflow:hidden;
  background:#F3F4F6;aspect-ratio:1/1;
  border:1px solid #EFE6D9;box-shadow:0 4px 24px rgba(53,27,47,.08);
}
.bhd-media img{width:100%;height:100%;object-fit:cover;display:block}
.bhd-media__badge{
  position:absolute;top:16px;left:16px;padding:6px 14px;
  background:linear-gradient(135deg,#F5D76E,#D4AF37);color:#4A2C0F;
  font-size:11px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;
  border-radius:99px;box-shadow:0 3px 10px rgba(0,0,0,.18);
}
.bhd-info{padding-top:8px}
.bhd-temple{
  display:inline-flex;align-items:center;gap:6px;
  font-size:11px;font-weight:700;letter-spacing:1.3px;
  text-transform:uppercase;color:#B76146;margin-bottom:12px;
}
.bhd-info h1{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:36px;line-height:1.18;color:#351B2F;margin:0 0 18px;font-weight:600;
}
@media(max-width:768px){.bhd-info h1{font-size:26px}}
.bhd-meta{
  display:flex;flex-wrap:wrap;gap:18px;margin-bottom:22px;
  padding-bottom:22px;border-bottom:1px solid #EFE6D9;
}
.bhd-meta__item{
  display:inline-flex;align-items:center;gap:6px;
  font-size:13px;color:#6B7280;
}
.bhd-meta__item b{color:#351B2F;font-weight:600}
.bhd-price{
  display:flex;align-items:baseline;gap:12px;margin-bottom:22px;
}
.bhd-price .amt{
  font-size:32px;font-weight:700;color:#351B2F;
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
}
.bhd-price .note{color:#6B7280;font-size:13px}
.bhd-desc{color:#4B5563;font-size:14.5px;line-height:1.75;margin:0 0 24px}
.bhd-desc h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:18px;color:#351B2F;margin:20px 0 10px;font-weight:600;
}
.bhd-included{
  list-style:none;padding:0;margin:0 0 28px;
  display:flex;flex-direction:column;gap:10px;
}
.bhd-included li{
  display:flex;align-items:flex-start;gap:10px;
  font-size:13.5px;color:#4B5563;line-height:1.55;
}
.bhd-included li::before{
  content:'✓';flex-shrink:0;
  width:20px;height:20px;border-radius:50%;
  background:#ECFDF5;color:#059669;
  display:grid;place-items:center;
  font-size:12px;font-weight:800;margin-top:1px;
}
.bhd-actions{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px}
.bhd-btn-primary{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 28px;font-size:14px;font-weight:600;
  background:#B76146;color:#fff;border-radius:12px;
  text-decoration:none;border:1px solid #B76146;
  transition:background .18s,transform .18s;
}
.bhd-btn-primary:hover{background:#9B4C34;color:#fff;transform:translateY(-1px)}
.bhd-btn-primary svg{width:16px;height:16px}
.bhd-btn-secondary{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 24px;font-size:14px;font-weight:600;
  background:#fff;color:#351B2F;border-radius:12px;
  text-decoration:none;border:1px solid #E5E7EB;
  transition:all .18s;
}
.bhd-btn-secondary:hover{border-color:#B76146;color:#B76146}
.bhd-trust{
  display:flex;flex-wrap:wrap;gap:18px;
  padding-top:20px;border-top:1px solid #EFE6D9;
}
.bhd-trust span{
  display:inline-flex;align-items:center;gap:6px;
  font-size:12.5px;color:#6B7280;font-weight:500;
}
.bhd-trust svg{width:15px;height:15px;color:#B76146}

/* ═══════════ BOOKING FORM ═══════════ */
.bhd-book{
  margin-top:56px;background:#fff;border-radius:20px;
  border:1px solid #EFE6D9;padding:36px 40px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);
}
@media(max-width:640px){.bhd-book{padding:24px 20px;margin-top:36px}}
.bhd-book h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:26px;color:#351B2F;margin:0 0 8px;font-weight:600;
}
.bhd-book > p{color:#6B7280;font-size:14px;margin:0 0 26px;line-height:1.6}
.bhd-form{display:grid;grid-template-columns:1fr 1fr;gap:18px}
@media(max-width:640px){.bhd-form{grid-template-columns:1fr}}
.bhd-form .full{grid-column:1 / -1}
.bhd-form label{
  display:block;font-size:12.5px;font-weight:600;
  color:#351B2F;margin-bottom:7px;letter-spacing:.2px;
}
.bhd-form input,
.bhd-form textarea{
  width:100%;padding:12px 14px;font-size:14px;
  border:1px solid #E5E7EB;border-radius:10px;
  background:#fff;color:#1F2937;font-family:inherit;
  transition:border-color .18s,box-shadow .18s;
}
.bhd-form input:focus,
.bhd-form textarea:focus{
  outline:none;border-color:#B76146;
  box-shadow:0 0 0 3px rgba(183,97,70,.12);
}
.bhd-form textarea{resize:vertical;min-height:90px}
.bhd-form button{
  grid-column:1 / -1;margin-top:8px;
  padding:15px 32px;font-size:14.5px;font-weight:600;
  background:#B76146;color:#fff;border:1px solid #B76146;
  border-radius:12px;cursor:pointer;font-family:inherit;
  transition:background .18s,transform .18s;
}
.bhd-form button:hover{background:#9B4C34;transform:translateY(-1px)}
.bhd-alert{
  padding:16px 20px;border-radius:12px;margin-bottom:24px;
  font-size:14px;line-height:1.55;display:flex;align-items:flex-start;gap:10px;
}
.bhd-alert.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.bhd-alert.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.bhd-login-note{
  padding:24px;background:#FFF7E0;border:1px solid #F5D76E;
  border-radius:12px;text-align:center;
  color:#92700C;font-size:14px;line-height:1.55;
}
.bhd-login-note a{color:#B76146;font-weight:700;text-decoration:none}
.bhd-login-note a:hover{text-decoration:underline}
</style>

<!-- HERO -->
<section class="bhd-hero" style="<?= $heroStyle ?>">
  <div class="bhd-hero__overlay"></div>
  <div class="bhd-hero__gold"></div>
  <div class="container bhd-hero__inner">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('bhet.php') ?>">Bhet</a>
      <span class="sep">›</span>
      <span class="current"><?= e($bhet['name']) ?></span>
    </nav>
  </div>
</section>

<!-- BODY -->
<section class="bhd-wrap">
  <div class="bhd-container">

    <div class="bhd-grid">

      <div class="bhd-media">
        <?= bhet_front_img($bhet['image_url'], $bhet['name']) ?>
        <?php if (!empty($bhet['is_featured'])): ?>
          <span class="bhd-media__badge">★ Featured Seva</span>
        <?php endif; ?>
      </div>

      <div class="bhd-info">

        <?php if (!empty($bhet['temple_name'])): ?>
          <div class="bhd-temple">🛕 <?= e($bhet['temple_name']) ?></div>
        <?php endif; ?>

        <h1><?= e($bhet['name']) ?></h1>

        <div class="bhd-meta">
          <?php if (!empty($bhet['type_name'])): ?>
            <span class="bhd-meta__item"><b><?= e($bhet['type_name']) ?></b></span>
          <?php endif; ?>
          <?php if (!empty($bhet['temple_city'])): ?>
            <span class="bhd-meta__item">
              📍 <?= e($bhet['temple_city']) ?><?= !empty($bhet['temple_state']) ? ', ' . e($bhet['temple_state']) : '' ?>
            </span>
          <?php endif; ?>
        </div>

        <div class="bhd-price">
          <span class="amt"><?= inr($bhet['price']) ?></span>
          <span class="note">includes temple acknowledgment &amp; prasad</span>
        </div>

        <div class="bhd-desc">
          <?php if (!empty($bhet['short_desc'])): ?>
            <p><?= e($bhet['short_desc']) ?></p>
          <?php endif; ?>

          <?php if (!empty($bhet['description'])): ?>
            <h3>About this Seva</h3>
            <p><?= nl2br(e($bhet['description'])) ?></p>
          <?php endif; ?>

          <h3>What's Included</h3>
          <ul class="bhd-included">
            <li>Performed at <?= e($bhet['temple_name'] ?: 'the temple') ?> by verified priests</li>
            <li>Sankalp with your name &amp; gotra</li>
            <li>Temple acknowledgment receipt</li>
            <li>Photo/video proof of the seva</li>
            <li>Divine blessings delivered to your home</li>
          </ul>
        </div>

        <div class="bhd-actions">
          <a href="#book-now" class="bhd-btn-primary">
            Offer Bhet Now
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
          </a>
          <a href="<?= url('bhet.php') ?>" class="bhd-btn-secondary">
            ← Back to All Bhets
          </a>
        </div>

        <div class="bhd-trust">
          <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2 4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6z"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
            Verified Temples
          </span>
          <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
            Transparent Process
          </span>
          <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
            Quick Confirmation
          </span>
        </div>

      </div>
    </div>

    <!-- BOOKING -->
    <div class="bhd-book" id="book-now">
      <h2>Offer This Bhet</h2>
      <p>Fill the form below. Our team will contact you to confirm the seva details and share the temple acknowledgment.</p>

      <?php if ($bookingSuccess): ?>
        <div class="bhd-alert success">
          <span>✓</span>
          <span><b>Dhanyavaad!</b> Your bhet booking request has been received. Our team will reach out shortly.</span>
        </div>
      <?php elseif ($bookingError !== ''): ?>
        <div class="bhd-alert error">
          <span>!</span>
          <span><?= e($bookingError) ?></span>
        </div>
      <?php endif; ?>

      <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
        <form method="POST" class="bhd-form" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="bhet_book">
          <input type="hidden" name="bhet_id" value="<?= (int) $bhet['id'] ?>">
          <input type="hidden" name="amount" value="<?= (float) $bhet['price'] ?>">

          <div>
            <label for="f-name">Your Name <span style="color:#DC2626">*</span></label>
            <input type="text" id="f-name" name="devotee_name" required
                   value="<?= e(function_exists('current_user_name') ? current_user_name() : '') ?>">
          </div>

          <div>
            <label for="f-gotra">Gotra (optional)</label>
            <input type="text" id="f-gotra" name="gotra" placeholder="e.g. Kashyap">
          </div>

          <div>
            <label for="f-phone">Phone (optional)</label>
            <input type="tel" id="f-phone" name="phone" placeholder="+91 …">
          </div>

          <div class="full">
            <label for="f-sankalp">Sankalp / Wish (optional)</label>
            <textarea id="f-sankalp" name="sankalp" placeholder="What do you wish to pray for?"></textarea>
          </div>

          <button type="submit">Confirm Bhet — <?= inr($bhet['price']) ?></button>
        </form>
      <?php else: ?>
        <div class="bhd-login-note">
          Please <a href="<?= url('login.php') ?>">login</a> to offer this bhet.
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>