<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Single order export or bulk export
 * ================================================================== */
$singleId = (int) get('id', 0);
$status   = trim((string) get('status', ''));

$validStatuses = ['pending','paid','packed','shipped','delivered','cancelled'];

/* Build query */
if ($singleId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$singleId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'order-' . ($orders[0]['order_number'] ?? $singleId) . '.csv';
} elseif ($status !== '' && in_array($status, $validStatuses, true)) {
    $stmt = $pdo->prepare(
        'SELECT o.*, u.email AS customer_email
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.status = ?
         ORDER BY o.created_at DESC'
    );
    $stmt->execute([$status]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'orders-' . $status . '-' . date('Y-m-d') . '.csv';
} else {
    $orders = $pdo->query(
        'SELECT o.*, u.email AS customer_email
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $filename = 'orders-' . date('Y-m-d') . '.csv';
}

/* ==================================================================
 |  Send CSV headers
 * ================================================================== */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

/* BOM for Excel UTF-8 */
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

/* Header row */
fputcsv($out, [
    'Order Number',
    'Date',
    'Customer Name',
    'Customer Email',
    'Phone',
    'Ship Address',
    'City',
    'State',
    'Pincode',
    'Subtotal',
    'Shipping',
    'Discount',
    'Total',
    'Coupon',
    'Payment Method',
    'Payment Ref',
    'Status',
]);

/* Data rows */
foreach ($orders as $o) {
    $shipAddress = trim(($o['ship_line1'] ?? '') . ' ' . ($o['ship_line2'] ?? ''));
    $email = $o['customer_email'] ?? '';

    fputcsv($out, [
        $o['order_number'] ?? '',
        $o['created_at'] ?? '',
        $o['ship_name'] ?? '',
        $email,
        $o['ship_phone'] ?? '',
        $shipAddress,
        $o['ship_city'] ?? '',
        $o['ship_state'] ?? '',
        $o['ship_pincode'] ?? '',
        number_format((float) ($o['subtotal'] ?? 0), 2, '.', ''),
        number_format((float) ($o['shipping'] ?? 0), 2, '.', ''),
        number_format((float) ($o['discount'] ?? 0), 2, '.', ''),
        number_format((float) ($o['total'] ?? 0), 2, '.', ''),
        $o['coupon_code'] ?? '',
        $o['payment_method'] ?? '',
        $o['payment_ref'] ?? '',
        $o['status'] ?? '',
    ]);
}

fclose($out);
exit;