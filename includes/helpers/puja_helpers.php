<?php
/* ==================================================================
 |  PUJA HELPERS — Temple ↔ Puja relationship resolver
 |
 |  Matches by temple_id (direct FK) OR custom_temple_name (fuzzy).
 |  Handles cases where pujas.temple_id is NULL.
 * ================================================================== */

if (!function_exists('get_temple_pujas')) {
    /**
     * Find active pujas for a given temple.
     */
    function get_temple_pujas(PDO $pdo, int $templeId, string $templeName = ''): array {
        try {
            $sql = "SELECT id, name, slug, short_desc, price, badge, badge_style,
                           image_url, duration_mins, temple_id, custom_temple_name,
                           is_popular
                    FROM pujas
                    WHERE is_active = 1
                      AND (
                           temple_id = ?
                           OR (
                                custom_temple_name IS NOT NULL
                                AND custom_temple_name <> ''
                                AND (
                                     LOWER(custom_temple_name) = LOWER(?)
                                     OR LOWER(?) LIKE CONCAT('%', LOWER(custom_temple_name), '%')
                                     OR LOWER(custom_temple_name) LIKE CONCAT('%', LOWER(?), '%')
                                )
                           )
                      )
                    ORDER BY is_popular DESC, sort_order ASC, id ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$templeId, $templeName, $templeName, $templeName]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[get_temple_pujas] ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_temple_book_puja_url')) {
    /**
     * Smart "Book Puja" URL resolver for temple cards/buttons.
     *
     *  1 puja    → direct booking form (puja-book.php?puja_id=X)
     *  multiple  → temple page pujas section (temple.php?slug=X#tp-pujas)
     *  0 pujas   → all pujas listing (pujas.php)
     */
    function get_temple_book_puja_url(PDO $pdo, int $templeId, string $templeSlug, string $templeName = ''): string {
        $pujas = get_temple_pujas($pdo, $templeId, $templeName);
        $count = count($pujas);

        if ($count === 1) {
            return url('puja-book.php?puja_id=' . (int)$pujas[0]['id'] . '&temple_id=' . $templeId);
        }
        if ($count > 1) {
            return url('temple.php?slug=' . urlencode($templeSlug)) . '#tp-pujas';
        }
        // 0 pujas — send to all pujas listing
        return url('pujas.php');
    }
}