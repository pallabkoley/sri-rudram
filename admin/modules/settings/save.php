<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/modules/settings/index.php');
}
csrf_verify(post('_csrf', ''));

$returnTo = (string) post('return_to', 'index');
$allowedReturns = ['index', 'email', 'payment', 'shipping'];
if (!in_array($returnTo, $allowedReturns, true)) $returnTo = 'index';

/* ==================================================================
 |  Whitelist of accepted setting keys — everything else is ignored
 * ================================================================== */
$allowed = [
    /* General */
    'announce_text', 'devotee_count', 'rating', 'site_logo',
    'social_facebook', 'social_instagram', 'social_whatsapp', 'social_youtube',

    /* Email */
    'contact_email', 'sender_email', 'reply_to_email',
    'contact_phone', 'contact_address',

    /* Payment */
    'currency', 'currency_symbol', 'gst_percent', 'cod_max_value',
    'razorpay_key_id', 'razorpay_key_secret',

    /* Shipping */
    'free_shipping_min', 'shipping_charge', 'delivery_estimate',
    'serviceable_pincodes', 'shipping_note',
    'prasad_delivery_days', 'video_delivery_days',
];

/* Checkbox keys (value = '1' or '0') */
$checkboxes = [
    'pay_upi', 'pay_cards', 'pay_netbanking', 'pay_wallets', 'pay_cod',
];

/* ==================================================================
 |  Collect new values
 * ================================================================== */
$updates = [];

/* Text/email fields (present in POST) */
foreach ($allowed as $key) {
    if (array_key_exists($key, $_POST)) {
        $updates[$key] = trim((string) $_POST[$key]);
    }
}

/* Checkboxes — even if unchecked, we store '0' to explicitly disable */
foreach ($checkboxes as $key) {
    if (array_key_exists($key, $_POST) || $returnTo === 'payment') {
        $updates[$key] = isset($_POST[$key]) ? '1' : '0';
    }
}

/* ==================================================================
 |  Save (UPSERT)
 * ================================================================== */
if (empty($updates)) {
    flash_set('error', 'No settings were submitted.');
    redirect('admin/modules/settings/' . ($returnTo === 'index' ? 'index' : $returnTo) . '.php');
}

try {
    $sql = 'INSERT INTO settings (key_name, value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()';
    $stmt = $pdo->prepare($sql);

    $count = 0;
    foreach ($updates as $key => $val) {
        /* Skip empty secret if not changed (don't overwrite with blank) */
        if ($key === 'razorpay_key_secret' && $val === '') {
            continue;
        }
        $stmt->execute([$key, $val]);
        $count++;
    }

    flash_set('success', $count . ' setting' . ($count === 1 ? '' : 's') . ' saved successfully.');
} catch (PDOException $e) {
    error_log('[settings.save] ' . $e->getMessage());
    flash_set('error', 'Could not save settings: ' . $e->getMessage());
}

/* ==================================================================
 |  Redirect back to source page
 * ================================================================== */
$url = $returnTo === 'index'
    ? 'admin/modules/settings/index.php'
    : 'admin/modules/settings/' . $returnTo . '.php';

redirect($url);