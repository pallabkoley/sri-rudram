<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre style='padding:20px;background:#eee;font-family:monospace;font-size:14px'>";
echo "Loading config...\n\n";

require_once __DIR__ . '/config/config.php';

echo "✅ config loaded\n";
echo "✅ db() exists: " . (function_exists('db') ? 'YES' : 'NO') . "\n";
echo "✅ get_temples_light() exists: " . (function_exists('get_temples_light') ? 'YES' : 'NO') . "\n\n";

echo "Fetching temples...\n";
$temples = get_temples_light();
echo "✅ Got " . count($temples) . " temples\n\n";

foreach ($temples as $t) {
    echo "  - " . ($t['name'] ?? '?') . "\n";
}

echo "</pre>";