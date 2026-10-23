<?php
// includes/db-queries.php
// All SELECT queries used by the frontend.

/* ═══════════════ TEMPLES ═══════════════ */
function get_temples_light(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM temples WHERE is_active=1 AND is_featured=1 ORDER BY sort_order ASC")->fetchAll();
}

function get_temples_dark(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM temples WHERE is_active=1 AND is_dark_featured=1 ORDER BY sort_order ASC LIMIT 4")->fetchAll();
}

function get_all_temples(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM temples WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
}

function get_temple_by_slug(string $slug): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM temples WHERE slug=? AND is_active=1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/* ═══════════════ PUJAS ═══════════════ */
function get_popular_pujas(int $limit = 8): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT p.*, t.name AS temple_name, t.city, t.state
         FROM pujas p LEFT JOIN temples t ON t.id = p.temple_id
         WHERE p.is_active=1 AND p.is_popular=1
         ORDER BY p.sort_order ASC LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_all_pujas(): array {
    global $pdo;
    return $pdo->query(
        "SELECT p.*, t.name AS temple_name, t.city, t.state
         FROM pujas p LEFT JOIN temples t ON t.id = p.temple_id
         WHERE p.is_active=1 ORDER BY p.sort_order ASC"
    )->fetchAll();
}

function get_puja_by_slug(string $slug): ?array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT p.*, t.name AS temple_name, t.city, t.state, t.image_url AS temple_image
         FROM pujas p LEFT JOIN temples t ON t.id = p.temple_id
         WHERE p.slug=? AND p.is_active=1 LIMIT 1"
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/* ═══════════════ PRODUCTS ═══════════════ */
function get_featured_products(int $limit = 12): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p LEFT JOIN product_categories c ON c.id = p.category_id
         WHERE p.is_active=1 AND p.is_featured=1
         ORDER BY p.sort_order ASC LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_all_products(?string $categorySlug = null): array {
    global $pdo;
    if ($categorySlug) {
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name
             FROM products p LEFT JOIN product_categories c ON c.id = p.category_id
             WHERE p.is_active=1 AND c.slug=?
             ORDER BY p.sort_order ASC"
        );
        $stmt->execute([$categorySlug]);
        return $stmt->fetchAll();
    }
    return $pdo->query(
        "SELECT p.*, c.name AS category_name
         FROM products p LEFT JOIN product_categories c ON c.id = p.category_id
         WHERE p.is_active=1 ORDER BY p.sort_order ASC"
    )->fetchAll();
}

function get_product_by_slug(string $slug): ?array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p LEFT JOIN product_categories c ON c.id = p.category_id
         WHERE p.slug=? AND p.is_active=1 LIMIT 1"
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_product_categories(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM product_categories ORDER BY sort_order ASC")->fetchAll();
}

/* ═══════════════ ARTICLES ═══════════════ */
function get_featured_articles(int $limit = 8): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT a.*, c.name AS category_name
         FROM articles a LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active=1 ORDER BY a.published_at DESC LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_trending_articles(int $limit = 4): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT a.*, c.name AS category_name
         FROM articles a LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.is_active=1 AND a.is_trending=1
         ORDER BY a.published_at DESC LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_article_by_slug(string $slug): ?array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT a.*, c.name AS category_name
         FROM articles a LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.slug=? AND a.is_active=1 LIMIT 1"
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/* ═══════════════ GYAN ═══════════════ */
function get_gyan_categories(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM gyan_categories WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
}

/* ═══════════════ FESTIVALS ═══════════════ */
function get_upcoming_festivals(int $limit = 6): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM festivals WHERE is_active=1 ORDER BY festival_date ASC LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_festival_by_slug(string $slug): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM festivals WHERE slug=? AND is_active=1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/* ═══════════════ TESTIMONIALS / FAQ ═══════════════ */
function get_testimonials(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
}

function get_faqs(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM faqs WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
}

/* ═══════════════ CART ═══════════════ */
function cart_current_id(): int {
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id=? LIMIT 1');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        if ($id) return (int) $id;
        $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)')->execute([$userId]);
        return (int) $pdo->lastInsertId();
    }

    $sid = session_id();
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_id=? LIMIT 1');
    $stmt->execute([$sid]);
    $id = $stmt->fetchColumn();
    if ($id) return (int) $id;
    $pdo->prepare('INSERT INTO carts (session_id) VALUES (?)')->execute([$sid]);
    return (int) $pdo->lastInsertId();
}

function cart_items(int $cartId): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT ci.id, ci.product_id, ci.qty, ci.price_at_add AS price,
                p.name, p.slug, p.image_url, p.stock
         FROM cart_items ci JOIN products p ON p.id = ci.product_id
         WHERE ci.cart_id=? ORDER BY ci.added_at DESC"
    );
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

function cart_subtotal(int $cartId): float {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(qty*price_at_add),0) FROM cart_items WHERE cart_id=?');
    $stmt->execute([$cartId]);
    return (float) $stmt->fetchColumn();
}

function cart_qty(int $cartId): int {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id=?');
    $stmt->execute([$cartId]);
    return (int) $stmt->fetchColumn();
}

/* ═══════════════ WISHLIST ═══════════════ */
function wishlist_count(): int {
    if (!is_logged_in()) return 0;
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id=?');
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

function wishlist_items(): array {
    if (!is_logged_in()) return [];
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT p.*, w.id AS wish_id
         FROM wishlists w JOIN products p ON p.id = w.product_id
         WHERE w.user_id=? ORDER BY w.created_at DESC"
    );
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll();
}

/* ═══════════════ COUPONS ═══════════════ */
function find_coupon(string $code): ?array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM coupons
         WHERE code=? AND is_active=1
           AND (valid_from IS NULL OR valid_from <= CURDATE())
           AND (valid_until IS NULL OR valid_until >= CURDATE())
           AND (usage_limit IS NULL OR used_count < usage_limit)
         LIMIT 1"
    );
    $stmt->execute([strtoupper($code)]);
    return $stmt->fetch() ?: null;
}

function calculate_discount(array $coupon, float $subtotal): float {
    if ($subtotal < (float) ($coupon['min_order'] ?? 0)) return 0;
    if ($coupon['type'] === 'percent') {
        $disc = $subtotal * ($coupon['value'] / 100);
        if (!empty($coupon['max_discount'])) $disc = min($disc, (float) $coupon['max_discount']);
        return round($disc, 2);
    }
    return min((float) $coupon['value'], $subtotal);
}