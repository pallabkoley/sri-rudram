<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Filter
 * ================================================================== */
$status = trim((string) get('status', ''));

if ($status === 'active' || $status === 'unsubscribed') {
    $stmt = $pdo->prepare(
        'SELECT id, email, status, subscribed_at FROM subscribers
         WHERE status = ? ORDER BY subscribed_at DESC'
    );
    $stmt->execute([$status]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'subscribers-' . $status . '-' . date('Y-m-d') . '.csv';
} else {
    $rows = $pdo->query(
        'SELECT id, email, status, subscribed_at FROM subscribers
         ORDER BY subscribed_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'subscribers-' . date('Y-m-d') . '.csv';
}

/* ==================================================================
 |  Send CSV headers
 * ================================================================== */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

/* UTF-8 BOM for Excel */
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

/* Header row */
fputcsv($out, [
    'ID',
    'Email',
    'Status',
    'Subscribed At',
]);

/* Data rows */
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id']            ?? '',
        $r['email']         ?? '',
        $r['status']        ?? '',
        $r['subscribed_at'] ?? '',
    ]);
}

fclose($out);
exit;