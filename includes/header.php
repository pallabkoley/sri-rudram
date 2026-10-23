<?php
require_once BASE_PATH . '/includes/language.php';

$cartId     = cart_current_id();
$cartCount  = cart_qty($cartId);
$wishCount  = wishlist_count();
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? APP_NAME . ' — Puja Booking, Siddha Store & Dharmik Gyan') ?></title>
<meta name="description" content="<?= e($pageDesc ?? 'Book authentic Hindu pujas, receive sacred prasad at home, shop rudraksha & consecrated tools.') ?>">

<meta property="og:title" content="<?= e($pageTitle ?? APP_NAME) ?>">
<meta property="og:description" content="<?= e($pageDesc ?? '') ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e(url($currentUri)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#351B2F">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">

<!-- Main Stylesheet -->
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

<!-- Responsive Overrides (must load AFTER app.css) -->
<link rel="stylesheet" href="<?= asset('assets/css/responsive.css') ?>">

<!-- ============ Language Switcher — INLINE (fixes CSS conflicts) ============ -->
<style>

.lang-switcher{position:relative;display:inline-block;margin:0 6px;vertical-align:middle}
.lang-switcher .lang-btn{
  display:inline-flex;align-items:center;gap:6px;height:40px;padding:0 12px;
  background:transparent;border:1px solid rgba(255,255,255,.25);border-radius:8px;
  color:inherit;font:500 13px/1 inherit;cursor:pointer;transition:background .2s,border-color .2s;
}
.lang-switcher .lang-btn:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.45)}
.lang-switcher .lang-current{white-space:nowrap}

.lang-switcher .lang-menu{
  display:none !important;
  position:absolute;top:calc(100% + 8px);right:0;min-width:190px;
  background:#fff;color:#351B2F;border-radius:10px;
  box-shadow:0 12px 32px rgba(0,0,0,.22);
  padding:6px;z-index:9999;margin:0;list-style:none;
}
.lang-switcher .lang-menu.open{display:block !important}

.lang-switcher .lang-menu a{
  display:flex !important;flex-direction:row;justify-content:space-between;
  align-items:center;gap:8px;width:100%;padding:10px 12px;
  color:#351B2F;text-decoration:none;border-radius:6px;
  font-size:14px;line-height:1.4;transition:background .15s;
}
.lang-switcher .lang-menu a:hover{background:#f7f0ea}
.lang-switcher .lang-menu a.active{background:#351B2F;color:#fff}
.lang-switcher .lang-menu a small{opacity:.55;font-size:11px;font-weight:400}
.lang-switcher .lang-menu a.active small{opacity:.85;color:#fff}

@media (max-width:900px){
  .lang-switcher{margin:0 2px}
  .lang-switcher .lang-btn{padding:0 8px;height:36px}
  .lang-switcher .lang-current{display:none}
  .lang-switcher .lang-menu{position:fixed;top:64px;right:12px;left:auto;min-width:200px}
}




/* ============ Language Switcher — themed to Divya Aastha palette ============ */
:root{
  --da-maroon:#4B080C;
  --da-burgundy:#310508;
  --da-gold:#D6A83E;
  --da-cream:#F6E8CF;
  --da-ivory:#FFF9F0;
  --da-brown:#432B26;
}

.lang-switcher{position:relative;display:inline-block;margin:0 8px;vertical-align:middle}

/* Trigger button */
.lang-switcher .lang-btn{
  display:inline-flex;align-items:center;gap:6px;height:40px;padding:0 14px;
  background:rgba(255,255,255,.06);
  border:1px solid rgba(214,168,62,.45);
  border-radius:10px;
  color:var(--da-cream,#F6E8CF);
  font:500 13px/1 inherit;
  cursor:pointer;
  transition:all .2s ease;
}
.lang-switcher .lang-btn:hover{
  background:rgba(214,168,62,.15);
  border-color:var(--da-gold,#D6A83E);
}
.lang-switcher .lang-btn[aria-expanded="true"]{
  background:rgba(214,168,62,.2);
  border-color:var(--da-gold,#D6A83E);
}
.lang-switcher .lang-btn svg{color:var(--da-gold,#D6A83E)}
.lang-switcher .lang-current{white-space:nowrap;letter-spacing:.2px}

/* Dropdown panel */
.lang-switcher .lang-menu{
  display:none !important;
  position:absolute;top:calc(100% + 10px);right:0;min-width:210px;
  background:var(--da-ivory,#FFF9F0);
  color:var(--da-brown,#432B26);
  border:1px solid rgba(214,168,62,.35);
  border-radius:12px;
  box-shadow:0 16px 40px rgba(49,5,8,.28), 0 2px 6px rgba(49,5,8,.12);
  padding:6px;z-index:9999;
  margin:0;list-style:none;
  overflow:hidden;
}
.lang-switcher .lang-menu.open{display:block !important;animation:langFade .18s ease}

@keyframes langFade{
  from{opacity:0;transform:translateY(-6px)}
  to{opacity:1;transform:translateY(0)}
}

/* Items */
.lang-switcher .lang-menu a{
  display:flex !important;
  flex-direction:row;
  justify-content:space-between;
  align-items:center;
  gap:10px;
  width:100%;
  padding:11px 14px;
  color:var(--da-brown,#432B26);
  text-decoration:none;
  border-radius:8px;
  font-size:14px;
  font-weight:500;
  line-height:1.4;
  transition:background .15s, color .15s;
}
.lang-switcher .lang-menu a:hover{
  background:rgba(214,168,62,.18);
  color:var(--da-maroon,#4B080C);
}
.lang-switcher .lang-menu a.active{
  background:linear-gradient(135deg,var(--da-maroon,#4B080C) 0%,var(--da-burgundy,#310508) 100%);
  color:var(--da-cream,#F6E8CF);
  font-weight:600;
}
.lang-switcher .lang-menu a.active::before{
  content:"";
  display:inline-block;
  width:6px;height:6px;
  border-radius:50%;
  background:var(--da-gold,#D6A83E);
  margin-right:6px;
  flex-shrink:0;
}
.lang-switcher .lang-menu a small{
  opacity:.6;
  font-size:11px;
  font-weight:400;
  letter-spacing:.3px;
}
.lang-switcher .lang-menu a.active small{
  opacity:.9;
  color:var(--da-cream,#F6E8CF);
}

/* Divider */
.lang-switcher .lang-menu a + a{
  margin-top:2px;
}

/* Mobile */
@media (max-width:900px){
  .lang-switcher{margin:0 4px}
  .lang-switcher .lang-btn{padding:0 10px;height:38px}
  .lang-switcher .lang-current{display:none}
  .lang-switcher .lang-menu{
    position:fixed;
    top:70px;
    right:12px;
    left:12px;
    min-width:auto;
    max-width:320px;
    margin-left:auto;
  }
}
</style>
</style>

<script>
window.APP_URL = <?= json_encode(APP_URL) ?>;
window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
window.CURRENT_LANG = <?= json_encode(current_lang()) ?>;
</script>
</head>
<body data-csrf="<?= e(csrf_token()) ?>" data-lang="<?= e(current_lang()) ?>">

<?php require BASE_PATH . '/includes/announce.php'; ?>
<?php require BASE_PATH . '/includes/main-header.php'; ?>
<?php require BASE_PATH . '/includes/mobile-nav.php'; ?>
<?php require BASE_PATH . '/includes/search-overlay.php'; ?>
<?php require BASE_PATH . '/includes/cart-drawer.php'; ?>

<main>