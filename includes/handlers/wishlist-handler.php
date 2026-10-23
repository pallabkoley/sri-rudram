<?php
/* ==================================================================
 |  WISHLIST HANDLER
 |  Handles AJAX add/remove requests for the public website.
 |
 |  Actions:
 |    - wishlist_toggle  (add or remove based on current state)
 |    - wishlist_add
 |    - wishlist_remove
 * ================================================================== */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

/* ---------- Helper: JSON response ---------- */
if (!function_exists('wishlist_json')) {
    function wishlist_json(array $data, int $code = 200): void {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}

/* ---------- CSRF verify (reuses project's existing token) ---------- */
$token = $_POST['_csrf'] ?? $_POST['_token'] ?? '';
$validToken = false;

if (function_exists('csrf_verify')) {
    $validToken = csrf_verify($token);
} else {
    // Fallback: compare with session token
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $validToken = ($sessionToken !== '' && hash_equals($sessionToken, (string)$token));
}

if (!$validToken) {
    wishlist_json(['ok' => false, 'error' => 'Invalid session. Please refresh the page.'], 419);
}

/* ---------- Action ---------- */
$action    = (string) ($_POST['action'] ?? '');
$productId = (int)    ($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    wishlist_json(['ok' => false, 'error' => 'Invalid product.'], 422);
}

/* ---------- Require login ---------- */
if (!is_logged_in()) {
    wishlist_json([
        'ok'    => false,
        'error' => 'Please login to use the wishlist.',
        'login' => true,
        'redirect' => url('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'))
    ], 401);
}

$userId = (int) current_user_id();
if ($userId <= 0) {
    wishlist_json(['ok' => false, 'error' => 'Session expired. Please login again.', 'login' => true], 401);
}

/* ---------- Verify product exists ---------- */
try {
    $chk = $pdo->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
    $chk->execute([$productId]);
    if (!$chk->fetchColumn()) {
        wishlist_json(['ok' => false, 'error' => 'Product not found.'], 404);
    }
} catch (Throwable $e) {
    error_log('[wishlist:verify] ' . $e->getMessage());
    wishlist_json(['ok' => false, 'error' => 'Server error.'], 500);
}

/* ---------- Perform action ---------- */
try {
    $pdo->beginTransaction();

    // Check current state
    $exists = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1');
    $exists->execute([$userId, $productId]);
    $row = $exists->fetch(PDO::FETCH_ASSOC);
    $isWished = (bool) $row;

    $stateNow = null; // 'added' | 'removed'

    if ($action === 'wishlist_remove') {
        if ($isWished) {
            $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
                ->execute([$userId, $productId]);
        }
        $stateNow = 'removed';
    } elseif ($action === 'wishlist_add') {
        if (!$isWished) {
            $pdo->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)')
                ->execute([$userId, $productId]);
        }
        $stateNow = 'added';
    } else {
        // Default: toggle
        if ($isWished) {
            $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
                ->execute([$userId, $productId]);
            $stateNow = 'removed';
        } else {
            $pdo->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)')
                ->execute([$userId, $productId]);
            $stateNow = 'added';
        }
    }

    // Get fresh count
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = ?');
    $cnt->execute([$userId]);
    $count = (int) $cnt->fetchColumn();

    $pdo->commit();

    $messages = [
        'added'   => 'Added to wishlist.',
        'removed' => 'Removed from wishlist.',
    ];

    wishlist_json([
        'ok'          => true,
        'state'       => $stateNow,
        'wished'      => $stateNow === 'added',
        'wish_count'  => $count,
        'product_id'  => $productId,
        'message'     => $messages[$stateNow] ?? 'Updated.',
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[wishlist:action] ' . $e->getMessage());
    wishlist_json(['ok' => false, 'error' => 'Could not update wishlist.'], 500);
}