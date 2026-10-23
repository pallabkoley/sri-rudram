<?php
require_once __DIR__ . '/config/config.php';

/* ==================================================================
 |  Helpers
 * ================================================================== */
if (!function_exists('checkout_img_url')) {
    function checkout_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;
        $stored = ltrim($stored, '/');
        if (strpos($stored, 'assets/img/') === 0) $stored = substr($stored, strlen('assets/img/'));
        $marker = 'assets/img/';
        $pos = strpos($stored, $marker);
        if ($pos !== false) $stored = substr($stored, $pos + strlen($marker));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}

if (!function_exists('checkout_img')) {
    function checkout_img(?string $stored, string $alt = ''): string {
        $src     = checkout_img_url($stored);
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES);
        if ($src === '') {
            return '<div class="ck-img-ph" role="img" aria-label="' . $safeAlt . '">'
                 .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
                 .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
                 .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
                 .     '<polyline points="21 15 16 10 5 21"/>'
                 .   '</svg>'
                 . '</div>';
        }
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . $safeAlt . '" loading="lazy" '
             . 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'grid\';">'
             . '<div class="ck-img-ph" style="display:none" role="img" aria-label="' . $safeAlt . '">'
             .   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
             .     '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>'
             .     '<circle cx="8.5" cy="8.5" r="1.5"/>'
             .     '<polyline points="21 15 16 10 5 21"/>'
             .   '</svg>'
             . '</div>';
    }
}

if (!function_exists('gen_order_number')) {
    function gen_order_number(): string {
        return 'DA-ORD-' . strtoupper(bin2hex(random_bytes(4)));
    }
}

/* ==================================================================
 |  Load cart
 * ================================================================== */
$cartId    = cart_current_id();
$items     = cart_items($cartId);
$subtotal  = (float) cart_subtotal($cartId);
$itemCount = 0;
foreach ($items as $it) { $itemCount += (int) $it['qty']; }

/* Empty cart → redirect back */
if (empty($items)) {
    flash_set('info', 'Your cart is empty. Add sacred items to continue.');
    redirect('shop.php');
}

/* Free shipping threshold */
$freeShipMin = (float) setting('free_shipping_min', 999);
$shipping    = ($subtotal >= $freeShipMin) ? 0 : 79; // Flat ₹79 if below threshold

/* ==================================================================
 |  Prefill from logged-in user
 * ================================================================== */
$prefill = [
    'name'    => '',
    'phone'   => '',
    'email'   => '',
    'line1'   => '',
    'line2'   => '',
    'city'    => '',
    'state'   => '',
    'pincode' => '',
];

if (is_logged_in()) {
    try {
        $uid = (int) current_user_id();
        $stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$uid]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $prefill['name']  = $u['name']  ?? '';
            $prefill['email'] = $u['email'] ?? '';
            $prefill['phone'] = $u['phone'] ?? '';
        }

        // Try default address
        $stmt = $pdo->prepare(
            "SELECT name, phone, line1, line2, city, state, pincode
             FROM user_addresses WHERE user_id = ?
             ORDER BY is_default DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$uid]);
        $addr = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($addr) {
            $prefill = array_merge($prefill, array_filter($addr, fn($v) => $v !== null && $v !== ''));
        }
    } catch (Throwable $e) { /* silent */ }
}

/* ==================================================================
 |  Coupon logic
 * ================================================================== */
$couponCode    = '';
$discount      = 0.0;
$couponMessage = '';
$couponError   = '';

// Restore coupon from session
if (!empty($_SESSION['checkout_coupon'])) {
    $couponCode = (string) $_SESSION['checkout_coupon'];
}

/* Handle coupon apply/remove */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_action'])) {
    if ($_POST['coupon_action'] === 'remove') {
        unset($_SESSION['checkout_coupon']);
        $couponCode = '';
        flash_set('info', 'Coupon removed.');
        redirect('checkout.php');
    }
    if ($_POST['coupon_action'] === 'apply') {
        $tryCode = strtoupper(trim((string) ($_POST['coupon_code'] ?? '')));
        if ($tryCode === '') {
            $couponError = 'Please enter a coupon code.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "SELECT * FROM coupons
                     WHERE code = ? AND is_active = 1
                       AND (valid_from IS NULL OR valid_from <= CURDATE())
                       AND (valid_until IS NULL OR valid_until >= CURDATE())
                     LIMIT 1"
                );
                $stmt->execute([$tryCode]);
                $c = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$c) {
                    $couponError = 'Invalid or expired coupon code.';
                } elseif ($subtotal < (float) $c['min_order']) {
                    $couponError = 'Minimum order of ' . inr($c['min_order']) . ' required.';
                } elseif (!empty($c['usage_limit']) && (int) $c['used_count'] >= (int) $c['usage_limit']) {
                    $couponError = 'This coupon has reached its usage limit.';
                } else {
                    $_SESSION['checkout_coupon'] = $tryCode;
                    $couponCode = $tryCode;
                    flash_set('success', 'Coupon ' . $tryCode . ' applied!');
                    redirect('checkout.php');
                }
            } catch (Throwable $e) {
                $couponError = 'Unable to verify coupon. Please try again.';
            }
        }
    }
}

/* Recalculate discount based on session coupon */
if ($couponCode !== '') {
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$couponCode]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($c && $subtotal >= (float) $c['min_order']) {
            if ($c['type'] === 'percent') {
                $discount = $subtotal * ((float) $c['value'] / 100);
                if (!empty($c['max_discount'])) {
                    $discount = min($discount, (float) $c['max_discount']);
                }
            } else {
                $discount = (float) $c['value'];
            }
            $discount = round(min($discount, $subtotal), 2);
        } else {
            // Invalid now — clear session
            unset($_SESSION['checkout_coupon']);
            $couponCode = '';
        }
    } catch (Throwable $e) { /* silent */ }
}

$total = max(0, $subtotal + $shipping - $discount);

/* ==================================================================
 |  Handle place order
 * ================================================================== */
$formErrors = [];
$formData = [
    'name'    => trim((string) ($_POST['ship_name']    ?? $prefill['name'])),
    'phone'   => trim((string) ($_POST['ship_phone']   ?? $prefill['phone'])),
    'email'   => trim((string) ($_POST['ship_email']   ?? $prefill['email'])),
    'line1'   => trim((string) ($_POST['ship_line1']   ?? $prefill['line1'])),
    'line2'   => trim((string) ($_POST['ship_line2']   ?? $prefill['line2'])),
    'city'    => trim((string) ($_POST['ship_city']    ?? $prefill['city'])),
    'state'   => trim((string) ($_POST['ship_state']   ?? $prefill['state'])),
    'pincode' => trim((string) ($_POST['ship_pincode'] ?? $prefill['pincode'])),
    'payment' => trim((string) ($_POST['payment_method'] ?? 'cod')),
    'notes'   => trim((string) ($_POST['order_notes'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    /* CSRF */
    if (!csrf_verify($_POST['_token'] ?? '')) {
        $formErrors[] = 'Session expired. Please refresh and try again.';
    }

    /* Validate */
    if ($formData['name'] === '')    $formErrors[] = 'Please enter your full name.';
    if ($formData['phone'] === '')   $formErrors[] = 'Please enter your phone number.';
    elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $formData['phone']))
                                     $formErrors[] = 'Please enter a valid phone number.';
    if ($formData['email'] === '')   $formErrors[] = 'Please enter your email address.';
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL))
                                     $formErrors[] = 'Please enter a valid email address.';
    if ($formData['line1'] === '')   $formErrors[] = 'Please enter your street address.';
    if ($formData['city'] === '')    $formErrors[] = 'Please enter your city.';
    if ($formData['state'] === '')   $formErrors[] = 'Please enter your state.';
    if ($formData['pincode'] === '') $formErrors[] = 'Please enter your pincode.';
    elseif (!preg_match('/^[0-9]{4,10}$/', $formData['pincode']))
                                     $formErrors[] = 'Please enter a valid pincode.';

    if (empty($formErrors)) {
        try {
            $pdo->beginTransaction();

            $orderNumber = gen_order_number();
            $userId      = is_logged_in() ? (int) current_user_id() : null;

            /* Insert order */
            $stmt = $pdo->prepare(
                "INSERT INTO orders
                  (order_number, user_id, subtotal, shipping, discount, total,
                   coupon_code, status, payment_method,
                   ship_name, ship_phone, ship_line1, ship_line2,
                   ship_city, ship_state, ship_pincode, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $orderNumber,
                $userId,
                $subtotal,
                $shipping,
                $discount,
                $total,
                $couponCode ?: null,
                'pending',
                $formData['payment'],
                $formData['name'],
                $formData['phone'],
                $formData['line1'],
                $formData['line2'] ?: null,
                $formData['city'],
                $formData['state'],
                $formData['pincode'],
            ]);
            $orderId = (int) $pdo->lastInsertId();

            /* Insert order items */
            $insItem = $pdo->prepare(
                "INSERT INTO order_items
                  (order_id, product_id, product_name, price, qty, line_total)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($items as $it) {
                $lineTotal = (float) $it['price'] * (int) $it['qty'];
                $insItem->execute([
                    $orderId,
                    (int) $it['product_id'],
                    $it['name'],
                    (float) $it['price'],
                    (int) $it['qty'],
                    $lineTotal,
                ]);
            }

            /* Clear cart */
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);

            /* Increment coupon usage */
            if ($couponCode !== '') {
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")
                    ->execute([$couponCode]);
            }

            $pdo->commit();

            /* Clear coupon session */
            unset($_SESSION['checkout_coupon']);

            /* Redirect to success page */
            redirect('order-success.php?order=' . urlencode($orderNumber));

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[checkout] ' . $e->getMessage());
            $formErrors[] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

$pageTitle = 'Secure Checkout — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<style>
/* ============================================================
   CHECKOUT PAGE — scoped styles (ck-* prefix)
   ============================================================ */
.ck-page{background:#FAF6EF;padding:0 0 100px;min-height:70vh}
.ck-wrap{max-width:1200px;margin:0 auto;padding:0 22px}

.ck-img-ph{
  width:100%;height:100%;display:grid;place-items:center;
  background:linear-gradient(135deg,#F7EBDD,#F4EAF0);color:#B76146;
}
.ck-img-ph svg{width:36%;height:36%;max-width:36px;max-height:36px;opacity:.55}

/* ---------- Hero ---------- */
.ck-hero{
  background:linear-gradient(135deg,#2B1220 0%,#351B2F 55%,#4A2440 100%);
  padding:44px 0 48px;position:relative;overflow:hidden;
}
.ck-hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(circle at 12% 30%,rgba(183,97,70,.28) 0%,transparent 55%),
    radial-gradient(circle at 88% 70%,rgba(212,175,55,.16) 0%,transparent 55%);
}
.ck-hero::after{
  content:'';position:absolute;left:0;right:0;bottom:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(212,175,55,.85),transparent);
}
.ck-hero .ck-wrap{position:relative;z-index:2}
.ck-breadcrumb{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  font-size:12.5px;color:rgba(247,235,221,.72);margin-bottom:18px;
}
.ck-breadcrumb a{color:rgba(247,235,221,.9);text-decoration:none}
.ck-breadcrumb a:hover{color:#D4AF37}
.ck-breadcrumb .sep{color:rgba(247,235,221,.35)}
.ck-breadcrumb .current{color:#F7EBDD;font-weight:600}
.ck-hero h1{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:38px;line-height:1.15;color:#F7EBDD;margin:0 0 8px;font-weight:600;
}
.ck-hero p{color:rgba(247,235,221,.75);font-size:14.5px;line-height:1.65;margin:0;max-width:560px}
@media(max-width:640px){
  .ck-hero{padding:32px 0 38px}
  .ck-hero h1{font-size:26px}
  .ck-hero p{font-size:13.5px}
}

/* ---------- Layout ---------- */
.ck-main{
  max-width:1200px;margin:0 auto;padding:40px 22px 0;
  display:grid;grid-template-columns:1.4fr 1fr;gap:32px;align-items:start;
}
@media(max-width:960px){.ck-main{grid-template-columns:1fr;gap:24px;padding:32px 18px 0}}

/* ---------- Form card ---------- */
.ck-card{
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  overflow:hidden;box-shadow:0 1px 3px rgba(53,27,47,.04);
  margin-bottom:22px;
}
.ck-card__head{
  display:flex;align-items:center;gap:12px;
  padding:20px 24px;border-bottom:1px solid #F3EEE6;
  background:linear-gradient(180deg,#FCF8F2,#FBF5EC);
}
.ck-card__num{
  width:32px;height:32px;border-radius:9px;
  display:grid;place-items:center;
  background:linear-gradient(135deg,#C9704F,#B76146);color:#fff;
  font-size:13px;font-weight:800;flex:0 0 auto;
  box-shadow:0 4px 10px -4px rgba(183,97,70,.7);
}
.ck-card__head h2{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;color:#351B2F;margin:0;font-weight:600;
}
.ck-card__body{padding:24px}

/* ---------- Form fields ---------- */
.ck-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:520px){.ck-grid-2{grid-template-columns:1fr}}

.ck-field{margin-bottom:16px}
.ck-field label{
  display:block;font-size:12.5px;font-weight:600;
  color:#351B2F;margin-bottom:7px;letter-spacing:.3px;
}
.ck-field label .req{color:#B76146;font-weight:700;margin-left:2px}
.ck-field input,
.ck-field select,
.ck-field textarea{
  width:100%;padding:13px 15px;
  background:#FCF8F2;border:1px solid #E5DDD2;
  border-radius:10px;outline:none;
  font-size:14px;color:#342823;
  font-family:inherit;transition:all .2s ease;
}
.ck-field textarea{min-height:88px;resize:vertical}
.ck-field input:focus,
.ck-field select:focus,
.ck-field textarea:focus{
  background:#fff;border-color:#B76146;
  box-shadow:0 0 0 4px rgba(183,97,70,.10);
}
.ck-field input::placeholder,
.ck-field textarea::placeholder{color:#A9A29B}
.ck-field .hint{font-size:11px;color:#9A8B7C;margin-top:5px}

/* ---------- Payment methods ---------- */
.ck-pay{display:flex;flex-direction:column;gap:10px}
.ck-pay__opt{
  display:flex;align-items:center;gap:14px;
  padding:15px 16px;border:1.5px solid #E5DDD2;border-radius:12px;
  background:#FCF8F2;cursor:pointer;
  transition:all .18s ease;
  position:relative;
}
.ck-pay__opt:hover{border-color:rgba(183,97,70,.42);background:#fff}
.ck-pay__opt input{position:absolute;opacity:0;pointer-events:none}
.ck-pay__opt input:checked + .ck-pay__radio{
  background:#B76146;border-color:#B76146;
}
.ck-pay__opt input:checked + .ck-pay__radio::after{opacity:1;transform:scale(1)}
.ck-pay__opt:has(input:checked){
  border-color:#B76146;background:#FFF7E0;
  box-shadow:0 0 0 3px rgba(183,97,70,.08);
}
.ck-pay__radio{
  width:20px;height:20px;border-radius:50%;
  border:2px solid #C9BFB2;flex:0 0 auto;
  display:grid;place-items:center;
  position:relative;transition:all .2s ease;
}
.ck-pay__radio::after{
  content:"";width:8px;height:8px;border-radius:50%;
  background:#fff;opacity:0;transform:scale(.4);
  transition:all .2s ease;
}
.ck-pay__body{flex:1;min-width:0}
.ck-pay__title{
  font-size:14px;font-weight:600;color:#351B2F;
  display:flex;align-items:center;gap:8px;
  margin-bottom:2px;
}
.ck-pay__title .tag{
  font-size:9.5px;font-weight:800;letter-spacing:.6px;
  text-transform:uppercase;padding:3px 8px;border-radius:99px;
  background:rgba(124,128,100,.18);color:#4F5340;
}
.ck-pay__desc{font-size:11.5px;color:#6B7280;line-height:1.5}
.ck-pay__ico{
  width:36px;height:36px;border-radius:9px;flex:0 0 auto;
  display:grid;place-items:center;
  background:#F3E8D8;color:#B76146;font-size:17px;
}

/* ---------- Errors ---------- */
.ck-alert{
  padding:14px 18px;border-radius:12px;font-size:13.5px;
  margin-bottom:20px;line-height:1.55;
  display:flex;gap:11px;align-items:flex-start;
}
.ck-alert svg{
  width:18px;height:18px;flex:0 0 auto;margin-top:2px;
  stroke:currentColor;fill:none;stroke-width:2;
  stroke-linecap:round;stroke-linejoin:round;
}
.ck-alert--error{background:rgba(183,97,70,.10);color:#8B3A22;border:1px solid rgba(183,97,70,.28)}
.ck-alert--info{background:var(--sand,#F3E8D8);color:#351B2F;border:1px solid rgba(212,175,55,.35)}
.ck-alert ul{margin:4px 0 0;padding-left:18px}
.ck-alert li{margin-bottom:3px}

/* ---------- Order summary ---------- */
.ck-summary{
  position:sticky;top:96px;
  background:#fff;border:1px solid #EFE6D9;border-radius:16px;
  overflow:hidden;box-shadow:0 8px 32px -20px rgba(53,27,47,.20);
}
.ck-summary__head{
  padding:20px 24px 16px;border-bottom:1px solid #F3EEE6;
  background:linear-gradient(180deg,#FCF8F2,#FBF5EC);
}
.ck-summary__head h3{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:19px;color:#351B2F;margin:0 0 4px;font-weight:600;
}
.ck-summary__head p{font-size:12px;color:#9A8B7C;margin:0;letter-spacing:.3px}

/* Mini item list */
.ck-summary__items{
  max-height:280px;overflow-y:auto;
  padding:14px 20px;
  border-bottom:1px solid #F3EEE6;
}
.ck-summary__items::-webkit-scrollbar{width:6px}
.ck-summary__items::-webkit-scrollbar-thumb{background:#E5DDD2;border-radius:99px}

.ck-mini{
  display:grid;grid-template-columns:52px 1fr auto;
  gap:12px;align-items:center;padding:10px 0;
}
.ck-mini + .ck-mini{border-top:1px dashed #F3EEE6}
.ck-mini__media{
  position:relative;width:52px;height:52px;border-radius:9px;
  overflow:hidden;background:#F5F1EC;border:1px solid #EFE6D9;
}
.ck-mini__media img{width:100%;height:100%;object-fit:cover;display:block}
.ck-mini__media .ck-img-ph{position:absolute;inset:0}
.ck-mini__info{min-width:0}
.ck-mini__name{
  font-size:12.5px;font-weight:600;color:#351B2F;
  line-height:1.3;margin:0 0 2px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.ck-mini__qty{font-size:11px;color:#9A8B7C}
.ck-mini__price{
  font-family:var(--font-serif,Georgia,serif);
  font-size:14px;font-weight:700;color:#351B2F;
}

/* Summary rows */
.ck-summary__body{padding:18px 22px 22px}
.ck-row{
  display:flex;justify-content:space-between;align-items:center;
  font-size:13.5px;color:#6B7280;padding:8px 0;
}
.ck-row span:last-child{color:#351B2F;font-weight:600}
.ck-row--free span:last-child{color:#4F5340;font-weight:700}
.ck-row--discount span:last-child{color:#4F5340}
.ck-divider{
  height:1px;background:linear-gradient(90deg,transparent,#E5DDD2,transparent);
  margin:12px 0;
}
.ck-total{
  display:flex;justify-content:space-between;align-items:baseline;
  padding:8px 0 20px;
}
.ck-total span:first-child{
  font-size:14px;color:#6B7280;letter-spacing:.3px;
  text-transform:uppercase;font-weight:600;
}
.ck-total span:last-child{
  font-family:var(--font-serif,'Playfair Display',Georgia,serif);
  font-size:28px;font-weight:700;color:#351B2F;
}

/* ---------- Coupon ---------- */
.ck-coupon{
  padding:14px 20px;border-top:1px solid #F3EEE6;
  border-bottom:1px solid #F3EEE6;background:#FBF5EC;
}
.ck-coupon__form{display:flex;gap:8px}
.ck-coupon__form input{
  flex:1;min-width:0;padding:11px 14px;
  background:#fff;border:1px solid #E5DDD2;border-radius:9px;
  outline:none;font-size:13px;color:#342823;
  text-transform:uppercase;letter-spacing:.5px;font-weight:600;
  font-family:inherit;transition:border-color .2s ease;
}
.ck-coupon__form input:focus{border-color:#B76146}
.ck-coupon__form input::placeholder{color:#A9A29B;font-weight:500;letter-spacing:.3px}
.ck-coupon__form button{
  padding:11px 18px;font-size:12.5px;font-weight:700;
  border-radius:9px;background:#351B2F;color:#F7EBDD;
  border:1px solid #351B2F;cursor:pointer;font-family:inherit;
  transition:all .18s ease;white-space:nowrap;
}
.ck-coupon__form button:hover{background:#4A2440}
.ck-coupon__active{
  display:flex;align-items:center;justify-content:space-between;gap:10px;
}
.ck-coupon__chip{
  display:inline-flex;align-items:center;gap:8px;
  padding:8px 14px;border-radius:9px;
  background:linear-gradient(135deg,#DCE5CD,#C8D4B5);color:#3A4A28;
  font-size:12.5px;font-weight:700;letter-spacing:.5px;
}
.ck-coupon__chip svg{
  width:14px;height:14px;stroke:currentColor;fill:none;
  stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;
}
.ck-coupon__remove{
  background:none;border:none;cursor:pointer;
  font-size:12px;color:#9A8B7C;font-weight:600;padding:6px 10px;
  border-radius:6px;transition:all .18s ease;font-family:inherit;
}
.ck-coupon__remove:hover{color:#B76146;background:rgba(183,97,70,.08)}

/* ---------- Trust ---------- */
.ck-trust{
  display:flex;flex-direction:column;gap:10px;
  padding:16px 22px 20px;background:#FAF6EF;
  border-top:1px solid #F3EEE6;
}
.ck-trust__item{
  display:flex;align-items:center;gap:10px;
  font-size:11.5px;color:#6B7280;line-height:1.4;
}
.ck-trust__item svg{
  width:15px;height:15px;flex:0 0 auto;
  stroke:#B76146;fill:none;stroke-width:1.8;
  stroke-linecap:round;stroke-linejoin:round;
}
.ck-trust__item b{color:#351B2F;font-weight:600;display:block;font-size:12px}

/* ---------- Place order button ---------- */
.ck-place{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:17px 22px;
  font-size:15px;font-weight:700;letter-spacing:.3px;
  border-radius:12px;border:1px solid #B76146;
  background:linear-gradient(135deg,#C9704F,#B76146);
  color:#fff;cursor:pointer;font-family:inherit;
  box-shadow:0 12px 28px -14px rgba(183,97,70,.95);
  transition:all .22s cubic-bezier(.22,.68,.32,1);
}
.ck-place:hover{
  background:linear-gradient(135deg,#B76146,#9B4C34);
  transform:translateY(-2px);
  box-shadow:0 18px 36px -14px rgba(183,97,70,1);
}
.ck-place:disabled{
  opacity:.6;cursor:not-allowed;transform:none;
}
.ck-place svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

.ck-note{
  display:flex;align-items:center;justify-content:center;gap:7px;
  margin-top:12px;font-size:11.5px;color:#9A8B7C;text-align:center;
}
.ck-note svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

/* ---------- Sticky mobile bar ---------- */
.ck-mobile-bar{display:none}
@media(max-width:640px){
  .ck-mobile-bar{
    display:block;position:fixed;bottom:0;left:0;right:0;z-index:900;
    padding:12px 16px calc(12px + env(safe-area-inset-bottom));
    background:rgba(53,27,47,.97);backdrop-filter:blur(10px);
    border-top:1px solid rgba(212,175,55,.35);
    box-shadow:0 -6px 20px rgba(0,0,0,.18);
  }
  .ck-mobile-bar__row{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
  }
  .ck-mobile-bar__total{display:flex;flex-direction:column;line-height:1.15}
  .ck-mobile-bar__total small{
    font-size:9.5px;color:rgba(247,235,221,.55);
    letter-spacing:.5px;text-transform:uppercase;
  }
  .ck-mobile-bar__total b{
    font-family:var(--font-serif,Georgia,serif);
    font-size:19px;font-weight:700;color:#F7EBDD;margin-top:2px;
  }
  .ck-mobile-bar__btn{
    display:inline-flex;align-items:center;gap:6px;
    padding:12px 20px;font-size:13px;font-weight:700;
    border-radius:10px;background:linear-gradient(135deg,#C9704F,#B76146);
    color:#fff;border:none;cursor:pointer;font-family:inherit;
  }
  .ck-page{padding-bottom:110px}
}

/* ---------- Animations ---------- */
.ck-card,.ck-summary{animation:ckFade .5s cubic-bezier(.22,.68,.32,1) both}
.ck-card:nth-child(2){animation-delay:.05s}
.ck-card:nth-child(3){animation-delay:.10s}
.ck-summary{animation-delay:.15s}
@keyframes ckFade{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
</style>

<div class="ck-page">

  <!-- ═══════════════ HERO ═══════════════ -->
  <section class="ck-hero">
    <div class="ck-wrap">
      <nav class="ck-breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span class="sep">›</span>
        <a href="<?= url('cart.php') ?>">Cart</a>
        <span class="sep">›</span>
        <span class="current">Checkout</span>
      </nav>
      <h1>Secure Checkout</h1>
      <p>Complete your order to receive sacred blessings at your doorstep. Your details are safe and encrypted.</p>
    </div>
  </section>

  <!-- ═══════════════ MAIN ═══════════════ -->
  <form method="post" class="ck-main" id="checkoutForm" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="place_order" value="1">
    <input type="hidden" name="payment_method" id="paymentMethodInput" value="<?= e($formData['payment']) ?>">

    <!-- ============================================================
         LEFT: FORM
         ============================================================ -->
    <div>

      <?php if (!empty($formErrors)): ?>
        <div class="ck-alert ck-alert--error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div>
            <strong>Please fix the following:</strong>
            <ul>
              <?php foreach ($formErrors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($couponError)): ?>
        <div class="ck-alert ck-alert--error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div><?= e($couponError) ?></div>
        </div>
      <?php endif; ?>

      <!-- ---------- Contact & Shipping ---------- -->
      <div class="ck-card">
        <div class="ck-card__head">
          <span class="ck-card__num">1</span>
          <h2>Contact &amp; Shipping Details</h2>
        </div>
        <div class="ck-card__body">

          <div class="ck-grid-2">
            <div class="ck-field">
              <label>Full Name <span class="req">*</span></label>
              <input type="text" name="ship_name" value="<?= e($formData['name']) ?>"
                     placeholder="Enter your full name" required>
            </div>
            <div class="ck-field">
              <label>Phone Number <span class="req">*</span></label>
              <input type="tel" name="ship_phone" value="<?= e($formData['phone']) ?>"
                     placeholder="+91 90000 00000" required>
            </div>
          </div>

          <div class="ck-field">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="ship_email" value="<?= e($formData['email']) ?>"
                   placeholder="you@example.com" required>
            <div class="hint">Order confirmation and tracking details will be sent here.</div>
          </div>

          <div class="ck-field">
            <label>Address Line 1 <span class="req">*</span></label>
            <input type="text" name="ship_line1" value="<?= e($formData['line1']) ?>"
                   placeholder="House / Flat / Building" required>
          </div>

          <div class="ck-field">
            <label>Address Line 2 <span class="hint" style="display:inline;margin-left:4px">(optional)</span></label>
            <input type="text" name="ship_line2" value="<?= e($formData['line2']) ?>"
                   placeholder="Landmark, area, locality">
          </div>

          <div class="ck-grid-2">
            <div class="ck-field">
              <label>City <span class="req">*</span></label>
              <input type="text" name="ship_city" value="<?= e($formData['city']) ?>"
                     placeholder="City" required>
            </div>
            <div class="ck-field">
              <label>State <span class="req">*</span></label>
              <input type="text" name="ship_state" value="<?= e($formData['state']) ?>"
                     placeholder="State" required>
            </div>
          </div>

          <div class="ck-field" style="margin-bottom:0">
            <label>Pincode <span class="req">*</span></label>
            <input type="text" name="ship_pincode" value="<?= e($formData['pincode']) ?>"
                   placeholder="6-digit pincode" inputmode="numeric" required>
          </div>

        </div>
      </div>

      <!-- ---------- Payment Method ---------- -->
      <div class="ck-card">
        <div class="ck-card__head">
          <span class="ck-card__num">2</span>
          <h2>Payment Method</h2>
        </div>
        <div class="ck-card__body">

          <div class="ck-pay">
            <label class="ck-pay__opt">
              <input type="radio" name="pm" value="cod"
                     <?= $formData['payment'] === 'cod' ? 'checked' : '' ?>
                     onchange="document.getElementById('paymentMethodInput').value='cod'">
              <span class="ck-pay__radio"></span>
              <span class="ck-pay__body">
                <span class="ck-pay__title">
                  Cash on Delivery
                  <span class="tag">Popular</span>
                </span>
                <span class="ck-pay__desc">Pay with cash when your order is delivered</span>
              </span>
              <span class="ck-pay__ico">💵</span>
            </label>

            <label class="ck-pay__opt">
              <input type="radio" name="pm" value="upi"
                     <?= $formData['payment'] === 'upi' ? 'checked' : '' ?>
                     onchange="document.getElementById('paymentMethodInput').value='upi'">
              <span class="ck-pay__radio"></span>
              <span class="ck-pay__body">
                <span class="ck-pay__title">UPI</span>
                <span class="ck-pay__desc">Google Pay, PhonePe, Paytm, BHIM &amp; more</span>
              </span>
              <span class="ck-pay__ico">📱</span>
            </label>

            <label class="ck-pay__opt">
              <input type="radio" name="pm" value="card"
                     <?= $formData['payment'] === 'card' ? 'checked' : '' ?>
                     onchange="document.getElementById('paymentMethodInput').value='card'">
              <span class="ck-pay__radio"></span>
              <span class="ck-pay__body">
                <span class="ck-pay__title">Credit / Debit Card</span>
                <span class="ck-pay__desc">Visa, Mastercard, RuPay, Amex</span>
              </span>
              <span class="ck-pay__ico">💳</span>
            </label>
          </div>

        </div>
      </div>

      <!-- ---------- Order Notes ---------- -->
      <div class="ck-card">
        <div class="ck-card__head">
          <span class="ck-card__num">3</span>
          <h2>Order Notes <span style="font-size:12.5px;font-weight:500;color:#9A8B7C;margin-left:6px">(optional)</span></h2>
        </div>
        <div class="ck-card__body">
          <div class="ck-field" style="margin-bottom:0">
            <textarea name="order_notes" placeholder="Any special instructions for your order or delivery?"><?= e($formData['notes']) ?></textarea>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         RIGHT: ORDER SUMMARY
         ============================================================ -->
    <aside class="ck-summary">

      <div class="ck-summary__head">
        <h3>Order Summary</h3>
        <p><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?> · All prices include GST</p>
      </div>

      <!-- Mini items list -->
      <div class="ck-summary__items">
        <?php foreach ($items as $it): ?>
          <?php $lineTotal = (float) $it['price'] * (int) $it['qty']; ?>
          <div class="ck-mini">
            <div class="ck-mini__media">
              <?= checkout_img($it['image_url'], $it['name']) ?>
            </div>
            <div class="ck-mini__info">
              <h4 class="ck-mini__name"><?= e($it['name']) ?></h4>
              <span class="ck-mini__qty">Qty <?= (int) $it['qty'] ?> × <?= inr($it['price']) ?></span>
            </div>
            <span class="ck-mini__price"><?= inr($lineTotal) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Coupon -->
      <div class="ck-coupon">
        <?php if ($couponCode !== ''): ?>
          <div class="ck-coupon__active">
            <span class="ck-coupon__chip">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <?= e($couponCode) ?> applied
            </span>
            <button type="submit" name="coupon_action" value="remove" class="ck-coupon__remove">Remove</button>
          </div>
        <?php else: ?>
          <div class="ck-coupon__form">
            <input type="text" name="coupon_code" placeholder="Enter coupon code" autocomplete="off">
            <button type="submit" name="coupon_action" value="apply">Apply</button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Summary rows -->
      <div class="ck-summary__body">

        <div class="ck-row">
          <span>Subtotal</span>
          <span><?= inr($subtotal) ?></span>
        </div>

        <div class="ck-row <?= $shipping == 0 ? 'ck-row--free' : '' ?>">
          <span>Shipping</span>
          <span>
            <?php if ($shipping == 0): ?>
              Free
            <?php else: ?>
              <?= inr($shipping) ?>
            <?php endif; ?>
          </span>
        </div>

        <?php if ($discount > 0): ?>
          <div class="ck-row ck-row--discount">
            <span>Discount (<?= e($couponCode) ?>)</span>
            <span>− <?= inr($discount) ?></span>
          </div>
        <?php endif; ?>

        <div class="ck-divider"></div>

        <div class="ck-total">
          <span>Total</span>
          <span><?= inr($total) ?></span>
        </div>

        <button type="submit" class="ck-place" id="placeOrderBtn">
          <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          Place Order · <?= inr($total) ?>
        </button>

        <div class="ck-note">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Your information is protected with 256-bit SSL encryption
        </div>

      </div>

      <!-- Trust -->
      <div class="ck-trust">
        <div class="ck-trust__item">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          <span><b>100% Secure Payments</b>UPI · Cards · Net Banking · COD</span>
        </div>
        <div class="ck-trust__item">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span><b>Fast Dispatch</b>Shipped within 24 hours</span>
        </div>
        <div class="ck-trust__item">
          <svg viewBox="0 0 24 24"><path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7a4 4 0 1 0 0-5 4 4 0 0 0 0 5z"/></svg>
          <span><b>Authentic &amp; Energized</b>Temple-sourced sacred items</span>
        </div>
      </div>

    </aside>
  </form>

</div>

<!-- ═══════════════ STICKY MOBILE BAR ═══════════════ -->
<div class="ck-mobile-bar">
  <div class="ck-mobile-bar__row">
    <div class="ck-mobile-bar__total">
      <small>Total Amount</small>
      <b><?= inr($total) ?></b>
    </div>
    <button type="button" class="ck-mobile-bar__btn" onclick="document.getElementById('checkoutForm').requestSubmit()">
      Place Order
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </button>
  </div>
</div>

<script>
/* Prevent double submission */
(function() {
  var form = document.getElementById('checkoutForm');
  var btn  = document.getElementById('placeOrderBtn');
  if (form && btn) {
    form.addEventListener('submit', function(e) {
      // Only lock if it's the place_order submit (not coupon)
      var submitter = e.submitter;
      if (submitter && submitter.name === 'coupon_action') return;
      if (submitter && submitter !== btn) return;
      if (btn.disabled) { e.preventDefault(); return; }
      btn.disabled = true;
      btn.innerHTML = '<svg viewBox="0 0 24 24" style="animation:ckSpin 1s linear infinite"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="30 60" stroke-linecap="round"/></svg> Placing Order...';
    });
  }
  // Smooth scroll to first error if any
  var alertBox = document.querySelector('.ck-alert--error');
  if (alertBox) {
    setTimeout(function() {
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 200);
  }
})();
</script>
<style>
@keyframes ckSpin { to { transform: rotate(360deg); } }
</style>

<?php require BASE_PATH . '/includes/footer.php'; ?>