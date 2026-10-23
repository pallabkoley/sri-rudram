<?php
// ============================================================
// DIVYA AASTHA - PDO Database Connection
// Creates global $pdo for legacy functions using "global $pdo"
// ============================================================

if (!defined('DIVYA_AASTHA')) {
    http_response_code(403);
    exit('Forbidden');
}

// Prevent double load
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
    return;
}

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_STRINGIFY_FETCHES  => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo '<pre style="padding:24px;background:#2B1728;color:#F7EBDD;font-family:monospace;font-size:14px">';
    echo "DATABASE CONNECTION FAILED\n\n";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n\n";
    echo "Check:\n";
    echo "  DB_HOST = " . DB_HOST . "\n";
    echo "  DB_NAME = " . DB_NAME . "\n";
    echo "  DB_USER = " . DB_USER . "\n";
    echo "  DB_PASS = " . str_repeat('*', strlen(DB_PASS)) . "\n";
    echo '</pre>';
    exit;
}

// CRITICAL: expose $pdo in global scope
$GLOBALS['pdo'] = $pdo;