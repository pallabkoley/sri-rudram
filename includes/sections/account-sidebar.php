<?php
/* Shared sidebar + CSS for all account pages
   Usage: include this after $pageTitle, before header.php content */

if (!function_exists('ac_active')) {
    function ac_active(string $file): string {
        return basename($_SERVER['PHP_SELF']) === $file ? ' active' : '';
    }
}

/* Helper — safe image URL */
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
?>
<style>
.ac-shell{max-width:1200px;margin:0 auto;padding:36px 22px 80px;background:#FAF6EF}
.ac-layout{display:grid;grid-template-columns:240px 1fr;gap:26px}
@media(max-width:900px){.ac-layout{grid-template-columns:1fr;gap:18px}}
.ac-nav{background:#fff;border:1px solid #EFE6D9;border-radius:16px;padding:14px;height:fit-content;
  position:sticky;top:90px;box-shadow:0 1px 3px rgba(53,27,47,.05)}
@media(max-width:900px){.ac-nav{position:static;display:flex;overflow-x:auto;gap:8px;padding:10px}}
.ac-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;
  font-size:13.5px;font-weight:600;color:#4B5563;text-decoration:none;transition:all .15s;
  margin-bottom:4px}
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
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__head a{font-size:12.5px;font-weight:600;color:#B76146;text-decoration:none}
.ac-card__head a:hover{color:#9B4C34}
.ac-card__body{padding:22px}

.ac-flash{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;line-height:1.55}
.ac-flash.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ac-flash.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.ac-flash.info{background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE}

.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:11px 22px;font-size:13px;font-weight:600;border-radius:10px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ac-btn-primary:hover{background:#9B4C34}
.ac-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.ac-btn-outline:hover{border-color:#B76146;color:#B76146}

.ac-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.ac-row{grid-template-columns:1fr}}
.ac-field{display:flex;flex-direction:column;gap:6px}
.ac-field label{font-size:12px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#6B7280}
.ac-field input,.ac-field select,.ac-field textarea{
  width:100%;padding:11px 14px;border:1px solid #E5E7EB;border-radius:10px;
  font-family:inherit;font-size:14px;color:#1F2937;background:#fff;
  transition:border-color .18s,box-shadow .18s}
.ac-field input:focus,.ac-field select:focus,.ac-field textarea:focus{
  outline:none;border-color:#B76146;box-shadow:0 0 0 3px rgba(183,97,70,.12)}

.ac-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
@media(max-width:800px){.ac-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.ac-stats{grid-template-columns:1fr}}
.ac-stat{background:#fff;border:1px solid #EFE6D9;border-radius:14px;padding:18px 20px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.ac-stat__label{font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#6B7280;margin-bottom:8px}
.ac-stat__value{font-size:24px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:6px}
.ac-stat__icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;
  font-size:18px;color:#fff;flex-shrink:0}
.ac-stat__icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.ac-stat__icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.ac-stat__icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.ac-stat__icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}

.ac-list{list-style:none;padding:0;margin:0}
.ac-list li{padding:16px 22px;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;align-items:center;gap:14px}
.ac-list li:last-child{border-bottom:0}
.ac-list__main{flex:1;min-width:0}
.ac-list__title{font-weight:600;color:#351B2F;font-size:14px;line-height:1.35}
.ac-list__meta{font-size:12px;color:#6B7280;margin-top:4px;line-height:1.5}
.ac-list__right{text-align:right;flex-shrink:0}

.ac-pill{display:inline-flex;padding:4px 11px;font-size:10.5px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.ac-pill.green{background:#ECFDF5;color:#059669}
.ac-pill.amber{background:#FFFBEB;color:#D97706}
.ac-pill.red{background:#FEF2F2;color:#DC2626}
.ac-pill.blue{background:#EFF6FF;color:#2563EB}
.ac-pill.gray{background:#F3F4F6;color:#6B7280}

.ac-empty{padding:60px 24px;text-align:center;color:#6B7280}
.ac-empty .ico{font-size:48px;color:#D4AF37;margin-bottom:14px;line-height:1}
.ac-empty h4{font-family:var(--font-serif,Georgia,serif);font-size:18px;color:#351B2F;margin:0 0 8px}
.ac-empty p{font-size:13.5px;margin:0 0 20px}
</style>

<aside class="ac-nav">
  <a href="<?= url('account.php') ?>" class="<?= trim(ac_active('account.php')) ?>">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>
  <a href="<?= url('account-orders.php') ?>" class="<?= trim(ac_active('account-orders.php')) ?>">
    <i class="bi bi-box-seam"></i> My Orders
  </a>
  <a href="<?= url('account-bookings.php') ?>" class="<?= trim(ac_active('account-bookings.php')) ?>">
    <i class="bi bi-calendar-check"></i> Puja Bookings
  </a>
  <a href="<?= url('account-addresses.php') ?>" class="<?= trim(ac_active('account-addresses.php')) ?>">
    <i class="bi bi-geo-alt"></i> Addresses
  </a>
  <a href="<?= url('account-edit.php') ?>" class="<?= trim(ac_active('account-edit.php')) ?>">
    <i class="bi bi-person"></i> Edit Profile
  </a>
  <a href="<?= url('wishlist.php') ?>">
    <i class="bi bi-heart"></i> Wishlist
  </a>
  <a href="<?= url('logout.php') ?>" class="logout">
    <i class="bi bi-box-arrow-right"></i> Logout
  </a>
</aside>