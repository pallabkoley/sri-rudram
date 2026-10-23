<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre style='padding:20px;background:#eee;font-family:monospace;font-size:14px'>";
echo "STEP 1: Loading config/config.php...\n";

$configFile = __DIR__ . '/config/config.php';
echo "Path: $configFile\n";
echo "Exists: " . (file_exists($configFile) ? 'YES ✅' : 'NO ❌') . "\n\n";

if (!file_exists($configFile)) {
    echo "❌ config/config.php ফাইল নেই! আগে এটা আপলোড করুন।\n";
    exit;
}

try {
    require_once $configFile;
    echo "✅ config.php loaded successfully!\n\n";
    
    echo "Constants defined:\n";
    echo "  DB_NAME     = " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
    echo "  APP_URL     = " . (defined('APP_URL') ? APP_URL : 'NOT DEFINED') . "\n";
    echo "  APP_NAME    = " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "\n";
    echo "  BASE_PATH   = " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "\n\n";
    
    echo "Functions available:\n";
    echo "  db()                 : " . (function_exists('db') ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  e()                  : " . (function_exists('e') ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  get_temples_light()  : " . (function_exists('get_temples_light') ? 'YES ✅' : 'NO ❌') . "\n";
    
} catch (Throwable $e) {
    echo "❌ FATAL ERROR while loading config:\n\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File   : " . $e->getFile() . "\n";
    echo "Line   : " . $e->getLine() . "\n";
}
echo "</pre>";