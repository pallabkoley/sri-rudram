<?php
/* ==================================================================
 |  includes/handlers/cart-handler.php
 |
 |  Handles AJAX cart actions: cart_add, cart_update, cart_remove, cart_data
 |  Caller must do:
 |      if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
 |          require BASE_PATH . '/includes/handlers/cart-handler.php';
 |      }
 * ================================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
if (!isset($_POST['action'])) return;

$action = (string) $_POST['action'];
if (!in_array($action, ['cart_add','cart_update','cart_remove','cart_data'], true)) {
    return; /* Not a cart action — let other handlers run */
}

/* ==================================================================
 |  Ensure JSON output helper exists
 * ================================================================== */
if (!function_exists('json_out')) {
    function json_out(array $data, int $status = 200): void {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/* ==================================================================
 |  Ensure cart helper functions exist (inline fallbacks)
 * ================================================================== */
if (!function_exists('cart_current_id')) {
    function cart_current_id(): ?int {
        if (empty($_SESSION['cart_id'])) {
            /* Caller should create a cart row; return null to signal */
            return null;
        }
        return (int) $_SESSION['cart_id'];
    }
}

if (!function_exists('cart_qty')) {
    function cart_qty(?int $cartId): int {
        global $pdo;
        if (!$cartId) return 0;
        try {
            $s = $pdo->prepare('SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id = ?');
            $s->execute([$cartId]);
            return (int) $s->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('cart_subtotal')) {
    function cart_subtotal(?int $cartId): float {
        global $pdo;
        if (!$cartId) return 0.0;
        try {
            $s = $pdo->prepare('SELECT COALESCE(SUM(qty * price_at_add),0) FROM cart_items WHERE cart_id = ?');
            $s->execute([$cartId]);
            return (float) $s->fetchColumn();
        } catch (Throwable $e) {
            return 0.0;
        }
    }
}

if (!function_exists('cart_items')) {
    function cart_items(?int $cartId): array {
        global $pdo;
        if (!$cartId) return [];
        try {
            $s = $pdo->prepare(
                'SELECT ci.id, ci.cart_id, ci.product_id, ci.qty, ci.price_at_add,
                        p.name, p.slug, p.image_url, p.price, p.stock
                 FROM cart_items ci
                 INNER JOIN products p ON p.id = ci.product_id
                 WHERE ci.cart_id = ?
                 ORDER BY ci.added_at DESC'
            );
            $s->execute([$cartId]);
            $rows = $s->fetchAll(PDO::FETCH_ASSOC);

            /* Build image URL for each item */
            foreach ($rows as &$r) {
                $img = trim((string) ($r['image_url'] ?? ''));
                if ($img === '') {
                    $r['image_url_full'] = '';
                } elseif (preg_match('#^https?://#i', $img)) {
                    $r['image_url_full'] = $img;
                } else {
                    $img = ltrim($img, '/');
                    if (strpos($img, 'assets/img/') === 0) {
                        $img = substr($img, strlen('assets/img/'));
                    }
                    $m = 'assets/img/';
                    $pos = strpos($img, $m);
                    if ($pos !== false) $img = substr($img, $pos + strlen($m));
                    $r['image_url_full'] = 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($img, '/');
                }
                $r['line_total'] = (float) $r['qty'] * (float) $r['price_at_add'];
            }
            unset($r);
            return $rows;
        } catch (Throwable $e) {
            error_log('[cart_items] ' . $e->getMessage());
            return [];
        }
    }
}

/* ==================================================================
 |  CSRF verification — accepts both _csrf and _token for safety
 * ================================================================== */
$token = $_POST['_csrf'] ?? $_POST['_token'] ?? '';
if (!function_exists('csrf_verify')) {
    /* CSRF helper not loaded — fallback to direct session check */
    $sessionToken = $_SESSION['_csrf'] ?? '';
    if (!$token || !$sessionToken || !hash_equals($sessionToken, (string) $token)) {
        json_out(['ok' => false, 'error' => 'Session expired. Please refresh and try again.'], 419);
    }
} else {
    try {
        /* csrf_verify exits with 419 on failure; wrap to catch that */
        csrf_verify($token);
    } catch (Throwable $e) {
        json_out(['ok' => false, 'error' => 'Session expired. Please refresh and try again.'], 419);
    }
}

/* ==================================================================
 |  Get cart id — create one if it doesn't exist
 * ================================================================== */
$cartId = cart_current_id();

if (!$cartId && function_exists('cart_current_id')) {
    /* Try to create a cart row */
    try {
        $sessionId = session_id() ?: bin2hex(random_bytes(16));
        $userId    = function_exists('current_user_id') ? current_user_id() : null;

        $ins = $pdo->prepare('INSERT INTO carts (user_id, session_id) VALUES (?, ?)');
        $ins->execute([$userId, $sessionId]);
        $cartId = (int) $pdo->lastInsertId();
        $_SESSION['cart_id'] = $cartId;
    } catch (Throwable $e) {
        error_log('[cart-handler] create cart failed: ' . $e->getMessage());
        json_out(['ok' => false, 'error' => 'Cart is not available right now.'], 500);
    }
}

if (!$cartId) {
    json_out(['ok' => false, 'error' => 'Cart is not available.'], 500);
}

/* ==================================================================
 |  Action dispatch
 * ================================================================== */
switch ($action) {

    /* --------------------------------------------------------------
     |  Add to cart
     * -------------------------------------------------------------- */
    case 'cart_add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty       = max(1, (int) ($_POST['qty'] ?? 1));

        if ($productId <= 0) {
            json_out(['ok' => false, 'error' => 'Invalid product.'], 400);
        }

        try {
            $stmt = $pdo->prepare('SELECT id, name, price, stock, is_active FROM products WHERE id = ? LIMIT 1');
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[cart_add] ' . $e->getMessage());
            json_out(['ok' => false, 'error' => 'Could not load product.'], 500);
        }

        if (!$product || (int) $product['is_active'] !== 1) {
            json_out(['ok' => false, 'error' => 'Product is not available.'], 404);
        }
        if ((int) $product['stock'] < $qty) {
            json_out(['ok' => false, 'error' => 'Only ' . (int) $product['stock'] . ' left in stock.'], 409);
        }

        try {
            $pdo->prepare(
                'INSERT INTO cart_items (cart_id, product_id, qty, price_at_add)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    qty = LEAST(qty + VALUES(qty), 999)'
            )->execute([$cartId, $productId, $qty, $product['price']]);
        } catch (Throwable $e) {
            error_log('[cart_add:insert] ' . $e->getMessage());
            json_out(['ok' => false, 'error' => 'Could not add item to cart.'], 500);
        }

        json_out([
            'ok'         => true,
            'message'    => $product['name'] . ' added to cart',
            'cart_count' => cart_qty($cartId),
            'qty'        => cart_qty($cartId),
            'subtotal'   => inr(cart_subtotal($cartId)),
            'items'      => cart_items($cartId),
        ]);

    /* --------------------------------------------------------------
     |  Update quantity
     * -------------------------------------------------------------- */
    case 'cart_update':
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $qty    = max(1, (int) ($_POST['qty'] ?? 1));

        if ($itemId <= 0) {
            json_out(['ok' => false, 'error' => 'Invalid item.'], 400);
        }

        try {
            $pdo->prepare('UPDATE cart_items SET qty = ? WHERE id = ? AND cart_id = ?')
                ->execute([$qty, $itemId, $cartId]);
        } catch (Throwable $e) {
            error_log('[cart_update] ' . $e->getMessage());
            json_out(['ok' => false, 'error' => 'Could not update quantity.'], 500);
        }

        json_out([
            'ok'         => true,
            'cart_count' => cart_qty($cartId),
            'qty'        => cart_qty($cartId),
            'subtotal'   => inr(cart_subtotal($cartId)),
            'items'      => cart_items($cartId),
        ]);

    /* --------------------------------------------------------------
     |  Remove item
     * -------------------------------------------------------------- */
    case 'cart_remove':
        $itemId = (int) ($_POST['item_id'] ?? 0);

        if ($itemId <= 0) {
            json_out(['ok' => false, 'error' => 'Invalid item.'], 400);
        }

        try {
            $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')
                ->execute([$itemId, $cartId]);
        } catch (Throwable $e) {
            error_log('[cart_remove] ' . $e->getMessage());
            json_out(['ok' => false, 'error' => 'Could not remove item.'], 500);
        }

        json_out([
            'ok'         => true,
            'message'    => 'Item removed',
            'cart_count' => cart_qty($cartId),
            'qty'        => cart_qty($cartId),
            'subtotal'   => inr(cart_subtotal($cartId)),
            'items'      => cart_items($cartId),
        ]);

    /* --------------------------------------------------------------
     |  Fetch cart data (for opening cart drawer)
     * -------------------------------------------------------------- */
    case 'cart_data':
        json_out([
            'ok'         => true,
            'items'      => cart_items($cartId),
            'cart_count' => cart_qty($cartId),
            'qty'        => cart_qty($cartId),
            'subtotal'   => inr(cart_subtotal($cartId)),
        ]);
}

/* If we somehow fall through, return safe JSON */
json_out(['ok' => false, 'error' => 'Unknown action.'], 400);