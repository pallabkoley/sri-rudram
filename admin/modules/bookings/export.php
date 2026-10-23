<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Filters
 * ================================================================== */
$singleId = (int) get('id', 0);
$status   = trim((string) get('status', ''));
$validStatuses = ['pending','confirmed','completed','cancelled'];

/* Build query */
if ($singleId > 0) {
    $stmt = $pdo->prepare(
        'SELECT b.*, p.name AS puja_name, t.name AS temple_name, u.email AS user_email
         FROM puja_bookings b
         LEFT JOIN pujas p   ON p.id = b.puja_id
         LEFT JOIN temples t ON t.id = b.temple_id
         LEFT JOIN users u   ON u.id = b.user_id
         WHERE b.id = ? LIMIT 1'
    );
    $stmt->execute([$singleId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'booking-' . ($bookings[0]['booking_ref'] ?? $singleId) . '.csv';
} elseif ($status !== '' && in_array($status, $validStatuses, true)) {
    $stmt = $pdo->prepare(
        'SELECT b.*, p.name AS puja_name, t.name AS temple_name, u.email AS user_email
         FROM puja_bookings b
         LEFT JOIN pujas p   ON p.id = b.puja_id
         LEFT JOIN temples t ON t.id = b.temple_id
         LEFT JOIN users u   ON u.id = b.user_id
         WHERE b.status = ?
         ORDER BY b.created_at DESC'
    );
    $stmt->execute([$status]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'bookings-' . $status . '-' . date('Y-m-d') . '.csv';
} else {
    $bookings = $pdo->query(
        'SELECT b.*, p.name AS puja_name, t.name AS temple_name, u.email AS user_email
         FROM puja_bookings b
         LEFT JOIN pujas p   ON p.id = b.puja_id
         LEFT JOIN temples t ON t.id = b.temple_id
         LEFT JOIN users u   ON u.id = b.user_id
         ORDER BY b.created_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'bookings-' . date('Y-m-d') . '.csv';
}

/* CSV Headers */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

/* Header row */
fputcsv($out, [
    'Booking Ref',
    'Booked On',
    'Status',
    'Puja',
    'Temple',
    'Booking Date',
    'Muhurat Time',
    'Devotee Name',
    'Gotra',
    'Sankalp',
    'User Email',
    'Amount',
    'Video URL',
    'Prasad Tracking',
]);

/* Data rows */
foreach ($bookings as $b) {
    fputcsv($out, [
        $b['booking_ref']      ?? '',
        $b['created_at']       ?? '',
        $b['status']           ?? '',
        $b['puja_name']        ?? '',
        $b['temple_name']      ?? '',
        $b['booking_date']     ?? '',
        $b['muhurat_time']     ?? '',
        $b['devotee_name']     ?? '',
        $b['gotra']            ?? '',
        $b['sankalp']          ?? '',
        $b['user_email']       ?? '',
        number_format((float) ($b['amount'] ?? 0), 2, '.', ''),
        $b['video_url']        ?? '',
        $b['prasad_tracking']  ?? '',
    ]);
}

fclose($out);
exit;