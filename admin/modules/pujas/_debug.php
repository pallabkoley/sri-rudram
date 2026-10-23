<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<pre style='font:14px monospace;padding:20px;background:#111;color:#0f0'>";
echo "=== Pujas module debug ===\n\n";

require_once dirname(__DIR__, 2) . '/includes/auth.php';
echo "[OK] auth.php loaded\n";

echo "APP_URL:        " . (defined('APP_URL')      ? APP_URL      : '❌ NOT DEFINED') . "\n";
echo "ASSETS_PATH:    " . (defined('ASSETS_PATH')  ? ASSETS_PATH  : '❌ NOT DEFINED') . "\n";
echo "\$pdo is set:    " . (isset($pdo) ? 'yes' : '❌ NO') . "\n\n";

echo "--- Function availability ---\n";
foreach ([
    'url','asset','redirect','e','post','get','slugify','excerpt','inr',
    'flash_set','flash_get','flash_render',
    'csrf_token','csrf_field','csrf_verify',
    'is_admin','current_admin',
    'img_url','img_tag','admin_delete_image','admin_upload_image','handle_image_upload',
] as $fn) {
    echo str_pad($fn . '()', 24) . (function_exists($fn) ? '✅ exists' : '❌ MISSING') . "\n";
}

echo "\n--- Table checks ---\n";
foreach (['pujas','puja_bookings','temples'] as $t) {
    try {
        $c = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo str_pad($t, 16) . "✅ rows: $c\n";
    } catch (Throwable $ex) {
        echo str_pad($t, 16) . "❌ " . $ex->getMessage() . "\n";
    }
}

echo "\n--- Column check on pujas ---\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM pujas")->fetchAll(PDO::FETCH_COLUMN);
    echo "columns: " . implode(', ', $cols) . "\n";
} catch (Throwable $ex) {
    echo "❌ " . $ex->getMessage() . "\n";
}

echo "\n--- Test helper calls used on index.php ---\n";
try { echo "inr(1999):   " . inr(1999) . "\n"; } catch (Throwable $ex) { echo "❌ inr: " . $ex->getMessage() . "\n"; }
try { echo "url('x'):    " . url('x') . "\n"; } catch (Throwable $ex) { echo "❌ url: " . $ex->getMessage() . "\n"; }
try { echo "img_tag(NULL): " . (function_exists('img_tag') ? img_tag(null) : 'n/a') . "\n"; } catch (Throwable $ex) { echo "❌ img_tag: " . $ex->getMessage() . "\n"; }
try { echo "csrf_field(): " . (function_exists('csrf_field') ? substr(csrf_field(),0,60) . '…' : 'n/a') . "\n"; } catch (Throwable $ex) { echo "❌ csrf_field: " . $ex->getMessage() . "\n"; }

echo "\n=== END ===\n";
echo "</pre>";