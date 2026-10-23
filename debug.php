<?php
// Show EVERYTHING — no frameworks, no includes
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

echo "PHP Version : " . PHP_VERSION . "\n";
echo "Doc Root    : " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script      : " . __FILE__ . "\n";
echo "Dir         : " . __DIR__ . "\n\n";

echo "=== FILE CHECK ===\n";
$files = [
    'config/config.php',
    'config/db.php',
    'includes/functions.php',
    'includes/db-queries.php',
    'includes/header.php',
    'includes/footer.php',
    'index.php',
    '.htaccess',
];

foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    printf("%-35s %s\n", $f, file_exists($path) ? '✅ (' . filesize($path) . ' bytes)' : '❌ MISSING');
}

echo "\n=== .htaccess content ===\n";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo file_get_contents(__DIR__ . '/.htaccess') . "\n";
} else {
    echo "(no .htaccess)\n";
}

echo "\n=== PHP INI ===\n";
printf("display_errors   : %s\n", ini_get('display_errors'));
printf("error_reporting  : %s\n", ini_get('error_reporting'));
printf("memory_limit     : %s\n", ini_get('memory_limit'));
printf("max_execution    : %s\n", ini_get('max_execution_time'));

echo "\n=== ATTEMPT CONFIG LOAD ===\n";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ config.php loaded\n";
    echo "✅ db() exists: " . (function_exists('db') ? 'yes' : 'NO') . "\n";
    echo "✅ get_temples_light() exists: " . (function_exists('get_temples_light') ? 'yes' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "❌ FATAL: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}