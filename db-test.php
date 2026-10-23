<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre style='padding:20px;background:#eee;font-family:monospace;font-size:14px'>";
echo "Testing database connection...\n\n";

$host = 'localhost';
$name = 'xinkpayr_divyaaastha';
$user = 'xinkpayr_divyaaastha';
$pass = 'Pallab5001@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ DATABASE CONNECTED!\n\n";
    echo "MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";
    
    echo "Tables found:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "  (no tables — run schema.sql first)\n";
    } else {
        foreach ($tables as $t) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "  - $t : $count rows\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ DATABASE FAILED\n\n";
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";