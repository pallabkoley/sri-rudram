<?php
require_once __DIR__ . '/config/config.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['_token'] ?? null)) {
    flash_set('error', 'Invalid request.');
    redirect('cart.php');
}

$cartId = cart_current_id();
$items = cart_items($cartId);

if (empty($items)) {
    flash_set('info', 'Your cart is empty.');
    redirect('shop.php');
}

$subtotal = cart_subtotal($cartId);
$orderNumber = 'DA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO orders
         (order_number, user_id, subtotal, shipping, discount, total, status,
          ship_name, ship_phone, ship_line1, ship_line2, ship_city, ship_state, ship_pincode)
         VALUES (?, ?, ?, 0, 0, ?, "pending", ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $orderNumber,
        current_user_id(),
        $subtotal,
        $subtotal,
        $_POST['ship_name'],
        $_POST['ship_phone'],
        $_POST['ship_line1'],
        $_POST['ship_line2'] ?? null,
        $_POST['ship_city'],
        $_POST['ship_state'],
        $_POST['ship_pincode'],
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, price, qty, line_total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['name'],
            $item['price'],
            $item['qty'],
            $item['price'] * $item['qty'],
        ]);
    }

    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);

    $pdo->commit();

    flash_set('success', 'Order placed successfully! Reference: ' . $orderNumber);
    redirect('account-orders.php');

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Order error: ' . $e->getMessage());
    flash_set('error', 'Something went wrong. Please try again.');
    redirect('cart.php');
}