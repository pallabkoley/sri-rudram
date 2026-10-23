<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  Load contact info from settings table
 * ================================================================== */
$setting = function (string $key, string $default = '') use ($pdo): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = $pdo->query('SELECT key_name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($rows) $cache = $rows;
        } catch (Throwable $e) {
            error_log('[contact:settings] ' . $e->getMessage());
        }
    }
    return $cache[$key] ?? $default;
};

$contactEmail   = $setting('contact_email',   'seva@divyaaastha.in');
$contactPhone   = $setting('contact_phone',   '+91 90000 00000');
$contactAddress = $setting('contact_address', 'Bharat · Serving worldwide');
$waNumber       = preg_replace('/\D+/', '', $setting('social_whatsapp', '919000000000'));
$socialFb       = $setting('social_facebook',  '#');
$socialIg       = $setting('social_instagram', '#');
$socialYt       = $setting('social_youtube',   '#');

/* ==================================================================
 |  Handle contact form POST
 * ================================================================== */
$formSuccess = false;
$formError   = '';
$saved = [
    'name'    => '',
    'email'   => '',
    'phone'   => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'contact_send') {
    csrf_verify(post('_csrf', ''));

    $saved['name']    = trim((string) post('name', ''));
    $saved['email']   = trim((string) post('email', ''));
    $saved['phone']   = trim((string) post('phone', ''));
    $saved['subject'] = trim((string) post('subject', ''));
    $saved['message'] = trim((string) post('message', ''));

    /* Validation */
    if ($saved['name'] === '')    $formError = 'Please enter your name.';
    elseif ($saved['email'] === '') $formError = 'Please enter your email.';
    elseif (!filter_var($saved['email'], FILTER_VALIDATE_EMAIL)) $formError = 'Please enter a valid email address.';
    elseif ($saved['message'] === '') $formError = 'Please enter your message.';
    elseif (mb_strlen($saved['message']) < 10) $formError = 'Message must be at least 10 characters.';
    else {
        /* Try to persist to a contacts table if it exists */
        $savedOk = false;
        try {
            $hasTable = (bool) $pdo->query("SHOW TABLES LIKE 'contact_messages'")->fetchColumn();
            if ($hasTable) {
                $pdo->prepare(
                    'INSERT INTO contact_messages
                       (name, email, phone, subject, message, ip_address, created_at)
                     VALUES (?,?,?,?,?,?,NOW())'
                )->execute([
                    $saved['name'],
                    $saved['email'],
                    $saved['phone'] ?: null,
                    $saved['subject'] ?: 'General Enquiry',
                    $saved['message'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                $savedOk = true;
            }
        } catch (Throwable $e) {
            error_log('[contact:save] ' . $e->getMessage());
        }

        /* Try to email (best effort, silent fail) */
        if (function_exists('mail') && $contactEmail) {
            @mail(
                $contactEmail,
                'Contact Form: ' . ($saved['subject'] ?: 'General Enquiry'),
                "Name: {$saved['name']}\nEmail: {$saved['email']}\nPhone: {$saved['phone']}\n\nMessage:\n{$saved['message']}",
                "From: no-reply@divyaaastha.xinkpayroll.co.in\r\nReply-To: {$saved['email']}"
            );
        }

        $formSuccess = true;
        $saved = ['name'=>'','email'=>'','phone'=>'','subject'=>'','message'=>''];
    }
}

/* ==================================================================
 |  FAQ data (static — matches site tone)
 * ================================================================== */
$faqs = [
    [
        'q' => 'How quickly do you respond to enquiries?',
        'a' => 'We typically respond to all enquiries within 24–48 hours on business days. For urgent puja booking queries, we recommend calling us directly.',
    ],
    [
        'q' => 'Can I book a puja over the phone?',
        'a' => 'Yes. You can call us and our team will help you select the puja, choose an auspicious date, and complete the booking on your behalf.',
    ],
    [
        'q' => 'Do you deliver prasad outside India?',
        'a' => 'Currently we deliver prasad within India. International shipping is planned for the near future — please reach out and we will keep you informed.',
    ],
    [
        'q' => 'How can I track my order or puja booking?',
        'a' => 'Once your order is dispatched or your puja is completed, you will receive tracking details via SMS and email. You can also check My Orders or My Bookings in your account.',
    ],
];

$pageTitle = 'Contact Us — Divya Aastha';
$pageDesc  = 'Reach out to Divya Aastha for puja bookings, temple bhet queries, prasad delivery and general support.';

require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Contact</span>
    </nav>
    <span class="eyebrow">We're Here For You</span>
    <h1>Contact Us</h1>
    <p>Questions, blessings, or feedback — our team is always happy to hear from you.</p>
  </div>
</section>

<style>
/* ============================================================
   CONTACT PAGE — scoped styles
   ============================================================ */
.ct-wrap{max-width:1180px;margin:0 auto;padding:56px 22px 80px;background:#FAF6EF}
@media(max-width:640px){.ct-wrap{padding:36px 16px 56px}}

/* Info cards row */
.ct-info{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:40px}
@media(max-width:900px){.ct-info{grid-template-columns:1fr;gap:14px}}

.ct-info-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  padding:26px 24px;box-shadow:0 1px 3px rgba(53,27,47,.05);
  display:flex;gap:16px;align-items:flex-start;
  transition:transform .22s,box-shadow .22s,border-color .22s}
.ct-info-card:hover{transform:translateY(-3px);
  box-shadow:0 12px 26px rgba(53,27,47,.08);border-color:rgba(183,97,70,.28)}
.ct-info-card__icon{width:48px;height:48px;border-radius:12px;flex-shrink:0;
  display:grid;place-items:center;font-size:22px;color:#fff}
.ct-info-card__icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.ct-info-card__icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.ct-info-card__icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.ct-info-card__label{font-size:11px;font-weight:700;letter-spacing:1.4px;
  text-transform:uppercase;color:#B76146;margin-bottom:6px}
.ct-info-card__value{font-family:var(--font-serif,Georgia,serif);font-size:16px;
  color:#351B2F;font-weight:600;line-height:1.45;word-break:break-word}
.ct-info-card__value a{color:inherit;text-decoration:none}
.ct-info-card__value a:hover{color:#B76146}
.ct-info-card__hint{font-size:12px;color:#6B7280;margin-top:6px;line-height:1.5}

/* Main grid: form + aside */
.ct-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:32px;align-items:flex-start}
@media(max-width:900px){.ct-grid{grid-template-columns:1fr;gap:24px}}

/* Form card */
.ct-card{background:#fff;border:1px solid #EFE6D9;border-radius:18px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05)}
.ct-card__head{padding:22px 26px;border-bottom:1px solid #EFE6D9}
.ct-card__head h2{font-family:var(--font-serif,Georgia,serif);font-size:22px;
  color:#351B2F;margin:0 0 6px;font-weight:600}
.ct-card__head p{color:#6B7280;font-size:13.5px;margin:0;line-height:1.6}
.ct-card__body{padding:26px}

.ct-field{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.ct-field label{font-size:12px;font-weight:700;letter-spacing:.4px;
  text-transform:uppercase;color:#6B7280}
.ct-field label .req{color:#DC2626}
.ct-field input,
.ct-field textarea{
  width:100%;padding:12px 14px;border:1px solid #E5E7EB;border-radius:10px;
  font-family:inherit;font-size:14px;color:#1F2937;background:#fff;
  transition:border-color .18s,box-shadow .18s;
}
.ct-field input:focus,
.ct-field textarea:focus{outline:none;border-color:#B76146;
  box-shadow:0 0 0 3px rgba(183,97,70,.12)}
.ct-field textarea{resize:vertical;min-height:130px}

.ct-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.ct-row{grid-template-columns:1fr}}

.ct-submit{display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:13px 28px;font-size:14px;font-weight:600;border-radius:11px;
  background:#B76146;color:#fff;border:1px solid #B76146;
  cursor:pointer;font-family:inherit;transition:all .18s;white-space:nowrap}
.ct-submit:hover{background:#9B4C34}
.ct-submit i{font-size:16px}

/* Alerts */
.ct-alert{padding:14px 18px;border-radius:12px;font-size:13.5px;
  margin-bottom:20px;line-height:1.55;display:flex;gap:10px;align-items:flex-start}
.ct-alert.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ct-alert.error  {background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}

/* Aside cards */
.ct-aside{display:flex;flex-direction:column;gap:20px}
.ct-side-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  padding:22px;box-shadow:0 1px 3px rgba(53,27,47,.05)}
.ct-side-card h3{font-family:var(--font-serif,Georgia,serif);font-size:17px;
  color:#351B2F;margin:0 0 14px;font-weight:600}
.ct-side-card p{color:#4B5563;font-size:13.5px;line-height:1.7;margin:0}

.ct-hours{list-style:none;padding:0;margin:0;font-size:13.5px;color:#4B5563}
.ct-hours li{display:flex;justify-content:space-between;padding:9px 0;
  border-bottom:1px solid #F3EEE6}
.ct-hours li:last-child{border-bottom:0}
.ct-hours li b{color:#351B2F;font-weight:600}
.ct-hours li span:last-child{color:#6B7280}

.ct-socials{display:flex;gap:10px;margin-top:6px}
.ct-socials a{width:42px;height:42px;border-radius:12px;border:1px solid #E5E7EB;
  display:grid;place-items:center;color:#351B2F;text-decoration:none;
  transition:all .18s;font-size:18px;background:#fff}
.ct-socials a:hover{border-color:#B76146;color:#B76146;transform:translateY(-2px)}

.ct-wa-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:12px 20px;font-size:13.5px;font-weight:600;border-radius:10px;
  background:#25D366;color:#fff;text-decoration:none;margin-top:14px;
  transition:background .18s}
.ct-wa-btn:hover{background:#1EA952;color:#fff}

/* FAQ */
.ct-faq{padding:56px 0 80px;background:#fff}
.ct-faq__head{text-align:center;max-width:660px;margin:0 auto 36px;padding:0 22px}
.ct-faq__head .eyebrow-sm{display:inline-block;font-size:11px;font-weight:700;
  letter-spacing:2px;text-transform:uppercase;color:#B76146;margin-bottom:12px}
.ct-faq__head h2{font-family:var(--font-serif,Georgia,serif);font-size:28px;
  color:#351B2F;margin:0 0 14px;font-weight:600}
.ct-faq__head .gold-line{width:70px;height:2px;
  background:linear-gradient(90deg,transparent,#D4AF37,transparent);
  margin:0 auto}
.ct-faq__list{max-width:820px;margin:0 auto;padding:0 22px;
  display:flex;flex-direction:column;gap:12px}
.ct-faq__item{background:#fff;border:1px solid #EFE6D9;border-radius:14px;
  overflow:hidden;transition:border-color .2s,box-shadow .2s}
.ct-faq__item.is-open{border-color:rgba(183,97,70,.35);
  box-shadow:0 4px 18px rgba(53,27,47,.06)}
.ct-faq__q{display:flex;align-items:center;justify-content:space-between;gap:16px;
  width:100%;padding:18px 22px;background:transparent;border:0;cursor:pointer;
  text-align:left;font-family:inherit;font-size:15px;font-weight:600;
  color:#351B2F;line-height:1.4}
.ct-faq__q:hover{color:#B76146}
.ct-faq__chev{flex-shrink:0;width:20px;height:20px;display:grid;place-items:center;
  color:#B76146;transition:transform .28s}
.ct-faq__item.is-open .ct-faq__chev{transform:rotate(180deg)}
.ct-faq__chev svg{width:14px;height:14px;stroke-width:2.6}
.ct-faq__a{max-height:0;overflow:hidden;transition:max-height .32s}
.ct-faq__a-inner{padding:0 22px 20px;color:#4B5563;font-size:14px;line-height:1.75}
.ct-faq__a-inner p{margin:0}
</style>

<!-- ═══════════════ CONTACT BLOCK ═══════════════ -->
<div class="ct-wrap">

  <!-- Info cards -->
  <div class="ct-info">
    <div class="ct-info-card">
      <div class="ct-info-card__icon plum"><i class="bi bi-envelope-fill"></i></div>
      <div>
        <div class="ct-info-card__label">Email Us</div>
        <div class="ct-info-card__value">
          <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
        </div>
        <div class="ct-info-card__hint">We reply within 24–48 hours</div>
      </div>
    </div>

    <div class="ct-info-card">
      <div class="ct-info-card__icon terra"><i class="bi bi-telephone-fill"></i></div>
      <div>
        <div class="ct-info-card__label">Call Us</div>
        <div class="ct-info-card__value">
          <a href="tel:<?= e(preg_replace('/\s+/', '', $contactPhone)) ?>"><?= e($contactPhone) ?></a>
        </div>
        <div class="ct-info-card__hint">Mon–Sat, 9 AM – 7 PM IST</div>
      </div>
    </div>

    <div class="ct-info-card">
      <div class="ct-info-card__icon green"><i class="bi bi-geo-alt-fill"></i></div>
      <div>
        <div class="ct-info-card__label">Visit Us</div>
        <div class="ct-info-card__value"><?= e($contactAddress) ?></div>
        <div class="ct-info-card__hint">Serving devotees worldwide</div>
      </div>
    </div>
  </div>

  <!-- Main grid -->
  <div class="ct-grid">

    <!-- Contact form -->
    <div class="ct-card">
      <div class="ct-card__head">
        <h2>Send Us a Message</h2>
        <p>Fill the form below and our team will get back to you shortly.</p>
      </div>
      <div class="ct-card__body">

        <?php if ($formSuccess): ?>
          <div class="ct-alert success">
            <i class="bi bi-check-circle-fill"></i>
            <span>
              <b>Dhanyavaad!</b> Your message has been received. Our team will
              contact you within 24–48 hours.
            </span>
          </div>
        <?php elseif ($formError !== ''): ?>
          <div class="ct-alert error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= e($formError) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="contact_send">

          <div class="ct-row">
            <div class="ct-field">
              <label for="ct-name">Your Name <span class="req">*</span></label>
              <input type="text" id="ct-name" name="name" required
                     value="<?= e($saved['name']) ?>"
                     placeholder="e.g. Pallab Koley">
            </div>

            <div class="ct-field">
              <label for="ct-email">Email Address <span class="req">*</span></label>
              <input type="email" id="ct-email" name="email" required
                     value="<?= e($saved['email']) ?>"
                     placeholder="you@example.com">
            </div>
          </div>

          <div class="ct-row">
            <div class="ct-field">
              <label for="ct-phone">Phone (optional)</label>
              <input type="tel" id="ct-phone" name="phone"
                     value="<?= e($saved['phone']) ?>"
                     placeholder="+91 90000 00000">
            </div>

            <div class="ct-field">
              <label for="ct-subject">Subject</label>
              <input type="text" id="ct-subject" name="subject"
                     value="<?= e($saved['subject']) ?>"
                     placeholder="Puja booking, order query, feedback…">
            </div>
          </div>

          <div class="ct-field">
            <label for="ct-message">Your Message <span class="req">*</span></label>
            <textarea id="ct-message" name="message" required
                      placeholder="Tell us how we can help you…"><?= e($saved['message']) ?></textarea>
          </div>

          <button type="submit" class="ct-submit">
            <i class="bi bi-send-fill"></i> Send Message
          </button>
        </form>

      </div>
    </div>

    <!-- Aside -->
    <div class="ct-aside">

      <div class="ct-side-card">
        <h3>Support Hours</h3>
        <ul class="ct-hours">
          <li><b>Monday – Friday</b><span>9:00 AM – 7:00 PM</span></li>
          <li><b>Saturday</b><span>9:00 AM – 5:00 PM</span></li>
          <li><b>Sunday</b><span>Closed</span></li>
          <li><b>Festival days</b><span>Extended hours</span></li>
        </ul>
      </div>

      <div class="ct-side-card">
        <h3>Chat With Us</h3>
        <p>Need a quick answer? Message us on WhatsApp and our team will respond during support hours.</p>
        <a class="ct-wa-btn" href="https://wa.me/<?= e($waNumber) ?>" target="_blank" rel="noopener">
          <i class="bi bi-whatsapp"></i> Message on WhatsApp
        </a>
      </div>

      <div class="ct-side-card">
        <h3>Follow Us</h3>
        <p>Stay connected for daily darshan, festival updates and new offerings.</p>
        <div class="ct-socials">
          <a href="<?= e($socialFb) ?>" target="_blank" rel="noopener" aria-label="Facebook">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="<?= e($socialIg) ?>" target="_blank" rel="noopener" aria-label="Instagram">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="<?= e($socialYt) ?>" target="_blank" rel="noopener" aria-label="YouTube">
            <i class="bi bi-youtube"></i>
          </a>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- ═══════════════ FAQ ═══════════════ -->
<section class="ct-faq">
  <div class="ct-faq__head">
    <span class="eyebrow-sm">Common Questions</span>
    <h2>Frequently Asked Questions</h2>
    <div class="gold-line"></div>
  </div>

  <div class="ct-faq__list">
    <?php foreach ($faqs as $f): ?>
      <div class="ct-faq__item">
        <button class="ct-faq__q" type="button" aria-expanded="false">
          <span><?= e($f['q']) ?></span>
          <span class="ct-faq__chev" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </span>
        </button>
        <div class="ct-faq__a">
          <div class="ct-faq__a-inner"><p><?= e($f['a']) ?></p></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<script>
(function () {
  'use strict';
  var items = document.querySelectorAll('.ct-faq__item');
  if (!items.length) return;

  items.forEach(function (item) {
    var btn  = item.querySelector('.ct-faq__q');
    var body = item.querySelector('.ct-faq__a');
    if (!btn || !body) return;

    btn.addEventListener('click', function () {
      var isOpen = item.classList.contains('is-open');

      items.forEach(function (other) {
        other.classList.remove('is-open');
        var ob = other.querySelector('.ct-faq__a');
        var obtn = other.querySelector('.ct-faq__q');
        if (ob) ob.style.maxHeight = null;
        if (obtn) obtn.setAttribute('aria-expanded', 'false');
      });

      if (!isOpen) {
        item.classList.add('is-open');
        body.style.maxHeight = body.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  window.addEventListener('resize', function () {
    items.forEach(function (item) {
      if (item.classList.contains('is-open')) {
        var body = item.querySelector('.ct-faq__a');
        if (body) body.style.maxHeight = body.scrollHeight + 'px';
      }
    });
  });
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>